<?php
declare(strict_types=1);

namespace BizBudding\MaiEngine\Tests\Integration;

use Mai\Cache\Cache;
use Mai_Grid;
use Mai_Query_Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use WP_Query;

/**
 * What the grid result cache writes, and the ID tiebreaker its ID-only copy depends on.
 *
 * - Lifetimes: after the soft one (mai_query_cache_ttl) an entry reads as old but is still
 *   served. After the hard one (mai_query_cache_hard_ttl) the store drops it.
 * - The marker: an entry the ID-only copy built carries 'by' => 'ids'. Only those can be
 *   rebuilt after the page, because that rebuild runs the same copy.
 * - The version stored is the one read before the query ran, so a save during the query
 *   still leaves the entry reading as out of date.
 * - The tiebreaker is registered once and stays registered, so a copy run after the grid has
 *   rendered still gets the grid's ORDER BY. It only touches a query that asks for it.
 */
final class GridCacheStoreTest extends MaiIntegrationTestCase {

	private const PER_PAGE = 3;

	/** @var int[] Newest first. */
	private array $post_ids = [];

	private int $term_id = 0;

	public function set_up(): void {
		parent::set_up();

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

		( new Mai_Query_Cache() )->flush_all();
	}

	public function tear_down(): void {
		Cache::set_clock( null );

		parent::tear_down();
	}

	/**
	 * The three ways a grid's entry gets stored.
	 *
	 * - kept: a deferring grid, whose miss the ID-only copy answers.
	 * - the_posts: a grid that does not defer, so core runs its query and the_posts stores it.
	 * - declined: a deferring grid whose ID-only copy selects an extra column, so the copy
	 *   cannot stand in, and the_posts stores the padded list instead.
	 */
	public static function paths(): array {
		return [
			'kept-only'                  => [ 'kept' ],
			'a grid that does not defer' => [ 'the_posts' ],
			'ID copy declined'           => [ 'declined' ],
		];
	}

	/** The two paths that store on the_posts. See paths(). */
	public static function the_posts_paths(): array {
		return [
			'a grid that does not defer' => [ 'the_posts' ],
			'ID copy declined'           => [ 'declined' ],
		];
	}

	/**
	 * Path, soft filter value, hard filter value (null leaves the filter off), then the
	 * expected soft lifetime and store expiry.
	 */
	public static function lifetime_cases(): array {
		return [
			'kept-only, both filtered'   => [ 'kept', 1234, 5678, 1234, 5678 ],
			'the_posts, both filtered'   => [ 'the_posts', 1234, 5678, 1234, 5678 ],
			'kept-only, defaults'        => [ 'kept', null, null, 4 * HOUR_IN_SECONDS, DAY_IN_SECONDS ],
			'the_posts, hard below soft' => [ 'the_posts', 1234, 60, 1234, 1234 ],
		];
	}

	// ---- Helpers ----

	private function grid_args( array $overrides = [] ): array {
		return array_merge( [
			'type'           => 'post',
			'post_type'      => [ 'post' ],
			'query_by'       => 'tax_meta',
			'posts_per_page' => self::PER_PAGE,
			'excludes'       => [],
			'taxonomies'     => [
				[ 'taxonomy' => 'category', 'terms' => [ $this->term_id ], 'current' => false, 'operator' => 'IN' ],
			],
		], $overrides );
	}

	/**
	 * Renders one grid down a path (see paths()) and reports what the result cache did.
	 *
	 * The key is captured during the query, at posts_pre_query priority 9, because Mai_Grid
	 * puts a deferring grid's vars back once the query returns. Writes are read off
	 * set_transient: there is no persistent object cache in this suite, and a stored result
	 * is the only envelope whose value carries `ids`.
	 *
	 * @return array{query:WP_Query,key:string,writes:array<array{envelope:array,expiry:int}>}
	 */
	private function render( string $path ): array {
		$defer = 'the_posts' !== $path;

		$this->go_to( $defer ? get_permalink( $this->post_ids[0] ) : home_url( '/' ) );

		$key     = '';
		$writes  = [];
		$capture = static function ( $posts, $query ) use ( &$key ) {
			if ( ! empty( $query->query_vars['mai_cache'] ) ) {
				$key = ( new Mai_Query_Cache() )->cache_key( $query->query_vars, (string) $query->request );
			}

			return $posts;
		};
		$record  = static function ( $transient, $value, $expiration ) use ( &$writes ) {
			if ( is_array( $value ) && isset( $value['value']['ids'] ) ) {
				$writes[] = [ 'envelope' => $value, 'expiry' => (int) $expiration ];
			}
		};
		$decline = static fn( $fields, $query ) => ( 'ids' === ( $query->query_vars['fields'] ?? '' ) && ! empty( $query->query_vars['mai_grid_tiebreak'] ) ) ? $fields . ', 1 AS mai_test_column' : $fields;

		add_filter( 'posts_pre_query', $capture, 9, 2 );
		add_action( 'set_transient', $record, 10, 3 );

		if ( 'declined' === $path ) {
			add_filter( 'posts_fields', $decline, 10, 2 );
		}

		$query = ( new Mai_Grid( $this->grid_args( $defer ? [ 'excludes' => [ 'exclude_current' ] ] : [] ) ) )->get_query();

		remove_filter( 'posts_pre_query', $capture, 9 );
		remove_action( 'set_transient', $record, 10 );
		remove_filter( 'posts_fields', $decline, 10 );

		// A deferring grid gets the ID tiebreaker. Without it the path did not happen.
		if ( $defer ) {
			$this->assertStringContainsString( '.ID DESC', $query->request, 'must actually have deferred' );
		} else {
			$this->assertStringNotContainsString( '.ID DESC', $query->request, 'must not have deferred' );
		}

		$this->assertNotSame( '', $key, 'the grid query must have reached the result cache' );

		return [ 'query' => $query, 'key' => $key, 'writes' => $writes ];
	}

	/** The entry stored under a key, read back as the cache reads it. */
	private function stored( string $key ): ?array {
		return mai_cache( 'grid' )->read_swr( $key, mai_cache( 'grid' )->version( [ 'post' ] ) );
	}

	/** How many times the tiebreaker is registered on posts_orderby, at any priority, in any form. */
	private function tiebreaker_registrations(): int {
		$hook  = $GLOBALS['wp_filter']['posts_orderby'] ?? null;
		$count = 0;

		if ( ! $hook ) {
			return 0;
		}

		foreach ( $hook->callbacks as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				$function = $callback['function'];

				if ( is_array( $function ) && 'add_deferred_orderby_tiebreaker' === ( $function[1] ?? '' ) ) {
					++$count;
				}
			}
		}

		return $count;
	}

	/**
	 * Runs a query and returns the ORDER BY it ended with, read after every other
	 * posts_orderby callback.
	 */
	private function final_orderby( array $args ): string {
		$orderby = '';
		$capture = static function ( $clause ) use ( &$orderby ) {
			$orderby = (string) $clause;

			return $clause;
		};

		add_filter( 'posts_orderby', $capture, PHP_INT_MAX );
		new WP_Query( $args );
		remove_filter( 'posts_orderby', $capture, PHP_INT_MAX );

		return $orderby;
	}

	// ---- Lifetimes ----

	/**
	 * The soft lifetime sets the envelope's soft deadline, and the hard one sets how long the
	 * store keeps it. A hard lifetime below the soft one is raised to it.
	 */
	#[DataProvider( 'lifetime_cases' )]
	public function test_store_uses_soft_and_hard_lifetimes( string $path, ?int $soft, ?int $hard, int $expected_soft, int $expected_expiry ): void {
		$now = time() - 1000;

		Cache::set_clock( static fn() => $now );

		$hard_vars = [];

		if ( null !== $soft ) {
			add_filter( 'mai_query_cache_ttl', static fn() => $soft );
		}

		add_filter(
			'mai_query_cache_hard_ttl',
			static function ( $ttl, $query_vars ) use ( $hard, &$hard_vars ) {
				$hard_vars = $query_vars;

				return $hard ?? $ttl;
			},
			10,
			2
		);

		$result = $this->render( $path );

		$this->assertCount( 1, $result['writes'], 'one store per miss' );

		$write = $result['writes'][0];

		$this->assertSame( $now, $write['envelope']['w'] );
		$this->assertSame( $now + $expected_soft, $write['envelope']['s'], 'the soft deadline' );
		$this->assertSame( $expected_expiry, $write['expiry'], 'the store expiry' );
		$this->assertSame( [ 'post' ], (array) ( $hard_vars['post_type'] ?? null ), 'the hard filter gets the query vars, like the soft one' );
	}

	// ---- The marker ----

	public function test_kept_only_store_has_marker(): void {
		$result = $this->render( 'kept' );
		$stored = $this->stored( $result['key'] );

		$this->assertCount( 1, $result['writes'], 'the_posts must not store a second time' );
		$this->assertTrue( $stored['fresh'] );
		$this->assertSame( 'ids', $stored['value']['by'] ?? null );
		$this->assertSame( array_slice( $this->post_ids, 0, self::PER_PAGE + 1 ), $stored['value']['ids'], 'the padded list, excluded post included' );
		$this->assertIsInt( $stored['value']['found'] );
	}

	/**
	 * Only the ID-only copy marks what it stored. A grid that does not defer, and a deferring
	 * grid whose copy was declined, are stored on the_posts from whatever the full query
	 * returned, which a rebuild after the page could not reproduce.
	 */
	#[DataProvider( 'the_posts_paths' )]
	public function test_the_posts_store_has_no_marker( string $path ): void {
		$result = $this->render( $path );
		$stored = $this->stored( $result['key'] );

		$this->assertCount( 1, $result['writes'] );
		$this->assertTrue( $stored['fresh'] );
		$this->assertArrayNotHasKey( 'by', $stored['value'] );

		$count = 'declined' === $path ? self::PER_PAGE + 1 : self::PER_PAGE;

		$this->assertSame( array_slice( $this->post_ids, 0, $count ), $stored['value']['ids'] );
	}

	// ---- The version ----

	/**
	 * A post saved while the grid's query runs must leave the entry reading as out of date.
	 * That only holds when the store uses the version pre_query() read before any SQL, never
	 * one read again at store time.
	 */
	#[DataProvider( 'paths' )]
	public function test_store_keeps_the_version_read_before_the_query( string $path ): void {
		$bumped = false;
		$bump   = static function ( $value, $query ) use ( &$bumped ) {
			if ( ! $bumped && ! empty( $query->query_vars['mai_cache'] ) ) {
				mai_cache( 'grid' )->bump( 'post' );

				$bumped = true;
			}

			return $value;
		};

		$before = mai_cache( 'grid' )->version( [ 'post' ] );

		// Kept-only stores in posts_pre_query at the latest priority, after pre_query() at 10
		// read the version. The other paths store on the_posts, after posts_results.
		$hook     = 'kept' === $path ? 'posts_pre_query' : 'posts_results';
		$priority = 'kept' === $path ? 11 : 10;

		add_filter( $hook, $bump, $priority, 2 );
		$result = $this->render( $path );
		remove_filter( $hook, $bump, $priority );

		$after = mai_cache( 'grid' )->version( [ 'post' ] );

		$this->assertTrue( $bumped );
		$this->assertNotSame( $before, $after );
		$this->assertCount( 1, $result['writes'] );
		$this->assertSame( $before, $result['writes'][0]['envelope']['_v'] );
		$this->assertSame( 'version', $this->stored( $result['key'] )['stale'] );
	}

	// ---- The tiebreaker ----

	public function test_tiebreaker_registered_once(): void {
		for ( $i = 0; $i < 3; $i++ ) {
			$this->render( 'kept' );
		}

		// Tests and odd load orders can register the cache twice. WordPress keys a static
		// callable by its name at a priority, so a second add replaces the first.
		mai_register_query_cache();

		$this->assertSame( 99, has_filter( 'posts_orderby', [ 'Mai_Grid', 'add_deferred_orderby_tiebreaker' ] ) );
		$this->assertSame( 1, $this->tiebreaker_registrations() );
	}

	public function test_tiebreaker_ignores_other_queries(): void {
		global $wpdb;

		// Live for every query, so the negative below is real.
		$this->assertSame( 99, has_filter( 'posts_orderby', [ 'Mai_Grid', 'add_deferred_orderby_tiebreaker' ] ) );

		$args = [
			'post_type'      => 'post',
			'posts_per_page' => self::PER_PAGE,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		];

		$this->assertStringNotContainsString( "{$wpdb->posts}.ID", $this->final_orderby( $args ), 'a query without mai_grid_tiebreak' );
		$this->assertStringEndsWith( ", {$wpdb->posts}.ID DESC", $this->final_orderby( $args + [ 'mai_grid_tiebreak' => true ] ), 'the same query with it' );
	}
}
