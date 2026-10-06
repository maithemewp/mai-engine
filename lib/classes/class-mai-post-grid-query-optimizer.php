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
 * A swapped statement that fails, or never reaches the database, is sent again unswapped, by
 * recover() for a grid's own query and by Mai_Query_Cache::fetch_ids() for the copy, and the swap
 * turns off for a day. So does a swapped statement that is slow, the copy's or the grid's own.
 * Anything that throws inside the swap sends the statement unchanged and turns the swap off for
 * a day too.
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
	 * Set for a day when a swapped statement failed or was slow. Holds why, such as
	 * "failed: " and the database error.
	 */
	public const TRANSIENT = 'mai_post_grid_optimize_off';

	/**
	 * A grid's own query, marked by Mai_Grid::get_query().
	 */
	public const ROLE_GRID = 'grid';

	/**
	 * Mai's ID-only copy of a grid's query, marked by Mai_Query_Cache::fetch_ids().
	 */
	public const ROLE_COPY = 'copy';

	/**
	 * What outcome() reports when the swapped statement worked.
	 */
	public const STATUS_OK = 'ok';

	/**
	 * What outcome() reports when the swapped statement failed or never reached the database.
	 */
	public const STATUS_FAILED = 'failed';

	/**
	 * The copy's statement.
	 */
	public const FORM_COPY = 'copy';

	/**
	 * A grid's ID-only statement, which WordPress sends first when it splits the query.
	 */
	public const FORM_SPLIT = 'split';

	/**
	 * A grid's whole statement, sent when WordPress does not split the query.
	 */
	public const FORM_FULL = 'full';

	/**
	 * The SQL parts recorded at the first and last plugin filter. posts_where comes first in
	 * core, so its first look starts a fresh record.
	 */
	private const PARTS = [ 'where', 'join', 'groupby', 'distinct', 'fields' ];

	/**
	 * The default of $slow.
	 */
	private const SLOW = 1.0;

	/**
	 * Seconds a swapped statement may take before the swap is turned off. Tests change it.
	 */
	public static float $slow = self::SLOW;

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
	 * Swaps waiting for their statement, oldest first, at most one per query.
	 *
	 * @var list<array{owner:WP_Query,original:string,split:?string,role:string}>
	 */
	private array $prepared = [];

	/**
	 * The copy running now, from mark() until drop(). While it runs, only a copy's swap may be
	 * taken. Weak, so a copy freed without drop() stops counting as running.
	 */
	private ?WeakReference $copy = null;

	/**
	 * What each swap recorded for its query, until outcome() reads it: the statement count and
	 * the length of WordPress's list of failed statements before it was sent, how deep in the
	 * filters it was swapped, its text, its form, when it started, and what the next statement
	 * found: when it ended, and whether it was never sent.
	 *
	 * Keyed by the query object itself, which a WeakMap forgets when the query is freed. A key
	 * made from spl_object_id() could be reused by a later query.
	 *
	 * @var WeakMap<WP_Query,array{queries:int,errors:int,depth:int,swapped:string,form:string,start:float,end:?float,unsent:bool}>
	 */
	private WeakMap $records;

	/**
	 * The query whose swapped statement the next statement checks. See check_last_swap().
	 */
	private ?WeakReference $pending = null;

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
	 * A copy counts as running from here until drop(), so call drop() once it has run.
	 *
	 * @since 2.41.0
	 *
	 * @param WP_Query $query The query, before it runs.
	 * @param string   $role  ROLE_GRID for a grid's own query, ROLE_COPY for Mai's ID-only copy.
	 *
	 * @return void
	 */
	public function mark( WP_Query $query, string $role ): void {
		if ( ! in_array( $role, [ self::ROLE_GRID, self::ROLE_COPY ], true ) ) {
			return;
		}

		$query->mai_optimize = $role;

		if ( self::ROLE_COPY === $role ) {
			$this->copy = WeakReference::create( $query );
		}
	}

	/**
	 * Removes a query's prepared swap, once its statement can no longer be sent. For a copy, it
	 * also ends the copy's run.
	 *
	 * @since 2.41.0
	 *
	 * @param WP_Query $query The query.
	 *
	 * @return void
	 */
	public function drop( WP_Query $query ): void {
		$this->forget( $query );

		if ( $query === $this->copy?->get() ) {
			$this->copy = null;
		}
	}

	/**
	 * How a query's swapped statement went, and forgets it.
	 *
	 * Failed when it never reached the database. A later query callback that returned an empty
	 * string or false for the swapped text stops $wpdb before it sends anything or clears its
	 * last result, so the query was handed the rows of the statement before, with no error. The
	 * next statement finds the statement count unchanged (see check_last_swap()), and when none
	 * came before this, the count is still what was recorded.
	 *
	 * Failed when WordPress's list of failed statements ($EZSQL_ERROR) gained one with the
	 * swapped text. $wpdb adds every failed statement to it, errors shown or not, so this holds
	 * after a later statement cleared $wpdb's error.
	 *
	 * Failed when $wpdb has an error, and any of these holds:
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
	 * The seconds run from the swap to the next statement, so loading a grid's posts afterwards
	 * does not count. When no statement came after it, they run to now.
	 *
	 * @since 2.41.0
	 *
	 * @param WP_Query $query The query.
	 *
	 * @return array{status:string,seconds:float,form:string,error:string}|null Null when nothing
	 *         was swapped for it. The error is what to log for a failure: "statement never sent",
	 *         or the database error. Empty when ok.
	 */
	public function outcome( WP_Query $query ): ?array {
		global $wpdb;

		if ( ! isset( $this->records[ $query ] ) ) {
			return null;
		}

		$record = $this->records[ $query ];

		unset( $this->records[ $query ] );

		if ( $query === $this->pending?->get() ) {
			$this->pending = null;
		}

		$sent   = (int) $wpdb->num_queries;
		$error  = (string) $wpdb->last_error;
		$unsent = $record['unsent'] || $sent === $record['queries'];
		$logged = $unsent ? null : self::logged_error( $record );
		$failed = $unsent
			|| null !== $logged
			|| (
				'' !== $error
				&& (
					$record['queries'] + 1 === $sent
					|| $record['swapped'] === $wpdb->last_query
					|| ( empty( $query->posts ) && $sent > $record['queries'] )
				)
			);

		return [
			'status'  => $failed ? self::STATUS_FAILED : self::STATUS_OK,
			'seconds' => ( $record['end'] ?? microtime( true ) ) - $record['start'],
			'form'    => $record['form'],
			'error'   => ! $failed ? '' : ( $unsent ? 'statement never sent' : ( $logged ?? $error ) ),
		];
	}

	/**
	 * Turns the swap off for the rest of this request and for a day, and logs one line.
	 *
	 * The request is cleared first, since the transient write can be a statement of its own,
	 * which comes back through swap(). The transient holds why and what happened. When it cannot
	 * be read back, the write did not stick, and the line says the swap is off for this request
	 * only.
	 *
	 * @since 2.41.0
	 *
	 * @param string $why    Why, such as 'failed' or 'slow'.
	 * @param string $detail What happened, such as the database error.
	 *
	 * @return void
	 */
	public function turn_off( string $why, string $detail = '' ): void {
		$this->off      = true;
		$this->prepared = [];
		$this->records  = new WeakMap();
		$this->pending  = null;

		set_transient( self::TRANSIENT, trim( "{$why}: {$detail}" ), DAY_IN_SECONDS );

		$for = false === get_transient( self::TRANSIENT ) ? 'this request only' : '24 hours';

		( $this->logger )( "Grid query optimizer off for {$for} ({$why}): {$detail}" );
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
	 * Clears the per-request state, as at the start of a request, and puts the default logger and
	 * slow limit back. The hooks stay registered.
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
		$this->copy     = null;
		$this->records  = new WeakMap();
		$this->pending  = null;
		$this->logger   = self::log( ... );

		self::$slow = self::SLOW;
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
	 * The ready verdict is read once and cleared, so it never carries over to another run. A
	 * query that runs again replaces its earlier swap, so it has at most one.
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
		if ( self::ROLE_GRID === $role && false === has_filter( 'posts_results', [ $this, 'recover' ] ) ) {
			return $request;
		}

		$this->forget( $query );

		$this->prepared[] = [
			'owner'    => $query,
			'original' => $request,
			'split'    => self::ROLE_GRID === $role ? Mai_Post_Grid_Query_Optimizer_Sql::split( $request, $wpdb->posts ) : null,
			'role'     => $role,
		];

		return $request;
	}

	/**
	 * Swaps a prepared statement for the EXISTS form as it goes to the database.
	 *
	 * Returns at once when nothing is prepared. Otherwise it takes the most recent prepared swap
	 * whose text equals the statement, and checks it. Anything that throws on the way, such as an
	 * object cache that fails on the transient read, sends the statement unchanged and turns the
	 * swap off for a day, like a failed statement, so a cause that repeats logs once a day. When
	 * the turn-off throws too, the swap is off for the rest of this request and one line says so.
	 *
	 * Every statement first checks the last swapped one, while one is waiting. See
	 * check_last_swap().
	 *
	 * @since 2.41.0
	 *
	 * @param mixed $sql The statement.
	 *
	 * @return mixed The statement, swapped or unchanged.
	 */
	public function swap( mixed $sql ): mixed {
		if ( null !== $this->pending ) {
			$this->check_last_swap();
		}

		if ( ! $this->prepared || ! is_string( $sql ) ) {
			return $sql;
		}

		try {
			return $this->swap_statement( $sql );
		} catch ( Throwable $e ) {
			$this->off      = true;
			$this->prepared = [];

			try {
				$this->turn_off( 'error', $e->getMessage() );
			} catch ( Throwable ) {
				( $this->logger )( "Grid query optimizer off for this request only (error): {$e->getMessage()}" );
			}

			return $sql;
		}
	}

	/**
	 * Repairs a grid whose swapped statement failed, before anything reads its posts.
	 *
	 * Every grid query reaches posts_results, including one answered by a cache, so this is
	 * where a grid's prepared swap is dropped. When its swapped statement failed, WordPress is
	 * made to forget the failed empty result it cached under the query's key, the swap is turned
	 * off, and the original statement is sent once more the way WordPress sent it. The list is
	 * then exactly today's, so the grid result cache stores it as usual. If the resend fails too,
	 * $wpdb holds its error, and the grid result cache stores nothing, as for any failed grid
	 * statement.
	 *
	 * When the swapped statement worked but took longer than $slow, the swap is turned off and
	 * the posts stand. The time stops at the next statement, so loading the posts does not count.
	 *
	 * outcome() reads the error before turn_off(), whose transient write is a statement of its own.
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

		if ( ! $query instanceof WP_Query || self::ROLE_GRID !== ( $query->mai_optimize ?? null ) ) {
			return $posts;
		}

		$outcome = $this->outcome( $query );

		$this->drop( $query );

		if ( null === $outcome ) {
			return $posts;
		}

		if ( self::STATUS_OK === $outcome['status'] ) {
			// Right, but slow enough that the database planned it badly. The posts stand.
			if ( $outcome['seconds'] > self::$slow ) {
				$this->turn_off( 'slow', sprintf( '%.3f s, grid %s form', $outcome['seconds'], $outcome['form'] ) );
			}

			return $posts;
		}

		$error   = $outcome['error'];
		$request = (string) $query->request;

		// WordPress cached the failed result under the query's key before this ran. Reset before
		// turn_off(), whose transient write is a statement of its own and can fail too.
		Mai_Query_Cache::forget_failure();

		$this->turn_off( 'failed', $error );

		if ( self::FORM_SPLIT === $outcome['form'] ) {
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

		if ( ! in_array( $role, [ self::ROLE_GRID, self::ROLE_COPY ], true ) || ! is_array( $first ) ) {
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
			&& ( self::ROLE_COPY === $role ? "{$wpdb->posts}.ID" : "{$wpdb->posts}.*" ) === $first['fields']
			&& "{$wpdb->posts}.ID" === $first['groupby']
			&& '' !== $first['join']
			&& is_string( $orderby )
			&& Mai_Post_Grid_Query_Optimizer_Sql::orderby_ok( $orderby, $wpdb->posts )
			&& $query->tax_query instanceof WP_Tax_Query
			&& ! empty( $query->tax_query->queries );
	}

	/**
	 * Swaps a statement that may have a prepared swap, or returns it unchanged.
	 *
	 * Takes the most recent prepared swap whose text equals the statement. The most recent wins
	 * because a copy runs inside its grid's query, and its text equals the grid's split form.
	 * Then come the checks, and any failed one sends the statement unchanged:
	 *
	 * - prepare()'s checks again, since a later callback can change them before the statement is
	 *   sent: the swap is still on for the request and the filter allows it, and for a grid's own
	 *   statement recover() is still on posts_results.
	 * - The database and its layer. These send no statement, so they come before the transient
	 *   read, which is a statement of its own on a site without a persistent object cache. When
	 *   either fails, the swap is off for the rest of the request, so prepare() stops preparing.
	 * - The day-long transient.
	 * - The rebuild of WordPress's taxonomy SQL, compared with the first looks.
	 *
	 * The statement count and the length of WordPress's list of failed statements are recorded
	 * last, since the checks can send statements of their own. The next statement then checks
	 * this one (check_last_swap()).
	 *
	 * @param string $sql The statement.
	 *
	 * @return string The statement, swapped or unchanged.
	 */
	private function swap_statement( string $sql ): string {
		global $wpdb, $wp_current_filter;

		$entry = $this->take( $sql );

		if ( null === $entry ) {
			return $sql;
		}

		if ( $this->off || ! $this->allowed() ) {
			return $sql;
		}

		if ( self::ROLE_GRID === $entry['role'] && false === has_filter( 'posts_results', [ $this, 'recover' ] ) ) {
			return $sql;
		}

		if ( ! $this->database_allows() ) {
			$this->off      = true;
			$this->prepared = [];

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

		$errors = $GLOBALS['EZSQL_ERROR'] ?? null;

		$this->records[ $owner ] = [
			'queries' => (int) $wpdb->num_queries,
			'errors'  => is_array( $errors ) ? count( $errors ) : 0,
			'depth'   => is_array( $wp_current_filter ) ? count( $wp_current_filter ) : 0,
			'swapped' => $swapped,
			'form'    => self::ROLE_COPY === $entry['role'] ? self::FORM_COPY : ( $sql === $entry['split'] ? self::FORM_SPLIT : self::FORM_FULL ),
			'start'   => microtime( true ),
			'end'     => null,
			'unsent'  => false,
		];

		$this->pending = WeakReference::create( $owner );

		return $swapped;
	}

	/**
	 * Checks the last swapped statement as the next statement comes through: whether it reached
	 * the database, and when it ended.
	 *
	 * The statement count is the one recorded when nothing was sent. outcome() can only count
	 * statements when posts_results runs, and by then WordPress may have loaded posts for the
	 * stale rows a dropped split statement handed back, which moves the count on. So the next
	 * statement records it. Its arrival is also when the swapped statement ended, so a grid's
	 * timer stops before its posts are loaded.
	 *
	 * A statement sent from inside the swapped statement's own query filters, by a callback after
	 * swap(), arrives before the swapped one is sent. It is one query filter deeper, at the
	 * swapped statement's level, so it is passed over and the check waits for the next one.
	 *
	 * Runs on every statement while a swap is waiting, so it only reads a few values.
	 *
	 * @return void
	 */
	private function check_last_swap(): void {
		global $wpdb, $wp_current_filter;

		$owner = $this->pending?->get();

		if ( ! $owner instanceof WP_Query || ! isset( $this->records[ $owner ] ) ) {
			$this->pending = null;

			return;
		}

		$record = $this->records[ $owner ];
		$stack  = is_array( $wp_current_filter ) ? $wp_current_filter : [];

		if ( count( $stack ) > $record['depth'] && 'query' === ( $stack[ $record['depth'] - 1 ] ?? null ) ) {
			return;
		}

		$record['end']    = microtime( true );
		$record['unsent'] = (int) $wpdb->num_queries === $record['queries'];

		$this->records[ $owner ] = $record;
		$this->pending           = null;
	}

	/**
	 * Whether the database and its layer can take the swap. Sends no statement.
	 *
	 * The layer must be WordPress's own or Query Monitor's, and mysqli must not be in exception
	 * mode: a failed swapped statement would then throw out of WP_Query before recover() or
	 * fetch_ids() could send it again. Both are checked on every swap, since a plugin can change
	 * either mid-request. The server is read once per request.
	 *
	 * @return bool
	 */
	private function database_allows(): bool {
		global $wpdb;

		if ( ! is_object( $wpdb ) || ! Mai_Post_Grid_Query_Optimizer_Database::layer_allows( $wpdb ) ) {
			return false;
		}

		if ( class_exists( 'mysqli_driver', false ) && ( ( new mysqli_driver() )->report_mode & MYSQLI_REPORT_STRICT ) ) {
			return false;
		}

		if ( null === $this->database ) {
			$info           = Mai_Post_Grid_Query_Optimizer_Database::server_info( $wpdb );
			$this->database = Mai_Post_Grid_Query_Optimizer_Database::allows( $info );
			$this->hint     = Mai_Post_Grid_Query_Optimizer_Database::hint( $info );
		}

		return $this->database;
	}

	/**
	 * The error WordPress's list of failed statements gained for the swapped text after the
	 * swap, if it did.
	 *
	 * @param array{errors:int,swapped:string} $record What swap_statement() recorded.
	 *
	 * @return string|null Null when the list has no such entry.
	 */
	private static function logged_error( array $record ): ?string {
		$errors = $GLOBALS['EZSQL_ERROR'] ?? null;

		if ( ! is_array( $errors ) ) {
			return null;
		}

		foreach ( array_slice( $errors, $record['errors'] ) as $error ) {
			if ( is_array( $error ) && ( $error['query'] ?? null ) === $record['swapped'] ) {
				return (string) ( $error['error_str'] ?? '' );
			}
		}

		return null;
	}

	/**
	 * Removes a query's prepared swap, without ending a copy's run.
	 *
	 * @param WP_Query $query The query.
	 *
	 * @return void
	 */
	private function forget( WP_Query $query ): void {
		$this->prepared = array_values( array_filter( $this->prepared, static fn( array $entry ): bool => $entry['owner'] !== $query ) );
	}

	/**
	 * Removes and returns the most recent prepared swap whose text equals the statement.
	 *
	 * While a copy runs, only a copy's swap can be taken. The copy's text equals its grid's split
	 * form, so a copy that prepared no swap of its own would otherwise take the grid's.
	 *
	 * @param string $sql The statement.
	 *
	 * @return array{owner:WP_Query,original:string,split:?string,role:string}|null
	 */
	private function take( string $sql ): ?array {
		$copy_only = null !== $this->copy?->get();

		for ( $i = count( $this->prepared ) - 1; $i >= 0; $i-- ) {
			$entry = $this->prepared[ $i ];

			if ( $copy_only && self::ROLE_COPY !== $entry['role'] ) {
				continue;
			}

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
	 * copy share one rebuild, and grids with different filters never do.
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
	 * The default logger. Writes the line to the PHP error log, whatever WP_DEBUG_LOG says, so a
	 * live site shows when the swap turned off. A turn-off is written at most once a day per
	 * site, unless the transient cannot be stored.
	 *
	 * @param string $message The message.
	 *
	 * @return void
	 */
	private static function log( string $message ): void {
		error_log( 'Mai Engine: ' . $message );
	}
}
