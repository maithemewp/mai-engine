<?php
declare(strict_types=1);

namespace BizBudding\MaiEngine\Tests\Integration;

use Mai_Grid;
use Mai_Query_Cache;
use WP_Query;

/**
 * A grid whose query fails stores nothing.
 *
 * A failed statement returns no rows. Stored, that would show an empty grid until the entry
 * expired. The grids here do not defer, so core runs the grid's own statement and the_posts
 * is where a miss would be stored.
 *
 * These grids keep cache_results on, and core keeps a failed query's empty result in its own
 * query cache like any other, so a second render in the same request could be answered with
 * no SQL. The tests empty the object cache between renders, which is what a new request does
 * on a site without a persistent object cache.
 */
final class GridCacheFailedQueryTest extends MaiIntegrationTestCase {

	private const PER_PAGE = 3;

	/** @var int[] Newest first. */
	private array $post_ids = [];

	private int $term_id = 0;

	/** @var string[] The grid statement's error as posts_results saw it, one per render. */
	private array $errors = [];

	public function set_up(): void {
		parent::set_up();

		$this->term_id = self::factory()->term->create( [ 'taxonomy' => 'category' ] );

		// Five posts one day apart. Index 0 is the newest. Factory titles all hold "title".
		for ( $i = 0; $i < 5; $i++ ) {
			$this->post_ids[] = self::factory()->post->create( [
				'post_status'   => 'publish',
				'post_date'     => gmdate( 'Y-m-d H:i:s', strtotime( "-{$i} days" ) ),
				'post_category' => [ $this->term_id ],
			] );
		}

		$this->errors = [];

		add_filter( 'posts_results', [ $this, 'record_error' ], 10, 2 );

		( new Mai_Query_Cache() )->flush_all();
	}

	public function tear_down(): void {
		remove_filter( 'posts_results', [ $this, 'record_error' ], 10 );

		parent::tear_down();
	}

	/**
	 * posts_results watcher: records the database error a grid query left behind, so a test
	 * can confirm its statement really failed. The result cache's own posts_results callback
	 * runs no SQL, so the error is still the grid statement's here.
	 *
	 * @param array    $posts The posts.
	 * @param WP_Query $query The query.
	 *
	 * @return array
	 */
	public function record_error( $posts, $query ) {
		global $wpdb;

		if ( ! empty( $query->query_vars['mai_cache'] ) ) {
			$this->errors[] = (string) $wpdb->last_error;
		}

		return $posts;
	}

	// ---- Helpers ----

	/** A grid that does not defer: no excludes. */
	private function grid_args(): array {
		return [
			'type'           => 'post',
			'post_type'      => [ 'post' ],
			'query_by'       => 'tax_meta',
			'posts_per_page' => self::PER_PAGE,
			'excludes'       => [],
			'taxonomies'     => [
				[ 'taxonomy' => 'category', 'terms' => [ $this->term_id ], 'current' => false, 'operator' => 'IN' ],
			],
		];
	}

	private function run_grid(): WP_Query {
		return ( new Mai_Grid( $this->grid_args() ) )->get_query();
	}

	/**
	 * Adds clauses to the grid's WHERE while the callback runs: a LIKE '%title%', which
	 * $wpdb->prepare() writes with its placeholder escape, and a column that does not exist,
	 * which makes the statement fail. Database errors are not printed meanwhile.
	 *
	 * @return mixed What the callback returns.
	 */
	private function with_where( bool $like, bool $broken, callable $callback ) {
		global $wpdb;

		$where = static function ( $where, $query ) use ( $like, $broken, $wpdb ) {
			if ( empty( $query->query_vars['mai_cache'] ) ) {
				return $where;
			}

			if ( $like ) {
				$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_title LIKE %s", '%' . $wpdb->esc_like( 'title' ) . '%' );
			}

			if ( $broken ) {
				$where .= ' AND mai_no_such_column = 1';
			}

			return $where;
		};

		add_filter( 'posts_where', $where, 10, 2 );
		$suppress = $wpdb->suppress_errors( true );

		try {
			return $callback();
		} finally {
			$wpdb->suppress_errors( $suppress );
			remove_filter( 'posts_where', $where, 10 );
		}
	}

	/**
	 * Runs the grid, and returns the query, how many results were stored, how many grid
	 * statements ran, and the ID lists stored.
	 *
	 * mai-cache writes through transients here (no persistent object cache in this suite), and
	 * a stored result is the only envelope whose value carries `ids`. A grid statement is one
	 * against the posts table with a LIMIT. Priming reads have none.
	 *
	 * @return array{0:WP_Query,1:int,2:int,3:array<int[]>}
	 */
	private function render(): array {
		global $wpdb;

		$stored  = [];
		$selects = 0;

		$count_stores = static function ( $transient, $value ) use ( &$stored ) {
			if ( is_array( $value ) && isset( $value['value']['ids'] ) ) {
				$stored[] = $value['value']['ids'];
			}
		};

		$count_selects = static function ( $sql ) use ( &$selects, $wpdb ) {
			if ( preg_match( '/\bFROM\s+' . preg_quote( $wpdb->posts, '/' ) . '\b/', $sql ) && str_contains( $sql, 'LIMIT' ) ) {
				++$selects;
			}

			return $sql;
		};

		add_action( 'set_transient', $count_stores, 10, 2 );
		add_filter( 'query', $count_selects );

		$query = $this->run_grid();

		remove_filter( 'query', $count_selects );
		remove_action( 'set_transient', $count_stores, 10 );

		return [ $query, count( $stored ), $selects, $stored ];
	}

	/** Whether a statement is the grid's own: an ID-only select from the posts table with a LIMIT. */
	private static function is_grid_statement( string $sql ): bool {
		global $wpdb;

		return (bool) preg_match( '/^\s*SELECT\s+(?:SQL_CALC_FOUND_ROWS\s+)?(?:DISTINCT\s+)?' . preg_quote( $wpdb->posts, '/' ) . '\.ID\s+FROM\s/i', $sql ) && str_contains( $sql, 'LIMIT' );
	}

	/** What a new request starts with on a site without a persistent object cache. */
	private function new_request(): void {
		wp_cache_flush();
	}

	// ---- Tests ----

	public function test_failed_query_is_not_stored(): void {
		[ [ $failed, $failed_stores ], [ $next, $next_stores, $next_selects ] ] = $this->with_where(
			false,
			true,
			function () {
				$first = $this->render();
				$this->new_request();

				return [ $first, $this->render() ];
			}
		);

		$this->assertNotSame( '', $this->errors[0], 'the grid statement failed' );
		$this->assertSame( [], $failed->posts );
		$this->assertSame( 0, $failed_stores, 'nothing stored from the failed query' );
		$this->assertSame( 1, $next_selects, 'the next request runs the query again' );
		$this->assertSame( 0, $next_stores, 'and stores nothing while it still fails' );
		$this->assertSame( [], $next->posts );
	}

	/**
	 * $wpdb records last_query after the query filter, where it strips its placeholder escape,
	 * so a grid whose SQL holds a LIKE is compared without the escape too.
	 */
	public function test_failed_query_with_a_like_is_not_stored(): void {
		global $wpdb;

		[ [ $failed, $failed_stores ], [ , $next_stores, $next_selects ] ] = $this->with_where(
			true,
			true,
			function () {
				$first = $this->render();
				$this->new_request();

				return [ $first, $this->render() ];
			}
		);

		$this->assertStringContainsString( $wpdb->placeholder_escape(), $failed->request, 'the grid SQL holds the placeholder escape' );
		$this->assertNotSame( '', $this->errors[0], 'the grid statement failed' );
		$this->assertSame( 0, $failed_stores, 'nothing stored from the failed query' );
		$this->assertSame( 1, $next_selects, 'the next request runs the query again' );
		$this->assertSame( 0, $next_stores );
	}

	public function test_a_like_query_that_succeeds_is_stored_and_served(): void {
		[ [ $miss, $miss_stores ], [ $hit, $hit_stores, $hit_selects ] ] = $this->with_where(
			true,
			false,
			function () {
				$first = $this->render();
				$this->new_request();

				return [ $first, $this->render() ];
			}
		);

		$this->assertSame( [], array_filter( $this->errors ), 'no grid statement failed' );
		$this->assertSame( array_slice( $this->post_ids, 0, self::PER_PAGE ), wp_list_pluck( $miss->posts, 'ID' ) );
		$this->assertSame( 1, $miss_stores );
		$this->assertSame( 0, $hit_selects, 'the next request is served from the stored entry' );
		$this->assertSame( 0, $hit_stores );
		$this->assertSame( wp_list_pluck( $miss->posts, 'ID' ), wp_list_pluck( $hit->posts, 'ID' ) );
	}

	/**
	 * Only the grid's own statement failing counts. When core answers the grid from its query
	 * cache, the statement never runs, and a failed statement some callback ran on the way
	 * must not stop the store.
	 */
	public function test_a_failed_statement_elsewhere_does_not_stop_the_store(): void {
		global $wpdb;

		// Warms core's query cache for the grid, then empties the result cache only.
		$warm = $this->run_grid();
		( new Mai_Query_Cache() )->flush_all();

		// Runs after the result cache's own reads on posts_pre_query, which query the options
		// table, so the failed statement is the last one before posts_results. It has no LIMIT,
		// so it is not counted as a grid statement.
		$noise = static function ( $posts, $query ) use ( $wpdb ) {
			if ( ! empty( $query->query_vars['mai_cache'] ) ) {
				$wpdb->query( "SELECT mai_no_such_column FROM {$wpdb->posts} WHERE 1=0" );
			}

			return $posts;
		};

		add_filter( 'posts_pre_query', $noise, 20, 2 );
		$suppress = $wpdb->suppress_errors( true );

		[ $query, $stores, $selects ] = $this->render();

		$wpdb->suppress_errors( $suppress );
		remove_filter( 'posts_pre_query', $noise, 20 );

		$this->assertSame( 0, $selects, 'core answered the grid from its query cache' );
		$this->assertNotSame( '', end( $this->errors ), 'a failed statement was the last one run' );
		$this->assertSame( 1, $stores, 'the grid did not fail, so its result is stored' );
		$this->assertSame( wp_list_pluck( $warm->posts, 'ID' ), wp_list_pluck( $query->posts, 'ID' ) );
	}

	// ---- Core's query cache forgets a failed query ----

	/**
	 * Core keeps a failed statement's empty result in its query cache, under a key salted with
	 * the posts last_changed time. Moving that time on, as a post save does, makes the entry
	 * unreachable. Here MySQL refuses the statement without its text changing, so the next view
	 * would look up the same entry.
	 */
	public function test_a_failed_query_moves_posts_last_changed(): void {
		$before = wp_cache_get_last_changed( 'posts' );

		[ [ $query, $stores ], $refused ] = $this->refuse_once( fn( $sql ) => self::is_grid_statement( $sql ), fn() => $this->render() );

		$this->assertTrue( $refused, 'the grid statement was refused' );
		$this->assertNotSame( '', $this->errors[0], 'and failed' );
		$this->assertSame( [], $query->posts );
		$this->assertSame( 0, $stores );
		$this->assertNotSame( $before, wp_cache_get_last_changed( 'posts' ) );
	}

	/**
	 * The next view, with the object cache as the failed view left it, runs the query again and
	 * stores the real posts. Otherwise core would answer it from its cache with no SQL, the
	 * result cache would see no error, and the empty list would be stored.
	 */
	public function test_after_a_failed_query_the_next_view_runs_it_and_stores_the_real_posts(): void {
		[ , $refused ] = $this->refuse_once( fn( $sql ) => self::is_grid_statement( $sql ), fn() => $this->render() );

		[ $next, $stores, $selects, $stored ] = $this->render();

		$expected = array_slice( $this->post_ids, 0, self::PER_PAGE );

		$this->assertTrue( $refused, 'the first view was refused' );
		$this->assertSame( 1, $selects, 'the next view runs the grid statement' );
		$this->assertSame( $expected, wp_list_pluck( $next->posts, 'ID' ) );
		$this->assertSame( 1, $stores );
		$this->assertSame( [ $expected ], $stored, 'and stores the real posts' );
	}

	public function test_a_successful_query_leaves_posts_last_changed_alone(): void {
		$before = wp_cache_get_last_changed( 'posts' );

		[ , $miss_stores ] = $this->render();
		[ , $hit_stores ]  = $this->render();

		$this->assertSame( 1, $miss_stores );
		$this->assertSame( 0, $hit_stores );
		$this->assertSame( $before, wp_cache_get_last_changed( 'posts' ) );
	}
}
