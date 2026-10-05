<?php
declare(strict_types=1);

/**
 * Mai Post Grid query optimizer.
 *
 * Swaps a Mai grid's taxonomy statement for the faster EXISTS form at the last moment, and only
 * when it can prove the swap returns the same posts. Mai marks the grid's query and its ID-only
 * copy. For a marked query this class records each SQL part when WordPress hands it to the
 * first plugin filter and again after the last one, and the finished statement at both ends of
 * posts_request. When nothing changed and the query is a covered shape, it prepares a swap. Its
 * $wpdb query callback then swaps the exact statement as it goes to the database, after
 * checking the database and rebuilding WordPress's taxonomy SQL with WordPress's own code.
 * Anything unexpected sends today's statement unchanged.
 *
 * A swapped statement that fails is sent again unswapped, by recover() for a grid's own query
 * and by Mai_Query_Cache::fetch_ids() for the copy, and the swap turns off for a day. So does a
 * copy statement that is slow.
 *
 * The query's own SQL text, as WordPress keeps it on the query, never changes, so cache keys
 * built from it work as before.
 *
 * @package BizBudding\MaiEngine
 */

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

final class Mai_Post_Grid_Query_Optimizer {

	/**
	 * Return false to turn the swap off for a site. Read once per request.
	 */
	public const FILTER = 'mai_post_grid_optimize_query';

	/**
	 * Set for a day when a swapped statement failed or was slow.
	 */
	public const TRANSIENT = 'mai_post_grid_optimize_off';

	/**
	 * The SQL parts recorded at the first and last plugin filter. posts_where comes first in
	 * core, so its first look starts a fresh record.
	 */
	private const PARTS = [ 'where', 'join', 'groupby', 'distinct', 'fields' ];

	/**
	 * Seconds a swapped copy statement may take before the swap is turned off. Tests lower it.
	 */
	public static float $slow = 1.0;

	/**
	 * The one instance the hooks use. See instance().
	 */
	private static ?self $instance = null;

	/**
	 * Whether register() has run.
	 */
	private bool $registered = false;

	/**
	 * Whether the swap is off for the rest of this request.
	 */
	private bool $off = false;

	/**
	 * What the filter returned, read once per request.
	 */
	private ?bool $allowed = null;

	/**
	 * Whether the day-long transient is set, read once per request, right before the first swap.
	 */
	private ?bool $paused = null;

	/**
	 * Whether the database may take the swap, read once per request.
	 */
	private ?bool $database = null;

	/**
	 * Whether to write the MySQL hint, read with $database.
	 */
	private bool $hint = false;

	/**
	 * Rebuilt taxonomy SQL for this request, keyed by the tax filters and the posts table.
	 *
	 * @var array<string,array|null>
	 */
	private array $rebuilds = [];

	/**
	 * Swaps waiting for their statement, oldest first.
	 *
	 * @var list<array{owner:WP_Query,original:string,split:?string,role:string}>
	 */
	private array $prepared = [];

	/**
	 * What each swap recorded for its query, until outcome() reads it.
	 *
	 * Keyed by the query object itself, which a WeakMap forgets when the query is freed. A key
	 * made from spl_object_id() could be reused by a later query.
	 *
	 * @var WeakMap<WP_Query,array{queries:int,swapped:string,form:string,start:float}>
	 */
	private WeakMap $records;

	/**
	 * Receives the one line written when the swap turns off.
	 */
	private Closure $logger;

	/**
	 * Sets up the per-request state.
	 */
	private function __construct() {
		$this->reset();
	}

	/**
	 * The one instance the hooks use.
	 *
	 * @since 2.41.0
	 *
	 * @return self
	 */
	public static function instance(): self {
		return self::$instance ??= new self();
	}

	/**
	 * Registers the hook callbacks. A second call does nothing.
	 *
	 * @since 2.41.0
	 *
	 * @return void
	 */
	public function register(): void {
		if ( $this->registered ) {
			return;
		}

		$this->registered = true;

		// Earliest priority, so each part is seen as WordPress built it.
		foreach ( self::PARTS as $part ) {
			add_filter( "posts_{$part}", fn( mixed $value, mixed $query = null ): mixed => $this->first_look( $part, $value, $query ), PHP_INT_MIN, 2 );
		}

		// Latest priorities, so what is left after every plugin filter is seen.
		add_filter( 'posts_search', [ $this, 'last_search' ], PHP_INT_MAX, 2 );
		add_filter( 'posts_clauses_request', [ $this, 'last_look' ], PHP_INT_MAX, 2 );

		// Both ends of posts_request, so a plugin that rewrites the statement is seen.
		add_filter( 'posts_request', [ $this, 'first_request' ], PHP_INT_MIN, 2 );
		add_filter( 'posts_request', [ $this, 'prepare' ], PHP_INT_MAX, 2 );

		// Latest priority, so the statement is seen exactly as it goes to the database.
		add_filter( 'query', [ $this, 'swap' ], PHP_INT_MAX );

		// Earliest priority, so a failed grid statement is repaired before anything reads the
		// result. The grid result cache shares the priority and registers later, so it runs after.
		add_filter( 'posts_results', [ $this, 'recover' ], PHP_INT_MIN, 2 );
	}

	/**
	 * Marks a query, so the optimizer looks at it. Any other role leaves it unmarked.
	 *
	 * @since 2.41.0
	 *
	 * @param WP_Query $query The query, before it runs.
	 * @param string   $role  'grid' for a grid's own query, 'copy' for Mai's ID-only copy.
	 *
	 * @return void
	 */
	public function mark( WP_Query $query, string $role ): void {
		if ( in_array( $role, [ 'grid', 'copy' ], true ) ) {
			$query->mai_optimize = $role;
		}
	}

	/**
	 * Removes a query's prepared swaps, once its statement can no longer be sent.
	 *
	 * @since 2.41.0
	 *
	 * @param WP_Query $query The query.
	 *
	 * @return void
	 */
	public function drop( WP_Query $query ): void {
		$this->prepared = array_values( array_filter( $this->prepared, static fn( array $entry ): bool => $entry['owner'] !== $query ) );
	}

	/**
	 * How a query's swapped statement went, and forgets it.
	 *
	 * Failed means $wpdb has an error, and any of these holds:
	 *
	 * - Exactly one statement ran since the swap. This holds whatever later query callbacks did
	 *   to the text.
	 * - The last statement is the swapped text. This holds when a later callback sent a
	 *   statement of its own.
	 * - The query has no posts and a statement ran since the swap. This holds when a later
	 *   callback did both. A failed SELECT returns no rows, so an error that comes with an empty
	 *   result counts against the swap, as Mai_Query_Cache::fetch_ids() counts it against an
	 *   empty copy. Core has set the query's posts by the time posts_results runs, and when
	 *   query() returns.
	 *
	 * A swapped statement that returns no rows without an error is ok.
	 *
	 * @since 2.41.0
	 *
	 * @param WP_Query $query The query.
	 *
	 * @return array{status:string,seconds:float,form:string}|null Null when nothing was swapped
	 *                                                             for it.
	 */
	public function outcome( WP_Query $query ): ?array {
		global $wpdb;

		if ( ! isset( $this->records[ $query ] ) ) {
			return null;
		}

		$record = $this->records[ $query ];

		unset( $this->records[ $query ] );

		$sent   = (int) $wpdb->num_queries;
		$failed = '' !== (string) $wpdb->last_error
			&& (
				$record['queries'] + 1 === $sent
				|| $record['swapped'] === $wpdb->last_query
				|| ( empty( $query->posts ) && $sent > $record['queries'] )
			);

		return [
			'status'  => $failed ? 'failed' : 'ok',
			'seconds' => microtime( true ) - $record['start'],
			'form'    => $record['form'],
		];
	}

	/**
	 * Turns the swap off for the rest of this request and for a day, and logs one line.
	 *
	 * @since 2.41.0
	 *
	 * @param string $why    Why, such as 'failed' or 'slow'.
	 * @param string $detail What happened, such as the database error.
	 *
	 * @return void
	 */
	public function turn_off( string $why, string $detail = '' ): void {
		$this->off = true;

		set_transient( self::TRANSIENT, $why, DAY_IN_SECONDS );

		$this->prepared = [];
		$this->records  = new WeakMap();

		( $this->logger )( "Grid query optimizer off for 24 hours ({$why}): {$detail}" );
	}

	/**
	 * Replaces what receives the log line. Tests collect it.
	 *
	 * @since 2.41.0
	 *
	 * @param Closure $logger Receives the message as a string.
	 *
	 * @return void
	 */
	public function set_logger( Closure $logger ): void {
		$this->logger = $logger;
	}

	/**
	 * Clears the per-request state, as at the start of a request, and puts the default logger
	 * back. The hooks stay registered.
	 *
	 * @since 2.41.0
	 *
	 * @return void
	 */
	public function reset(): void {
		$this->off      = false;
		$this->allowed  = null;
		$this->paused   = null;
		$this->database = null;
		$this->hint     = false;
		$this->rebuilds = [];
		$this->prepared = [];
		$this->records  = new WeakMap();
		$this->logger   = self::log( ... );
	}

	/**
	 * Records a SQL part as WordPress built it, before any plugin filter.
	 *
	 * The where comes first in core, so its look starts a fresh record, and a query that runs
	 * twice never reuses the looks from its first run. A part that is not a string is not
	 * recorded, so the query steps aside.
	 *
	 * @since 2.41.0
	 *
	 * @param string $part  'where', 'join', 'groupby', 'distinct' or 'fields'.
	 * @param mixed  $value The part.
	 * @param mixed  $query The query.
	 *
	 * @return mixed The part, unchanged.
	 */
	public function first_look( string $part, mixed $value, mixed $query ): mixed {
		if ( ! $query instanceof WP_Query || ! isset( $query->mai_optimize ) ) {
			return $value;
		}

		if ( 'where' === $part ) {
			$first                       = [];
			$query->mai_optimize_ready   = false;
			$query->mai_optimize_request = null;
		} else {
			$first = $query->mai_optimize_first ?? [];
			$first = is_array( $first ) ? $first : [];
		}

		if ( is_string( $value ) ) {
			$first[ $part ] = $value;
		}

		$query->mai_optimize_first = $first;

		return $value;
	}

	/**
	 * Records the search part of the where, after every posts_search callback.
	 *
	 * @since 2.41.0
	 *
	 * @param mixed $search The search SQL.
	 * @param mixed $query  The query.
	 *
	 * @return mixed The search SQL, unchanged.
	 */
	public function last_search( mixed $search, mixed $query = null ): mixed {
		if ( $query instanceof WP_Query && isset( $query->mai_optimize ) ) {
			$query->mai_optimize_search = $search;
		}

		return $search;
	}

	/**
	 * Decides whether the query is ready for a swap, after every clause filter.
	 *
	 * The search look is read once and cleared. Core runs posts_search before posts_where, so
	 * the where look cannot clear it, and a second run of the same query must see its own.
	 *
	 * @since 2.41.0
	 *
	 * @param mixed $clauses The query's clauses.
	 * @param mixed $query   The query.
	 *
	 * @return mixed The clauses, unchanged.
	 */
	public function last_look( mixed $clauses, mixed $query = null ): mixed {
		if ( ! $query instanceof WP_Query || ! isset( $query->mai_optimize ) ) {
			return $clauses;
		}

		$search = $query->mai_optimize_search ?? null;

		$query->mai_optimize_search = null;
		$query->mai_optimize_ready  = '' === $search && is_array( $clauses ) && $this->covered( $clauses, $query );

		return $clauses;
	}

	/**
	 * Records the finished statement before any posts_request callback.
	 *
	 * @since 2.41.0
	 *
	 * @param mixed $request The statement.
	 * @param mixed $query   The query.
	 *
	 * @return mixed The statement, unchanged.
	 */
	public function first_request( mixed $request, mixed $query = null ): mixed {
		if ( $query instanceof WP_Query && isset( $query->mai_optimize ) ) {
			$query->mai_optimize_request = is_string( $request ) ? $request : null;
		}

		return $request;
	}

	/**
	 * Prepares a swap for a ready query whose statement no posts_request callback changed.
	 *
	 * The ready verdict is read once and cleared, so it never carries over to another run.
	 *
	 * A statement with a placeholder escape is left alone. WordPress strips the escape in its
	 * own query callback at priority 0, so the text would never match.
	 *
	 * A grid's own statement is left alone when recover() is no longer on posts_results, since
	 * nothing could then repair it if it failed.
	 *
	 * @since 2.41.0
	 *
	 * @param mixed $request The statement.
	 * @param mixed $query   The query.
	 *
	 * @return mixed The statement, unchanged.
	 */
	public function prepare( mixed $request, mixed $query = null ): mixed {
		global $wpdb;

		if ( ! is_string( $request ) || ! $query instanceof WP_Query || true !== ( $query->mai_optimize_ready ?? null ) ) {
			return $request;
		}

		// The verdict is used once. A later run of this query that skips the looks, because a
		// plugin removed them, must earn its own.
		$query->mai_optimize_ready = false;

		if ( $this->off || ! $this->allowed() || $request !== ( $query->mai_optimize_request ?? null ) ) {
			return $request;
		}

		if ( ! method_exists( $wpdb, 'remove_placeholder_escape' ) || $wpdb->remove_placeholder_escape( $request ) !== $request ) {
			return $request;
		}

		$role = $query->mai_optimize;

		// A grid's failed statement is repaired by recover(). A plugin that removed every
		// posts_results callback removed that too, and then a failed swap would show no posts.
		if ( 'grid' === $role && false === has_filter( 'posts_results', [ $this, 'recover' ] ) ) {
			return $request;
		}

		$this->prepared[] = [
			'owner'    => $query,
			'original' => $request,
			'split'    => 'grid' === $role ? Mai_Post_Grid_Query_Optimizer_Sql::split( $request, $wpdb->posts ) : null,
			'role'     => $role,
		];

		return $request;
	}

	/**
	 * Swaps a prepared statement for the EXISTS form as it goes to the database.
	 *
	 * Returns at once when nothing is prepared. Otherwise it takes the most recent prepared swap
	 * whose text equals the statement. The most recent wins because a copy runs inside its
	 * grid's query, and its text equals the grid's split form. Then come the checks, and any
	 * failed one sends the statement unchanged. The statement count is recorded last, since the
	 * checks can send statements of their own.
	 *
	 * @since 2.41.0
	 *
	 * @param mixed $sql The statement.
	 *
	 * @return mixed The statement, swapped or unchanged.
	 */
	public function swap( mixed $sql ): mixed {
		global $wpdb;

		if ( ! $this->prepared || ! is_string( $sql ) ) {
			return $sql;
		}

		$entry = $this->take( $sql );

		if ( null === $entry ) {
			return $sql;
		}

		// On a site without a persistent object cache this read is a statement of its own. It
		// comes back through here and finds nothing prepared for it.
		$this->paused ??= false !== get_transient( self::TRANSIENT );

		if ( $this->paused ) {
			$this->off      = true;
			$this->prepared = [];

			return $sql;
		}

		if ( ! is_object( $wpdb ) || ! Mai_Post_Grid_Query_Optimizer_Database::layer_allows( $wpdb ) ) {
			return $sql;
		}

		if ( null === $this->database ) {
			$info           = Mai_Post_Grid_Query_Optimizer_Database::server_info( $wpdb );
			$this->database = Mai_Post_Grid_Query_Optimizer_Database::allows( $info );
			$this->hint     = Mai_Post_Grid_Query_Optimizer_Database::hint( $info );
		}

		if ( ! $this->database ) {
			return $sql;
		}

		$owner   = $entry['owner'];
		$rebuilt = $this->rebuild( $owner, $wpdb->posts );
		$first   = $owner->mai_optimize_first ?? null;

		// Nothing else is joined, and WordPress's taxonomy condition is in the where once.
		if ( null === $rebuilt || ! is_array( $first ) || ( $first['join'] ?? null ) !== $rebuilt['join'] ) {
			return $sql;
		}

		if ( ! is_string( $first['where'] ?? null ) || '' === $rebuilt['where'] || 1 !== substr_count( $first['where'], $rebuilt['where'] ) ) {
			return $sql;
		}

		$condition = Mai_Post_Grid_Query_Optimizer_Sql::condition( $rebuilt, $wpdb->posts, $wpdb->term_relationships, $this->hint );
		$swapped   = Mai_Post_Grid_Query_Optimizer_Sql::swap( $sql, $rebuilt, $condition, $wpdb->posts );

		if ( null === $swapped ) {
			return $sql;
		}

		$this->records[ $owner ] = [
			'queries' => (int) $wpdb->num_queries,
			'swapped' => $swapped,
			'form'    => 'copy' === $entry['role'] ? 'copy' : ( $sql === $entry['split'] ? 'split' : 'full' ),
			'start'   => microtime( true ),
		];

		return $swapped;
	}

	/**
	 * Repairs a grid whose swapped statement failed, before anything reads its posts.
	 *
	 * Every grid query reaches posts_results, including one answered by a cache, so this is
	 * where a grid's prepared swaps are dropped. When its swapped statement failed, the swap is
	 * turned off, WordPress is made to forget the failed empty result it cached under the
	 * query's key, and the original statement is sent once more the way WordPress sent it. The
	 * list is then exactly today's, so the grid result cache stores it as usual. If the resend
	 * fails too, $wpdb holds its error, and the grid result cache stores nothing, as for any
	 * failed grid statement.
	 *
	 * The error is read before turn_off(), whose transient write is a statement of its own.
	 *
	 * @since 2.41.0
	 *
	 * @param mixed $posts The posts.
	 * @param mixed $query The query.
	 *
	 * @return mixed The posts, or the resent list when the swapped statement failed.
	 */
	public function recover( mixed $posts, mixed $query = null ): mixed {
		global $wpdb;

		if ( ! $query instanceof WP_Query || 'grid' !== ( $query->mai_optimize ?? null ) ) {
			return $posts;
		}

		$outcome = $this->outcome( $query );

		$this->drop( $query );

		if ( 'failed' !== ( $outcome['status'] ?? null ) ) {
			return $posts;
		}

		$error   = (string) $wpdb->last_error;
		$request = (string) $query->request;

		$this->turn_off( 'failed', $error );

		wp_cache_set_posts_last_changed();

		if ( 'split' === $outcome['form'] ) {
			$vars = $query->query_vars;
			$ids  = array_map( 'intval', (array) $wpdb->get_col( $request ) );

			_prime_post_caches( $ids, (bool) ( $vars['update_post_term_cache'] ?? true ), (bool) ( $vars['update_post_meta_cache'] ?? true ) );

			return array_map( 'get_post', $ids );
		}

		return array_map( 'get_post', (array) $wpdb->get_results( $request ) );
	}

	/**
	 * Whether the query is a covered shape and every part is as WordPress built it.
	 *
	 * @param array    $clauses The clauses after every clause filter.
	 * @param WP_Query $query   The marked query.
	 *
	 * @return bool
	 */
	private function covered( array $clauses, WP_Query $query ): bool {
		global $wpdb;

		$role  = $query->mai_optimize;
		$first = $query->mai_optimize_first ?? null;

		if ( ! in_array( $role, [ 'grid', 'copy' ], true ) || ! is_array( $first ) ) {
			return false;
		}

		foreach ( self::PARTS as $part ) {
			if ( ! isset( $first[ $part ] ) || ( $clauses[ $part ] ?? null ) !== $first[ $part ] ) {
				return false;
			}
		}

		$vars    = $query->query_vars;
		$limits  = $clauses['limits'] ?? '';
		$orderby = $clauses['orderby'] ?? null;

		return '' === ( $vars['s'] ?? '' )
			&& $query->meta_query instanceof WP_Meta_Query
			&& empty( $query->meta_query->queries )
			&& ! empty( $vars['no_found_rows'] )
			&& is_string( $limits )
			&& '' !== $limits
			&& '' === $first['distinct']
			&& ( 'copy' === $role ? "{$wpdb->posts}.ID" : "{$wpdb->posts}.*" ) === $first['fields']
			&& "{$wpdb->posts}.ID" === $first['groupby']
			&& '' !== $first['join']
			&& is_string( $orderby )
			&& Mai_Post_Grid_Query_Optimizer_Sql::orderby_ok( $orderby, $wpdb->posts )
			&& $query->tax_query instanceof WP_Tax_Query
			&& ! empty( $query->tax_query->queries );
	}

	/**
	 * Removes and returns the most recent prepared swap whose text equals the statement.
	 *
	 * @param string $sql The statement.
	 *
	 * @return array{owner:WP_Query,original:string,split:?string,role:string}|null
	 */
	private function take( string $sql ): ?array {
		for ( $i = count( $this->prepared ) - 1; $i >= 0; $i-- ) {
			$entry = $this->prepared[ $i ];

			if ( $sql === $entry['original'] || $sql === $entry['split'] ) {
				array_splice( $this->prepared, $i, 1 );

				return $entry;
			}
		}

		return null;
	}

	/**
	 * WordPress's taxonomy SQL for a query, rebuilt once per request.
	 *
	 * Keyed by the tax filters and the posts table, never by the query object, so a grid and its
	 * copy share one rebuild and two grids never do.
	 *
	 * @param WP_Query $query       The query.
	 * @param string   $posts_table The posts table name.
	 *
	 * @return array|null What Mai_Post_Grid_Query_Optimizer_Sql::rebuild() returned.
	 */
	private function rebuild( WP_Query $query, string $posts_table ): ?array {
		if ( ! $query->tax_query instanceof WP_Tax_Query ) {
			return null;
		}

		$key = md5( serialize( $query->tax_query->queries ) ) . $posts_table;

		if ( ! array_key_exists( $key, $this->rebuilds ) ) {
			$this->rebuilds[ $key ] = Mai_Post_Grid_Query_Optimizer_Sql::rebuild( $query->tax_query, $posts_table );
		}

		return $this->rebuilds[ $key ];
	}

	/**
	 * Whether the swap is allowed by the filter, read once per request.
	 *
	 * @return bool
	 */
	private function allowed(): bool {
		return $this->allowed ??= (bool) apply_filters( self::FILTER, true );
	}

	/**
	 * The default logger. Writes the line to the debug log when WP_DEBUG_LOG is on.
	 *
	 * @param string $message The message.
	 *
	 * @return void
	 */
	private static function log( string $message ): void {
		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log( 'Mai Engine: ' . $message );
		}
	}
}
