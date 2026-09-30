<?php

namespace BizBudding\MaiEngine\Tests\Integration;

use Mai_Grid;
use Mai_Query_Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use WP_Post;
use WP_Query;

/**
 * The three ways a deferring grid can load its posts, picked by the temporary
 * mai_grid_load_strategy filter.
 *
 * - current: loads and primes the whole padded set, then Mai_Grid drops the excludes.
 * - prime_late: loads the padded set with meta and term priming off, then primes the kept posts.
 * - kept_only: Mai_Query_Cache answers posts_pre_query with only the kept posts.
 *
 * Contract: whatever the strategy, a grid shows the same posts in the same order, a miss and
 * the hit after it agree, and the query object handed back describes the request as it was
 * asked. What differs is how much gets loaded, which is what the priming tests pin.
 */
final class GridLoadStrategyTest extends MaiIntegrationTestCase {

	private const PER_PAGE = 6;

	/** @var int[] Newest first. */
	private $post_ids = [];

	/** @var int */
	private $term_id;

	public function set_up() {
		parent::set_up();

		$this->term_id = self::factory()->term->create( [ 'taxonomy' => 'category' ] );

		// Forty posts one day apart, each with one meta row. Index 0 is the newest, so post_date
		// DESC returns them in creation order.
		for ( $i = 0; $i < 40; $i++ ) {
			$id = self::factory()->post->create( [
				'post_status'   => 'publish',
				'post_date'     => gmdate( 'Y-m-d H:i:s', strtotime( "-{$i} days" ) ),
				'post_category' => [ $this->term_id ],
			] );

			add_post_meta( $id, 'mai_test_meta', $i );

			$this->post_ids[] = $id;
		}
	}

	public function tear_down() {
		Mai_Grid::$existing_post_ids = [];

		remove_all_filters( 'mai_grid_load_strategy' );

		parent::tear_down();
	}

	public static function strategies(): array {
		return [
			'current'    => [ 'current' ],
			'prime_late' => [ 'prime_late' ],
			'kept_only'  => [ 'kept_only' ],
		];
	}

	public static function strategies_and_scenarios(): array {
		$cases = [];

		foreach ( [ 'prime_late', 'kept_only' ] as $strategy ) {
			foreach ( [ 'current_none', 'current_in_window', 'current_outside_window', 'displayed_none', 'displayed_few', 'displayed_many', 'both_many', 'tied' ] as $scenario ) {
				$cases[ "{$strategy} / {$scenario}" ] = [ $strategy, $scenario ];
			}
		}

		return $cases;
	}

	/** Every ordered pair of different strategies: the one that writes, then the one that reads. */
	public static function strategy_pairs(): array {
		$pairs = [];

		foreach ( [ 'current', 'prime_late', 'kept_only' ] as $writer ) {
			foreach ( [ 'current', 'prime_late', 'kept_only' ] as $reader ) {
				if ( $writer !== $reader ) {
					$pairs[ "{$writer} then {$reader}" ] = [ $writer, $reader ];
				}
			}
		}

		return $pairs;
	}

	// ---- Helpers ----

	private function grid_args( array $overrides = [] ): array {
		return array_merge( [
			'type'           => 'post',
			'post_type'      => [ 'post' ],
			'query_by'       => 'tax_meta',
			'posts_per_page' => self::PER_PAGE,
			'excludes'       => [ 'exclude_current' ],
			'taxonomies'     => [
				[ 'taxonomy' => 'category', 'terms' => [ $this->term_id ], 'current' => false, 'operator' => 'IN' ],
			],
		], $overrides );
	}

	/** Every third post kept back, so the displayed ones are spread through the window. */
	private function many_displayed(): array {
		$ids = [];

		for ( $i = 0; $i < 38; $i++ ) {
			if ( 2 !== $i % 3 ) {
				$ids[] = $this->post_ids[ $i ];
			}
		}

		return $ids;
	}

	/** Makes every post share a post_date, so only the ID tiebreaker settles the order. */
	private function make_everything_tie(): void {
		$same = gmdate( 'Y-m-d H:i:s', strtotime( '-1 hour' ) );

		foreach ( $this->post_ids as $id ) {
			wp_update_post( [ 'ID' => $id, 'post_date' => $same, 'post_date_gmt' => $same ] );
		}
	}

	/**
	 * Puts the page in the state a scenario needs and returns the grid args for it.
	 *
	 * @return array{args:array,excluded:int[]}
	 */
	private function prepare( string $scenario ): array {
		$current   = 0;
		$displayed = [];
		$excludes  = [ 'exclude_current' ];

		switch ( $scenario ) {
			case 'current_none':
				// Not singular, so exclude_current has nothing to exclude and the grid does not defer.
				break;
			case 'current_in_window':
				$current = $this->post_ids[2];
				break;
			case 'current_outside_window':
				// The oldest post, so nothing is filtered out and the padding row has to be trimmed.
				$current = $this->post_ids[39];
				break;
			case 'displayed_none':
				$excludes = [ 'exclude_displayed' ];
				break;
			case 'displayed_few':
				$excludes  = [ 'exclude_displayed' ];
				$displayed = [ $this->post_ids[1], $this->post_ids[3], $this->post_ids[4] ];
				break;
			case 'displayed_many':
				$excludes  = [ 'exclude_displayed' ];
				$displayed = $this->many_displayed();
				break;
			case 'both_many':
				$excludes  = [ 'exclude_current', 'exclude_displayed' ];
				$current   = $this->post_ids[5];
				$displayed = $this->many_displayed();
				break;
			case 'tied':
				$this->make_everything_tie();
				$excludes  = [ 'exclude_displayed' ];
				$displayed = [ $this->post_ids[1], $this->post_ids[3], $this->post_ids[4] ];
				break;
			default:
				$this->fail( "Unknown scenario {$scenario}" );
		}

		$this->go_to( $current ? get_permalink( $current ) : home_url( '/' ) );

		Mai_Grid::$existing_post_ids = $displayed ? [ 'post' => $displayed ] : [];

		return [
			'args'     => $this->grid_args( [ 'excludes' => $excludes ] ),
			'excluded' => array_values( array_filter( array_merge( $displayed, [ $current ] ) ) ),
		];
	}

	/** Runs a grid under one strategy. */
	private function run_grid( string $strategy, array $args ): WP_Query {
		$pick = static fn() => $strategy;

		add_filter( 'mai_grid_load_strategy', $pick );
		$query = ( new Mai_Grid( $args ) )->get_query();
		remove_filter( 'mai_grid_load_strategy', $pick );

		return $query;
	}

	/** Runs the same grid with deferring switched off, for a like-for-like comparison. */
	private function undeferred( array $args ): WP_Query {
		add_filter( 'mai_post_grid_defer_excludes', '__return_false' );
		$query = ( new Mai_Grid( $args ) )->get_query();
		remove_filter( 'mai_post_grid_defer_excludes', '__return_false' );

		return $query;
	}

	private function flush_result_cache(): void {
		( new Mai_Query_Cache() )->flush_all();
	}

	private function ids( WP_Query $query ): array {
		return wp_list_pluck( $query->posts, 'ID' );
	}

	/** Empties the object cache for every test post, so priming can be read off it afterwards. */
	private function clean_post_caches(): void {
		foreach ( $this->post_ids as $id ) {
			clean_post_cache( $id );
		}
	}

	/**
	 * Records every SQL statement run while the callback runs.
	 *
	 * @return array{0:mixed,1:string[]}
	 */
	private function capture_sql( callable $callback ): array {
		$sql     = [];
		$capture = static function ( $statement ) use ( &$sql ) {
			$sql[] = $statement;

			return $statement;
		};

		add_filter( 'query', $capture );
		$result = $callback();
		remove_filter( 'query', $capture );

		return [ $result, $sql ];
	}

	/** The grid's own statements: against the posts table and carrying a LIMIT. Priming reads have none. */
	private function grid_selects( array $sql ): array {
		global $wpdb;

		return array_values(
			array_filter(
				$sql,
				static fn( $statement ) => preg_match( '/\bFROM\s+' . preg_quote( $wpdb->posts, '/' ) . '\b/', $statement ) && str_contains( $statement, 'LIMIT' )
			)
		);
	}

	/** ID-only statements against the posts table. */
	private function id_selects( array $sql ): array {
		global $wpdb;

		return array_values(
			array_filter(
				$sql,
				static fn( $statement ) => (bool) preg_match( '/^\s*SELECT\s+(?:DISTINCT\s+)?' . preg_quote( $wpdb->posts, '/' ) . '\.ID\s+FROM\s/i', $statement )
			)
		);
	}

	/**
	 * Counts result-cache stores while the callback runs. mai-cache writes through transients
	 * here (no persistent object cache in this suite), and a stored result is the only envelope
	 * whose value carries `ids`. Version tokens are plain strings.
	 *
	 * @return array{0:mixed,1:int}
	 */
	private function count_stores( callable $callback ): array {
		$stores  = 0;
		$counter = static function ( $transient, $value ) use ( &$stores ) {
			if ( is_array( $value ) && isset( $value['value']['ids'] ) ) {
				++$stores;
			}
		};

		add_action( 'set_transient', $counter, 10, 2 );
		$result = $callback();
		remove_action( 'set_transient', $counter, 10 );

		return [ $result, $stores ];
	}

	/**
	 * query_vars in a form two queries can be compared in. Core sorts post__not_in in place
	 * when it builds the NOT IN clause (WP_Query::get_posts()), and a key the grid set for its
	 * own query keeps the position it was set at. Neither changes what the query means.
	 */
	private function comparable_vars( WP_Query $query ): array {
		$vars = $query->query_vars;

		if ( isset( $vars['post__not_in'] ) && is_array( $vars['post__not_in'] ) ) {
			sort( $vars['post__not_in'] );
		}

		ksort( $vars );

		return $vars;
	}

	private function without_fields( string $sql ): string {
		return preg_replace( '/^\s*SELECT\s+.*?\s+FROM\s/is', 'SELECT FIELDS FROM ', $sql, 1 );
	}

	/** Whether each ID has its row, its meta and its category terms in the object cache. */
	private function cached( array $ids ): array {
		$state = [];

		foreach ( $ids as $id ) {
			$state[ $id ] = [
				'row'   => false !== wp_cache_get( $id, 'posts' ),
				'meta'  => false !== wp_cache_get( $id, 'post_meta' ),
				'terms' => false !== wp_cache_get( $id, 'category_relationships' ),
			];
		}

		return $state;
	}

	// ---- Same posts, same order ----

	#[DataProvider( 'strategies_and_scenarios' )]
	public function test_kept_posts_match_the_current_strategy( string $strategy, string $scenario ): void {
		$prepared = $this->prepare( $scenario );
		$args     = $prepared['args'];

		$this->flush_result_cache();
		$baseline = $this->ids( $this->run_grid( 'current', $args ) );

		$this->flush_result_cache();
		$miss = $this->run_grid( $strategy, $args );
		$hit  = $this->run_grid( $strategy, $args );

		$this->assertCount( self::PER_PAGE, $baseline, 'every scenario has enough posts left to fill the grid' );
		$this->assertSame( $baseline, $this->ids( $miss ), 'a miss must show what the current strategy shows' );
		$this->assertSame( $baseline, $this->ids( $hit ), 'the hit after it must too' );
		$this->assertSame( [], array_intersect( $baseline, $prepared['excluded'] ) );

		if ( $prepared['excluded'] ) {
			$this->assertStringNotContainsString( 'NOT IN', $miss->request, 'must actually have deferred' );
		} else {
			// Nothing to exclude, so nothing defers, and every strategy is the same plain query.
			$this->assertStringNotContainsString( '.ID DESC', $miss->request, 'a grid with nothing to exclude must not defer' );
		}

		// Tied rows are the one place the deferred order is allowed to differ from the plain
		// query, because only the deferred query gets the ID tiebreaker.
		if ( 'tied' !== $scenario ) {
			$this->assertSame( $this->ids( $this->undeferred( $args ) ), $baseline );
		}
	}

	// ---- What posts_results and the_posts see ----

	#[DataProvider( 'strategies' )]
	public function test_posts_results_and_the_posts_get_full_post_objects( string $strategy ): void {
		$prepared = $this->prepare( 'both_many' );
		$seen     = [];

		$watch = static function ( string $hook ) use ( &$seen ) {
			return static function ( $posts, $query ) use ( $hook, &$seen ) {
				if ( ! empty( $query->query_vars['mai_cache'] ) ) {
					$seen[ $hook ][] = $posts;
				}

				return $posts;
			};
		};

		add_filter( 'posts_results', $watch( 'posts_results' ), 10, 2 );
		add_filter( 'the_posts', $watch( 'the_posts' ), 5, 2 );

		$this->flush_result_cache();
		$shown = [
			$this->ids( $this->run_grid( $strategy, $prepared['args'] ) ),
			$this->ids( $this->run_grid( $strategy, $prepared['args'] ) ),
		];

		foreach ( [ 'posts_results', 'the_posts' ] as $hook ) {
			$this->assertCount( 2, $seen[ $hook ], "{$hook} must fire for the miss and for the hit" );

			foreach ( $seen[ $hook ] as $run => $posts ) {
				foreach ( $posts as $post ) {
					$this->assertInstanceOf( WP_Post::class, $post, "{$hook} must get post objects, not IDs" );
				}

				if ( 'kept_only' === $strategy ) {
					$this->assertSame( $shown[ $run ], wp_list_pluck( $posts, 'ID' ), "{$hook} must see only the posts the grid shows" );
				} else {
					// The other two still hand the padded set to both hooks. Pinned so the
					// difference is visible: 6 asked for, plus 27 excluded.
					$this->assertCount( self::PER_PAGE + count( $prepared['excluded'] ), $posts );
				}
			}
		}
	}

	// ---- How much gets loaded ----

	#[DataProvider( 'strategies' )]
	public function test_meta_and_terms_are_primed_only_for_kept_posts( string $strategy ): void {
		$prepared = $this->prepare( 'displayed_many' );
		$window   = array_slice( $this->post_ids, 0, self::PER_PAGE + count( $prepared['excluded'] ) );

		$this->flush_result_cache();

		foreach ( [ 'miss', 'hit' ] as $pass ) {
			$this->clean_post_caches();

			[ $query, $sql ] = $this->capture_sql( fn() => $this->run_grid( $strategy, $prepared['args'] ) );

			$kept    = $this->ids( $query );
			$dropped = array_values( array_diff( $window, $kept ) );

			$this->assertCount( self::PER_PAGE, $kept );
			$this->assertCount( count( $window ) - self::PER_PAGE, $dropped );

			foreach ( $this->cached( $kept ) as $id => $state ) {
				$this->assertSame( [ 'row' => true, 'meta' => true, 'terms' => true ], $state, "{$pass}: kept post {$id} must be fully primed" );
			}

			$expected = [
				'current'    => [ 'row' => true, 'meta' => true, 'terms' => true ],
				'prime_late' => [ 'row' => true, 'meta' => false, 'terms' => false ],
				'kept_only'  => [ 'row' => false, 'meta' => false, 'terms' => false ],
			][ $strategy ];

			foreach ( $this->cached( $dropped ) as $id => $state ) {
				$this->assertSame( $expected, $state, "{$pass}: dropped post {$id} under {$strategy}" );
			}

			$selects = $this->grid_selects( $sql );

			if ( 'hit' === $pass ) {
				$this->assertSame( [], $selects, 'a hit must not run the grid query' );
			} elseif ( 'kept_only' === $strategy ) {
				global $wpdb;

				$this->assertCount( 1, $selects );
				$this->assertMatchesRegularExpression( '/^\s*SELECT\s+' . preg_quote( $wpdb->posts, '/' ) . '\.ID\s+FROM\s/i', $selects[0], 'the miss must select IDs only' );
				$this->assertStringContainsString( 'LIMIT 0, ' . count( $window ), $selects[0], 'over the padded window' );
			} else {
				$this->assertCount( 1, $selects );
			}
		}
	}

	/**
	 * A site can turn cache_results off for a grid. Core still primes meta and terms for a query
	 * it splits, which every unfiltered deferring grid is. Only the priming it does after
	 * the_posts checks cache_results. So the kept posts are primed under every strategy, as
	 * they are under the current one.
	 */
	#[DataProvider( 'strategies' )]
	public function test_kept_posts_are_primed_when_a_site_turned_cache_results_off( string $strategy ): void {
		$off = static fn( $query_args ) => array_merge( $query_args, [ 'cache_results' => false ] );

		add_filter( 'mai_post_grid_query_args', $off );

		$prepared = $this->prepare( 'displayed_many' );

		$this->flush_result_cache();
		$this->clean_post_caches();
		$query = $this->run_grid( $strategy, $prepared['args'] );

		remove_filter( 'mai_post_grid_query_args', $off );

		$this->assertStringNotContainsString( 'NOT IN', $query->request, 'must actually have deferred' );
		$this->assertCount( self::PER_PAGE, $this->ids( $query ) );

		foreach ( $this->cached( $this->ids( $query ) ) as $id => $state ) {
			$this->assertSame( [ 'row' => true, 'meta' => true, 'terms' => true ], $state, "kept post {$id}" );
		}
	}

	// ---- Miss, then hit ----

	#[DataProvider( 'strategies' )]
	public function test_a_miss_stores_once_and_the_hit_serves_the_same_posts( string $strategy ): void {
		$prepared = $this->prepare( 'both_many' );

		$this->flush_result_cache();

		[ $miss, $miss_stores ] = $this->count_stores( fn() => $this->capture_sql( fn() => $this->run_grid( $strategy, $prepared['args'] ) ) );
		[ $hit, $hit_stores ]   = $this->count_stores( fn() => $this->capture_sql( fn() => $this->run_grid( $strategy, $prepared['args'] ) ) );

		$this->assertSame( 1, $miss_stores, 'a miss must store exactly once' );
		$this->assertSame( 0, $hit_stores, 'a hit must not store' );

		$this->assertCount( 1, $this->grid_selects( $miss[1] ) );
		$this->assertSame( [], $this->grid_selects( $hit[1] ), 'the hit must not run the grid query' );

		$this->assertCount( self::PER_PAGE, $this->ids( $miss[0] ) );
		$this->assertSame( $this->ids( $miss[0] ), $this->ids( $hit[0] ) );
	}

	/**
	 * The shared-entry round trip, for every strategy: what one article stores has to be the
	 * padded list, so the next article can drop its own post and still fill the grid. For
	 * kept_only this is the store in posts_pre_query, which never sees the_posts.
	 */
	#[DataProvider( 'strategies' )]
	public function test_the_stored_entry_is_the_padded_list_and_fills_the_next_page( string $strategy ): void {
		$args = $this->grid_args();

		$this->flush_result_cache();

		$this->go_to( get_permalink( $this->post_ids[0] ) );

		$key     = '';
		$capture = static function ( $posts, $query ) use ( &$key ) {
			$key = ( new Mai_Query_Cache() )->cache_key( $query->query_vars, (string) $query->request );

			return $posts;
		};

		add_filter( 'posts_pre_query', $capture, 9, 2 );
		$first = $this->ids( $this->run_grid( $strategy, $args ) );
		remove_filter( 'posts_pre_query', $capture, 9 );

		$stored = mai_cache( 'grid' )->read_swr( $key, mai_cache( 'grid' )->version( [ 'post' ] ) );

		$this->assertSame( array_slice( $this->post_ids, 0, self::PER_PAGE + 1 ), $stored['value']['ids'], 'the padded list, excluded post included' );
		$this->assertSame( array_slice( $this->post_ids, 1, self::PER_PAGE ), $first );

		$this->go_to( get_permalink( $this->post_ids[1] ) );

		[ $second, $sql ] = $this->capture_sql( fn() => $this->run_grid( $strategy, $args ) );

		$this->assertSame( [], $this->grid_selects( $sql ), 'the second article must be served from the entry' );
		$this->assertSame(
			array_merge( [ $this->post_ids[0] ], array_slice( $this->post_ids, 2, self::PER_PAGE - 1 ) ),
			$this->ids( $second ),
			'a full grid without the post being viewed'
		);
	}

	/**
	 * An entry one strategy wrote must read back correctly under another, so switching the
	 * strategy on a warm site cannot show a short or wrong grid.
	 */
	#[DataProvider( 'strategy_pairs' )]
	public function test_an_entry_written_under_one_strategy_reads_under_another( string $writer, string $reader ): void {
		$args = $this->grid_args();

		$this->flush_result_cache();

		$this->go_to( get_permalink( $this->post_ids[0] ) );
		$first = $this->ids( $this->run_grid( $writer, $args ) );

		$this->go_to( get_permalink( $this->post_ids[1] ) );
		[ $second, $sql ] = $this->capture_sql( fn() => $this->run_grid( $reader, $args ) );

		$this->assertSame( array_slice( $this->post_ids, 1, self::PER_PAGE ), $first );
		$this->assertSame( [], $this->grid_selects( $sql ), "{$reader} must read the entry {$writer} wrote" );
		$this->assertSame(
			array_merge( [ $this->post_ids[0] ], array_slice( $this->post_ids, 2, self::PER_PAGE - 1 ) ),
			$this->ids( $second ),
			'a full grid without the post being viewed'
		);
	}

	/**
	 * kept_only's miss gets its IDs from an ID-only copy of the grid's query, which uses core's
	 * query cache. The grid's own query stays at cache_results off: left on, core would store
	 * the kept answer, which differs per page view, under the padded query's key. So core gets
	 * one entry per grid, whatever the page view excludes. go_to() empties the object cache, so
	 * the views here differ by the posts already displayed instead.
	 */
	public function test_kept_only_writes_one_core_query_cache_entry_per_grid_not_per_view(): void {
		global $wp_object_cache;

		$count = static fn() => count( $wp_object_cache->cache['post-queries'] ?? [] );
		$args  = $this->grid_args( [ 'excludes' => [ 'exclude_displayed' ] ] );
		$added = [];

		$this->go_to( home_url( '/' ) );

		foreach ( [ 1, 3, 4 ] as $displayed ) {
			Mai_Grid::$existing_post_ids = [ 'post' => [ $this->post_ids[ $displayed ] ] ];

			// Every view is a result cache miss, so every view runs the ID query.
			$this->flush_result_cache();

			$before = $count();
			$query  = $this->run_grid( 'kept_only', $args );

			$added[] = $count() - $before;

			$this->assertStringNotContainsString( 'NOT IN', $query->request, 'must actually have deferred' );
			$this->assertNotContains( $this->post_ids[ $displayed ], $this->ids( $query ) );
			$this->assertCount( self::PER_PAGE, $this->ids( $query ) );
		}

		$this->assertSame( [ 1, 0, 0 ], $added, 'one entry for the grid, read back by the later views' );
	}

	/**
	 * The warm case that used to be slower than the current strategy: the result cache has no
	 * entry, but core's query cache does. The ID query must read core's entry instead of running
	 * the grid's SQL again.
	 */
	public function test_a_kept_only_miss_reads_its_ids_from_core_query_cache(): void {
		global $wpdb;

		$prepared = $this->prepare( 'current_in_window' );

		$this->flush_result_cache();
		[ $first, $first_sql ] = $this->capture_sql( fn() => $this->run_grid( 'kept_only', $prepared['args'] ) );

		// The result cache loses its entry. Core's query cache keeps its own.
		$this->flush_result_cache();
		[ $result, $stores ]     = $this->count_stores( fn() => $this->capture_sql( fn() => $this->run_grid( 'kept_only', $prepared['args'] ) ) );
		[ $second, $second_sql ] = $result;

		$posts_reads = array_filter(
			$second_sql,
			static fn( $statement ) => (bool) preg_match( '/\bFROM\s+' . preg_quote( $wpdb->posts, '/' ) . '\b/', $statement )
		);

		$this->assertCount( 1, $this->grid_selects( $first_sql ), 'the first miss runs the ID query' );
		$this->assertSame( [], array_values( $posts_reads ), 'the second miss reads nothing from the posts table' );
		$this->assertSame( 1, $stores, 'and still stores the result cache entry' );
		$this->assertSame( $this->ids( $first ), $this->ids( $second ) );
		$this->assertCount( self::PER_PAGE, $this->ids( $second ) );
	}

	/**
	 * A site that turned cache_results off for a grid does not want core's query cache for it:
	 * its results may depend on something core's cache does not track. The ID query must not
	 * read or write that cache either.
	 */
	public function test_the_id_query_skips_core_query_cache_when_the_grid_asked_to(): void {
		global $wp_object_cache;

		$off   = static fn( $query_args ) => array_merge( $query_args, [ 'cache_results' => false ] );
		$count = static fn() => count( $wp_object_cache->cache['post-queries'] ?? [] );

		$prepared = $this->prepare( 'current_in_window' );

		add_filter( 'mai_post_grid_query_args', $off );

		$this->flush_result_cache();
		$before = $count();
		[ , $first ] = $this->capture_sql( fn() => $this->run_grid( 'kept_only', $prepared['args'] ) );
		$added = $count() - $before;

		$this->flush_result_cache();
		[ $query, $second ] = $this->capture_sql( fn() => $this->run_grid( 'kept_only', $prepared['args'] ) );

		remove_filter( 'mai_post_grid_query_args', $off );

		$this->assertStringNotContainsString( 'NOT IN', $query->request, 'must actually have deferred' );
		$this->assertSame( 0, $added, 'nothing written to core\'s query cache' );
		$this->assertCount( 1, $this->id_selects( $first ) );
		$this->assertCount( 1, $this->id_selects( $second ), 'the second miss runs the ID query again' );
		$this->assertCount( self::PER_PAGE, $this->ids( $query ) );
	}

	/**
	 * The ID query is core's own ID-only statement, byte for byte the one core runs when it
	 * splits the current strategy's query, before posts_request_ids. So both return the same
	 * rows in the same order.
	 */
	public function test_the_id_query_is_the_statement_core_splits_the_grid_into(): void {
		$prepared = $this->prepare( 'both_many' );

		$this->flush_result_cache();
		[ , $current ] = $this->capture_sql( fn() => $this->run_grid( 'current', $prepared['args'] ) );

		$this->flush_result_cache();
		[ , $kept_only ] = $this->capture_sql( fn() => $this->run_grid( 'kept_only', $prepared['args'] ) );

		$this->assertCount( 1, $this->grid_selects( $current ) );
		$this->assertSame( $this->grid_selects( $current ), $this->grid_selects( $kept_only ) );
	}

	/**
	 * The ID query must not reach the result cache as a query of its own. If it did, pre_query()
	 * would flag it as a miss, and with a persistent object cache it would also take, or wait on,
	 * the single-flight lock the grid's own query already holds.
	 */
	public function test_the_id_query_is_left_alone_by_the_result_cache(): void {
		$prepared = $this->prepare( 'both_many' );
		$seen     = [];

		$watch = static function ( $posts, $query ) use ( &$seen ) {
			if ( 'ids' === ( $query->query_vars['fields'] ?? '' ) && ! empty( $query->query_vars['mai_grid_tiebreak'] ) ) {
				$seen[] = [
					'answered' => null !== $posts,
					'flagged'  => isset( $query->mai_cache_store_key ),
				];
			}

			return $posts;
		};

		add_filter( 'posts_pre_query', $watch, 11, 2 );

		$this->flush_result_cache();
		[ , $stores ] = $this->count_stores( fn() => $this->run_grid( 'kept_only', $prepared['args'] ) );

		remove_filter( 'posts_pre_query', $watch, 11 );

		$this->assertSame( [ [ 'answered' => false, 'flagged' => false ] ], $seen, 'one ID query, neither answered nor flagged by the result cache' );
		$this->assertSame( 1, $stores );
	}

	/**
	 * A failed ID query must not be stored as an empty result for the length of the TTL. The
	 * grid falls back to its own query for that view and stores nothing, and the next view
	 * fills the entry.
	 */
	public function test_a_failed_id_query_is_not_stored(): void {
		global $wpdb;

		$prepared = $this->prepare( 'both_many' );
		$broken   = false;

		$this->flush_result_cache();
		$baseline = $this->ids( $this->run_grid( 'current', $prepared['args'] ) );

		// Breaks the ID query's own statement, once.
		$break = static function ( $sql, $query ) use ( &$broken ) {
			if ( ! $broken && 'ids' === ( $query->query_vars['fields'] ?? '' ) && ! empty( $query->query_vars['mai_grid_tiebreak'] ) ) {
				$broken = true;

				return $sql . ' BROKEN';
			}

			return $sql;
		};

		add_filter( 'posts_request', $break, 10, 2 );
		$suppress = $wpdb->suppress_errors( true );

		$this->flush_result_cache();
		[ $miss, $miss_stores ] = $this->count_stores( fn() => $this->run_grid( 'kept_only', $prepared['args'] ) );
		[ $next, $next_stores ] = $this->count_stores( fn() => $this->run_grid( 'kept_only', $prepared['args'] ) );

		$wpdb->suppress_errors( $suppress );
		remove_filter( 'posts_request', $break, 10 );

		$this->assertTrue( $broken, 'the ID query was broken' );
		$this->assertSame( $baseline, $this->ids( $miss ), 'the grid fell back to its own query' );
		$this->assertSame( 0, $miss_stores, 'nothing stored from the failed view' );
		$this->assertSame( $baseline, $this->ids( $next ) );
		$this->assertSame( 1, $next_stores, 'the next view fills the entry' );
	}

	/**
	 * An empty ID list is not trusted. Core stores a failed statement's empty result in its
	 * query cache like any other, and a failure fetch_ids() cannot see, here one in a query
	 * filter that rewrote the statement, looks the same. The grid's own query answers instead,
	 * and its result is what gets stored.
	 */
	public function test_an_empty_id_list_falls_back_to_the_grid_query(): void {
		global $wpdb;

		$prepared = $this->prepare( 'both_many' );
		$padded   = self::PER_PAGE + count( $prepared['excluded'] );
		$broken   = false;

		$this->flush_result_cache();
		$baseline = $this->ids( $this->run_grid( 'current', $prepared['args'] ) );

		// Breaks the first ID-only statement over the padded window, once, as it reaches the database.
		$break = static function ( $sql ) use ( &$broken, $padded, $wpdb ) {
			if ( ! $broken && preg_match( '/^\s*SELECT\s+' . preg_quote( $wpdb->posts, '/' ) . '\.ID\s+FROM\s/', $sql ) && str_contains( $sql, 'LIMIT 0, ' . $padded ) ) {
				$broken = true;

				return $sql . ' BROKEN';
			}

			return $sql;
		};

		add_filter( 'query', $break );
		$suppress = $wpdb->suppress_errors( true );

		$this->flush_result_cache();
		[ $miss, $stores ] = $this->count_stores( fn() => $this->run_grid( 'kept_only', $prepared['args'] ) );
		$hit = $this->run_grid( 'kept_only', $prepared['args'] );

		$wpdb->suppress_errors( $suppress );
		remove_filter( 'query', $break );

		$this->assertTrue( $broken, 'the ID query was broken' );
		$this->assertSame( $baseline, $this->ids( $miss ), 'the grid fell back to its own query' );
		$this->assertSame( 1, $stores, 'and stored what that query found' );
		$this->assertSame( $baseline, $this->ids( $hit ) );
	}

	/**
	 * Only the ID query's own statement failing counts. When core answers the ID query from its
	 * cache, a statement some callback ran and failed on the way must not stop the store.
	 */
	public function test_a_failed_statement_elsewhere_is_not_a_failed_id_query(): void {
		global $wpdb;

		$prepared = $this->prepare( 'current_in_window' );

		// Warms core's query cache for the ID query.
		$this->flush_result_cache();
		$this->run_grid( 'kept_only', $prepared['args'] );

		$noise = static function ( $query ) use ( $wpdb ) {
			if ( 'ids' === ( $query->query_vars['fields'] ?? '' ) && ! empty( $query->query_vars['mai_grid_tiebreak'] ) ) {
				$wpdb->query( "SELECT mai_no_such_column FROM {$wpdb->posts} LIMIT 1" );
			}
		};

		add_action( 'pre_get_posts', $noise );
		$suppress = $wpdb->suppress_errors( true );

		$this->flush_result_cache();
		[ $query, $stores ] = $this->count_stores( fn() => $this->run_grid( 'kept_only', $prepared['args'] ) );

		$wpdb->suppress_errors( $suppress );
		remove_action( 'pre_get_posts', $noise );

		$this->assertSame( 1, $stores, 'core answered the ID query, so the entry is stored' );
		$this->assertCount( self::PER_PAGE, $this->ids( $query ) );
	}

	/**
	 * The Events Calendar writes "now", to the second, into the meta_query of every event query,
	 * so its key changes on every view. Such a query is not cached at all: no entry is written,
	 * each view runs its own query, and the grid still shows the right posts. Here the value
	 * moves one second on every query.
	 */
	#[DataProvider( 'strategies' )]
	public function test_a_query_that_holds_now_is_not_cached( string $strategy ): void {
		$prepared = $this->prepare( 'current_in_window' );
		$second   = 0;
		$start    = time();

		$now = static function ( $query ) use ( &$second, $start ) {
			if ( $query->is_main_query() ) {
				return;
			}

			$query->set(
				'meta_query',
				[
					'mai_test_now' => [
						'key'     => 'mai_test_meta',
						'value'   => gmdate( 'Y-m-d H:i:s', $start + $second++ ),
						'compare' => '!=',
					],
				]
			);
		};

		add_action( 'pre_get_posts', $now );

		$this->flush_result_cache();
		[ [ $first, $first_sql ], $first_stores ]   = $this->count_stores( fn() => $this->capture_sql( fn() => $this->run_grid( $strategy, $prepared['args'] ) ) );
		[ [ $second_run, $second_sql ], $second_stores ] = $this->count_stores( fn() => $this->capture_sql( fn() => $this->run_grid( $strategy, $prepared['args'] ) ) );

		remove_action( 'pre_get_posts', $now );

		$this->assertGreaterThan( 1, $second, 'the value moved between queries' );
		$this->assertSame( 0, $first_stores + $second_stores, 'nothing is stored' );
		$this->assertCount( 1, $this->grid_selects( $first_sql ) );
		$this->assertCount( 1, $this->grid_selects( $second_sql ), 'the second view runs its own query' );
		$this->assertCount( self::PER_PAGE, $this->ids( $second_run ) );
		$this->assertSame( $this->ids( $first ), $this->ids( $second_run ) );
	}

	/** All three strategies land on one entry, so a benchmark can switch between them warm. */
	public function test_every_strategy_uses_the_same_cache_key(): void {
		$prepared = $this->prepare( 'both_many' );
		$keys     = [];

		foreach ( [ 'current', 'prime_late', 'kept_only' ] as $strategy ) {
			$capture = static function ( $posts, $query ) use ( &$keys, $strategy ) {
				$keys[ $strategy ] = ( new Mai_Query_Cache() )->cache_key( $query->query_vars, (string) $query->request );

				return $posts;
			};

			add_filter( 'posts_pre_query', $capture, 9, 2 );
			$this->run_grid( $strategy, $prepared['args'] );
			remove_filter( 'posts_pre_query', $capture, 9 );
		}

		$this->assertCount( 1, array_unique( $keys ), 'the strategy markers must not reach the key' );
	}

	// ---- The restore ----

	#[DataProvider( 'strategies' )]
	public function test_query_vars_are_restored( string $strategy ): void {
		$prepared   = $this->prepare( 'both_many' );
		$deferred   = $this->run_grid( $strategy, $prepared['args'] );
		$undeferred = $this->undeferred( $prepared['args'] );

		$this->assertStringNotContainsString( 'NOT IN', $deferred->request, 'must actually have deferred' );

		// Mai Load More serializes these and rebuilds a WP_Query from them.
		$this->assertSame( $undeferred->query, $deferred->query, 'the raw args copy must be exactly what was asked' );
		$this->assertSame( $this->comparable_vars( $undeferred ), $this->comparable_vars( $deferred ), 'query_vars must be what a grid that does not defer ends up with' );

		// Named one by one as well, so a failure says which one.
		$expected = $this->comparable_vars( $undeferred );
		$actual   = $this->comparable_vars( $deferred );

		foreach ( [ 'posts_per_page', 'post__not_in', 'fields', 'cache_results', 'update_post_meta_cache', 'update_post_term_cache', 'lazy_load_term_meta' ] as $var ) {
			$this->assertSame( $expected[ $var ] ?? 'unset', $actual[ $var ] ?? 'unset', $var );
		}

		foreach ( [ 'mai_grid_tiebreak', 'mai_grid_keep' ] as $marker ) {
			$this->assertArrayNotHasKey( $marker, $deferred->query_vars );
			$this->assertArrayNotHasKey( $marker, $deferred->query );
		}

		$this->assertObjectNotHasProperty( 'mai_grid_keep', $deferred );
		$this->assertObjectNotHasProperty( 'mai_grid_kept', $deferred );
	}

	/**
	 * Cache flags a site set itself must come back as set, including the one core rewrites:
	 * lazy_load_term_meta on turns update_post_term_cache back on.
	 */
	#[DataProvider( 'strategies' )]
	public function test_cache_flags_a_site_set_are_restored_as_set( string $strategy ): void {
		$flags = static fn( $query_args ) => array_merge( $query_args, [
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'lazy_load_term_meta'    => true,
		] );

		add_filter( 'mai_post_grid_query_args', $flags );

		$prepared   = $this->prepare( 'current_in_window' );
		$deferred   = $this->run_grid( $strategy, $prepared['args'] );
		$undeferred = $this->undeferred( $prepared['args'] );

		remove_filter( 'mai_post_grid_query_args', $flags );

		$this->assertStringNotContainsString( 'NOT IN', $deferred->request, 'must actually have deferred' );
		$this->assertSame( $undeferred->query, $deferred->query );
		$this->assertSame( $this->comparable_vars( $undeferred ), $this->comparable_vars( $deferred ) );
		$this->assertFalse( $deferred->query_vars['update_post_meta_cache'] );
		$this->assertTrue( $deferred->query_vars['update_post_term_cache'], 'core turns term priming on for lazy_load_term_meta' );
		$this->assertSame( $this->ids( $undeferred ), $this->ids( $deferred ) );
	}

	// ---- Grids that do not defer ----

	#[DataProvider( 'strategies' )]
	public function test_a_grid_that_does_not_defer_is_unchanged( string $strategy ): void {
		$prepared = $this->prepare( 'current_in_window' );
		$args     = $this->grid_args( [ 'offset' => 2 ] );
		$seen     = [];

		$watch = static function ( $posts, $query ) use ( &$seen ) {
			if ( ! empty( $query->query_vars['mai_cache'] ) ) {
				$seen[] = count( $posts );
			}

			return $posts;
		};

		add_filter( 'the_posts', $watch, 5, 2 );
		$query    = $this->run_grid( $strategy, $args );
		$baseline = $this->undeferred( $args );
		remove_filter( 'the_posts', $watch, 5 );

		$this->assertStringContainsString( 'NOT IN', $query->request, 'an offset grid keeps its excludes in the SQL' );
		// Select list taken out: core swaps $query->request for its split ID statement when it
		// runs the query itself, and leaves the full statement when a cache answers.
		$this->assertSame( $this->without_fields( $baseline->request ), $this->without_fields( $query->request ) );
		$this->assertSame( $this->ids( $baseline ), $this->ids( $query ) );
		$this->assertSame( [ self::PER_PAGE, self::PER_PAGE ], $seen, 'the_posts sees exactly the page asked for' );
		$this->assertSame( $this->comparable_vars( $baseline ), $this->comparable_vars( $query ) );
		$this->assertTrue( $query->query_vars['cache_results'] );
		$this->assertTrue( $query->query_vars['update_post_meta_cache'] );
		$this->assertArrayNotHasKey( 'mai_grid_keep', $query->query_vars );
		$this->assertNotContains( $prepared['excluded'][0], $this->ids( $query ) );
	}

	// ---- Caching switched off ----

	#[DataProvider( 'strategies' )]
	public function test_a_grid_with_caching_off_does_not_defer( string $strategy ): void {
		$prepared = $this->prepare( 'both_many' );

		add_filter( 'mai_post_grid_cache', '__return_false' );
		$query    = $this->run_grid( $strategy, $prepared['args'] );
		$baseline = $this->undeferred( $prepared['args'] );
		remove_filter( 'mai_post_grid_cache', '__return_false' );

		// Caching off means no deferring, so the excludes stay in the SQL and only the posts
		// the grid shows are ever read. That is loading only the kept posts already.
		$this->assertStringContainsString( 'NOT IN', $query->request );
		$this->assertStringContainsString( 'LIMIT 0, ' . self::PER_PAGE, $query->request );
		$this->assertSame( $this->ids( $baseline ), $this->ids( $query ) );
		$this->assertCount( self::PER_PAGE, $query->posts );
	}

	/**
	 * mai-cache refusing to store (SCRIPT_DEBUG, or the mai_can_cache filter) means there is no
	 * shared entry to gain, so the grid must not defer. Deferring would only turn off core's
	 * query cache under kept_only and pad the query under every strategy, for nothing.
	 */
	#[DataProvider( 'strategies' )]
	public function test_a_store_that_cannot_cache_does_not_defer( string $strategy ): void {
		$prepared = $this->prepare( 'displayed_many' );

		add_filter( 'mai_can_cache', '__return_false' );

		$baseline = $this->ids( $this->undeferred( $prepared['args'] ) );

		foreach ( [ 1, 2 ] as $run ) {
			[ $query, $stores ] = $this->count_stores( fn() => $this->run_grid( $strategy, $prepared['args'] ) );

			$this->assertStringContainsString( 'NOT IN', $query->request, "run {$run}: the excludes stay in the SQL" );
			$this->assertStringContainsString( 'LIMIT 0, ' . self::PER_PAGE, $query->request, "run {$run}: not padded" );
			$this->assertTrue( $query->query_vars['cache_results'], "run {$run}: core's query cache stays on" );
			$this->assertSame( $baseline, $this->ids( $query ), "run {$run}" );
			$this->assertSame( 0, $stores );
		}

		remove_filter( 'mai_can_cache', '__return_false' );
	}

	/**
	 * The result cache can decline a query at run time that the grid thought it would take:
	 * mai_query_cache sees the final query vars, which only exist once the query runs. kept_only
	 * then answers without reading or storing anything.
	 */
	public function test_a_query_the_cache_declines_at_run_time_still_loads_only_kept_posts(): void {
		$prepared = $this->prepare( 'displayed_many' );
		$window   = array_slice( $this->post_ids, 0, self::PER_PAGE + count( $prepared['excluded'] ) );

		// Only the padded query carries the tiebreak marker. The grid's own check runs before it is set.
		$decline = static fn( $cacheable, $query_vars ) => isset( $query_vars['mai_grid_tiebreak'] ) ? false : $cacheable;

		$baseline = $this->ids( $this->run_grid( 'current', $prepared['args'] ) );

		add_filter( 'mai_query_cache', $decline, 10, 2 );
		$this->clean_post_caches();
		[ $query, $stores ] = $this->count_stores( fn() => $this->run_grid( 'kept_only', $prepared['args'] ) );
		remove_filter( 'mai_query_cache', $decline, 10 );

		$this->assertSame( $baseline, $this->ids( $query ) );
		$this->assertSame( 0, $stores );

		foreach ( $this->cached( array_diff( $window, $baseline ) ) as $id => $state ) {
			$this->assertFalse( $state['row'], "dropped post {$id} must not be loaded" );
		}
	}

	// ---- Posts a the_posts callback adds ----

	#[DataProvider( 'strategies' )]
	public function test_a_post_pinned_to_the_top_survives( string $strategy ): void {
		$prepared = $this->prepare( 'current_in_window' );
		$pinned   = $this->post_ids[39];

		$pin = static function ( $posts, $query ) use ( $pinned ) {
			if ( ! empty( $query->query_vars['mai_cache'] ) ) {
				array_unshift( $posts, get_post( $pinned ) );
			}

			return $posts;
		};

		add_filter( 'the_posts', $pin, 20, 2 );

		$this->flush_result_cache();
		$miss = $this->ids( $this->run_grid( $strategy, $prepared['args'] ) );
		$hit  = $this->ids( $this->run_grid( $strategy, $prepared['args'] ) );

		$this->flush_result_cache();
		$current = $this->ids( $this->run_grid( 'current', $prepared['args'] ) );

		remove_filter( 'the_posts', $pin, 20 );

		$this->assertSame( $pinned, $miss[0] );
		$this->assertCount( self::PER_PAGE + 1, $miss, 'the pinned post widens the grid rather than pushing one out' );
		$this->assertSame( $current, $miss );
		$this->assertSame( $miss, $hit );
	}

	/**
	 * A the_posts callback can put an excluded post back, and so can core's sticky handling if a
	 * pre_get_posts callback turned stickies back on. kept_only has already dropped the excludes
	 * by then, so the grid has to drop them again, while still keeping every other post the
	 * callback added.
	 */
	#[DataProvider( 'strategies' )]
	public function test_an_excluded_post_a_the_posts_callback_adds_back_is_dropped( string $strategy ): void {
		$prepared = $this->prepare( 'current_in_window' );
		$excluded = $prepared['excluded'][0];

		$add_back = static function ( $posts, $query ) use ( $excluded ) {
			if ( ! empty( $query->query_vars['mai_cache'] ) ) {
				array_unshift( $posts, get_post( $excluded ) );
			}

			return $posts;
		};

		$this->flush_result_cache();
		$baseline = $this->ids( $this->run_grid( 'current', $prepared['args'] ) );

		add_filter( 'the_posts', $add_back, 20, 2 );

		$this->flush_result_cache();
		$miss = $this->ids( $this->run_grid( $strategy, $prepared['args'] ) );
		$hit  = $this->ids( $this->run_grid( $strategy, $prepared['args'] ) );

		remove_filter( 'the_posts', $add_back, 20 );

		$this->assertNotContains( $excluded, $miss );
		$this->assertNotContains( $excluded, $hit );
		$this->assertSame( $baseline, $miss );
		$this->assertSame( $baseline, $hit );
	}

	/**
	 * With sticky posts on, core adds the stickies that are missing from the results, and it
	 * reads post__not_in to know which to leave out (WP_Query::get_posts()). A deferring grid
	 * has taken its excludes out of post__not_in, so core would add the post being viewed right
	 * back. Such a grid must not defer.
	 */
	#[DataProvider( 'strategies' )]
	public function test_a_grid_with_sticky_posts_on_does_not_defer( string $strategy ): void {
		// Outside the window, so core fetches it and puts it first.
		$current = $this->post_ids[39];

		stick_post( $current );

		$stickies = static fn( $query_args ) => array_merge( $query_args, [ 'ignore_sticky_posts' => false ] );

		add_filter( 'mai_post_grid_query_args', $stickies );

		$this->go_to( get_permalink( $current ) );

		// No taxonomy, so the grid's query counts as the blog home, which is the only place
		// core puts stickies first.
		$args = $this->grid_args( [ 'query_by' => 'date', 'taxonomies' => [] ] );

		$this->flush_result_cache();
		$miss       = $this->run_grid( $strategy, $args );
		$hit        = $this->run_grid( $strategy, $args );
		$undeferred = $this->undeferred( $args );

		remove_filter( 'mai_post_grid_query_args', $stickies );
		unstick_post( $current );

		$this->assertStringContainsString( 'NOT IN', $miss->request, 'the excludes stay in the SQL' );
		$this->assertNotContains( $current, $this->ids( $miss ) );
		$this->assertNotContains( $current, $this->ids( $hit ) );
		$this->assertSame( $this->ids( $undeferred ), $this->ids( $miss ) );
		$this->assertSame( $this->ids( $undeferred ), $this->ids( $hit ) );
	}

	/**
	 * The one place kept_only shows something the current strategy does not: a post a
	 * the_posts callback appends. The current strategy slices after the_posts, so when nothing
	 * was dropped the padding row takes the widened slot and the appended post falls off.
	 * kept_only slices before the_posts, so the appended post stays, which is also what a grid
	 * that does not defer shows.
	 */
	public function test_kept_only_keeps_an_appended_post_like_a_grid_that_does_not_defer(): void {
		$prepared = $this->prepare( 'current_outside_window' );
		$pinned   = $this->post_ids[38];

		$pin = static function ( $posts, $query ) use ( $pinned ) {
			if ( ! empty( $query->query_vars['mai_cache'] ) ) {
				$posts[] = get_post( $pinned );
			}

			return $posts;
		};

		add_filter( 'the_posts', $pin, 20, 2 );

		$this->flush_result_cache();
		$kept_only = $this->ids( $this->run_grid( 'kept_only', $prepared['args'] ) );

		$this->flush_result_cache();
		$current = $this->ids( $this->run_grid( 'current', $prepared['args'] ) );

		$undeferred = $this->ids( $this->undeferred( $prepared['args'] ) );

		remove_filter( 'the_posts', $pin, 20 );

		$expected = array_merge( array_slice( $this->post_ids, 0, self::PER_PAGE ), [ $pinned ] );

		$this->assertSame( $expected, $undeferred );
		$this->assertSame( $expected, $kept_only );
		$this->assertSame( array_slice( $this->post_ids, 0, self::PER_PAGE + 1 ), $current, 'pinned so the difference is on record' );
	}

	// ---- When kept_only's ID query cannot stand in for the grid's ----

	public static function rewrites(): array {
		return [
			'posts_request rewrote only the grid statement' => [ 'posts_request' ],
			'posts_fields added a column'                   => [ 'posts_fields' ],
			'pre_get_posts turned counting on'              => [ 'pre_get_posts' ],
		];
	}

	/**
	 * kept_only answers a miss from its ID query only when that query is the grid's own query
	 * with the IDs selected, and nothing counts rows. Anything else falls back to the current
	 * strategy's filtering, and must still show the right posts, prime them, and store the
	 * padded list.
	 */
	#[DataProvider( 'rewrites' )]
	public function test_kept_only_falls_back_when_its_id_query_cannot_stand_in( string $rewrite ): void {
		$prepared = $this->prepare( 'both_many' );
		$padded   = self::PER_PAGE + count( $prepared['excluded'] );

		$callbacks = [
			// The ID query runs with mai_cache off, so this leaves it alone.
			'posts_request' => [
				'posts_request',
				static fn( $sql, $query ) => empty( $query->query_vars['mai_cache'] ) ? $sql : $sql . ' /* rewritten */',
			],
			// Both get the column, so the ID query selects more than the ID.
			'posts_fields'  => [
				'posts_fields',
				static fn( $fields, $query ) => empty( $query->query_vars['mai_grid_tiebreak'] ) ? $fields : $fields . ', 1 AS mai_test_column',
			],
			'pre_get_posts' => [
				'pre_get_posts',
				static function ( $query ) {
					if ( ! empty( $query->query_vars['mai_cache'] ) ) {
						$query->set( 'no_found_rows', false );
					}
				},
			],
		];

		[ $hook, $callback ] = $callbacks[ $rewrite ];

		add_filter( $hook, $callback, 10, 2 );

		$this->flush_result_cache();
		$baseline = $this->ids( $this->run_grid( 'current', $prepared['args'] ) );

		$seen  = [];
		$watch = static function ( $posts, $query ) use ( &$seen ) {
			if ( ! empty( $query->query_vars['mai_cache'] ) ) {
				$seen[] = count( $posts );
			}

			return $posts;
		};

		add_filter( 'the_posts', $watch, 5, 2 );

		$this->flush_result_cache();
		$this->clean_post_caches();
		[ $miss, $stores ] = $this->count_stores( fn() => $this->run_grid( 'kept_only', $prepared['args'] ) );
		$hit = $this->run_grid( 'kept_only', $prepared['args'] );

		remove_filter( 'the_posts', $watch, 5 );
		remove_filter( $hook, $callback, 10 );

		$this->assertSame( $baseline, $this->ids( $miss ) );
		$this->assertSame( $baseline, $this->ids( $hit ) );
		$this->assertSame( 1, $stores, 'the fallback stores through the_posts, once' );
		$this->assertSame( $padded, $seen[0], 'the miss fell back: the_posts saw the padded set' );
		$this->assertSame( self::PER_PAGE, $seen[1], 'the hit still answers with the kept posts only' );

		foreach ( $this->cached( $this->ids( $miss ) ) as $id => $state ) {
			$this->assertTrue( $state['meta'] && $state['terms'], "kept post {$id} must be primed on the fallback too" );
		}
	}

	/**
	 * A plugin that answers posts_pre_query after Mai, and steps aside when something already
	 * answered, must still get a kept_only miss. The Events Calendar's custom tables query does
	 * exactly that at priority 100. kept_only answers a miss last, so it must leave theirs
	 * standing, store it, and let Mai_Grid filter it. Their answer here is the padded window in
	 * reverse, so a grid that used Mai's own IDs instead would show different posts.
	 */
	public function test_a_plugin_answering_a_miss_after_mai_still_answers_it(): void {
		$prepared = $this->prepare( 'both_many' );
		$padded   = self::PER_PAGE + count( $prepared['excluded'] );
		$window   = array_slice( $this->post_ids, 0, $padded );
		$answered = 0;

		$theirs = static function ( $posts, $query ) use ( $window, &$answered ) {
			if ( null !== $posts || empty( $query->query_vars['mai_cache'] ) ) {
				return $posts;
			}

			++$answered;

			return array_map( 'get_post', array_reverse( $window ) );
		};

		add_filter( 'posts_pre_query', $theirs, 100, 2 );

		$this->flush_result_cache();
		[ $result, $stores ] = $this->count_stores( fn() => $this->capture_sql( fn() => $this->run_grid( 'kept_only', $prepared['args'] ) ) );
		[ $miss, $sql ]      = $result;
		$hit = $this->run_grid( 'kept_only', $prepared['args'] );

		remove_filter( 'posts_pre_query', $theirs, 100 );

		$this->assertSame( [], $this->id_selects( $sql ), 'no ID query runs once another callback answered' );

		$expected = array_slice( array_values( array_diff( array_reverse( $window ), $prepared['excluded'] ) ), 0, self::PER_PAGE );

		$this->assertSame( 1, $answered, 'their callback answers the miss, and steps aside on the hit' );
		$this->assertSame( $expected, $this->ids( $miss ), 'their answer, with the excludes dropped by Mai_Grid' );
		$this->assertSame( 1, $stores );
		$this->assertSame( $expected, $this->ids( $hit ), 'the hit serves what they answered' );
	}

	/**
	 * The Events Calendar answers posts_pre_query at priority 100 with a copy of the grid's
	 * query: Custom_Tables_Query::from_wp_query() builds a new query from the grid's query and
	 * query_vars and runs it. Anything Mai keeps in the query vars reaches that copy too. The
	 * kept-only marker must not, or the copy comes back trimmed to the kept posts, and the
	 * grid's miss stores that trimmed list as the shared padded entry. The next article then
	 * drops its own post from a list that has no spare and shows one short.
	 */
	#[DataProvider( 'strategies' )]
	public function test_a_plugin_answering_with_a_copy_of_the_query_leaves_the_entry_padded( string $strategy ): void {
		$args    = $this->grid_args();
		$running = false;

		$theirs = static function ( $posts, $query ) use ( &$running ) {
			if ( $running || null !== $posts || empty( $query->query_vars['mai_cache'] ) ) {
				return $posts;
			}

			// What from_wp_query() does: a fresh query carrying the grid's args and vars.
			$copy             = new WP_Query();
			$copy->query      = $query->query;
			$copy->query_vars = $query->query_vars;

			$running = true;
			$answer  = $copy->get_posts();
			$running = false;

			return $answer;
		};

		$key     = '';
		$capture = static function ( $posts, $query ) use ( &$key ) {
			// The grid's own query reaches priority 9 first. The copy runs inside priority 100.
			if ( '' === $key && ! empty( $query->query_vars['mai_cache'] ) ) {
				$key = ( new Mai_Query_Cache() )->cache_key( $query->query_vars, (string) $query->request );
			}

			return $posts;
		};

		add_filter( 'posts_pre_query', $theirs, 100, 2 );
		add_filter( 'posts_pre_query', $capture, 9, 2 );

		$this->flush_result_cache();

		$this->go_to( get_permalink( $this->post_ids[0] ) );
		$first = $this->ids( $this->run_grid( $strategy, $args ) );

		remove_filter( 'posts_pre_query', $capture, 9 );

		$stored = mai_cache( 'grid' )->read_swr( $key, mai_cache( 'grid' )->version( [ 'post' ] ) );

		$this->go_to( get_permalink( $this->post_ids[1] ) );
		$second = $this->ids( $this->run_grid( $strategy, $args ) );

		remove_filter( 'posts_pre_query', $theirs, 100 );

		$this->assertSame( array_slice( $this->post_ids, 1, self::PER_PAGE ), $first );
		$this->assertSame( array_slice( $this->post_ids, 0, self::PER_PAGE + 1 ), $stored['value']['ids'], 'the padded list, excluded post included' );
		$this->assertSame(
			array_merge( [ $this->post_ids[0] ], array_slice( $this->post_ids, 2, self::PER_PAGE - 1 ) ),
			$second,
			'a full grid without the post being viewed'
		);
	}

	/**
	 * The other way round: a later callback replaces the kept answer Mai served from cache.
	 * What it hands back can hold the excluded posts, so the grid has to filter it.
	 */
	public function test_a_hit_replaced_by_a_later_callback_is_still_filtered(): void {
		$prepared = $this->prepare( 'both_many' );
		$window   = array_slice( $this->post_ids, 0, self::PER_PAGE + count( $prepared['excluded'] ) );

		$this->flush_result_cache();
		$baseline = $this->ids( $this->run_grid( 'kept_only', $prepared['args'] ) );

		$replace = static function ( $posts, $query ) use ( $window ) {
			return ( null !== $posts && ! empty( $query->query_vars['mai_cache'] ) ) ? array_map( 'get_post', $window ) : $posts;
		};

		add_filter( 'posts_pre_query', $replace, 20, 2 );
		$hit = $this->run_grid( 'kept_only', $prepared['args'] );
		remove_filter( 'posts_pre_query', $replace, 20 );

		$this->assertSame( $baseline, $this->ids( $hit ) );
		$this->assertSame( [], array_intersect( $this->ids( $hit ), $prepared['excluded'] ) );
	}
}
