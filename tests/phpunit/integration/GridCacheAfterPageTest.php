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
 * a default queue and the real clock back, since MaiIntegrationTestCase resets neither.
 */
final class GridCacheAfterPageTest extends MaiIntegrationTestCase {

	private const PER_PAGE = 3;

	/** The default soft lifetime. */
	private const SOFT = 4 * HOUR_IN_SECONDS;

	/** Stands in for Mai Publisher's views count, which is not in this suite. */
	private const VIEWS = 'mai_test_views';

	/** @var int[] Posts in the category, newest first. */
	private array $post_ids = [];

	/** The newest post of all, outside the category until a test moves it in. */
	private int $outsider = 0;

	private int $term_id = 0;

	/** What the mai-cache clock reads. Tests move it forward. */
	private int $now = 0;

	private Mai_Query_Cache_Queue $queue;

	/** How many times the current queue finished the response. */
	private int $finishes = 0;

	private string $method = 'GET';

	public function set_up(): void {
		parent::set_up();

		$this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
		$this->now    = time();

		Cache::set_clock( fn() => $this->now );

		$this->term_id = self::factory()->term->create( [ 'taxonomy' => 'category' ] );

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
		// A default queue, so later test classes store inline as before, and the queue run at
		// shutdown finds nothing.
		Mai_Query_Cache::instance()->set_queue( new Mai_Query_Cache_Queue() );

		Cache::set_clock( null );

		Mai_Grid::$existing_post_ids = [];

		$_SERVER['REQUEST_METHOD'] = $this->method;

		parent::tear_down();
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
			'displayed' => array_merge( $args, [ 'excludes' => [ 'exclude_displayed' ] ] ),
			'views'     => array_merge( $args, [ 'orderby' => 'meta_value_num', 'orderby_meta_key' => self::VIEWS, 'order' => 'DESC' ] ),
			'plain'     => array_merge( $args, [ 'excludes' => [] ] ),
		};
	}

	/** Goes to the page a shape needs. */
	private function visit( string $shape ): void {
		$this->go_to( in_array( $shape, [ 'current', 'views' ], true ) ? get_permalink( $this->post_ids[0] ) : home_url( '/' ) );

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

		if ( 'plain' === $shape ) {
			$this->assertStringNotContainsString( '.ID DESC', $query->request, 'must not have deferred' );
		} else {
			$this->assertStringContainsString( '.ID DESC', $query->request, 'must actually have deferred' );
		}

		$this->assertNotSame( '', $key, 'the grid query must have reached the result cache' );

		return [ 'query' => $query, 'key' => $key, 'answered' => $answered, 'selects' => $selects ];
	}

	/**
	 * Runs the callback and counts the grid statements it ran: against the posts table and
	 * carrying a LIMIT. Priming reads have none.
	 *
	 * @return array{0:mixed,1:int}
	 */
	private function count_selects( callable $callback ): array {
		global $wpdb;

		$selects = 0;
		$count   = static function ( $sql ) use ( &$selects, $wpdb ) {
			if ( preg_match( '/\bFROM\s+' . preg_quote( $wpdb->posts, '/' ) . '\b/', $sql ) && str_contains( $sql, 'LIMIT' ) ) {
				++$selects;
			}

			return $sql;
		};

		add_filter( 'query', $count );
		$result = $callback();
		remove_filter( 'query', $count );

		return [ $result, $selects ];
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

		add_filter(
			'posts_request',
			static fn( $sql, $query ) => empty( $query->query_vars['mai_grid_tiebreak'] ) ? $sql : str_replace( 'WHERE 1=1', 'WHERE 1=1 AND 2=2', $sql ),
			10,
			2
		);

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
}
