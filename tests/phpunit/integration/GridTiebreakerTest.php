<?php
declare(strict_types=1);

namespace BizBudding\MaiEngine\Tests\Integration;

use Mai_Grid;
use Mai_Post_Grid_Query_Optimizer_Sql;
use PHPUnit\Framework\Attributes\DataProvider;
use WP_Query;

/**
 * Which grids get the ID tiebreaker, and what it does to the order.
 *
 * Every post grid that does not count rows gets an ID tiebreaker on its ORDER BY, whether or
 * not it defers its excludes. A date or author sort breaks ties by ID in its own direction. Any
 * other sort breaks ties newest first, by post date and then ID. A grid that counts rows, which
 * is what Mai Load More turns on, is left out, because its second page is built from the grid's
 * saved args and would be ordered without it. The marker that asks for the tiebreaker lives on
 * the query only while it runs.
 */
final class GridTiebreakerTest extends MaiIntegrationTestCase {

	private const PER_PAGE = 3;

	/** @var int[] Newest first. */
	private array $post_ids = [];

	private int $term_id = 0;

	public function set_up(): void {
		parent::set_up();

		$this->term_id = self::factory()->term->create( [ 'taxonomy' => 'category' ] );

		// Ten posts one day apart, all with menu_order 0. Index 0 is the newest and has the
		// lowest ID, so ordering by date and ordering by ID disagree.
		for ( $i = 0; $i < 10; $i++ ) {
			$this->post_ids[] = self::factory()->post->create( [
				'post_status'   => 'publish',
				'post_date'     => gmdate( 'Y-m-d H:i:s', strtotime( "-{$i} days" ) ),
				'post_category' => [ $this->term_id ],
			] );
		}
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

	/** The grid's query, run the way a page run does. */
	private function run_grid( array $overrides = [] ): WP_Query {
		return ( new Mai_Grid( $this->grid_args( $overrides ) ) )->get_query();
	}

	/** The text between ORDER BY and LIMIT in a statement. */
	private function order_by( WP_Query $query ): string {
		$this->assertSame( 1, preg_match( '/\sORDER BY\s+(.+?)(?:\s+LIMIT\s+[\d, ]+)?\s*$/s', (string) $query->request, $matches ), 'the statement has an ORDER BY' );

		return trim( $matches[1] );
	}

	/** Makes every post share a date, so the sort field and the date both tie. */
	private function make_the_dates_tie(): void {
		$same = gmdate( 'Y-m-d H:i:s', strtotime( '-1 hour' ) );

		foreach ( $this->post_ids as $id ) {
			wp_update_post( [ 'ID' => $id, 'post_date' => $same, 'post_date_gmt' => $same ] );
		}
	}

	/**
	 * Records the mai_grid_tiebreak query var while the query runs, one entry per query.
	 *
	 * Runs at posts_orderby priority 98, one before the tiebreaker callback at 99. Pass the
	 * returned callback to remove_filter() at priority 98.
	 *
	 * @param array $seen Receives the value of the var, or null when it is absent.
	 */
	private function watch_the_var( array &$seen ): callable {
		$watch = static function ( $orderby, $query ) use ( &$seen ) {
			$seen[] = $query->query_vars['mai_grid_tiebreak'] ?? null;

			return $orderby;
		};

		add_filter( 'posts_orderby', $watch, 98, 2 );

		return $watch;
	}

	// ---- Grids that get it ----

	public function test_a_grid_without_excludes_gets_the_tiebreaker(): void {
		global $wpdb;

		$query = $this->run_grid();

		$this->assertStringContainsString( "ORDER BY {$wpdb->posts}.post_date DESC", (string) $query->request, 'the grid sorts by date' );
		$this->assertStringEndsWith( ", {$wpdb->posts}.ID DESC", $this->order_by( $query ) );
	}

	public function test_a_sort_that_is_not_by_date_breaks_ties_newest_first(): void {
		global $wpdb;

		// Every post has menu_order 0, so the sort field decides nothing. The dates differ, and
		// index 0 is the newest with the lowest ID, so date and ID disagree.
		$query = $this->run_grid( [ 'orderby' => 'menu_order', 'order' => 'ASC' ] );

		$this->assertStringEndsWith( ", {$wpdb->posts}.post_date DESC, {$wpdb->posts}.ID DESC", $this->order_by( $query ) );
		$this->assertSame( array_slice( $this->post_ids, 0, self::PER_PAGE ), wp_list_pluck( $query->posts, 'ID' ), 'the newest dates show first' );
	}

	public function test_a_sort_that_is_not_by_date_shows_the_highest_id_among_equal_dates(): void {
		$this->make_the_dates_tie();

		$query    = $this->run_grid( [ 'orderby' => 'menu_order', 'order' => 'ASC' ] );
		$expected = $this->post_ids;
		rsort( $expected );

		$this->assertSame( array_slice( $expected, 0, self::PER_PAGE ), wp_list_pluck( $query->posts, 'ID' ), 'the highest IDs show first' );
	}

	/**
	 * The whole ORDER BY a grid ends up with, by sort. `{posts}` stands for the posts table.
	 *
	 * @return array<string,array{0:array,1:string}>
	 */
	public static function sorts(): array {
		return [
			'date, ascending'                  => [ [ 'orderby' => 'date', 'order' => 'ASC' ], '{posts}.post_date ASC, {posts}.ID ASC' ],
			'date, descending'                 => [ [ 'orderby' => 'date', 'order' => 'DESC' ], '{posts}.post_date DESC, {posts}.ID DESC' ],
			'author, ascending'                => [ [ 'orderby' => 'author', 'order' => 'ASC' ], '{posts}.post_author ASC, {posts}.ID ASC' ],
			'author, descending'               => [ [ 'orderby' => 'author', 'order' => 'DESC' ], '{posts}.post_author DESC, {posts}.ID DESC' ],
			'menu order, ascending'            => [ [ 'orderby' => 'menu_order', 'order' => 'ASC' ], '{posts}.menu_order ASC, {posts}.post_date DESC, {posts}.ID DESC' ],
			'menu order, descending'           => [ [ 'orderby' => 'menu_order', 'order' => 'DESC' ], '{posts}.menu_order DESC, {posts}.post_date DESC, {posts}.ID DESC' ],
			'title, ascending'                 => [ [ 'orderby' => 'title', 'order' => 'ASC' ], '{posts}.post_title ASC, {posts}.post_date DESC, {posts}.ID DESC' ],
			'date, then menu order'            => [ [ 'orderby' => [ 'post_date' => 'DESC', 'menu_order' => 'ASC' ] ], '{posts}.post_date DESC, {posts}.menu_order ASC, {posts}.ID DESC' ],
			'a sort that already names the ID' => [ [ 'orderby' => 'ID', 'order' => 'ASC' ], '{posts}.ID ASC' ],
		];
	}

	#[DataProvider( 'sorts' )]
	public function test_the_tiebreaker_by_sort( array $overrides, string $expected ): void {
		global $wpdb;

		$this->assertSame( str_replace( '{posts}', $wpdb->posts, $expected ), $this->order_by( $this->run_grid( $overrides ) ) );
	}

	public function test_a_date_sort_ascending_is_still_swapped(): void {
		global $wpdb;

		$this->assertTrue( Mai_Post_Grid_Query_Optimizer_Sql::orderby_ok( $this->order_by( $this->run_grid( [ 'orderby' => 'date', 'order' => 'ASC' ] ) ), $wpdb->posts ), 'date ascending' );
		$this->assertTrue( Mai_Post_Grid_Query_Optimizer_Sql::orderby_ok( $this->order_by( $this->run_grid( [ 'orderby' => 'author', 'order' => 'ASC' ] ) ), $wpdb->posts ), 'author ascending' );
	}

	/**
	 * ORDER BY clauses as they arrive, and the same clause once the tiebreaker is added. `{posts}`
	 * stands for the posts table. Each is run straight through the method, so the last key can be
	 * anything.
	 *
	 * @return array<string,array{0:string,1:string}>
	 */
	public static function clauses(): array {
		return [
			'date, no direction is ascending'    => [ '{posts}.post_date', '{posts}.post_date, {posts}.ID ASC' ],
			'date, direction in lower case'      => [ '{posts}.post_date desc', '{posts}.post_date desc, {posts}.ID DESC' ],
			'author, no direction is ascending'  => [ '{posts}.post_author', '{posts}.post_author, {posts}.ID ASC' ],
			'a key after a top-level comma'      => [ 'mt1.meta_value+0 DESC, {posts}.post_date ASC', 'mt1.meta_value+0 DESC, {posts}.post_date ASC, {posts}.ID ASC' ],
			'date, then another key'             => [ '{posts}.post_date DESC, {posts}.menu_order ASC', '{posts}.post_date DESC, {posts}.menu_order ASC, {posts}.ID DESC' ],
			'author, then date'                  => [ '{posts}.post_author ASC, {posts}.post_date DESC', '{posts}.post_author ASC, {posts}.post_date DESC, {posts}.ID DESC' ],
			'the GMT date is not the date'       => [ '{posts}.post_date_gmt ASC', '{posts}.post_date_gmt ASC, {posts}.post_date DESC, {posts}.ID DESC' ],
			'the date inside a function'         => [ 'COALESCE(mt1.meta_value, {posts}.post_date) ASC', 'COALESCE(mt1.meta_value, {posts}.post_date) ASC, {posts}.ID DESC' ],
			'the date of another table'          => [ 'other_posts.post_date ASC', 'other_posts.post_date ASC, {posts}.post_date DESC, {posts}.ID DESC' ],
			'random order gets the general rule' => [ 'RAND()', 'RAND(), {posts}.post_date DESC, {posts}.ID DESC' ],
			'a sort with no date'                => [ '{posts}.comment_count DESC', '{posts}.comment_count DESC, {posts}.post_date DESC, {posts}.ID DESC' ],
			'a clause that names the ID'         => [ 'FIELD({posts}.ID, 3,2,1)', 'FIELD({posts}.ID, 3,2,1)' ],
			'an empty clause'                    => [ '', '' ],
		];
	}

	#[DataProvider( 'clauses' )]
	public function test_the_tiebreaker_by_clause( string $orderby, string $expected ): void {
		global $wpdb;

		$query             = new WP_Query();
		$query->query_vars = [ 'mai_grid_tiebreak' => true ];

		$this->assertSame(
			str_replace( '{posts}', $wpdb->posts, $expected ),
			Mai_Grid::add_grid_orderby_tiebreaker( str_replace( '{posts}', $wpdb->posts, $orderby ), $query )
		);
	}

	public function test_a_query_without_the_marker_is_left_alone(): void {
		global $wpdb;

		$query             = new WP_Query();
		$query->query_vars = [];

		$this->assertSame( "{$wpdb->posts}.menu_order ASC", Mai_Grid::add_grid_orderby_tiebreaker( "{$wpdb->posts}.menu_order ASC", $query ) );
	}

	public function test_a_deferring_grid_on_a_sort_that_is_not_by_date_breaks_ties_newest_first(): void {
		global $wpdb;

		// The newest and lowest ID, so it would lead the grid if it were not excluded.
		$current = $this->post_ids[0];
		$this->go_to( get_permalink( $current ) );

		$query = $this->run_grid( [ 'orderby' => 'menu_order', 'order' => 'ASC', 'excludes' => [ 'exclude_current' ] ] );

		$this->assertStringContainsString( 'LIMIT 0, 4', (string) $query->request, 'must actually have deferred: 3 asked for, plus the 1 excluded' );
		$this->assertStringEndsWith( ", {$wpdb->posts}.post_date DESC, {$wpdb->posts}.ID DESC", $this->order_by( $query ) );
		$this->assertSame( array_slice( $this->post_ids, 1, self::PER_PAGE ), wp_list_pluck( $query->posts, 'ID' ) );
	}

	public function test_tied_dates_show_the_highest_ids_first(): void {
		$this->make_the_dates_tie();

		$expected = $this->post_ids;
		rsort( $expected );

		$this->assertSame( array_slice( $expected, 0, self::PER_PAGE ), wp_list_pluck( $this->run_grid()->posts, 'ID' ) );
	}

	// ---- Grids that do not ----

	public function test_load_more_grids_do_not(): void {
		global $wpdb;

		// Mai Load More sets this through the same filter, before get_query() decides.
		$counts_rows = static function ( $query_args ) {
			$query_args['no_found_rows'] = false;

			return $query_args;
		};

		// The same grid without the filter does get it, or the check below proves nothing.
		$this->assertStringEndsWith( ", {$wpdb->posts}.ID DESC", $this->order_by( $this->run_grid() ), 'without the filter' );

		add_filter( 'mai_post_grid_query_args', $counts_rows );

		$seen  = [];
		$watch = $this->watch_the_var( $seen );
		$query = $this->run_grid();

		remove_filter( 'posts_orderby', $watch, 98 );
		remove_filter( 'mai_post_grid_query_args', $counts_rows );

		$this->assertStringNotContainsString( "{$wpdb->posts}.ID", $this->order_by( $query ), 'a grid that counts rows keeps its own order' );
		$this->assertNotEmpty( $seen, 'the grid query reached posts_orderby' );
		$this->assertSame( [], array_filter( $seen ), 'and never carried the marker' );
	}

	// ---- The marker ----

	/** @return array<string,array{0:bool}> */
	public static function defers_or_not(): array {
		return [
			'a grid that defers its excludes' => [ true ],
			'a grid that does not'            => [ false ],
		];
	}

	#[DataProvider( 'defers_or_not' )]
	public function test_the_var_is_removed_afterwards( bool $defers ): void {
		$this->go_to( get_permalink( $this->post_ids[0] ) );

		$seen  = [];
		$watch = $this->watch_the_var( $seen );
		$query = $this->run_grid( $defers ? [ 'excludes' => [ 'exclude_current' ] ] : [] );

		remove_filter( 'posts_orderby', $watch, 98 );

		// Without the first two, the checks below them pass with the feature off.
		$this->assertNotEmpty( $seen, 'the grid query reached posts_orderby' );
		$this->assertContains( true, $seen, 'the marker was on the query while it ran' );
		$this->assertSame( $defers, str_contains( (string) $query->request, 'LIMIT 0, 4' ), 'the grid deferred, or did not, as asked' );

		// What Mai Load More and custom pagination serialize and run again.
		$this->assertArrayNotHasKey( 'mai_grid_tiebreak', $query->query_vars );
		$this->assertArrayNotHasKey( 'mai_grid_tiebreak', $query->query );
	}
}
