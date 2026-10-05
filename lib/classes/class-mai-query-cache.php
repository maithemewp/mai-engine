<?php
/**
 * Mai Grid Result Cache.
 *
 * Caches resolved grid query results (ordered post IDs + found count) under a version
 * token we control (via the mai-cache SWR primitive), with stable keys and
 * stale-while-revalidate, so grids survive the last_changed churn that defeats core's
 * query caching on write-heavy sites. Activated per query by the `mai_cache` query var.
 *
 * @package BizBudding\MaiEngine
 */

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

// HOUR_IN_SECONDS and DAY_IN_SECONDS are defined by WordPress; guard for unit-test
// environments that load this file without booting WP.
defined( 'HOUR_IN_SECONDS' ) || define( 'HOUR_IN_SECONDS', 3600 );
defined( 'DAY_IN_SECONDS' ) || define( 'DAY_IN_SECONDS', 86400 );

class Mai_Query_Cache {

	/**
	 * mai-cache group. Transient mode: Redis when present, wp_options otherwise.
	 */
	private const GROUP = 'grid';

	/**
	 * Default soft lifetime (filterable per grid via `mai_query_cache_ttl`). After it an entry
	 * reads as old but is still served. The store keeps it until the hard lifetime, see
	 * lifetimes().
	 */
	private const TTL = 4 * HOUR_IN_SECONDS;

	/**
	 * Single-flight lock TTL (seconds), filterable via `mai_query_cache_lock_ttl`. The lock is
	 * released explicitly only after a declined or failed rebuild after the page, while the job
	 * still holds it. Otherwise it expires. Once a fill stores, later requests read the now-fresh
	 * value and never consult the lock. Short so a winner that dies mid-recompute releases quickly
	 * and the next request becomes the new winner.
	 */
	private const LOCK_TTL = 5;

	/**
	 * Default cap (ms) a cold-fill loser waits for the winner before falling back to its own
	 * query. Filterable via `mai_query_cache_wait_ms`. Cover the typical recompute time.
	 */
	private const WAIT_MS = 500;

	/**
	 * Poll interval (ms) while a cold-fill loser waits on the winner.
	 */
	private const POLL_MS = 25;

	/**
	 * Default time (ms) the rebuilds after the page may take, filterable via
	 * `mai_query_cache_after_page_ms`. No new rebuild starts once it is used up. What is left
	 * is queued again by the next visitor.
	 */
	private const AFTER_PAGE_MS = 1000;

	/**
	 * How close to now, in seconds, a datetime query var has to be for holds_now() to treat it
	 * as "now" and the query as not cacheable.
	 */
	private const NOW_WINDOW = 300;

	/**
	 * Post statuses a grid can show: published, and private for editors. A change to a post in
	 * either status rotates its post type's token.
	 */
	private const SHOWN = [ 'publish', 'private' ];

	/**
	 * Query vars that do not change which posts are returned, removed before hashing.
	 * Mirrors WP_Query::generate_cache_key().
	 */
	private const VOLATILE = [
		'cache_results',
		'fields',
		'update_post_meta_cache',
		'update_post_term_cache',
		'update_menu_item_cache',
		'lazy_load_term_meta',
		'suppress_filters',
		'mai_cache',
	];

	/**
	 * Order-insensitive integer-id arrays: unique + int-cast + sorted before hashing.
	 */
	private const SORTABLE_IDS = [ 'post__in', 'post_parent__in' ];

	/**
	 * Order-insensitive slug arrays: unique + sorted before hashing. NOT int-cast: these hold
	 * strings (slugs), and int-casting would collapse every slug to 0 and alias different queries.
	 */
	private const SORTABLE_SLUGS = [ 'post_name__in' ];

	/**
	 * The one instance the hooks use. See instance().
	 *
	 * @var Mai_Query_Cache|null
	 */
	private static ?Mai_Query_Cache $instance = null;

	/**
	 * This request's queue: results waiting to be stored after the page, and keys already
	 * rebuilt during it.
	 *
	 * @var Mai_Query_Cache_Queue
	 */
	private Mai_Query_Cache_Queue $queue;

	/**
	 * Sets up the queue.
	 *
	 * @since 2.41.0
	 *
	 * @param Mai_Query_Cache_Queue|null $queue The queue. Default: a new one.
	 */
	public function __construct( ?Mai_Query_Cache_Queue $queue = null ) {
		$this->queue = $queue ?? new Mai_Query_Cache_Queue();
	}

	/**
	 * The one instance the hooks use, so the queue it holds is the one run at shutdown.
	 *
	 * @since 2.41.0
	 *
	 * @return Mai_Query_Cache
	 */
	public static function instance(): Mai_Query_Cache {
		return self::$instance ??= new self();
	}

	/**
	 * Replaces the queue. Tests install a fresh one per pretend request.
	 *
	 * @since 2.41.0
	 *
	 * @param Mai_Query_Cache_Queue $queue The queue.
	 *
	 * @return void
	 */
	public function set_queue( Mai_Query_Cache_Queue $queue ): void {
		$this->queue = $queue;
	}

	/**
	 * shutdown, at the latest priority: send the visitor their page, store the results that
	 * waited for it, then rebuild the aged-out entries queued for after it.
	 *
	 * The latest priority runs after WordPress flushes the output buffers (priority 1), and
	 * after a page cache plugin that saves the page from an output buffer callback has saved
	 * it. Does nothing when nothing is waiting, so a request with nothing queued is never
	 * finished early.
	 *
	 * The queue is closed first, even when it is empty. A grid that another plugin's shutdown
	 * callback renders after this one is then stored at once and never queued, since nothing
	 * would run the queue again.
	 *
	 * Each result is stored under the version read before its query ran, never one read now.
	 * The stores come first. They are cheap and already computed, so they never wait behind a
	 * slow rebuild or get cut off by the time limit. The rebuilds run oldest entry first. None
	 * starts once the time spent after the page reaches `mai_query_cache_after_page_ms`.
	 *
	 * A rebuild that throws is logged, and the next one still runs.
	 *
	 * @since 2.41.0
	 * @since 2.41.0 Rebuilds aged-out entries after the stores.
	 *
	 * @return void
	 */
	public function run_queue(): void {
		$this->queue->close();

		if ( $this->queue->is_empty() ) {
			return;
		}

		$this->queue->finish();

		foreach ( $this->queue->take_stores() as $key => $store ) {
			mai_cache( self::GROUP )->write_swr( (string) $key, $store['value'], $store['version'], $store['soft'], $store['hard'] );
		}

		$budget = (float) apply_filters( 'mai_query_cache_after_page_ms', self::AFTER_PAGE_MS );

		foreach ( $this->queue->take_jobs() as $job ) {
			if ( $this->queue->elapsed_ms() >= $budget ) {
				break;
			}

			// One failing rebuild must not stop the rest. run_job() has already cleaned up after
			// it. Logged whatever WP_DEBUG_LOG says, since PHP would have logged it uncaught.
			try {
				$this->run_job( $job );
			} catch ( Throwable $e ) {
				error_log(
					sprintf(
						'Mai Engine: rebuilding grid cache entry %s after the page failed with %s "%s" at %s:%d.',
						(string) ( $job['key'] ?? '' ),
						get_class( $e ),
						$e->getMessage(),
						$e->getFile(),
						$e->getLine()
					)
				);
			}
		}
	}

	/**
	 * Rebuilds one aged-out entry after the page.
	 *
	 * Works only from the values pre_query() captured when it queued the job, never from the
	 * grid's query, which Mai_Grid has put back the way it was asked for by now. It runs the
	 * same ID-only copy, with the same args, that a rebuild during the page runs, so it stores
	 * the same list.
	 *
	 * Skips the job when:
	 * - this is not the blog it was queued on. Its args and key belong to that blog.
	 * - this request already rebuilt the key.
	 * - another request holds the lock the rebuild during the page takes. After a rebuild that
	 *   stores, the lock is not released. It expires after `mai_query_cache_lock_ttl` seconds.
	 *
	 * Stores under the version read before any SQL ran, so a post saved since still leaves the
	 * entry out of date. An empty grid's empty list is stored like any other. When the copy
	 * cannot stand in for the grid, its statement failed, or anything threw, give_up() deletes
	 * the entry. A throw then carries on to run_queue().
	 *
	 * @since 2.41.0
	 *
	 * @param array{key:string,version:string,written:int|null,args:array,cache_results:bool,soft:int,hard:int,blog_id:int} $job
	 *        The job pre_query() queued. args is a copy of the grid query's args as it ran them.
	 *
	 * @return void
	 */
	private function run_job( array $job ): void {
		$key = (string) $job['key'];

		if ( get_current_blog_id() !== (int) $job['blog_id'] ) {
			return;
		}

		if ( $this->queue->was_rebuilt( $key ) ) {
			return;
		}

		// Started before the lock is taken, so the time the lock has been held is never
		// undercounted. See give_up().
		$start = hrtime( true );

		if ( ! mai_cache( self::GROUP )->lock( $key, $this->lock_ttl() ) ) {
			return;
		}

		$stored = false;

		try {
			$fetched = $this->fetch_ids( (array) $job['args'], (bool) $job['cache_results'], $key );

			if ( is_array( $fetched ) ) {
				$this->store( $key, (string) $job['version'], $fetched + [ 'by' => 'ids' ], (int) $job['soft'], (int) $job['hard'] );

				$stored = true;

				$this->log( sprintf( 'rebuilt grid cache entry %s after the page in %.1f ms.', $key, $this->ms_since( $start ) ) );
			}
		} finally {
			// A decline, or a throw on its way to run_queue().
			if ( ! $stored ) {
				$this->give_up( $key, $start );
			}
		}
	}

	/**
	 * Deletes an entry a rebuild after the page gave up on, so the next visitor rebuilds it from
	 * cold, and releases the job's lock while it is still the job's.
	 *
	 * Released, the next visitor takes the lock and rebuilds straight away. Held, every visitor
	 * until it expired would lose the lock, wait for a rebuild nobody is running, then run the
	 * query anyway. A rebuild that stored keeps its lock until it expires, as before.
	 *
	 * @since 2.41.0
	 *
	 * @param string $key   Cache key.
	 * @param int    $start When the job started, from hrtime( true ), taken before the lock.
	 *
	 * @return void
	 */
	private function give_up( string $key, int $start ): void {
		mai_cache( self::GROUP )->delete( $key );

		// The lock expires lock_ttl() seconds after it was taken. From then on it may be another
		// request's, and releasing theirs would let a second rebuild run beside it. So release
		// it only with at least a second to spare.
		if ( $this->ms_since( $start ) < ( $this->lock_ttl() - 1 ) * 1000 ) {
			mai_cache( self::GROUP )->unlock( $key );
		}

		$this->log( sprintf( 'could not rebuild grid cache entry %s after the page, so it was deleted. Took %.1f ms.', $key, $this->ms_since( $start ) ) );
	}

	/**
	 * Milliseconds since a time from hrtime( true ).
	 *
	 * @since 2.41.0
	 *
	 * @param int $start The earlier time, from hrtime( true ).
	 *
	 * @return float
	 */
	private function ms_since( int $start ): float {
		return ( hrtime( true ) - $start ) / 1e6;
	}

	/**
	 * Writes a line to the debug log when WP_DEBUG_LOG is on.
	 *
	 * @since 2.41.0
	 *
	 * @param string $message The message.
	 *
	 * @return void
	 */
	private function log( string $message ): void {
		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log( 'Mai Engine: ' . $message );
		}
	}

	/**
	 * Build a stable cache key from the query vars and final SQL.
	 *
	 * Closely follows WordPress core's WP_Query cache-key derivation (generate_cache_key() plus the
	 * field normalization in WP_Query::get_posts(), verified against WP 6.x/7.0): the same volatile
	 * denylist, post_type / post_status / post__in / orderby normalization in the same spirit, and
	 * the SELECT field list normalized out of the SQL so a split (SELECT wp_posts.ID) and a full
	 * (SELECT wp_posts.*) build of the same grid share one key.
	 *
	 * Deliberate divergences from core (do NOT "fix" to match without re-checking): post_status is
	 * normalized unconditionally (core only when set); orderby defaults to 'date' on empty() (core
	 * only when unset, so orderby => '' differs); the field list becomes a literal 'FIELDS'
	 * placeholder (core uses wp_posts.*). These differ only in the hashed representation, not in
	 * which queries collapse, and the full SQL is in the hash as a backstop; Mai_Grid always sets
	 * post_status/orderby explicitly so those two never fire for real grids. Kept in sync by hand.
	 *
	 * @param array  $query_vars The WP_Query vars.
	 * @param string $sql        The final SQL request.
	 *
	 * @return string
	 */
	public function cache_key( array $query_vars, string $sql ): string {
		foreach ( self::VOLATILE as $key ) {
			unset( $query_vars[ $key ] );
		}

		$query_vars['post_type'] = (array) ( $query_vars['post_type'] ?? 'post' );
		sort( $query_vars['post_type'] );

		$query_vars['post_status'] = (array) ( $query_vars['post_status'] ?? '' );
		sort( $query_vars['post_status'] );

		if ( empty( $query_vars['orderby'] ) ) {
			$query_vars['orderby'] = 'date';
		}

		foreach ( self::SORTABLE_IDS as $key ) {
			if ( isset( $query_vars[ $key ] ) && is_array( $query_vars[ $key ] ) ) {
				$query_vars[ $key ] = array_values( array_unique( array_map( 'intval', $query_vars[ $key ] ) ) );
				sort( $query_vars[ $key ] );
			}
		}

		foreach ( self::SORTABLE_SLUGS as $key ) {
			if ( isset( $query_vars[ $key ] ) && is_array( $query_vars[ $key ] ) ) {
				$query_vars[ $key ] = array_values( array_unique( $query_vars[ $key ] ) );
				sort( $query_vars[ $key ] );
			}
		}

		// $wpdb->prepare() swaps every % for a placeholder that changes on every request, so a
		// grid with a LIKE, in its SQL or in a query var such as search_orderby_title, got a new
		// key on every view. Swap it back first, as core does (WP_Query::generate_cache_key()).
		// Skipped where $wpdb is missing or a stand-in without these methods, as in unit tests.
		$wpdb = $GLOBALS['wpdb'] ?? null;

		if ( is_object( $wpdb ) && method_exists( $wpdb, 'placeholder_escape' ) && method_exists( $wpdb, 'remove_placeholder_escape' ) ) {
			$placeholder = $wpdb->placeholder_escape();

			array_walk_recursive(
				$query_vars,
				static function ( &$value ) use ( $wpdb, $placeholder ) {
					if ( is_string( $value ) && str_contains( $value, $placeholder ) ) {
						$value = $wpdb->remove_placeholder_escape( $value );
					}
				}
			);

			$sql = $wpdb->remove_placeholder_escape( $sql );
		}

		ksort( $query_vars );

		// Normalize the SELECT field list out of the SQL: a split (SELECT wp_posts.ID) and a full
		// (SELECT wp_posts.*) build of the same grid differ only by fields, which do not change the
		// result. A regex miss leaves the SQL unchanged (at worst a duplicate entry, never wrong).
		$sql = preg_replace( '/^(SELECT\s+(?:SQL_CALC_FOUND_ROWS\s+)?).*?(\s+FROM\s+)/is', '$1FIELDS$2', $sql, 1 );

		// Truncate datetime literals to the hour. The After/Before date fields ship
		// "3 months ago" and "30 days" as placeholders, and WP_Date_Query resolves relative
		// values against now to the second, so the key changed every second: every read missed
		// while every request still wrote a new entry, which is worse than not caching.
		//
		// Only the key is coarsened; the executed query keeps its exact bounds, so a served
		// result can be at most an hour stale against a TTL that already allows four. Two
		// grids with different relative dates cannot collide, because $query_vars still holds
		// the raw unresolved string and is hashed alongside this.
		//
		// Applies to every datetime literal in the statement, not just date_query bounds. A
		// window shorter than an hour is therefore effectively widened to an hour, so short
		// windows are not supported; the shortest in real use is measured in days.
		$sql = preg_replace( "/'(\d{4}-\d{2}-\d{2} \d{2}):\d{2}:\d{2}'/", "'$1:00:00'", $sql );

		return md5( serialize( $query_vars ) . $sql );
	}

	/**
	 * Whether a query var holds the current time, to the second.
	 *
	 * Something rewrote the query with "now" (The Events Calendar does this to every event
	 * query), so its key changes on every page view. Caching it would never hit, and a site
	 * without an object cache would write a new row on every view. Only a whole datetime within
	 * NOW_WINDOW of now, in UTC or site time, counts. Any other datetime was set on purpose.
	 *
	 * @param array $query_vars The WP_Query vars.
	 *
	 * @return bool
	 */
	private function holds_now( array $query_vars ): bool {
		$now   = null;
		$found = false;

		array_walk_recursive(
			$query_vars,
			static function ( $value ) use ( &$now, &$found ) {
				if ( $found || ! is_string( $value ) || ! preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value ) ) {
					return;
				}

				$now ??= [
					strtotime( current_time( 'mysql', true ) . ' UTC' ),
					strtotime( current_time( 'mysql' ) . ' UTC' ),
				];

				$time = strtotime( $value . ' UTC' );

				foreach ( $now as $reference ) {
					if ( false !== $time && abs( $time - $reference ) <= self::NOW_WINDOW ) {
						$found = true;

						return;
					}
				}
			}
		);

		return $found;
	}

	/**
	 * Whether this query should use the grid cache.
	 *
	 * @param array $query_vars The WP_Query vars.
	 *
	 * @return bool
	 */
	public function is_cacheable( array $query_vars ): bool {
		$cacheable = true;

		// ElasticPress offloads to ES and hooks posts_pre_query itself; that interaction is
		// unverified (eurweb is not on EP), so skip ep_integrate grids until it is tested.
		if ( ! empty( $query_vars['ep_integrate'] ) ) {
			$cacheable = false;
		}

		// Caching a random order defeats it, so both spellings must match: Mai_Grid emits the
		// bare string 'rand' from the Order By setting, and WP_Query also accepts the seeded
		// 'RAND(123)' form.
		$orderby = $query_vars['orderby'] ?? '';
		if ( is_string( $orderby ) && preg_match( '/\brand\b|\bRAND\(/i', $orderby ) ) {
			$cacheable = false;
		}

		// A hit returns post objects, and core turns those into 1s for an ids query
		// (WP_Query::get_posts()). Mai's own grids never ask for either field mode.
		if ( in_array( $query_vars['fields'] ?? '', [ 'ids', 'id=>parent' ], true ) ) {
			$cacheable = false;
		}

		// A query that holds the current time gets a new key on every view, so it never hits.
		if ( $cacheable && $this->holds_now( $query_vars ) ) {
			$cacheable = false;
		}

		return (bool) apply_filters( 'mai_query_cache', $cacheable, $query_vars );
	}

	/**
	 * Whether the grid cache can write at all. mai-cache refuses under SCRIPT_DEBUG, when its
	 * store is unavailable, and through the mai_can_cache filter.
	 *
	 * @return bool
	 */
	public function can_store(): bool {
		return mai_cache( self::GROUP )->can_cache();
	}

	/**
	 * posts_pre_query: serve from cache (fresh or stale) or flag a miss for storage.
	 *
	 * A kept-only grid (see keep_request()) is served here on a hit with only the posts it will
	 * show. Its miss is answered later, by pre_query_kept(), so every other posts_pre_query
	 * callback gets its turn first, exactly as it does for any other miss.
	 *
	 * A result this request already computed, and that is waiting to be stored after the page,
	 * is served before the cache is read. See store().
	 *
	 * An entry past its soft lifetime, with no post saved since, is served as it is and rebuilt
	 * after the page when can_rebuild_after_page() allows. Nothing is locked or written before
	 * the page is sent. Every other stale entry is rebuilt during the page by the request that
	 * wins the lock. A key that is already queued is served stale again, so one key gets one
	 * decision per request.
	 *
	 * A request that loses the lock on an entry a save made out of date is served the old list,
	 * and asks page caches not to keep its page (Mai_Query_Cache_Queue::no_page_cache()). A
	 * fresh entry, or one that only aged out, still holds the right posts, so it never asks.
	 *
	 * @since 2.41.0 Serves a result waiting in the queue.
	 * @since 2.41.0 Rebuilds an aged-out entry after the page.
	 * @since 2.41.0 Asks page caches not to keep a page served an entry a save made out of date.
	 *
	 * @param array|null $posts Posts (null to run the query normally).
	 * @param WP_Query   $query The query.
	 *
	 * @return array|null
	 */
	public function pre_query( $posts, $query ) {
		if ( empty( $query->query_vars['mai_cache'] ) || ! $this->is_cacheable( $query->query_vars ) ) {
			return $posts;
		}

		$keep  = $this->keep_request( $posts, $query );
		$cache = mai_cache( self::GROUP );
		$key   = $this->cache_key( $query->query_vars, (string) $query->request );

		// A grid earlier on this page already ran this query, and its result is waiting to be
		// stored after the page. Serve that rather than run the query again.
		$pending = $this->queue->pending( $key );

		if ( null !== $pending ) {
			$served = $this->serve( $query, $pending['value'], $keep );

			if ( null !== $served ) {
				return $served;
			}
		}

		$version = $cache->version( (array) ( $query->query_vars['post_type'] ?? 'post' ) );
		$hit     = $cache->read_swr( $key, $version );

		// Cold key. With single-flight, one request recomputes while concurrent others briefly
		// wait for it, so a flush/restart does not stampede the DB. Only attempt it where the
		// lock is atomic (a persistent object cache); otherwise recompute (no worse than uncached).
		if ( null === $hit ) {
			if ( $this->use_single_flight() && ! $cache->lock( $key, $this->lock_ttl() ) ) {
				// Lost the lock: another request is filling. Wait briefly, then serve its result.
				$value  = $this->wait_for_fill( $cache, $key, $version );
				$served = ( null !== $value ) ? $this->serve( $query, $value, $keep ) : null;
				if ( null !== $served ) {
					return $served;
				}
				// Winner did not deliver (or gave a bad envelope): fall through and recompute.
			}
			return $this->flag_miss( $query, $key, $version, $posts );
		}

		$out_of_date = false;

		// Stale entry. A key already queued for after the page skips this and is served stale.
		if ( ! $hit['fresh'] && ! $this->queue->has_job( $key ) ) {
			// Aged out with no save: serve it now and rebuild it after the page. An entry serve()
			// cannot use goes on to be rebuilt during the page.
			if ( $this->can_rebuild_after_page( $hit, $keep ) ) {
				$served = $this->serve( $query, $hit['value'], $keep );

				if ( null !== $served ) {
					// Captured now, because Mai_Grid puts the query back once it returns.
					[ $soft, $hard ] = $this->lifetimes( $query->query_vars );

					$this->queue->add_job(
						[
							'key'           => $key,
							'version'       => $version,
							'written'       => $hit['written'],
							'args'          => (array) $query->query,
							'cache_results' => $keep['cache_results'],
							'soft'          => $soft,
							'hard'          => $hard,
							'blog_id'       => get_current_blog_id(),
						]
					);

					return $served;
				}
			}

			// The single-flight winner recomputes; everyone else serves the stale value.
			if ( $cache->lock( $key, $this->lock_ttl() ) ) {
				return $this->flag_miss( $query, $key, $version, $posts );
			}

			// Lost the lock on an entry a save made out of date. This page shows the old list
			// until the winner stores the new one, so page caches must not keep it.
			$out_of_date = 'version' === $hit['stale'];
		}

		// Fresh hit, or a stale hit served while another request refreshes. A malformed envelope
		// (serve returns null) is treated as a miss and recomputed rather than fataling.
		$served = $this->serve( $query, $hit['value'], $keep );

		if ( null === $served ) {
			return $this->flag_miss( $query, $key, $version, $posts );
		}

		if ( $out_of_date ) {
			$this->queue->no_page_cache();
		}

		return $served;
	}

	/**
	 * posts_pre_query, at the latest priority: answer a kept-only grid's miss with only the
	 * posts it will show.
	 *
	 * This is the last stop before WordPress runs the SQL itself, so a plugin that answers
	 * posts_pre_query, and steps aside when something already has, still gets to answer a
	 * miss. The Events Calendar's custom tables query at priority 100 is one. When one does,
	 * or when fetch_ids() cannot stand in for the grid's query, this leaves the answer alone:
	 * the_posts stores whatever came back, and Mai_Grid drops the excludes itself.
	 *
	 * Otherwise the IDs come from fetch_ids() and are stored here, then only the kept posts are
	 * loaded. The store has to happen here rather than on the_posts, which only ever sees the
	 * kept posts on this path, and clearing the miss flag stops the_posts storing a second
	 * time. A query the cache declined is answered the same way, just with nothing stored.
	 *
	 * An empty grid's empty list is stored the same way, and answered with no posts. That is an
	 * empty array, never null, which would make WordPress run the grid's query as well.
	 *
	 * The entry is marked 'by' => 'ids', because the ID-only copy built it. Only an entry with
	 * that mark can be rebuilt after the page, since that rebuild runs the same copy and so
	 * stores the same list. It is stored under the version pre_query() read before any SQL ran.
	 *
	 * Also confirms a kept answer pre_query() served from cache is still the one in hand. A
	 * later callback that replaced it could hand back anything, and then Mai_Grid has to
	 * filter it as before.
	 *
	 * @since 2.41.0 Stores the entry with the 'by' => 'ids' mark, and its soft and hard lifetimes.
	 * @since 2.41.0 Stores an empty grid's empty list, and answers it with no posts.
	 *
	 * @param array|null $posts Posts (null to run the query normally).
	 * @param WP_Query   $query The query.
	 *
	 * @return array|null
	 */
	public function pre_query_kept( $posts, $query ) {
		if ( isset( $query->mai_grid_kept ) ) {
			if ( $posts !== $query->mai_grid_kept ) {
				unset( $query->mai_grid_kept, $query->mai_grid_kept_primed );
			}

			return $posts;
		}

		$keep = $this->keep_request( $posts, $query );

		// The total is core's job. A deferring grid never counts rows (can_defer_excludes()),
		// so only a pre_get_posts callback gets here, and then core runs the grid's query.
		if ( $keep && empty( $query->query_vars['no_found_rows'] ) ) {
			$keep = null;
		}

		// A copy of a query that holds the current time gets its own "now", so fetch_ids() would
		// run it and then throw it away as a different query. Let the grid's own query answer.
		if ( $keep && $this->holds_now( $query->query_vars ) ) {
			$keep = null;
		}

		if ( ! $keep ) {
			return $posts;
		}

		$fetched = $this->fetch_ids(
			(array) $query->query,
			$keep['cache_results'],
			$this->cache_key( $query->query_vars, (string) $query->request )
		);

		// The copy's statement failed. Store nothing, so a view that just failed does not leave
		// an empty list behind for the length of the lifetime.
		if ( false === $fetched ) {
			unset( $query->mai_cache_store_key, $query->mai_cache_store_version );

			return $posts;
		}

		if ( null === $fetched ) {
			return $posts;
		}

		// From here an empty ID list is an empty grid. It is stored, and keep() answers it with
		// an empty array, so WordPress does not run the grid's query.
		if ( ! empty( $query->mai_cache_store_key ) ) {
			[ $soft, $hard ] = $this->lifetimes( $query->query_vars );

			$this->store( $query->mai_cache_store_key, $query->mai_cache_store_version, $fetched + [ 'by' => 'ids' ], $soft, $hard );

			unset( $query->mai_cache_store_key, $query->mai_cache_store_version );
		}

		return $this->keep( $query, $keep, $fetched['ids'] );
	}

	/**
	 * Whether a stale entry can be served as it is and rebuilt after the page.
	 *
	 * All of these must hold:
	 * - it aged out with no post saved since. A save means the visitor must see the new list.
	 * - the grid is kept-only, so the job has the cache_results the grid asked for.
	 * - the ID-only copy built the entry ('by' => 'ids'). The job runs that same copy, so it
	 *   stores the same list. An entry built any other way, or by beta.4 or earlier, is rebuilt
	 *   during the page and gets the mark on its next store if the copy built it.
	 * - the queue has not run yet. Nothing would run a job added after it.
	 * - this request can send the visitor their page and keep running.
	 * - `mai_query_cache_after_page` is on.
	 *
	 * @since 2.41.0
	 *
	 * @param array      $hit  The entry, from read_swr().
	 * @param array|null $keep The kept-only request, from keep_request().
	 *
	 * @return bool
	 */
	private function can_rebuild_after_page( array $hit, ?array $keep ): bool {
		return 'age' === $hit['stale']
			&& null !== $keep
			&& is_array( $hit['value'] )
			&& 'ids' === ( $hit['value']['by'] ?? null )
			&& ! $this->queue->closed()
			&& $this->queue->can_finish_early()
			&& (bool) apply_filters( 'mai_query_cache_after_page', true );
	}

	/**
	 * Flag this query as a miss for the_posts to store, and let WP run the real query.
	 *
	 * @param WP_Query   $query   The query.
	 * @param string     $key     Cache key.
	 * @param string     $version Current composite version.
	 * @param array|null $posts   The pre_query posts (null -> WP runs the real query).
	 *
	 * @return array|null
	 */
	private function flag_miss( $query, string $key, string $version, $posts ) {
		$query->mai_cache_store_key     = $key;
		$query->mai_cache_store_version = $version;
		return $posts;
	}

	/**
	 * Apply a cached result to the query and hydrate it. Returns null for a malformed or legacy
	 * envelope (missing/!array `ids`) so the caller treats it as a miss and recomputes, rather than
	 * fataling on a bad shape in posts_pre_query.
	 *
	 * An empty `ids` list is a valid entry, an empty grid. It returns an empty array, not null,
	 * so the caller serves no posts and WordPress runs no query.
	 *
	 * Note on `found`: it is the found_posts of the query that built the entry. With
	 * no_found_rows => true (the Mai_Grid default) core never counts rows, so both store paths
	 * store 0, and max_num_pages comes out 0 too. Mai_Grid reads neither, so this is correct for
	 * grids. A paginating mai_cache consumer must run with no_found_rows => false to get an
	 * accurate total.
	 *
	 * @param WP_Query   $query The query.
	 * @param mixed      $value Stored value, expected [ 'ids' => int[], 'found' => int ]. ids may be empty.
	 * @param array|null $keep  The kept-only request, from keep_request(). Null hydrates every ID.
	 *
	 * @return WP_Post[]|null
	 */
	private function serve( $query, $value, ?array $keep = null ): ?array {
		if ( ! is_array( $value ) || ! isset( $value['ids'] ) || ! is_array( $value['ids'] ) ) {
			return null;
		}

		$query->found_posts   = (int) ( $value['found'] ?? count( $value['ids'] ) );
		$query->max_num_pages = ( ( $query->query_vars['posts_per_page'] ?? 0 ) > 0 )
			? (int) ceil( $query->found_posts / $query->query_vars['posts_per_page'] )
			: 1;

		$ids = array_map( 'intval', $value['ids'] );

		return $keep ? $this->keep( $query, $keep, $ids ) : $this->hydrate( $ids, $query->query_vars );
	}

	/**
	 * The kept-only request a deferring grid attached to this query, or null to answer it the
	 * usual way.
	 *
	 * Mai_Grid sets it as the mai_grid_keep property of its query when it defers its excludes:
	 * the IDs to drop, how many posts it will show, and the cache_results it was asked for. A
	 * property rather than a query var, so a
	 * copy another plugin builds from this query's vars does not carry it. Answering with only those posts means the padding rows and the excluded posts
	 * are never loaded or primed, and posts_results and the_posts see what the grid shows, as
	 * they did before excludes were deferred.
	 *
	 * Declines when another posts_pre_query callback already answered, so that answer stands,
	 * and when the query has since become an ids or id=>parent query. Core returns those two
	 * straight out of get_posts() as ints or stdClass, and a list of post objects is the wrong
	 * shape for either.
	 *
	 * @param array|null $posts The pre_query posts.
	 * @param WP_Query   $query The query.
	 *
	 * @return array{exclude:int[],count:int,cache_results:bool}|null
	 */
	private function keep_request( $posts, $query ): ?array {
		$keep = $query->mai_grid_keep ?? null;

		if ( null !== $posts || ! is_array( $keep ) || ! isset( $keep['exclude'], $keep['count'] ) || ! is_array( $keep['exclude'] ) ) {
			return null;
		}

		if ( in_array( $query->query_vars['fields'] ?? '', [ 'ids', 'id=>parent' ], true ) ) {
			return null;
		}

		return [
			'exclude'       => array_map( 'intval', $keep['exclude'] ),
			'count'         => max( 0, (int) $keep['count'] ),
			'cache_results' => (bool) ( $keep['cache_results'] ?? true ),
		];
	}

	/**
	 * The grid's padded ID list, in order, from an ID-only copy of its query, false when the
	 * copy's statement failed, or null when the copy cannot stand in for the grid's query. An
	 * empty list means the copy found nothing and $wpdb showed no error after it ran.
	 *
	 * It works from plain values, never from the grid's live query: the args, the cache_results
	 * the grid asked for, and the key the grid's query has. So it gives the same answer when it
	 * runs after the page, by which time Mai_Grid has put the query's vars back.
	 *
	 * The copy is built from the args the grid's query was built from, so it goes through the
	 * same pre_get_posts and SQL filters, and asks core for IDs only. Its statement is the one
	 * core runs when it splits the grid's full query, so it returns the same rows in the same
	 * order without loading them. The one difference: core runs posts_request_ids on a split
	 * statement only, so a callback there never sees the copy. It also reads and writes core's post-queries cache, which the
	 * grid's own query cannot while it runs with cache_results off, so a result cache miss on a
	 * warm site costs no SQL. Unless the grid was asked to run with cache_results off: its
	 * results may then depend on something core's cache does not track, so the copy skips that
	 * cache too. It is built from the args rather than query_vars, because core
	 * writes back-compat vars such as cat and category_name into query_vars as it runs, and a
	 * query built from those would join the same taxonomy twice.
	 *
	 * Core files it under a key of its own, not the full query's. The key swaps every
	 * wp_posts.ID in the statement for wp_posts.*, not only the select list, and a grid's
	 * statement has more of them (the tiebreaker, a taxonomy JOIN and GROUP BY). So the entry is
	 * shared with the next kept-only miss of the same grid, not with a full run of it.
	 *
	 * mai_cache is off on the copy, so pre_query() does not treat it as a query to cache, and it
	 * has no kept-only marker, so pre_query_kept() leaves it alone. It only runs once every
	 * other posts_pre_query callback has declined the grid's query. Only fields and the cache
	 * flags differ on the copy, so a callback that answers it anyway is answering the same
	 * query, and its IDs stand.
	 *
	 * Returns false when the copy's statement failed. The caller then stores nothing, rather than
	 * an empty list for the length of the lifetime. Core is made to forget the copy's cached
	 * empty result here (forget_failure()), so neither the next view nor the next visitor after
	 * a rebuild after the page gave up reads it back. A failed statement also returns no rows,
	 * so this is checked first. The copy's statement counts as failed when:
	 * - $wpdb's error is on a statement with the copy's text.
	 * - the copy found nothing, $wpdb shows an error, and at least one statement reached the
	 *   database while the copy ran. The text cannot pin the error on the copy, since any plugin
	 *   that changes statements through the query filter, even to add a comment, makes
	 *   last_query differ from the request. So the error of the last statement sent during the
	 *   copy is taken as the copy's. When that statement was really a callback's, and core
	 *   answered the copy from its cache, this costs one reset of core's query cache and leaves
	 *   this view unstored. It never stores a wrong list.
	 *
	 * Returns null when:
	 * - the copy found nothing, $wpdb shows an error, and no statement reached the database
	 *   while the copy ran. Core answered the copy from its cache, so the error is a stale one an
	 *   earlier statement left. The empty list cannot be vouched for, so the grid's own query
	 *   answers, as before. Core's query cache is left alone, since the error may come from a
	 *   statement that fails on every view.
	 * - the copy selects more than the ID. get_col() reads the first column.
	 * - the copy's key is not the expected one. A callback treated the copy differently from the
	 *   grid's query, so the copy's IDs may not be the grid's.
	 *
	 * The last two checks read no rows, so an empty copy is checked like any other.
	 *
	 * A copy that passes and finds nothing, with no error from $wpdb, is an empty grid, and
	 * returns an empty ID list. The caller stores it like any other, so an empty grid costs one
	 * query per result cache miss, is served from the cache, and is rebuilt after the page once
	 * it ages out.
	 *
	 * Counting rows is the caller's check, because only the grid's own query can say whether
	 * it counts.
	 *
	 * The copy is marked for the grid query optimizer, which may send a faster statement for it.
	 * When that statement failed, or never reached the database, core is made to forget the
	 * copy's cached result, the optimizer is turned off, and the copy's own statement is sent
	 * once more. The checks described above then judge that statement. When the faster statement
	 * took longer than the optimizer's limit, the optimizer is turned off and the IDs stand.
	 *
	 * @since 2.41.0 Takes the args, the asked cache_results and the expected key instead of the
	 *            live query, and returns the copy's found_posts with the IDs.
	 * @since 2.41.0 Returns an empty ID list when the copy finds nothing and $wpdb shows no error,
	 *            rather than null.
	 * @since 2.41.0 Counts an empty copy's error as its own failure when a statement was sent
	 *            while it ran.
	 * @since 2.41.0 Marks the copy for the grid query optimizer, and resends a failed faster
	 *            statement.
	 *
	 * @param array  $args          The args the grid's query was built from.
	 * @param bool   $cache_results The cache_results the grid asked for.
	 * @param string $expected_key  The grid query's cache key. The copy's must match it.
	 *
	 * @return array{ids:int[],found:int}|false|null
	 */
	private function fetch_ids( array $args, bool $cache_results, string $expected_key ): array|false|null {
		global $wpdb;

		$copy      = new WP_Query();
		$optimizer = Mai_Post_Grid_Query_Optimizer::instance();

		// Lets the grid query optimizer send a faster statement for the copy when it can prove it
		// returns the same IDs. The copy's request text stays as WordPress built it, so the key
		// check below is unchanged.
		$optimizer->mark( $copy, Mai_Post_Grid_Query_Optimizer::ROLE_COPY );

		// $wpdb counts a statement once it is sent, after the query filter, so this tells whether
		// anything reached the database while the copy ran. See the empty copy check below.
		$sent = $wpdb->num_queries;

		try {
			$copy->query(
				array_merge(
					$args,
					[
						'fields'        => 'ids',
						'cache_results' => $cache_results,
						'mai_cache'     => false,
					]
				)
			);
		} finally {
			// The copy's statement has been sent or never will be, and the copy is done.
			$optimizer->drop( $copy );
		}

		$outcome = $optimizer->outcome( $copy );

		if ( Mai_Post_Grid_Query_Optimizer::STATUS_FAILED === ( $outcome['status'] ?? null ) ) {
			// The faster statement failed, or never reached the database. Read the error before
			// anything else sends a statement. Core cached the copy's result, so it is made to
			// forget it first, since turn_off()'s transient write is a statement of its own and
			// can fail too. Then the copy's own statement is sent once more, unswapped. The checks
			// below read that one, so if it fails too, today's failure path applies.
			$error = (string) $wpdb->last_error;

			self::forget_failure();

			$optimizer->turn_off( 'failed', $error );

			$copy->posts = $wpdb->get_col( (string) $copy->request );
		} elseif ( Mai_Post_Grid_Query_Optimizer::STATUS_OK === ( $outcome['status'] ?? null ) && $outcome['seconds'] > Mai_Post_Grid_Query_Optimizer::$slow ) {
			// The swapped statement worked, but it was slow enough that the database planned it
			// badly. The IDs stand.
			$optimizer->turn_off( 'slow', sprintf( '%.3f s, copy form', $outcome['seconds'] ) );
		}

		// Only an error from the copy's own statement, which is the last one it runs. When core
		// answers the copy from its cache, that statement never runs, and last_error belongs to
		// whatever ran before, possibly some callback's own query. last_query is recorded with
		// the placeholder escape already stripped, so the request is compared the same way.
		//
		// This comes first. A failed statement returns no rows, and after the next check no rows
		// is an empty grid.
		if ( $wpdb->last_error && $wpdb->last_query === $wpdb->remove_placeholder_escape( (string) $copy->request ) ) {
			// Core cached the copy's empty result too, and the next view would read it back.
			self::forget_failure();

			return false;
		}

		// An empty copy with an error on some other statement. A failed SELECT returns no rows, so
		// the error cannot be ignored. Its text cannot pin it on the copy either: any plugin that
		// changes statements through the query filter, even to add a comment, makes last_query
		// differ from the request. So it is pinned on whether anything reached the database while
		// the copy ran.
		if ( ! $copy->posts && $wpdb->last_error ) {
			// Something did, and the last statement sent failed. Count it as the copy's failure.
			// Core may have cached the empty list under the copy's key, and the next view, or the
			// next visitor after a rebuild after the page gave up, would read it back.
			if ( $wpdb->num_queries > $sent ) {
				self::forget_failure();

				return false;
			}

			// Nothing did. Core answered the copy from its cache, and the error is a stale one an
			// earlier statement left. The empty list cannot be vouched for, so the grid's own query
			// answers, as before. Core's cache is left alone: the error may come from a statement
			// that fails on every view, and resetting core's cache each time would cost far more.
			return null;
		}

		// Both checks read the copy's statement and vars, never its rows, so an empty copy is
		// checked the same as any other.
		$ids_only = '/^\s*SELECT\s+(?:DISTINCT\s+)?' . preg_quote( "{$wpdb->posts}.ID", '/' ) . '\s+FROM\s/i';

		if ( ! preg_match( $ids_only, (string) $copy->request ) ) {
			return null;
		}

		if ( $this->cache_key( $copy->query_vars, (string) $copy->request ) !== $expected_key ) {
			return null;
		}

		return [
			'ids'   => array_map( 'intval', $copy->posts ),
			'found' => (int) $copy->found_posts,
		];
	}

	/**
	 * Drop a kept-only grid's excludes from the padded ID list, keep the count it asked for,
	 * and load only those posts.
	 *
	 * hydrate() drops an ID whose post was deleted or no longer has an allowed status, which a
	 * stale entry can hold. Topping up from the rest of the list keeps the grid as full as
	 * hydrating the whole list used to, while the usual case loads exactly the posts it shows.
	 *
	 * Also records the flags hydrate() primed those posts with, so Mai_Grid does not prime them
	 * again. Nothing that runs while this primes is given the query, so its vars, and with them
	 * the flags, are the same for every batch.
	 *
	 * @since 2.41.0 Records the flags it primed the posts with.
	 *
	 * @param WP_Query $query The query.
	 * @param array    $keep  The kept-only request, from keep_request().
	 * @param int[]    $ids   The padded ID list, in order.
	 *
	 * @return WP_Post[]
	 */
	private function keep( $query, array $keep, array $ids ): array {
		$candidates = array_values( array_diff( $ids, $keep['exclude'] ) );
		$posts      = [];
		$next       = 0;

		while ( count( $posts ) < $keep['count'] && $next < count( $candidates ) ) {
			$batch = array_slice( $candidates, $next, $keep['count'] - count( $posts ) );
			$next += count( $batch );
			$posts = array_merge( $posts, $this->hydrate( $batch, $query->query_vars ) );
		}

		// Tells Mai_Grid the excludes and the slice are already done, so it does not do them
		// again. It holds the answer itself so pre_query_kept() can tell if a later callback
		// replaced it.
		$query->mai_grid_kept = $posts;

		// Tells Mai_Grid how every post in that answer was primed. See prime_shown_posts().
		$query->mai_grid_kept_primed = $this->prime_flags( $query->query_vars );

		return $posts;
	}

	/**
	 * Whether to single-flight a cold fill. The lock is only atomic with a persistent object
	 * cache, and that also confines the brief wait to better-provisioned hosts. Killable via
	 * `mai_query_cache_single_flight`.
	 *
	 * @return bool
	 */
	private function use_single_flight(): bool {
		return wp_using_ext_object_cache() && (bool) apply_filters( 'mai_query_cache_single_flight', true );
	}

	/**
	 * Single-flight lock TTL in seconds.
	 *
	 * @return int
	 */
	private function lock_ttl(): int {
		return max( 1, (int) apply_filters( 'mai_query_cache_lock_ttl', self::LOCK_TTL ) );
	}

	/**
	 * Wait briefly for the single-flight winner to store a fresh result for this key.
	 *
	 * Polls the cache up to a bounded cap (`mai_query_cache_wait_ms`). Returns the stored
	 * value once fresh, or null on timeout so the caller falls back to running the query itself.
	 *
	 * @param object $cache   The mai-cache instance.
	 * @param string $key     Cache key.
	 * @param string $version Current composite version.
	 *
	 * @return array|null
	 */
	private function wait_for_fill( $cache, string $key, string $version ): ?array {
		$cap_ms   = max( 0, (int) apply_filters( 'mai_query_cache_wait_ms', self::WAIT_MS ) );
		$poll_ms  = max( 1, min( self::POLL_MS, $cap_ms ) );
		$deadline = microtime( true ) + ( $cap_ms / 1000 );

		// Read first, then sleep: serve immediately if the winner already stored.
		while ( microtime( true ) < $deadline ) {
			$hit = $cache->read_swr( $key, $version );
			if ( null !== $hit && $hit['fresh'] ) {
				return $hit['value'];
			}
			usleep( $poll_ms * 1000 );
		}

		return null;
	}

	/**
	 * posts_results, at the earliest priority: store nothing when the grid's query failed.
	 *
	 * A failed statement returns no rows, and storing that would show an empty grid until the
	 * entry expires. Only an error from the grid's own statement counts, so it is checked here,
	 * while that statement is still the last one run. By the_posts, the last one is usually a
	 * priming query. When core or another callback answered without running the statement,
	 * last_error belongs to whatever ran before, so it does not count.
	 *
	 * $wpdb records last_query after the query filter, where it strips its placeholder escape,
	 * so the request is compared the same way. A grid with a LIKE holds that escape.
	 *
	 * Core has already put the empty result in its own query cache by now. See forget_failure().
	 *
	 * @since 2.41.0
	 *
	 * @param array    $posts The posts.
	 * @param WP_Query $query The query.
	 *
	 * @return array
	 */
	public function posts_results( $posts, $query ) {
		global $wpdb;

		if ( empty( $query->mai_cache_store_key ) || ! $wpdb->last_error ) {
			return $posts;
		}

		if ( $wpdb->last_query === $wpdb->remove_placeholder_escape( (string) $query->request ) ) {
			unset( $query->mai_cache_store_key, $query->mai_cache_store_version );

			self::forget_failure();
		}

		return $posts;
	}

	/**
	 * Make WordPress forget the empty result of a grid statement that just failed.
	 *
	 * Core puts a query's result in its post-queries cache before posts_results runs, failed or
	 * not, under a key salted with the posts last_changed time. On a site with a persistent
	 * object cache, the next view would get that empty result with no SQL, see no error, and
	 * store it, and the grid would stay empty until a post or its meta was saved. Moving the
	 * time on, as core does on every post save, means that entry is never read again.
	 *
	 * Only called when a grid's own statement failed, never on success. Every entry in core's
	 * post-queries cache misses once afterwards, the same as after one post save.
	 *
	 * Public and static so the grid query optimizer resets the cache the same way when a faster
	 * statement failed.
	 *
	 * @since 2.41.0
	 *
	 * @return void
	 */
	public static function forget_failure(): void {
		wp_cache_set_posts_last_changed();
	}

	/**
	 * the_posts: store the freshly computed result for a flagged miss.
	 *
	 * Stored without the 'by' => 'ids' mark, because the list is whatever the full query and
	 * its filters returned, which a rebuild after the page could not reproduce. It is stored
	 * under the version pre_query() read before any SQL ran.
	 *
	 * @since 2.41.0 Stores the entry with its soft and hard lifetimes.
	 *
	 * @param array    $posts The posts.
	 * @param WP_Query $query The query.
	 *
	 * @return array
	 */
	public function the_posts( $posts, $query ) {
		if ( empty( $query->mai_cache_store_key ) ) {
			return $posts;
		}

		[ $soft, $hard ] = $this->lifetimes( $query->query_vars );

		$this->store(
			$query->mai_cache_store_key,
			$query->mai_cache_store_version,
			[ 'ids' => wp_list_pluck( $posts, 'ID' ), 'found' => (int) $query->found_posts ],
			$soft,
			$hard
		);

		unset( $query->mai_cache_store_key, $query->mai_cache_store_version );

		return $posts;
	}

	/**
	 * How long a grid's entry lasts, as soft and hard lifetimes in seconds.
	 *
	 * After the soft lifetime the entry reads as old but is still served. After the hard one
	 * the store drops it. The hard lifetime is never shorter than the soft one.
	 *
	 * @since 2.41.0
	 *
	 * @param array $query_vars The grid query's vars, passed to both filters.
	 *
	 * @return array{0:int,1:int} The soft and hard lifetimes.
	 */
	private function lifetimes( array $query_vars ): array {
		$soft = (int) apply_filters( 'mai_query_cache_ttl', self::TTL, $query_vars );
		$hard = (int) apply_filters( 'mai_query_cache_hard_ttl', DAY_IN_SECONDS, $query_vars );

		return [ $soft, max( $soft, $hard ) ];
	}

	/**
	 * Write a result, now or after the page.
	 *
	 * Without a persistent object cache a write is two wp_options rows, so it waits in the
	 * queue until the visitor has their page, and run_queue() writes it. Meanwhile pre_query()
	 * serves it to any other grid with the same key. It is written now instead when:
	 * - the site has a persistent object cache. The write is one fast SET, and requests that
	 *   lost the cold-miss lock are waiting for it (wait_for_fill()).
	 * - the queue has already run, even with nothing in it. Nothing would write the list again.
	 *   This covers a rebuild after the page, and a grid another plugin's shutdown callback
	 *   renders after the queue ran.
	 * - this request cannot finish early. Waiting would only make the visitor wait longer.
	 * - the cache cannot store at all. The write does nothing, and nothing should wait for it.
	 *
	 * Takes plain values only, never the query, so it can run after the page, once Mai_Grid has
	 * put the query's vars back.
	 *
	 * @since 2.41.0 Takes the value and the lifetimes instead of the query.
	 * @since 2.41.0 Waits until after the page on sites without a persistent object cache.
	 * @since 2.41.0 Writes straight away after the queue has run, even an empty one.
	 *
	 * @param string $key     Cache key.
	 * @param string $version The version read before the query ran, never one read now. A post
	 *                        saved while the query ran must leave the entry out of date.
	 * @param array  $value   [ 'ids' => int[], 'found' => int ], plus 'by' => 'ids' when the
	 *                        ID-only copy built it.
	 * @param int    $soft    Soft lifetime in seconds, from lifetimes().
	 * @param int    $hard    Hard lifetime in seconds, from lifetimes().
	 *
	 * @return void
	 */
	private function store( string $key, string $version, array $value, int $soft, int $hard ): void {
		if ( wp_using_ext_object_cache() || $this->queue->closed() || ! $this->queue->can_finish_early() || ! $this->can_store() ) {
			mai_cache( self::GROUP )->write_swr( $key, $value, $version, $soft, $hard );
		} else {
			$this->queue->add_store( $key, $version, $value, $soft, $hard );
		}

		$this->queue->mark_rebuilt( $key );
	}

	/**
	 * Hydrate post objects from cached IDs in stored order, with no query.
	 *
	 * A cached id may point to a post whose status changed after it was cached (published then
	 * moved to draft, private, or pending). Because stale entries are served while a refresh
	 * runs, drop any post that no longer satisfies the query's explicit post_status so a stale
	 * grid can only shrink, never expose content that is no longer public. Hard-deleted ids
	 * resolve to null via get_post and fall out the same array_filter.
	 *
	 * A post whose type is no longer one the query asks for is dropped too. Changing a post's
	 * type with set_post_type() fires no status transition, so it does not rotate any token.
	 *
	 * Meta and terms are primed only when the query asks for them, as core does on its own
	 * cache hits. WP_Query fills both flags in before posts_pre_query, so the true defaults here
	 * only apply to a direct caller that leaves them out.
	 *
	 * @param int[] $ids        Ordered post IDs.
	 * @param array $query_vars The query vars, for the post_status and post_type guards and the
	 *                          cache flags.
	 *
	 * @return WP_Post[]
	 */
	public function hydrate( array $ids, array $query_vars = [] ): array {
		if ( ! $ids ) {
			return [];
		}

		$flags = $this->prime_flags( $query_vars );

		_prime_post_caches( $ids, $flags['update_post_term_cache'], $flags['update_post_meta_cache'] );

		$posts    = array_filter( array_map( 'get_post', $ids ) );
		$statuses = $this->allowed_statuses( $query_vars );
		$types    = $this->allowed_types( $query_vars );

		if ( $statuses || $types ) {
			$posts = array_filter(
				$posts,
				static function ( $post ) use ( $statuses, $types ) {
					return ( ! $statuses || in_array( $post->post_status, $statuses, true ) )
						&& ( ! $types || in_array( $post->post_type, $types, true ) );
				}
			);
		}

		return array_values( $posts );
	}

	/**
	 * The term and meta flags hydrate() primes posts with, read from the query vars.
	 *
	 * @since 2.41.0
	 *
	 * @param array $query_vars The query vars.
	 *
	 * @return array{update_post_term_cache:bool,update_post_meta_cache:bool}
	 */
	private function prime_flags( array $query_vars ): array {
		return [
			'update_post_term_cache' => (bool) ( $query_vars['update_post_term_cache'] ?? true ),
			'update_post_meta_cache' => (bool) ( $query_vars['update_post_meta_cache'] ?? true ),
		];
	}

	/**
	 * The post types a hit may return, taken from the query's post_type.
	 *
	 * An empty post_type or 'any' means do not filter. Mai_Grid always sets post_type, so real
	 * grids are always guarded.
	 *
	 * @param array $query_vars The query vars.
	 *
	 * @return string[] Allowed post types, or [] to skip the guard.
	 */
	private function allowed_types( array $query_vars ): array {
		$types = array_filter( (array) ( $query_vars['post_type'] ?? '' ) );

		if ( ! $types || in_array( 'any', $types, true ) ) {
			return [];
		}

		return $types;
	}

	/**
	 * The post statuses a hit may return, taken from the query's explicit post_status.
	 *
	 * An empty status or 'any' means do not filter: the caller opted into whatever the stored
	 * ids resolve to. Mai_Grid always sets post_status explicitly (publish, or publish+private
	 * for editors), so real grids are always guarded.
	 *
	 * @param array $query_vars The query vars.
	 *
	 * @return string[] Allowed statuses, or [] to skip the guard.
	 */
	private function allowed_statuses( array $query_vars ): array {
		$status = array_filter( (array) ( $query_vars['post_status'] ?? '' ) );

		// 'any' and 'all' are WP_Query meta-statuses, not literal column values, so skip the guard.
		if ( ! $status || in_array( 'any', $status, true ) || in_array( 'all', $status, true ) ) {
			return [];
		}

		return $status;
	}

	/**
	 * Rotate a post type's token on any change to a post a grid can show. Grids show published
	 * posts, and editors' grids show private ones too, so both count. transition_post_status
	 * fires on every save, so this also covers a live edit of an already-published or private
	 * post (publish->publish), publish/unpublish/trash, and a scheduled post going live.
	 * Revisions and autosaves transition inherit->inherit (neither status is shown) and so are
	 * skipped by the first guard.
	 *
	 * @param string  $new_status New status.
	 * @param string  $old_status Old status.
	 * @param WP_Post $post       The post.
	 *
	 * @return void
	 */
	public function on_transition( $new_status, $old_status, $post ) {
		if ( ! in_array( $new_status, self::SHOWN, true ) && ! in_array( $old_status, self::SHOWN, true ) ) {
			return;
		}
		// Belt-and-suspenders: a revision/autosave normally transitions inherit->inherit (caught
		// above), but skip it explicitly in case a flow gives a revision a shown status.
		if ( wp_is_post_revision( $post->ID ) || 'revision' === $post->post_type ) {
			return;
		}
		mai_cache( self::GROUP )->bump( $post->post_type );
	}

	/**
	 * Rotate on hard delete of a published or private post.
	 *
	 * @param int     $post_id The post ID.
	 * @param WP_Post $post    The post.
	 *
	 * @return void
	 */
	public function on_delete( $post_id, $post ) {
		if ( $post && in_array( $post->post_status, self::SHOWN, true ) && 'revision' !== $post->post_type ) {
			mai_cache( self::GROUP )->bump( $post->post_type );
		}
	}

	/**
	 * Full reset of the grid group (wp cache flush / wp mai flush). Orphans everything;
	 * the cold-start case, not per-type invalidation.
	 *
	 * @return void
	 */
	public function flush_all() {
		mai_cache( self::GROUP )->flush();
	}
}
