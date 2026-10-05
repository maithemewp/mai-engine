<?php
declare(strict_types=1);

namespace BizBudding\MaiEngine\Tests\Integration;

use Mai\Cache\Cache;
use Mai_Grid;
use Mai_Query_Cache;
use Mai_Query_Cache_Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use WP_Query;

/**
 * DONOTCACHEPAGE for a request served a grid that is out of date because a post was saved.
 *
 * - With a persistent object cache, the request that wins the lock runs the grid's query again
 *   during the page. A request that loses it is served the old list until that result is
 *   stored, and asks page caches not to keep its page.
 * - The winner, a fresh hit, and an entry that only aged out never ask. Nor does a request
 *   that is not a normal page view.
 *
 * Every test runs with the object cache on (see set_up()), as on a site with Redis. go_to()
 * empties it, so each test stays on one page. A request is made to lose the lock by taking the
 * lock first with mai_cache( 'grid' )->lock().
 *
 * The queue's no-page-cache seam counts its calls, so this process never defines the real
 * constant. One test runs in its own process with the real seam, and checks the constant.
 */
final class GridCacheDoNotCacheTest extends MaiIntegrationTestCase {

	private const PER_PAGE = 3;

	/** The default soft lifetime. */
	private const SOFT = 4 * HOUR_IN_SECONDS;

	/** @var int[] Newest first. */
	private array $post_ids = [];

	private int $term_id = 0;

	/** What the mai-cache clock reads. Tests move it forward. */
	private int $now = 0;

	private Mai_Query_Cache_Queue $queue;

	/** How many times the queue asked page caches not to keep the page. */
	private int $marks = 0;

	/** The wp_using_ext_object_cache() value before the test. */
	private bool $object_cache_was = false;

	public function set_up(): void {
		// Before anything reads the cache, the posts made below included, so every token is
		// read from and written to the object cache.
		$this->object_cache_was = (bool) wp_using_ext_object_cache( true );

		Cache::reset_runtime();

		parent::set_up();

		$this->now = time();

		Cache::set_clock( fn() => $this->now );

		$this->term_id = self::factory()->term->create( [ 'taxonomy' => 'category' ] );

		// Ten posts one day apart, the newest a day old. Index 0 is the newest.
		for ( $i = 0; $i < 10; $i++ ) {
			$this->post_ids[] = self::factory()->post->create( [
				'post_status'   => 'publish',
				'post_date'     => gmdate( 'Y-m-d H:i:s', strtotime( '-' . ( $i + 1 ) . ' days' ) ),
				'post_category' => [ $this->term_id ],
			] );
		}

		$this->install_queue();

		( new Mai_Query_Cache() )->flush_all();
	}

	public function tear_down(): void {
		Cache::set_clock( null );

		Mai_Grid::$existing_post_ids = [];

		parent::tear_down();

		wp_using_ext_object_cache( $this->object_cache_was );

		// The tokens this test read came from the object cache, which is gone now.
		Cache::reset_runtime();
	}

	/** Where a lock loser is checked: a kept-only grid, a grid that does not defer, and a server that cannot finish early. */
	public static function paths(): array {
		return [
			'kept-only grid'              => [ 'kept', true ],
			'a grid that does not defer'  => [ 'plain', true ],
			'a server that cannot finish' => [ 'kept', false ],
		];
	}

	/** The two ways an aged-out entry is served stale. */
	public static function age_paths(): array {
		return [
			'served now, rebuilt after the page'    => [ true ],
			'served while another request rebuilds' => [ false ],
		];
	}

	// ---- Helpers ----

	/**
	 * Installs a fresh queue on the instance the hooks use, with a no-page-cache seam that
	 * counts its calls.
	 *
	 * @param bool          $can_finish   What the finish-function seam answers.
	 * @param \Closure|null $is_page_view The page-view seam. Null keeps the real rules.
	 *
	 * @return void
	 */
	private function install_queue( bool $can_finish = true, ?\Closure $is_page_view = null ): void {
		$this->marks = 0;

		$this->queue = new Mai_Query_Cache_Queue(
			static fn() => $can_finish,
			static function () {},
			function () {
				++$this->marks;
			},
			$is_page_view
		);

		Mai_Query_Cache::instance()->set_queue( $this->queue );
	}

	/**
	 * The grid for a path. kept defers its Exclude current, so it is served with only the
	 * posts it shows. plain has no excludes and does not defer.
	 */
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
	 * Goes to the page a path needs. A kept-only grid defers only on a single post, where
	 * Exclude current has something to exclude. go_to() empties the object cache, so a test
	 * calls this once.
	 */
	private function visit( string $path ): void {
		$this->go_to( 'kept' === $path ? get_permalink( $this->post_ids[0] ) : home_url( '/' ) );
	}

	/**
	 * Starts a new pretend request on the page already visited, keeping the object cache, as a
	 * persistent object cache shared between requests would. Core's query cache is emptied so
	 * a grid statement still runs when the result cache does not answer.
	 */
	private function next_request( bool $can_finish = true, ?\Closure $is_page_view = null ): void {
		$this->install_queue( $can_finish, $is_page_view );

		wp_cache_flush_group( 'post-queries' );
	}

	/**
	 * Renders one grid on the current request, and reports its key, whether the result cache
	 * answered it in posts_pre_query, and how many grid statements ran.
	 *
	 * The key is read at posts_pre_query priority 9, because Mai_Grid puts a deferring grid's
	 * vars back once the query returns. Whether the cache answered is read at priority 11,
	 * just after pre_query(). A grid statement is one against the posts table with a LIMIT.
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

		// A deferring grid asks for one row more than it shows, for the entry Exclude current
		// drops. A grid that does not defer asks for exactly what it shows.
		if ( 'kept' === $path ) {
			$this->assertStringContainsString( 'LIMIT 0, ' . ( self::PER_PAGE + 1 ), (string) $query->request, 'must actually have deferred' );
		} else {
			$this->assertStringContainsString( 'LIMIT 0, ' . self::PER_PAGE, (string) $query->request, 'must not have deferred' );
		}

		$this->assertNotSame( '', $key, 'the grid query must have reached the result cache' );

		return [ 'query' => $query, 'key' => $key, 'answered' => $answered, 'selects' => $selects ];
	}

	/**
	 * Visits the page, renders the grid cold so its entry is stored, then makes that entry
	 * stale. Returns the key and the IDs the grid showed.
	 *
	 * The cold render took the lock, which lasts until it runs out. It is released here, as the
	 * next request would find it once that happened.
	 *
	 * @param string $path  kept or plain.
	 * @param string $stale version (a post was saved) or age (the soft lifetime ran out).
	 *
	 * @return array{key:string,ids:int[]}
	 */
	private function stale_entry( string $path, string $stale ): array {
		$this->visit( $path );

		$grid = $this->render( $path );

		$this->assertFalse( $grid['answered'], 'a cold miss' );
		$this->assertTrue( $this->stored( $grid['key'] )['fresh'] ?? false, 'stored during the page, as with any object cache' );
		$this->assertTrue( mai_cache( 'grid' )->unlock( $grid['key'] ), 'the cold render held the lock' );

		if ( 'version' === $stale ) {
			$this->save_a_post();
		} else {
			$this->now += self::SOFT + 1;
		}

		$this->assertSame( $stale, $this->stored( $grid['key'] )['stale'] ?? null );

		return [ 'key' => $grid['key'], 'ids' => $this->ids( $grid['query'] ) ];
	}

	/** Publishes a post in the category, newer than all the others, which changes the version. */
	private function save_a_post(): int {
		$before = $this->version();
		$id     = self::factory()->post->create( [ 'post_status' => 'publish', 'post_category' => [ $this->term_id ] ] );

		$this->assertNotSame( $before, $this->version(), 'the save changed the version' );

		return $id;
	}

	/** Takes the lock, as another request rebuilding the entry would hold it. */
	private function lock( string $key ): void {
		$this->assertTrue( mai_cache( 'grid' )->lock( $key, 5 ), 'nobody else held the lock' );
	}

	/** The entry stored under a key, read as the cache reads it, against the current version. */
	private function stored( string $key ): ?array {
		return mai_cache( 'grid' )->read_swr( $key, $this->version() );
	}

	private function version(): string {
		return mai_cache( 'grid' )->version( [ 'post' ] );
	}

	private function ids( WP_Query $query ): array {
		return wp_list_pluck( $query->posts, 'ID' );
	}

	// ---- Version-stale ----

	/**
	 * A request that loses the lock on an entry a save made out of date is served the old list,
	 * and asks page caches not to keep the page. The finish-function check plays no part.
	 */
	#[DataProvider( 'paths' )]
	public function test_version_stale_loser_marks_page( string $path, bool $can_finish ): void {
		$entry = $this->stale_entry( $path, 'version' );

		$this->next_request( $can_finish );
		$this->lock( $entry['key'] );

		$grid = $this->render( $path );

		$this->assertTrue( $grid['answered'], 'served from the cache' );
		$this->assertSame( 0, $grid['selects'], 'no grid query' );
		$this->assertSame( $entry['ids'], $this->ids( $grid['query'] ), 'the old list' );
		$this->assertSame( 1, $this->marks, 'asked page caches not to keep the page, once' );
		$this->assertFalse( defined( 'DONOTCACHEPAGE' ), 'the seam stood in for the constant' );
	}

	/** The request that wins the lock runs the query, so its page is current. */
	public function test_winner_does_not_mark_page(): void {
		$entry = $this->stale_entry( 'kept', 'version' );

		$this->next_request();

		$grid = $this->render( 'kept' );

		$this->assertFalse( $grid['answered'], 'the winner runs the query' );
		$this->assertSame( 1, $grid['selects'] );
		$this->assertTrue( $this->stored( $entry['key'] )['fresh'] ?? false, 'and stores the new list' );
		$this->assertSame( 0, $this->marks );
	}

	/** Outside a normal page view, nothing asks page caches anything. */
	public function test_not_marked_outside_page_view(): void {
		$entry = $this->stale_entry( 'kept', 'version' );

		$this->next_request( true, static fn() => false );
		$this->lock( $entry['key'] );

		$grid = $this->render( 'kept' );

		$this->assertTrue( $grid['answered'], 'still served the old list' );
		$this->assertSame( $entry['ids'], $this->ids( $grid['query'] ) );
		$this->assertSame( 0, $this->marks );
	}

	// ---- Fresh and age-stale ----

	/** A fresh hit is current, so nothing asks. */
	public function test_fresh_hit_never_marks_page(): void {
		$this->visit( 'kept' );

		$key = $this->render( 'kept' )['key'];

		$this->assertTrue( mai_cache( 'grid' )->unlock( $key ), 'the cold render held the lock' );

		$this->next_request();
		$this->lock( $key );

		$grid = $this->render( 'kept' );

		$this->assertTrue( $grid['answered'] );
		$this->assertSame( 0, $this->marks );
	}

	/**
	 * An entry that only aged out still holds the right posts, so nothing asks. Whether it is
	 * served now and rebuilt after the page, or served while another request holds the lock.
	 */
	#[DataProvider( 'age_paths' )]
	public function test_age_stale_never_marks_page( bool $can_finish ): void {
		$entry = $this->stale_entry( 'kept', 'age' );

		$this->next_request( $can_finish );
		$this->lock( $entry['key'] );

		$grid = $this->render( 'kept' );

		$this->assertTrue( $grid['answered'], 'served from the cache' );
		$this->assertSame( $entry['ids'], $this->ids( $grid['query'] ) );
		$this->assertSame( $can_finish, $this->queue->has_job( $entry['key'] ), 'queued only when the page can finish early' );
		$this->assertSame( 0, $this->marks );
	}

	// ---- The real constant ----

	/**
	 * The queue MaiIntegrationTestCase gives every test serves a lock loser the same way, and
	 * leaves the constant alone. So a test that never installs a queue of its own cannot
	 * define it in this process.
	 */
	public function test_suite_queue_leaves_the_constant_alone(): void {
		$entry = $this->stale_entry( 'kept', 'version' );

		self::install_test_queue();
		wp_cache_flush_group( 'post-queries' );

		$this->lock( $entry['key'] );

		$grid = $this->render( 'kept' );

		$this->assertTrue( $grid['answered'], 'served the old list' );
		$this->assertSame( $entry['ids'], $this->ids( $grid['query'] ) );
		$this->assertFalse( defined( 'DONOTCACHEPAGE' ) );
	}

	/**
	 * With the default queue, a lock loser defines DONOTCACHEPAGE as true. Its own process,
	 * because a constant cannot be undefined.
	 */
	#[RunInSeparateProcess]
	public function test_loser_defines_the_real_constant(): void {
		$entry = $this->stale_entry( 'kept', 'version' );

		Mai_Query_Cache::instance()->set_queue( new Mai_Query_Cache_Queue() );
		wp_cache_flush_group( 'post-queries' );

		$this->lock( $entry['key'] );

		$this->assertFalse( defined( 'DONOTCACHEPAGE' ), 'not defined before the render' );

		$grid = $this->render( 'kept' );

		$this->assertTrue( $grid['answered'], 'served the old list' );
		$this->assertTrue( defined( 'DONOTCACHEPAGE' ) );
		$this->assertTrue( constant( 'DONOTCACHEPAGE' ) );
	}
}
