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
 * When a grid result is stored, on a site without a persistent object cache.
 *
 * - A result computed during the page waits in the queue, and is stored after the page when
 *   the request can be finished early. It keeps the version read before the query ran, so a
 *   post saved in between still leaves it out of date.
 * - A second grid with the same key on the same page is served the waiting result.
 * - It is stored straight away when the request cannot finish early, when the site has a
 *   persistent object cache, and once the queue has already finished.
 *
 * Each test installs a fresh queue on the instance the hooks use. Its seams stand in for
 * fastcgi_finish_request() and DONOTCACHEPAGE. tear_down puts a default queue back, so no
 * other test class inherits this one, and nothing runs at shutdown. The suite sets
 * REQUEST_METHOD to GET (wp-phpunit's includes/functions.php), so these queues count the
 * request as a page view unless told otherwise.
 */
final class GridCacheDeferredStoreTest extends MaiIntegrationTestCase {

	private const PER_PAGE = 3;

	/** The one test that runs with a persistent object cache. See set_up(). */
	private const OBJECT_CACHE_TEST = 'test_store_inline_with_persistent_object_cache';

	/** @var int[] Newest first. */
	private array $post_ids = [];

	private int $term_id = 0;

	private Mai_Query_Cache_Queue $queue;

	/** How many times the queue finished the response. */
	private int $finishes = 0;

	/** How many times the queue asked page caches to skip the page. */
	private int $no_page_cache = 0;

	/** Runs inside the finish seam, so a test can look at the cache at that moment. */
	private ?\Closure $on_finish = null;

	/** What $on_finish returned. */
	private mixed $at_finish = null;

	/** The wp_using_ext_object_cache() value before the test, when this test changed it. */
	private ?bool $object_cache_was = null;

	private string $method = 'GET';

	public function set_up(): void {
		// Before anything reads the cache, the posts made below included, so every token is
		// read from and written to the object cache.
		if ( self::OBJECT_CACHE_TEST === $this->name() ) {
			$this->object_cache_was = (bool) wp_using_ext_object_cache( true );

			Cache::reset_runtime();
		}

		parent::set_up();

		$this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

		$this->term_id = self::factory()->term->create( [ 'taxonomy' => 'category' ] );

		// Ten posts one day apart. Index 0 is the newest, so post_date DESC returns them in
		// creation order.
		for ( $i = 0; $i < 10; $i++ ) {
			$this->post_ids[] = self::factory()->post->create( [
				'post_status'   => 'publish',
				'post_date'     => gmdate( 'Y-m-d H:i:s', strtotime( "-{$i} days" ) ),
				'post_category' => [ $this->term_id ],
			] );
		}

		$this->finishes      = 0;
		$this->no_page_cache = 0;
		$this->on_finish     = null;
		$this->at_finish     = null;

		$this->install_queue();

		( new Mai_Query_Cache() )->flush_all();
	}

	public function tear_down(): void {
		// A default queue, so later test classes store inline as before, and the queue run at
		// shutdown finds nothing.
		Mai_Query_Cache::instance()->set_queue( new Mai_Query_Cache_Queue() );

		$_SERVER['REQUEST_METHOD'] = $this->method;

		parent::tear_down();

		if ( null !== $this->object_cache_was ) {
			wp_using_ext_object_cache( $this->object_cache_was );

			// The tokens this test read came from the object cache, which is gone now.
			Cache::reset_runtime();

			$this->object_cache_was = null;
		}
	}

	/** The two places a miss is stored. See render(). */
	public static function paths(): array {
		return [
			'kept-only, stored in posts_pre_query' => [ 'kept' ],
			'a grid that does not defer, stored on the_posts' => [ 'the_posts' ],
		];
	}

	/** The ways a request cannot be finished early, with the store left inline. */
	public static function cannot_finish(): array {
		return [
			'the server has no finish function' => [ 'no_finish' ],
			'not a page view, by the seam'      => [ 'seam' ],
			'not a page view, a POST request'   => [ 'post' ],
		];
	}

	// ---- Helpers ----

	/**
	 * Installs a fresh queue on the instance the hooks use.
	 *
	 * @param bool          $can_finish   What the finish-function seam answers.
	 * @param \Closure|null $is_page_view The page-view seam. Null keeps the real rules.
	 *
	 * @return void
	 */
	private function install_queue( bool $can_finish = true, ?\Closure $is_page_view = null ): void {
		$this->queue = new Mai_Query_Cache_Queue(
			static fn() => $can_finish,
			function () {
				++$this->finishes;

				if ( $this->on_finish ) {
					$this->at_finish = ( $this->on_finish )();
				}
			},
			function () {
				++$this->no_page_cache;
			},
			$is_page_view
		);

		Mai_Query_Cache::instance()->set_queue( $this->queue );
	}

	private function grid_args( string $path ): array {
		return [
			'type'           => 'post',
			'post_type'      => [ 'post' ],
			'query_by'       => 'tax_meta',
			'posts_per_page' => self::PER_PAGE,
			'excludes'       => 'kept' === $path ? [ 'exclude_current' ] : [],
			'taxonomies'     => [
				[ 'taxonomy' => 'category', 'terms' => [ $this->term_id ], 'current' => false, 'operator' => 'IN' ],
			],
		];
	}

	/**
	 * Starts a new pretend request on the page a path needs. A kept-only grid defers only on
	 * a single post, where Exclude current has something to exclude.
	 */
	private function visit( string $path ): void {
		$this->go_to( 'kept' === $path ? get_permalink( $this->post_ids[0] ) : home_url( '/' ) );
	}

	/**
	 * Renders one grid on the current request, and reports its key, whether the result cache
	 * answered it in posts_pre_query, and how many grid statements ran.
	 *
	 * The key is read at posts_pre_query priority 9, because Mai_Grid puts a deferring grid's
	 * vars back once the query returns. Whether the cache answered is read at priority 11,
	 * just after pre_query(). A grid statement is one against the posts table with a LIMIT.
	 * Priming reads have none.
	 *
	 * @return array{query:WP_Query,key:string,answered:?bool,selects:int}
	 */
	private function render( string $path ): array {
		global $wpdb;

		$key      = '';
		$answered = null;
		$selects  = 0;

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

		$count_selects = static function ( $sql ) use ( &$selects, $wpdb ) {
			if ( preg_match( '/\bFROM\s+' . preg_quote( $wpdb->posts, '/' ) . '\b/', $sql ) && str_contains( $sql, 'LIMIT' ) ) {
				++$selects;
			}

			return $sql;
		};

		add_filter( 'posts_pre_query', $capture_key, 9, 2 );
		add_filter( 'posts_pre_query', $capture_answer, 11, 2 );
		add_filter( 'query', $count_selects );

		$query = ( new Mai_Grid( $this->grid_args( $path ) ) )->get_query();

		remove_filter( 'query', $count_selects );
		remove_filter( 'posts_pre_query', $capture_answer, 11 );
		remove_filter( 'posts_pre_query', $capture_key, 9 );

		if ( 'kept' === $path ) {
			$this->assertStringContainsString( '.ID DESC', $query->request, 'must actually have deferred' );
		} else {
			$this->assertStringNotContainsString( '.ID DESC', $query->request, 'must not have deferred' );
		}

		$this->assertNotSame( '', $key, 'the grid query must have reached the result cache' );

		return [ 'query' => $query, 'key' => $key, 'answered' => $answered, 'selects' => $selects ];
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

	/** The IDs a path's miss stores: kept-only stores the padded list. */
	private function expected_ids( string $path ): array {
		return array_slice( $this->post_ids, 0, 'kept' === $path ? self::PER_PAGE + 1 : self::PER_PAGE );
	}

	private function ids( WP_Query $query ): array {
		return wp_list_pluck( $query->posts, 'ID' );
	}

	private function run_queue(): void {
		Mai_Query_Cache::instance()->run_queue();
	}

	// ---- Deferred ----

	#[DataProvider( 'paths' )]
	public function test_cold_miss_store_is_deferred( string $path ): void {
		$this->visit( $path );

		$version = $this->version();
		$grid    = $this->render( $path );

		$this->assertFalse( $grid['answered'], 'a cold miss' );
		$this->assertFalse( $this->envelope( $grid['key'] ), 'nothing stored while the page renders' );
		$this->assertSame( $version, $this->queue->pending( $grid['key'] )['version'] ?? null, 'it waits with the version read before the query' );
		$this->assertTrue( $this->queue->was_rebuilt( $grid['key'] ) );
		$this->assertSame( 0, $this->finishes );

		$this->run_queue();

		$envelope = $this->envelope( $grid['key'] );
		$stored   = $this->stored( $grid['key'] );
		$timeout  = (int) get_option( '_transient_timeout_' . mai_cache( 'grid' )->key( $grid['key'] ) );

		$this->assertSame( 1, $this->finishes, 'the response was finished' );
		$this->assertSame( $version, $envelope['_v'] );
		$this->assertTrue( $stored['fresh'] );
		$this->assertSame( $this->expected_ids( $path ), $stored['value']['ids'] );
		$this->assertSame( 'kept' === $path ? 'ids' : null, $stored['value']['by'] ?? null, 'the marker survives the wait' );
		$this->assertSame( 4 * HOUR_IN_SECONDS, $envelope['s'] - $envelope['w'], 'the soft lifetime' );
		$this->assertEqualsWithDelta( DAY_IN_SECONDS, $timeout - $envelope['w'], 1, 'the hard lifetime' );
		$this->assertTrue( $this->queue->is_empty(), 'the list is emptied' );
	}

	/**
	 * A post saved after the query ran and before the store must leave the entry out of date.
	 * That holds because the store carries the version read before the query, never one read
	 * when it is written.
	 */
	#[DataProvider( 'paths' )]
	public function test_save_between_query_and_deferred_store_leaves_entry_stale( string $path ): void {
		$this->visit( $path );

		$before = $this->version();
		$grid   = $this->render( $path );

		self::factory()->post->create( [ 'post_status' => 'publish', 'post_category' => [ $this->term_id ] ] );

		$this->assertNotSame( $before, $this->version(), 'the save changed the version' );

		$this->run_queue();

		$this->assertSame( $before, $this->envelope( $grid['key'] )['_v'] );
		$this->assertSame( 'version', $this->stored( $grid['key'] )['stale'] );
	}

	/**
	 * Two identical grids on one page: the second is served the result waiting in the queue,
	 * so the query runs once. Core's own query cache is emptied in between, so without the
	 * waiting result the second grid would have to run its statement again.
	 */
	#[DataProvider( 'paths' )]
	public function test_second_grid_same_key_uses_pending( string $path ): void {
		$this->visit( $path );

		$first = $this->render( $path );

		wp_cache_flush_group( 'post-queries' );

		$second = $this->render( $path );

		$this->assertSame( $first['key'], $second['key'] );
		$this->assertFalse( $first['answered'], 'the first grid is a miss' );
		$this->assertTrue( $second['answered'], 'the second is answered from the queue' );
		$this->assertSame( 1, $first['selects'] + $second['selects'], 'one grid query for both grids' );
		$this->assertSame( $this->ids( $first['query'] ), $this->ids( $second['query'] ) );
		$this->assertCount( self::PER_PAGE, $this->ids( $second['query'] ) );
		$this->assertFalse( $this->envelope( $first['key'] ), 'still waiting to be stored' );
		$this->assertCount( 1, $this->queue->take_stores(), 'stored once' );
	}

	// ---- Inline ----

	#[DataProvider( 'cannot_finish' )]
	public function test_store_inline_when_cannot_finish_early( string $case ): void {
		match ( $case ) {
			'no_finish' => $this->install_queue( false ),
			'seam'      => $this->install_queue( true, static fn() => false ),
			'post'      => $_SERVER['REQUEST_METHOD'] = 'POST',
		};

		$this->visit( 'kept' );

		$version = $this->version();
		$grid    = $this->render( 'kept' );

		$this->assertSame( $version, $this->envelope( $grid['key'] )['_v'] ?? null, 'stored during the page' );
		$this->assertTrue( $this->stored( $grid['key'] )['fresh'] );
		$this->assertTrue( $this->queue->is_empty(), 'nothing waits' );
		$this->assertTrue( $this->queue->was_rebuilt( $grid['key'] ) );

		$this->run_queue();

		$this->assertSame( 0, $this->finishes, 'an empty queue does not finish the response' );
	}

	/**
	 * With a persistent object cache a store is one fast write, and requests that lost the
	 * cold-miss lock wait for it, so it stays inline. set_up() turns the object cache on for
	 * this test only.
	 */
	public function test_store_inline_with_persistent_object_cache(): void {
		$this->assertTrue( wp_using_ext_object_cache() );
		$this->assertTrue( $this->queue->can_finish_early(), 'the request could finish early' );

		$this->visit( 'kept' );

		$version = $this->version();
		$grid    = $this->render( 'kept' );
		$stored  = $this->stored( $grid['key'] );

		$this->assertTrue( $stored['fresh'] ?? false, 'stored during the page' );
		$this->assertSame( $this->expected_ids( 'kept' ), $stored['value']['ids'] );
		$this->assertSame( $version, $this->envelope( $grid['key'] )['_v'] );
		$this->assertTrue( $this->queue->is_empty(), 'nothing waits' );

		$this->run_queue();

		$this->assertSame( 0, $this->finishes );
	}

	/**
	 * With the cache switched off (mai_can_cache, as SCRIPT_DEBUG does), a write does nothing.
	 * So nothing waits for one: no second grid is served from the queue, and the response is
	 * not finished early for it. A grid that cannot store does not defer, so this is the
	 * the_posts path.
	 */
	public function test_nothing_waits_when_the_cache_cannot_store(): void {
		add_filter( 'mai_can_cache', '__return_false' );

		$this->visit( 'the_posts' );

		$grid = $this->render( 'the_posts' );

		$this->assertTrue( $this->queue->is_empty(), 'nothing waits' );
		$this->assertFalse( $this->envelope( $grid['key'] ), 'and nothing is stored' );

		$this->run_queue();

		$this->assertSame( 0, $this->finishes );
	}

	/**
	 * After the response is finished, the pending stores are written before anything else
	 * runs, so the rebuild jobs that come after them read a stored entry. The job queued here
	 * stays queued for the job runner.
	 */
	public function test_pending_stores_written_before_jobs(): void {
		$this->visit( 'kept' );

		$grid = $this->render( 'kept' );

		$this->queue->add_job( [ 'key' => 'another-grid', 'written' => null ] );

		$this->on_finish = fn() => $this->envelope( $grid['key'] );

		$this->run_queue();

		$this->assertSame( 1, $this->finishes );
		$this->assertFalse( $this->at_finish, 'the response is finished first' );
		$this->assertTrue( $this->stored( $grid['key'] )['fresh'], 'then the store is written' );
		$this->assertNull( $this->queue->pending( $grid['key'] ) );
		$this->assertTrue( $this->queue->has_job( 'another-grid' ) );
	}

	/** Once the queue has finished, nothing reads the list again, so a later store is written at once. */
	public function test_store_after_finish_is_inline(): void {
		$this->visit( 'kept' );

		$this->queue->finish();

		$this->assertTrue( $this->queue->finished() );

		$grid = $this->render( 'kept' );

		$this->assertTrue( $this->stored( $grid['key'] )['fresh'], 'stored at once' );
		$this->assertTrue( $this->queue->is_empty() );
		$this->assertSame( 1, $this->finishes, 'finished once' );
	}
}
