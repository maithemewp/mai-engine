<?php
declare(strict_types=1);

namespace BizBudding\MaiEngine\Tests\Integration;

use Mai\Cache\Cache;
use Mai_Grid;
use Mai_Query_Cache;
use Mai_Query_Cache_Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use WP_Query;

/**
 * Rebuilding an aged-out grid entry after the page.
 *
 * - An entry past its soft lifetime, with no post saved since, built by the ID-only copy, for a
 *   kept-only grid, on a request that can finish early, is served as it is. One job per key is
 *   queued, and it runs after the response is finished.
 * - The job works from values captured when it was queued, never from the grid's live query,
 *   which Mai_Grid has put back by then. It stores exactly what a rebuild during the page would.
 * - Every other stale entry is rebuilt during the page, as before.
 *
 * Time is moved with Cache::set_clock(), which only mai-cache reads. A pretend request is
 * go_to() plus a fresh queue, whose seams stand in for fastcgi_finish_request(). tear_down puts
 * the real clock back, and MaiIntegrationTestCase puts a test queue back.
 */
final class GridCacheAfterPageTest extends MaiIntegrationTestCase {

	private const PER_PAGE = 3;

	/** The default soft lifetime. */
	private const SOFT = 4 * HOUR_IN_SECONDS;

	/** Stands in for Mai Publisher's views count, which is not in this suite. */
	private const VIEWS = 'mai_test_views';

	/** The one test that runs with a persistent object cache. See set_up(). */
	private const OBJECT_CACHE_TEST = 'test_decline_releases_the_lock';

	/** @var int[] Posts in the category, newest first. */
	private array $post_ids = [];

	/** The newest post of all, outside the category until a test moves it in. */
	private int $outsider = 0;

	private int $term_id = 0;

	/** A category with no posts in it, for the empty grid. */
	private int $empty_term_id = 0;

	/** What the mai-cache clock reads. Tests move it forward. */
	private int $now = 0;

	private Mai_Query_Cache_Queue $queue;

	/** How many times the current queue finished the response. */
	private int $finishes = 0;

	private string $method = 'GET';

	/** The wp_using_ext_object_cache() value before the test, when this test changed it. */
	private ?bool $object_cache_was = null;

	public function set_up(): void {
		// Before anything reads the cache, the posts made below included, so every token is
		// read from and written to the object cache.
		if ( self::OBJECT_CACHE_TEST === $this->name() ) {
			$this->object_cache_was = (bool) wp_using_ext_object_cache( true );

			Cache::reset_runtime();
		}

		parent::set_up();

		$this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
		$this->now    = time();

		Cache::set_clock( fn() => $this->now );

		$this->term_id       = self::factory()->term->create( [ 'taxonomy' => 'category' ] );
		$this->empty_term_id = self::factory()->term->create( [ 'taxonomy' => 'category' ] );

		// Ten posts one day apart, the newest a day old. Views tie in pairs after the first, so
		// the ID tiebreaker settles the order of a views grid.
		for ( $i = 0; $i < 10; $i++ ) {
			$id = self::factory()->post->create( [
				'post_status'   => 'publish',
				'post_date'     => gmdate( 'Y-m-d H:i:s', strtotime( '-' . ( $i + 1 ) . ' days' ) ),
				'post_category' => [ $this->term_id ],
			] );

			add_post_meta( $id, self::VIEWS, intdiv( 10 - $i, 2 ) * 10 );

			$this->post_ids[] = $id;
		}

		$this->outsider = self::factory()->post->create( [
			'post_status' => 'publish',
			'post_date'   => gmdate( 'Y-m-d H:i:s', strtotime( '-1 hour' ) ),
		] );

		add_post_meta( $this->outsider, self::VIEWS, 5 );

		$this->install_queue();

		( new Mai_Query_Cache() )->flush_all();
	}

	public function tear_down(): void {
		Cache::set_clock( null );

		Mai_Grid::$existing_post_ids = [];

		$_SERVER['REQUEST_METHOD'] = $this->method;

		parent::tear_down();

		if ( null !== $this->object_cache_was ) {
			wp_using_ext_object_cache( $this->object_cache_was );

			// The tokens this test read came from the object cache, which is gone now.
			Cache::reset_runtime();

			$this->object_cache_was = null;
		}
	}

	/** The kept-only grid shapes the no-drift rule is checked on. */
	public static function shapes(): array {
		return [
			'fixed category, exclude current' => [ 'current' ],
			'exclude displayed'               => [ 'displayed' ],
			'sorted by views'                 => [ 'views' ],
		];
	}

	/** How a key went stale. */
	public static function staleness(): array {
		return [
			'aged out'      => [ 'age' ],
			'a post saved'  => [ 'version' ],
		];
	}

	// ---- Helpers ----

	/**
	 * Installs a fresh queue on the instance the hooks use.
	 *
	 * @param bool $can_finish What the finish-function seam answers.
	 *
	 * @return void
	 */
	private function install_queue( bool $can_finish = true ): void {
		$this->finishes = 0;

		$this->queue = new Mai_Query_Cache_Queue(
			static fn() => $can_finish,
			function () {
				++$this->finishes;
			},
			static function () {}
		);

		Mai_Query_Cache::instance()->set_queue( $this->queue );
	}

	/**
	 * The grid for a shape.
	 *
	 * - current, views: on a single post, with Exclude current. views sorts by a meta number.
	 * - empty: like current, over a category with no posts.
	 * - displayed: on the home page, with Exclude displayed.
	 * - plain: on the home page, with no excludes, so it does not defer.
	 */
	private function grid_args( string $shape ): array {
		$args = [
			'type'           => 'post',
			'post_type'      => [ 'post' ],
			'query_by'       => 'tax_meta',
			'posts_per_page' => self::PER_PAGE,
			'excludes'       => [ 'exclude_current' ],
			'taxonomies'     => [
				[ 'taxonomy' => 'category', 'terms' => [ $this->term_id ], 'current' => false, 'operator' => 'IN' ],
			],
		];

		return match ( $shape ) {
			'current'   => $args,
			'empty'     => array_merge( $args, [ 'taxonomies' => [ [ 'taxonomy' => 'category', 'terms' => [ $this->empty_term_id ], 'current' => false, 'operator' => 'IN' ] ] ] ),
			'displayed' => array_merge( $args, [ 'excludes' => [ 'exclude_displayed' ] ] ),
			'views'     => array_merge( $args, [ 'orderby' => 'meta_value_num', 'orderby_meta_key' => self::VIEWS, 'order' => 'DESC' ] ),
			'plain'     => array_merge( $args, [ 'excludes' => [] ] ),
		};
	}

	/** Goes to the page a shape needs. */
	private function visit( string $shape ): void {
		$this->go_to( in_array( $shape, [ 'current', 'views', 'empty' ], true ) ? get_permalink( $this->post_ids[0] ) : home_url( '/' ) );

		Mai_Grid::$existing_post_ids = 'displayed' === $shape ? [ 'post' => [ $this->post_ids[1] ] ] : [];
	}

	/** Starts a new pretend request: a fresh queue, then the page. */
	private function new_request( string $shape, bool $can_finish = true ): void {
		$this->install_queue( $can_finish );
		$this->visit( $shape );
	}

	/**
	 * Renders one grid on the current request, and reports its key, whether the result cache
	 * answered it in posts_pre_query, and how many grid statements ran.
	 *
	 * The key is read at posts_pre_query priority 9, because Mai_Grid puts a deferring grid's
	 * vars back once the query returns. Whether the cache answered is read at priority 11,
	 * just after pre_query().
	 *
	 * @return array{query:WP_Query,key:string,answered:?bool,selects:int}
	 */
	private function render( string $shape ): array {
		$key      = '';
		$answered = null;

		$capture_key = static function ( $posts, $query ) use ( &$key ) {
			if ( ! empty( $query->query_vars['mai_cache'] ) ) {
				$key = ( new Mai_Query_Cache() )->cache_key( $query->query_vars, (string) $query->request );
			}

			return $posts;
		};

		$capture_answer = static function ( $posts, $query ) use ( &$answered ) {
			if ( ! empty( $query->query_vars['mai_cache'] ) ) {
				$answered = null !== $posts;
			}

			return $posts;
		};

		add_filter( 'posts_pre_query', $capture_key, 9, 2 );
		add_filter( 'posts_pre_query', $capture_answer, 11, 2 );

		[ $query, $selects ] = $this->count_selects( fn() => ( new Mai_Grid( $this->grid_args( $shape ) ) )->get_query() );

		remove_filter( 'posts_pre_query', $capture_answer, 11 );
		remove_filter( 'posts_pre_query', $capture_key, 9 );

		// A deferring grid asks for one row more than it shows, for the entry it will drop. The
		// plain shape has nothing to drop, so it asks for exactly what it shows.
		if ( 'plain' === $shape ) {
			$this->assertStringContainsString( 'LIMIT 0, ' . self::PER_PAGE, (string) $query->request, 'must not have deferred' );
		} else {
			$this->assertStringContainsString( 'LIMIT 0, ' . ( self::PER_PAGE + 1 ), (string) $query->request, 'must actually have deferred' );
		}

		$this->assertNotSame( '', $key, 'the grid query must have reached the result cache' );

		return [ 'query' => $query, 'key' => $key, 'answered' => $answered, 'selects' => $selects ];
	}

	/** Whether a statement is a grid statement: against the posts table and carrying a LIMIT. Priming reads have none. */
	private static function is_grid_statement( string $sql ): bool {
		global $wpdb;

		return preg_match( '/\bFROM\s+' . preg_quote( $wpdb->posts, '/' ) . '\b/', $sql ) && str_contains( $sql, 'LIMIT' );
	}

	/**
	 * Runs the callback and records the grid statements it ran, as they went to the database.
	 *
	 * @return array{0:mixed,1:string[]}
	 */
	private function grid_statements( callable $callback ): array {
		$statements = [];
		$record     = static function ( $sql ) use ( &$statements ) {
			if ( self::is_grid_statement( (string) $sql ) ) {
				$statements[] = (string) $sql;
			}

			return $sql;
		};

		add_filter( 'query', $record );
		$result = $callback();
		remove_filter( 'query', $record );

		return [ $result, $statements ];
	}

	/**
	 * Runs the callback with a query filter that rewrites the first ID-only grid statement into
	 * one that fails, on its way to the database. last_query is then the rewritten text, so
	 * fetch_ids() cannot see the failure as the copy's own. Database errors are not printed
	 * meanwhile.
	 *
	 * @return array{0:mixed,1:bool} What the callback returned, and whether a statement was broken.
	 */
	private function break_copy_once( callable $callback ): array {
		global $wpdb;

		$broken = false;
		$break  = static function ( $sql ) use ( &$broken, $wpdb ) {
			if ( ! $broken && self::is_grid_statement( (string) $sql ) && preg_match( '/^\s*SELECT\s+' . preg_quote( $wpdb->posts, '/' ) . '\.ID\s+FROM\s/', (string) $sql ) ) {
				$broken = true;

				return $sql . ' BROKEN';
			}

			return $sql;
		};

		add_filter( 'query', $break );
		$suppress = $wpdb->suppress_errors( true );

		try {
			$result = $callback();
		} finally {
			$wpdb->suppress_errors( $suppress );
			remove_filter( 'query', $break );
		}

		return [ $result, $broken ];
	}

	/**
	 * Runs the callback and counts the grid statements it ran.
	 *
	 * @return array{0:mixed,1:int}
	 */
	private function count_selects( callable $callback ): array {
		[ $result, $statements ] = $this->grid_statements( $callback );

		return [ $result, count( $statements ) ];
	}

	/** Runs the queue as shutdown would, and returns how many grid statements it ran. */
	private function run_queue(): int {
		return $this->count_selects( static fn() => Mai_Query_Cache::instance()->run_queue() )[1];
	}

	/**
	 * A cold render of a grid on its own request, stored after the page. Returns the grid and
	 * the IDs stored.
	 *
	 * @return array{key:string,ids:int[]}
	 */
	private function warm( string $shape ): array {
		$this->new_request( $shape );

		$grid = $this->render( $shape );

		$this->assertFalse( $grid['answered'], 'a cold miss' );

		$this->run_queue();

		$stored = $this->stored( $grid['key'] );

		$this->assertTrue( $stored['fresh'] ?? false, 'stored after the page' );
		$this->assertSame( 'plain' === $shape ? null : 'ids', $stored['value']['by'] ?? null );

		return [ 'key' => $grid['key'], 'ids' => $stored['value']['ids'] ];
	}

	/** Moves the clock past the soft lifetime of everything stored so far. */
	private function age(): void {
		$this->now += self::SOFT + 1;
	}

	/**
	 * Changes what a shape's query returns without saving a post, so the version stays and the
	 * entry can only go stale by age. Adding a term or changing a meta value is not a save.
	 */
	private function drift( string $shape ): void {
		$before = $this->version();

		if ( 'views' === $shape ) {
			update_post_meta( $this->post_ids[8], self::VIEWS, 1000 );
		} else {
			wp_set_object_terms( $this->outsider, [ $this->term_id ], 'category', true );
		}

		$this->assertSame( $before, $this->version(), 'no save, so the version stays' );
	}

	/** Publishes a post in the category, which changes the version. */
	private function save_a_post(): int {
		$before = $this->version();
		$id     = self::factory()->post->create( [ 'post_status' => 'publish', 'post_category' => [ $this->term_id ] ] );

		$this->assertNotSame( $before, $this->version(), 'the save changed the version' );

		return $id;
	}

	/** Overwrites an entry, under the current version, with the default lifetimes. */
	private function overwrite( string $key, mixed $value ): void {
		mai_cache( 'grid' )->write_swr( $key, $value, $this->version(), self::SOFT, DAY_IN_SECONDS );
	}

	/** The entry stored under a key, read as the cache reads it, against the current version. */
	private function stored( string $key ): ?array {
		return mai_cache( 'grid' )->read_swr( $key, $this->version() );
	}

	/** The raw envelope stored under a key, or false. */
	private function envelope( string $key ): mixed {
		return get_transient( mai_cache( 'grid' )->key( $key ) );
	}

	private function version(): string {
		return mai_cache( 'grid' )->version( [ 'post' ] );
	}

	private function ids( WP_Query $query ): array {
		return wp_list_pluck( $query->posts, 'ID' );
	}

	/** Asserts the grid was rebuilt during the page, and nothing was queued. */
	private function assert_rebuilt_during_page( array $grid ): void {
		$this->assertFalse( $grid['answered'], 'the lock winner runs the query' );
		$this->assertSame( 1, $grid['selects'], 'one grid query during the page' );
		$this->assertSame( [], $this->queue->jobs(), 'nothing queued' );
		$this->assertTrue( $this->queue->was_rebuilt( $grid['key'] ) );
	}

	/**
	 * Starts a new pretend request on the page already visited, keeping the object cache, as a
	 * persistent object cache shared between requests would. go_to() would empty it. The last
	 * request's locks have run out by now, and core's query cache is emptied so grid
	 * statements still run.
	 */
	private function next_request_same_cache(): void {
		$this->install_queue();

		wp_cache_flush_group( 'mai_cache_lock' );
		wp_cache_flush_group( 'post-queries' );
	}

	/**
	 * Starts a new pretend request on the page already visited, keeping core's query cache as
	 * the last request left it, as a persistent object cache would. Only the last request's
	 * locks are dropped.
	 */
	private function next_request_keeping_core_cache(): void {
		$this->install_queue();

		wp_cache_flush_group( 'mai_cache_lock' );
	}

	/**
	 * Changes a deferring grid's SQL from now on, so the job's copy gets a different key and
	 * declines. Returns the callback, for remove_filter().
	 */
	private function decline_at_shutdown(): \Closure {
		$change = static fn( $sql, $query ) => empty( $query->query_vars['mai_grid_tiebreak'] ) ? $sql : str_replace( 'WHERE 1=1', 'WHERE 1=1 AND 2=2', $sql );

		add_filter( 'posts_request', $change, 10, 2 );

		return $change;
	}

	/**
	 * Whether a query is a job's ID-only copy of one shape's grid: current, or views, which is
	 * the only one sorted by a meta key.
	 */
	private static function is_copy_of( WP_Query $query, string $shape ): bool {
		return 'ids' === ( $query->query_vars['fields'] ?? '' )
			&& ! empty( $query->query_vars['mai_grid_tiebreak'] )
			&& ( 'views' === $shape ) === ! empty( $query->query_vars['meta_key'] );
	}

	/**
	 * Warms a current grid, then a views grid one second later, so the current grid's job runs
	 * first. Then a new request after both aged out queues a job for each.
	 *
	 * @return array{0:string,1:string} The current and views keys.
	 */
	private function queue_two_jobs(): array {
		$current = $this->warm( 'current' );

		++$this->now;

		$views = $this->warm( 'views' );

		$this->age();
		$this->new_request( 'current' );
		$this->render( 'current' );
		$this->render( 'views' );

		$this->assertSame( [ $current['key'], $views['key'] ], array_column( $this->queue->jobs(), 'key' ), 'the current grid first' );

		return [ $current['key'], $views['key'] ];
	}

	/**
	 * Runs the callback with error_log() writing to a temporary file instead of the real log.
	 *
	 * @return array{0:mixed,1:string} What the callback returned, and what was logged.
	 */
	private function capture_error_log( callable $callback ): array {
		$file = (string) tempnam( sys_get_temp_dir(), 'mai-test-log-' );
		$was  = ini_set( 'error_log', $file );

		try {
			$result = $callback();
		} finally {
			ini_set( 'error_log', (string) $was );
		}

		$log = (string) file_get_contents( $file );

		unlink( $file );

		return [ $result, $log ];
	}

	/**
	 * Installs a queue whose time after the page always reads the same, so a budget check can
	 * be tested at its exact edge.
	 */
	private function install_queue_with_elapsed( float $elapsed ): void {
		$this->queue = new class( $elapsed ) extends Mai_Query_Cache_Queue {
			public function __construct( private float $fixed ) {
				parent::__construct( static fn() => true, static function () {}, static function () {} );
			}

			public function elapsed_ms(): float {
				return $this->fixed;
			}
		};

		Mai_Query_Cache::instance()->set_queue( $this->queue );
	}

	// ---- Queued ----

	public function test_age_stale_marked_entry_is_served_and_queued(): void {
		$warm = $this->warm( 'current' );

		$this->drift( 'current' );
		$this->age();
		$this->new_request( 'current' );

		$version = $this->version();
		$before  = $this->envelope( $warm['key'] );
		$grid    = $this->render( 'current' );

		$this->assertSame( 'age', $this->stored( $warm['key'] )['stale'] );
		$this->assertTrue( $grid['answered'], 'served from the entry' );
		$this->assertSame( 0, $grid['selects'], 'no grid query during the page' );
		$this->assertSame( array_slice( $warm['ids'], 1, self::PER_PAGE ), $this->ids( $grid['query'] ), 'the stale list, without the post being viewed' );

		$jobs = $this->queue->jobs();

		$this->assertCount( 1, $jobs );
		$this->assertSame( $warm['key'], $jobs[0]['key'] );
		$this->assertSame( $version, $jobs[0]['version'], 'the version read before any SQL' );
		$this->assertSame( $before['w'], $jobs[0]['written'] );
		$this->assertSame( self::PER_PAGE + 1, $jobs[0]['args']['posts_per_page'], 'the padded args, as the grid ran them' );
		$this->assertTrue( $jobs[0]['args']['mai_grid_tiebreak'] );
		$this->assertTrue( $jobs[0]['cache_results'], 'the cache_results the grid asked for' );
		$this->assertSame( self::SOFT, $jobs[0]['soft'] );
		$this->assertSame( DAY_IN_SECONDS, $jobs[0]['hard'] );
		$this->assertSame( get_current_blog_id(), $jobs[0]['blog_id'] );

		$this->assertSame( $before, $this->envelope( $warm['key'] ), 'nothing written before the page is sent' );
		$this->assertNull( $this->queue->pending( $warm['key'] ) );
		$this->assertFalse( $this->queue->was_rebuilt( $warm['key'] ) );
		$this->assertTrue( mai_cache( 'grid' )->lock( $warm['key'], 5 ), 'no lock taken before the page is sent' );
	}

	/**
	 * By the time the job runs, Mai_Grid has put the grid's query back the way it was asked
	 * for. The job still runs the padded query the grid ran, because it captured it.
	 */
	public function test_job_rebuilds_after_query_restored(): void {
		$warm = $this->warm( 'current' );

		$this->drift( 'current' );
		$this->age();
		$this->new_request( 'current' );

		$version = $this->version();
		$grid    = $this->render( 'current' );
		$live    = $grid['query'];

		$this->assertTrue( $this->queue->has_job( $warm['key'] ) );
		$this->assertSame( self::PER_PAGE, $live->query['posts_per_page'], 'Mai_Grid restored posts_per_page' );
		$this->assertSame( self::PER_PAGE, $live->query_vars['posts_per_page'] );
		$this->assertSame( [ $this->post_ids[0] ], $live->query['post__not_in'], 'and the excludes' );
		$this->assertArrayNotHasKey( 'mai_grid_tiebreak', $live->query );
		$this->assertArrayNotHasKey( 'mai_grid_tiebreak', $live->query_vars );

		$selects  = $this->run_queue();
		$stored   = $this->stored( $warm['key'] );
		$envelope = $this->envelope( $warm['key'] );
		$timeout  = (int) get_option( '_transient_timeout_' . mai_cache( 'grid' )->key( $warm['key'] ) );

		$this->assertSame( 1, $this->finishes, 'the response was finished first' );
		$this->assertSame( 1, $selects, 'the job ran the ID query once' );
		$this->assertTrue( $stored['fresh'] );
		$this->assertSame( $this->now, $envelope['w'], 'written now' );
		$this->assertSame( $version, $envelope['_v'] );
		$this->assertSame( self::SOFT, $envelope['s'] - $envelope['w'], 'the soft lifetime' );
		$this->assertEqualsWithDelta( DAY_IN_SECONDS, $timeout - time(), 2, 'the hard lifetime' );

		// What a rebuild during the page stores: the padded list, the post being viewed
		// included, with the post moved into the category first.
		$this->assertSame( [ $this->outsider, $this->post_ids[0], $this->post_ids[1], $this->post_ids[2] ], $stored['value']['ids'] );
		$this->assertSame( 'ids', $stored['value']['by'] );
		$this->assertFalse( $this->queue->has_job( $warm['key'] ), 'the job was taken off the queue' );
	}

	/**
	 * The no-drift rule: the job stores exactly what a rebuild during the page would. The data
	 * changes between the first store and the job, without a save, so the job has a new list
	 * to get right. The comparison is a cold rebuild of the same grid during the page, with core's
	 * query cache emptied so it runs its own SQL.
	 */
	#[DataProvider( 'shapes' )]
	public function test_no_drift( string $shape ): void {
		$warm = $this->warm( $shape );

		$this->drift( $shape );
		$this->age();
		$this->new_request( $shape );

		$grid = $this->render( $shape );

		$this->assertTrue( $grid['answered'] );
		$this->assertSame( 0, $grid['selects'] );
		$this->assertTrue( $this->queue->has_job( $warm['key'] ) );
		$this->assertSame( 1, $this->run_queue(), 'the job ran its query' );

		$by_job = $this->stored( $warm['key'] );

		$this->assertTrue( $by_job['fresh'] );

		( new Mai_Query_Cache() )->flush_all();

		$this->new_request( $shape );

		wp_cache_flush_group( 'post-queries' );

		$cold = $this->render( $shape );

		$this->assertSame( $warm['key'], $cold['key'] );
		$this->assertFalse( $cold['answered'], 'a cold miss' );
		$this->assertSame( 1, $cold['selects'] );

		$this->run_queue();

		$by_page = $this->stored( $warm['key'] );

		$this->assertSame( $by_page['value'], $by_job['value'], 'the same IDs, count and marker' );
		$this->assertNotSame( $warm['ids'], $by_job['value']['ids'], 'the list did change' );
	}

	/**
	 * A post saved after the entry was read and before the job runs must leave the rebuilt
	 * entry out of date. The job stores under the version read before any SQL ran.
	 */
	public function test_save_between_read_and_job_leaves_entry_stale(): void {
		$warm = $this->warm( 'current' );

		$this->age();
		$this->new_request( 'current' );

		$before = $this->version();

		$this->render( 'current' );
		$this->save_a_post();
		$this->run_queue();

		$envelope = $this->envelope( $warm['key'] );

		$this->assertSame( $this->now, $envelope['w'], 'the job did store' );
		$this->assertSame( $before, $envelope['_v'] );
		$this->assertSame( 'version', $this->stored( $warm['key'] )['stale'] );
	}

	// ---- Rebuilt during the page instead ----

	/** A save wins over age. Rebuilt during the page, as before. */
	public function test_version_stale_is_rebuilt_during_page(): void {
		$warm = $this->warm( 'current' );
		$new  = $this->save_a_post();

		$this->age();
		$this->new_request( 'current' );

		$this->assertSame( 'version', $this->stored( $warm['key'] )['stale'] );

		$grid = $this->render( 'current' );

		$this->assert_rebuilt_during_page( $grid );
		$this->assertSame( [ $new, $this->post_ids[1], $this->post_ids[2] ], $this->ids( $grid['query'] ), 'the new post shows' );
	}

	/** An entry built some other way than by the ID-only copy could not be rebuilt the same way. */
	public function test_not_queued_without_marker(): void {
		$warm = $this->warm( 'current' );

		$this->overwrite( $warm['key'], [ 'ids' => $warm['ids'], 'found' => count( $warm['ids'] ) ] );
		$this->age();
		$this->new_request( 'current' );

		$this->assert_rebuilt_during_page( $this->render( 'current' ) );
	}

	/**
	 * Only a kept-only grid has the asked cache_results for the job. The entry here carries the
	 * marker, so only the grid itself stops it being queued.
	 */
	public function test_not_queued_for_non_kept_grid(): void {
		$warm = $this->warm( 'plain' );

		$this->overwrite( $warm['key'], [ 'ids' => $warm['ids'], 'found' => count( $warm['ids'] ), 'by' => 'ids' ] );
		$this->age();
		$this->new_request( 'plain' );

		$this->assert_rebuilt_during_page( $this->render( 'plain' ) );
	}

	public function test_not_queued_when_filter_off(): void {
		$warm = $this->warm( 'current' );

		add_filter( 'mai_query_cache_after_page', '__return_false' );

		$this->age();
		$this->new_request( 'current' );

		$this->assert_rebuilt_during_page( $this->render( 'current' ) );
	}

	/** Never queue a job that would run at shutdown without the response being finished. */
	public function test_not_queued_when_cannot_finish_early(): void {
		$warm = $this->warm( 'current' );

		$this->age();
		$this->new_request( 'current', false );

		$this->assert_rebuilt_during_page( $this->render( 'current' ) );
		$this->assertTrue( $this->stored( $warm['key'] )['fresh'], 'stored during the page' );
	}

	public function test_not_queued_when_not_a_page_view(): void {
		$warm = $this->warm( 'current' );

		$this->age();
		$this->new_request( 'current' );

		$_SERVER['REQUEST_METHOD'] = 'POST';

		$this->assert_rebuilt_during_page( $this->render( 'current' ) );
		$this->assertTrue( $this->stored( $warm['key'] )['fresh'], 'stored during the page' );
	}

	/**
	 * A grid rendered once the queue has run, from a later shutdown callback, cannot be
	 * rebuilt after the page any more. Nothing would run the job.
	 */
	public function test_not_queued_after_the_queue_ran(): void {
		$warm = $this->warm( 'current' );

		$this->age();
		$this->new_request( 'current' );
		$this->run_queue();

		$this->assert_rebuilt_during_page( $this->render( 'current' ) );
		$this->assertTrue( $this->stored( $warm['key'] )['fresh'], 'stored at once' );
		$this->assertSame( 0, $this->finishes, 'an empty queue does not finish the response' );
	}

	/**
	 * An entry beta.4 wrote has no write time, so it never ages out. Its own store expiry
	 * retires it. Written by hand in that shape, as GridKeptOnlyTest does.
	 */
	public function test_not_queued_for_beta4_entry(): void {
		$warm = $this->warm( 'current' );

		( new Mai_Query_Cache() )->flush_all();

		set_transient( mai_cache( 'grid' )->key( $warm['key'] ), [ '_v' => $this->version(), 'value' => [ 'ids' => $warm['ids'], 'found' => count( $warm['ids'] ) ] ], HOUR_IN_SECONDS );

		$this->age();
		$this->new_request( 'current' );

		$this->assertNull( $this->stored( $warm['key'] )['stale'], 'never age-stale' );

		$grid = $this->render( 'current' );

		$this->assertTrue( $grid['answered'] );
		$this->assertSame( 0, $grid['selects'] );
		$this->assertSame( [], $this->queue->jobs() );
	}

	/** An entry serve() cannot use is rebuilt during the page, never queued. */
	public function test_malformed_entry_rebuilds_during_page(): void {
		$warm = $this->warm( 'current' );

		$this->overwrite( $warm['key'], [ 'ids' => 'not a list', 'found' => 4, 'by' => 'ids' ] );
		$this->age();
		$this->new_request( 'current' );

		$this->assertSame( 'age', $this->stored( $warm['key'] )['stale'] );

		$grid = $this->render( 'current' );

		$this->assert_rebuilt_during_page( $grid );
		$this->assertSame( array_slice( $this->post_ids, 1, self::PER_PAGE ), $this->ids( $grid['query'] ) );

		$this->run_queue();

		$this->assertSame( $warm['ids'], $this->stored( $warm['key'] )['value']['ids'], 'repaired' );
	}

	/**
	 * Nothing stores an object today. If one were stored, reading its marker as an array key
	 * would throw inside posts_pre_query. It is rebuilt during the page instead, never queued.
	 */
	public function test_object_value_is_not_queued_and_does_not_throw(): void {
		$warm = $this->warm( 'current' );

		$this->overwrite( $warm['key'], (object) [ 'ids' => $warm['ids'], 'found' => count( $warm['ids'] ), 'by' => 'ids' ] );
		$this->age();
		$this->new_request( 'current' );

		$this->assertSame( 'age', $this->stored( $warm['key'] )['stale'] );
		$this->assertIsObject( $this->stored( $warm['key'] )['value'], 'the value really is an object' );

		$grid = $this->render( 'current' );

		$this->assert_rebuilt_during_page( $grid );
		$this->assertSame( array_slice( $this->post_ids, 1, self::PER_PAGE ), $this->ids( $grid['query'] ) );
	}

	// ---- One decision per key ----

	/**
	 * Two identical grids on one page. Core's own query cache is emptied in between, so the
	 * second grid could only avoid a query by reusing the first one's decision. For an aged-out
	 * entry, the after-page filter is turned off in between too, so the second grid is only
	 * served stale because its key is already queued.
	 */
	#[DataProvider( 'staleness' )]
	public function test_same_key_twice_one_query( string $stale ): void {
		$this->warm( 'current' );

		if ( 'version' === $stale ) {
			$this->save_a_post();
		}

		$this->age();
		$this->new_request( 'current' );

		$first = $this->render( 'current' );

		wp_cache_flush_group( 'post-queries' );

		if ( 'age' === $stale ) {
			add_filter( 'mai_query_cache_after_page', '__return_false' );
		}

		$second = $this->render( 'current' );

		$this->assertSame( $first['key'], $second['key'] );
		$this->assertTrue( $second['answered'] );
		$this->assertSame( $this->ids( $first['query'] ), $this->ids( $second['query'] ) );

		if ( 'age' === $stale ) {
			$this->assertSame( 0, $first['selects'] + $second['selects'], 'no grid query during the page' );
			$this->assertCount( 1, $this->queue->jobs(), 'one job' );
		} else {
			$this->assertSame( 1, $first['selects'] + $second['selects'], 'one grid query' );
			$this->assertSame( [], $this->queue->jobs() );
		}
	}

	// ---- The job ----

	/**
	 * The key check is the safety net. A filter that changes the SQL only after the page gives
	 * the copy a different key, so nothing is stored and the entry is deleted.
	 */
	public function test_decline_deletes_entry(): void {
		$warm = $this->warm( 'current' );

		$this->age();
		$this->new_request( 'current' );
		$this->render( 'current' );

		$this->assertTrue( $this->queue->has_job( $warm['key'] ) );

		$this->decline_at_shutdown();

		$stores  = 0;
		$counter = static function ( $transient, $value ) use ( &$stores ) {
			if ( is_array( $value ) && isset( $value['value']['ids'] ) ) {
				++$stores;
			}
		};

		add_action( 'set_transient', $counter, 10, 2 );
		$selects = $this->run_queue();
		remove_action( 'set_transient', $counter, 10 );

		$this->assertSame( 1, $selects, 'the job ran its query' );
		$this->assertSame( 0, $stores, 'nothing stored' );
		$this->assertFalse( $this->envelope( $warm['key'] ), 'the entry is deleted' );
	}

	/**
	 * After a decline the lock is released, so the next visitor can take it and rebuild at
	 * once. Runs with a persistent object cache (see set_up()), where the lock is shared.
	 * Without the release, the next visitor would lose the lock and wait for a rebuild nobody
	 * is running.
	 */
	public function test_decline_releases_the_lock(): void {
		$this->assertTrue( wp_using_ext_object_cache() );

		// go_to() empties the object cache, so this test stays on one page throughout.
		$this->visit( 'current' );

		$key = $this->render( 'current' )['key'];

		$this->assertTrue( $this->stored( $key )['fresh'] ?? false, 'stored during the page, as with any object cache' );

		$this->age();
		$this->next_request_same_cache();
		$this->render( 'current' );

		$this->assertTrue( $this->queue->has_job( $key ) );

		$change = $this->decline_at_shutdown();

		$this->run_queue();

		remove_filter( 'posts_request', $change, 10 );

		$this->assertFalse( $this->envelope( $key ), 'the entry is deleted' );
		$this->assertTrue( mai_cache( 'grid' )->lock( $key, 5 ), 'the lock was released' );

		mai_cache( 'grid' )->unlock( $key );

		// The next visitor, while the job's lock would still be held had it not been released.
		$waited = false;

		add_filter(
			'mai_query_cache_wait_ms',
			static function () use ( &$waited ) {
				$waited = true;

				return 0;
			}
		);

		$this->install_queue();

		wp_cache_flush_group( 'post-queries' );

		$next = $this->render( 'current' );

		$this->assertFalse( $next['answered'], 'a cold miss' );
		$this->assertSame( 1, $next['selects'], 'one rebuild during the page' );
		$this->assertFalse( $waited, 'it took the lock, rather than wait for a rebuild nobody is running' );
		$this->assertTrue( $this->stored( $key )['fresh'] );
	}

	/**
	 * The lock is released only while it is still ours. A lock that lasts 1 second leaves no
	 * margin, so the job cannot be sure it still holds it, and leaves it to expire.
	 */
	public function test_decline_keeps_a_lock_that_may_have_expired(): void {
		add_filter( 'mai_query_cache_lock_ttl', static fn() => 1 );

		$warm = $this->warm( 'current' );

		$this->age();
		$this->new_request( 'current' );
		$this->render( 'current' );
		$this->decline_at_shutdown();
		$this->run_queue();

		$this->assertFalse( $this->envelope( $warm['key'] ), 'the entry is deleted' );
		$this->assertFalse( mai_cache( 'grid' )->lock( $warm['key'], 5 ), 'the lock is left to expire' );
	}

	/**
	 * The other kind of decline: the copy's own statement fails after the page. refuse_once()
	 * fails it without changing its SQL.
	 */
	public function test_failed_copy_statement_deletes_entry_and_releases_the_lock(): void {
		$warm = $this->warm( 'current' );

		$this->age();
		$this->new_request( 'current' );
		$this->render( 'current' );

		[ $selects, $refused ] = $this->refuse_once( self::is_grid_statement( ... ), fn() => $this->run_queue() );

		$this->assertTrue( $refused, 'the copy\'s statement was refused' );
		$this->assertSame( 1, $selects );
		$this->assertFalse( $this->envelope( $warm['key'] ), 'the entry is deleted' );
		$this->assertTrue( mai_cache( 'grid' )->lock( $warm['key'], 5 ), 'the lock was released' );
	}

	/**
	 * A job that throws is handled like a decline, and the job after it still runs. PHP would
	 * have logged the throw had it not been caught, so it is logged whatever WP_DEBUG_LOG says.
	 */
	public function test_job_that_throws_does_not_stop_the_next(): void {
		[ $current, $views ] = $this->queue_two_jobs();

		$error = new \RuntimeException( 'Test failure in the ID query' );

		add_filter(
			'posts_pre_query',
			static function ( $posts, $query ) use ( $error ) {
				if ( self::is_copy_of( $query, 'current' ) ) {
					throw $error;
				}

				return $posts;
			},
			1,
			2
		);

		[ $selects, $log ] = $this->capture_error_log( fn() => $this->run_queue() );

		$this->assertSame( 1, $selects, 'only the second job reached the database' );
		$this->assertFalse( $this->envelope( $current ), 'the entry is deleted' );
		$this->assertTrue( mai_cache( 'grid' )->lock( $current, 5 ), 'the lock was released' );
		$this->assertTrue( $this->stored( $views )['fresh'], 'the next job still ran' );

		$this->assertSame( 1, substr_count( $log, 'after the page failed with' ), 'logged once' );
		$this->assertStringContainsString( $current, $log );
		$this->assertStringContainsString( 'RuntimeException', $log );
		$this->assertStringContainsString( 'Test failure in the ID query', $log );
		$this->assertStringContainsString( $error->getFile() . ':' . $error->getLine(), $log );
	}

	/** A job captured on one blog does not run on another. Works on a single site. */
	public function test_job_skipped_on_other_blog(): void {
		$warm = $this->warm( 'current' );

		$this->age();
		$this->new_request( 'current' );
		$this->render( 'current' );

		$job    = $this->queue->jobs()[0];
		$before = $this->envelope( $warm['key'] );

		$this->install_queue();
		$this->queue->add_job( array_merge( $job, [ 'blog_id' => get_current_blog_id() + 1 ] ) );

		$this->assertSame( 0, $this->run_queue(), 'no query' );
		$this->assertSame( $before, $this->envelope( $warm['key'] ), 'the entry is left alone' );

		// The same job on its own blog does run.
		$this->install_queue();
		$this->queue->add_job( $job );

		$this->assertSame( 1, $this->run_queue() );
		$this->assertTrue( $this->stored( $warm['key'] )['fresh'] );
	}

	public function test_job_skipped_when_key_already_rebuilt(): void {
		$warm = $this->warm( 'current' );

		$this->age();
		$this->new_request( 'current' );
		$this->render( 'current' );

		$before = $this->envelope( $warm['key'] );

		$this->queue->mark_rebuilt( $warm['key'] );

		$this->assertSame( 0, $this->run_queue(), 'no query' );
		$this->assertSame( $before, $this->envelope( $warm['key'] ) );
	}

	/** Another request holds the lock the rebuild during the page uses. */
	public function test_job_skipped_when_lock_held(): void {
		$warm = $this->warm( 'current' );

		$this->age();
		$this->new_request( 'current' );
		$this->render( 'current' );

		$before = $this->envelope( $warm['key'] );

		$this->assertTrue( mai_cache( 'grid' )->lock( $warm['key'], 5 ) );
		$this->assertSame( 0, $this->run_queue(), 'no query' );
		$this->assertSame( $before, $this->envelope( $warm['key'] ) );
	}

	/**
	 * The loop stops once the time spent after the page reaches the budget. With a budget of 0
	 * no job starts, and the next visitor queues the entries again.
	 */
	public function test_budget_stops_jobs(): void {
		$this->new_request( 'current' );
		$current = $this->render( 'current' );
		$views   = $this->render( 'views' );
		$this->run_queue();

		$this->assertNotSame( $current['key'], $views['key'] );

		add_filter( 'mai_query_cache_after_page_ms', '__return_zero' );

		$this->age();
		$this->new_request( 'current' );
		$this->render( 'current' );
		$this->render( 'views' );

		$this->assertCount( 2, $this->queue->jobs() );

		$before = [ $this->envelope( $current['key'] ), $this->envelope( $views['key'] ) ];

		$this->assertSame( 0, $this->run_queue(), 'no job ran' );
		$this->assertSame( 1, $this->finishes, 'the response was still finished' );
		$this->assertSame( $before, [ $this->envelope( $current['key'] ), $this->envelope( $views['key'] ) ] );

		// The next visitor.
		$this->new_request( 'current' );

		$this->assertSame( 0, $this->render( 'current' )['selects'] + $this->render( 'views' )['selects'] );
		$this->assertCount( 2, $this->queue->jobs(), 'queued again' );

		// With the default budget, both run.
		remove_filter( 'mai_query_cache_after_page_ms', '__return_zero' );

		$this->assertSame( 2, $this->run_queue() );
		$this->assertTrue( $this->stored( $current['key'] )['fresh'] );
		$this->assertTrue( $this->stored( $views['key'] )['fresh'] );
	}

	/**
	 * The budget is checked before each job. The first job is made slower than the budget, so
	 * it runs and the second does not.
	 */
	public function test_budget_stops_partway(): void {
		[ $current, $views ] = $this->queue_two_jobs();

		$before = $this->envelope( $views );

		add_filter( 'mai_query_cache_after_page_ms', static fn() => 50 );
		add_filter(
			'posts_pre_query',
			static function ( $posts, $query ) {
				if ( self::is_copy_of( $query, 'current' ) ) {
					usleep( 120000 );
				}

				return $posts;
			},
			1,
			2
		);

		$this->assertSame( 1, $this->run_queue(), 'one job ran' );
		$this->assertTrue( $this->stored( $current )['fresh'], 'the first' );
		$this->assertSame( $before, $this->envelope( $views ), 'the second did not' );
	}

	/**
	 * At exactly the budget no job starts. Just under it, the job runs. The queue here always
	 * reports the same time after the page, so the edge is hit exactly, with no real timing.
	 */
	public function test_budget_reached_exactly_stops_jobs(): void {
		$warm = $this->warm( 'current' );

		add_filter( 'mai_query_cache_after_page_ms', static fn() => 50 );

		$this->age();
		$this->install_queue_with_elapsed( 50.0 );
		$this->visit( 'current' );
		$this->render( 'current' );

		$before = $this->envelope( $warm['key'] );

		$this->assertTrue( $this->queue->has_job( $warm['key'] ) );
		$this->assertSame( 0, $this->run_queue(), 'at the budget, no job starts' );
		$this->assertSame( $before, $this->envelope( $warm['key'] ) );

		$this->install_queue_with_elapsed( 49.9 );
		$this->visit( 'current' );
		$this->render( 'current' );

		$this->assertSame( 1, $this->run_queue(), 'just under it, the job runs' );
		$this->assertTrue( $this->stored( $warm['key'] )['fresh'] );
	}

	// ---- An empty grid ----

	/** Whether a post joins the empty grid's category, without a save, before the job runs. */
	public static function empty_grid_changes(): array {
		return [
			'still empty'                  => [ false ],
			'a post joined without a save' => [ true ],
		];
	}

	/**
	 * The ID-only copy finding nothing is an answer, not a reason to run the grid's own query
	 * as well. A cold miss runs that one statement, shows no posts, and stores the empty list
	 * with the marker.
	 */
	public function test_empty_grid_cold_miss_runs_one_query_and_stores_the_empty_list(): void {
		global $wpdb;

		$this->new_request( 'empty' );

		[ $grid, $statements ] = $this->grid_statements( fn() => $this->render( 'empty' ) );

		$this->assertFalse( $grid['answered'], 'a cold miss' );
		$this->assertCount( 1, $statements, 'one grid statement' );
		$this->assertMatchesRegularExpression( '/^\s*SELECT\s+' . preg_quote( $wpdb->posts, '/' ) . '\.ID\s+FROM\s/', $statements[0], 'the ID-only copy' );
		$this->assertSame( [], $this->ids( $grid['query'] ), 'no posts' );
		$this->assertSame( 0, $grid['query']->post_count );

		$this->run_queue();

		$stored = $this->stored( $grid['key'] );

		$this->assertTrue( $stored['fresh'] ?? false, 'stored' );
		$this->assertSame( [ 'ids' => [], 'found' => 0, 'by' => 'ids' ], $stored['value'] );
	}

	/**
	 * The stored empty list is served like any other entry: from the store waiting for after
	 * the page, and on the next request from the cache. Neither runs a grid statement. Core's
	 * query cache is emptied before the second render, so only the result cache could answer it.
	 */
	public function test_empty_grid_is_served_from_the_cache(): void {
		$this->new_request( 'empty' );

		$cold = $this->render( 'empty' );

		wp_cache_flush_group( 'post-queries' );

		$same_page = $this->render( 'empty' );

		$this->run_queue();
		$this->new_request( 'empty' );

		$next = $this->render( 'empty' );

		$this->assertSame( 1, $cold['selects'] );

		foreach ( [ 'same page' => $same_page, 'next request' => $next ] as $name => $grid ) {
			$this->assertSame( $cold['key'], $grid['key'], $name );
			$this->assertTrue( $grid['answered'], "{$name}: a hit" );
			$this->assertSame( 0, $grid['selects'], "{$name}: no grid statement" );
			$this->assertSame( [], $this->ids( $grid['query'] ), "{$name}: no posts" );
			$this->assertNull( $grid['query']->post, "{$name}: no current post" );
		}

		$this->assertSame( [], $this->queue->jobs(), 'fresh, so nothing queued' );
	}

	/**
	 * An aged empty entry carries the marker, so it is served as it is and rebuilt after the
	 * page, like any other. When a post joins the category without a save, the rebuild finds it.
	 */
	#[DataProvider( 'empty_grid_changes' )]
	public function test_aged_empty_entry_is_rebuilt_after_the_page( bool $joined ): void {
		$warm = $this->warm( 'empty' );

		$this->assertSame( [], $warm['ids'] );

		if ( $joined ) {
			$before = $this->version();

			wp_set_object_terms( $this->outsider, [ $this->empty_term_id ], 'category', true );

			$this->assertSame( $before, $this->version(), 'no save, so the version stays' );
		}

		$this->age();
		$this->new_request( 'empty' );

		$version = $this->version();
		$grid    = $this->render( 'empty' );

		$this->assertTrue( $grid['answered'], 'served from the entry' );
		$this->assertSame( 0, $grid['selects'], 'no grid query during the page' );
		$this->assertSame( [], $this->ids( $grid['query'] ), 'the old, empty list' );
		$this->assertTrue( $this->queue->has_job( $warm['key'] ), 'queued' );

		$this->assertSame( 1, $this->run_queue(), 'the job ran its query' );
		$this->assertSame( 1, $this->finishes, 'after the response was finished' );

		$stored   = $this->stored( $warm['key'] );
		$envelope = $this->envelope( $warm['key'] );
		$expected = $joined ? [ $this->outsider ] : [];

		$this->assertTrue( $stored['fresh'] );
		$this->assertSame( $this->now, $envelope['w'], 'written now' );
		$this->assertSame( $version, $envelope['_v'] );
		$this->assertSame( [ 'ids' => $expected, 'found' => 0, 'by' => 'ids' ], $stored['value'] );

		$this->new_request( 'empty' );

		$next = $this->render( 'empty' );

		$this->assertTrue( $next['answered'] );
		$this->assertSame( 0, $next['selects'] );
		$this->assertSame( $expected, $this->ids( $next['query'] ) );
	}

	/** A post published into the empty grid's category changes the version, so the next render shows it. */
	public function test_post_added_to_an_empty_grid_shows_on_the_next_render(): void {
		$warm   = $this->warm( 'empty' );
		$before = $this->version();
		$new    = self::factory()->post->create( [ 'post_status' => 'publish', 'post_category' => [ $this->empty_term_id ] ] );

		$this->assertNotSame( $before, $this->version(), 'the save changed the version' );

		$this->new_request( 'empty' );

		$this->assertSame( 'version', $this->stored( $warm['key'] )['stale'] );

		$grid = $this->render( 'empty' );

		$this->assert_rebuilt_during_page( $grid );
		$this->assertSame( [ $new ], $this->ids( $grid['query'] ), 'the new post shows' );

		$this->run_queue();

		$this->assertSame( [ 'ids' => [ $new ], 'found' => 0, 'by' => 'ids' ], $this->stored( $warm['key'] )['value'] );
	}

	/** The two ways an ID-only copy cannot stand in for the grid's query. */
	public static function copy_declines(): array {
		return [
			'the copy selects more than the ID' => [ 'fields' ],
			'the copy\'s key differs'            => [ 'key' ],
		];
	}

	/**
	 * An empty copy is still checked like any other. One that cannot stand in for the grid's
	 * query is not an answer, so the grid's own query runs, and the_posts stores its empty
	 * result without the marker.
	 */
	#[DataProvider( 'copy_declines' )]
	public function test_empty_copy_that_cannot_stand_in_falls_back_to_the_grid_query( string $decline ): void {
		$is_copy = static fn( $query ) => 'ids' === ( $query->query_vars['fields'] ?? '' ) && ! empty( $query->query_vars['mai_grid_tiebreak'] );

		[ $hook, $change ] = match ( $decline ) {
			'fields' => [ 'posts_fields', static fn( $fields, $query ) => $is_copy( $query ) ? $fields . ', 1 AS mai_test_column' : $fields ],
			'key'    => [ 'posts_request', static fn( $sql, $query ) => $is_copy( $query ) ? str_replace( 'WHERE 1=1', 'WHERE 1=1 AND 2=2', $sql ) : $sql ],
		};

		add_filter( $hook, $change, 10, 2 );

		$this->new_request( 'empty' );

		$grid = $this->render( 'empty' );

		remove_filter( $hook, $change, 10 );

		$this->assertFalse( $grid['answered'] );
		$this->assertSame( 2, $grid['selects'], 'the declined copy, then the grid\'s own query' );
		$this->assertSame( [], $this->ids( $grid['query'] ) );

		$this->run_queue();

		$this->assertSame( [ 'ids' => [], 'found' => 0 ], $this->stored( $grid['key'] )['value'], 'stored by the_posts, without the marker' );
	}

	/**
	 * A failed statement returns no rows too, so it must still be caught before an empty list
	 * counts as an answer. The copy's statement is refused on a cold miss: nothing is stored,
	 * core is made to forget its cached empty result, and the grid's own query answers.
	 */
	public function test_failed_copy_statement_on_an_empty_grid_stores_nothing(): void {
		$this->new_request( 'empty' );

		$before = wp_cache_get_last_changed( 'posts' );

		[ $grid, $refused ] = $this->refuse_once( self::is_grid_statement( ... ), fn() => $this->render( 'empty' ) );

		$this->assertTrue( $refused, 'the copy\'s statement was refused' );
		$this->assertFalse( $grid['answered'] );
		$this->assertSame( 2, $grid['selects'], 'the refused copy, then the grid\'s own query' );
		$this->assertSame( [], $this->ids( $grid['query'] ) );
		$this->assertNotSame( $before, wp_cache_get_last_changed( 'posts' ), 'core forgot the failed result' );
		$this->assertNull( $this->queue->pending( $grid['key'] ), 'nothing waiting to be stored' );

		$this->run_queue();

		$this->assertFalse( $this->envelope( $grid['key'] ), 'nothing stored' );
	}

	/** A grid with posts, whose copy comes back empty only because it failed, and an empty grid. */
	public static function hidden_failure_shapes(): array {
		return [
			'a grid with posts' => [ 'current' ],
			'an empty grid'     => [ 'empty' ],
		];
	}

	/**
	 * A query filter rewrites the copy's statement into one that fails, so last_query is not
	 * the copy's request. A statement still reached the database while the copy ran, and the
	 * last one failed, so the failure is treated as the copy's. Nothing is stored, the grid's
	 * own query answers, and core forgets the failed empty list. The next request, with core's
	 * query cache as this one left it, runs the copy again and stores the real list.
	 */
	#[DataProvider( 'hidden_failure_shapes' )]
	public function test_failure_hidden_by_a_query_filter_is_forgotten_and_not_stored( string $shape ): void {
		$shown  = 'empty' === $shape ? [] : array_slice( $this->post_ids, 1, self::PER_PAGE );
		$padded = 'empty' === $shape ? [] : array_slice( $this->post_ids, 0, self::PER_PAGE + 1 );

		$this->new_request( $shape );

		$before = wp_cache_get_last_changed( 'posts' );

		[ $grid, $broken ] = $this->break_copy_once( fn() => $this->render( $shape ) );

		$this->assertTrue( $broken, 'the copy\'s statement was broken' );
		$this->assertFalse( $grid['answered'] );
		$this->assertSame( 2, $grid['selects'], 'the broken copy, then the grid\'s own query' );
		$this->assertSame( $shown, $this->ids( $grid['query'] ), 'what the grid\'s own query found' );
		$this->assertNotSame( $before, wp_cache_get_last_changed( 'posts' ), 'core forgot the failed empty list' );
		$this->assertNull( $this->queue->pending( $grid['key'] ), 'nothing waiting to be stored' );

		$this->run_queue();

		$this->assertFalse( $this->envelope( $grid['key'] ), 'nothing stored' );

		$this->next_request_keeping_core_cache();

		$next = $this->render( $shape );

		$this->assertFalse( $next['answered'], 'a cold miss' );
		$this->assertSame( 1, $next['selects'], 'the copy ran its statement, rather than read the failed list back' );
		$this->assertSame( $shown, $this->ids( $next['query'] ) );

		$this->run_queue();

		$this->assertSame( [ 'ids' => $padded, 'found' => 0, 'by' => 'ids' ], $this->stored( $next['key'] )['value'] );
	}

	/**
	 * The same after the page. The job's copy fails behind a query filter. The entry is deleted
	 * and core forgets the failed empty list, so the next visitor's cold miss runs the copy
	 * again and shows the real posts, rather than read the empty list back from core's cache.
	 */
	public function test_failure_hidden_by_a_query_filter_after_the_page_is_forgotten(): void {
		$warm = $this->warm( 'current' );

		$this->age();
		$this->new_request( 'current' );
		$this->render( 'current' );

		$this->assertTrue( $this->queue->has_job( $warm['key'] ) );

		$before = wp_cache_get_last_changed( 'posts' );

		[ , $broken ] = $this->break_copy_once( fn() => $this->run_queue() );

		$this->assertTrue( $broken, 'the copy\'s statement was broken' );
		$this->assertFalse( $this->envelope( $warm['key'] ), 'deleted, not stored as an empty list' );
		$this->assertNotSame( $before, wp_cache_get_last_changed( 'posts' ), 'core forgot the failed empty list' );

		$this->next_request_keeping_core_cache();

		$next = $this->render( 'current' );

		$this->assertFalse( $next['answered'], 'a cold miss' );
		$this->assertSame( 1, $next['selects'], 'the copy ran its statement, rather than read the failed list back' );
		$this->assertSame( array_slice( $this->post_ids, 1, self::PER_PAGE ), $this->ids( $next['query'] ), 'the real posts' );

		$this->run_queue();

		$this->assertSame( [ 'ids' => array_slice( $this->post_ids, 0, self::PER_PAGE + 1 ), 'found' => 0, 'by' => 'ids' ], $this->stored( $warm['key'] )['value'] );
	}

	/**
	 * A copy that core answers from its query cache runs no statement, so last_error still holds
	 * what the last statement before it left. Here that is an unrelated statement that failed
	 * just before the copy started. Nothing reached the database while the copy ran, so the
	 * error is stale, not the copy's. The empty result cannot be vouched for, so the grid's own
	 * query answers, and core's query cache is not reset.
	 */
	public function test_empty_copy_from_core_cache_after_an_unrelated_error_declines(): void {
		global $wpdb;

		// The first render runs the copy, which puts its empty result in core's query cache. A
		// fresh queue drops the result waiting to be stored, so the next render is a cold miss.
		$this->new_request( 'empty' );
		$this->render( 'empty' );
		$this->install_queue();

		// Fails on the grid's own query, after the result cache's miss and before the copy.
		$noise = static function ( $posts, $query ) use ( $wpdb ) {
			if ( ! empty( $query->query_vars['mai_cache'] ) ) {
				$wpdb->query( "SELECT mai_no_such_column FROM {$wpdb->options} LIMIT 1" );
			}

			return $posts;
		};

		// Core runs posts_request_ids only when it splits a query it runs itself, so it fires for
		// the grid's own query and never for the copy.
		$fallbacks = 0;
		$watch     = static function ( $request, $query ) use ( &$fallbacks ) {
			if ( ! empty( $query->query_vars['mai_cache'] ) ) {
				++$fallbacks;
			}

			return $request;
		};

		add_filter( 'posts_pre_query', $noise, 100, 2 );
		add_filter( 'posts_request_ids', $watch, 10, 2 );
		$suppress = $wpdb->suppress_errors( true );

		$before = wp_cache_get_last_changed( 'posts' );
		$grid   = $this->render( 'empty' );

		$wpdb->suppress_errors( $suppress );
		remove_filter( 'posts_request_ids', $watch, 10 );
		remove_filter( 'posts_pre_query', $noise, 100 );

		$this->assertFalse( $grid['answered'] );
		$this->assertSame( 1, $fallbacks, 'the grid\'s own query answered' );
		$this->assertSame( 1, $grid['selects'], 'and was the only grid statement, so core answered the copy' );
		$this->assertSame( [], $this->ids( $grid['query'] ) );
		$this->assertSame( $before, wp_cache_get_last_changed( 'posts' ), 'core\'s query cache was not reset' );

		$this->run_queue();

		$this->assertSame( [ 'ids' => [], 'found' => 0 ], $this->stored( $grid['key'] )['value'], 'stored by the_posts, without the marker' );
	}

	/** The same after the page: a refused copy deletes the aged empty entry rather than store an empty list. */
	public function test_failed_copy_statement_on_an_aged_empty_entry_deletes_it(): void {
		$warm = $this->warm( 'empty' );

		$this->age();
		$this->new_request( 'empty' );
		$this->render( 'empty' );

		$this->assertTrue( $this->queue->has_job( $warm['key'] ) );

		[ $selects, $refused ] = $this->refuse_once( self::is_grid_statement( ... ), fn() => $this->run_queue() );

		$this->assertTrue( $refused, 'the copy\'s statement was refused' );
		$this->assertSame( 1, $selects );
		$this->assertFalse( $this->envelope( $warm['key'] ), 'the entry is deleted' );
		$this->assertTrue( mai_cache( 'grid' )->lock( $warm['key'], 5 ), 'the lock was released' );
	}
}
