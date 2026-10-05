<?php
declare(strict_types=1);

namespace BizBudding\MaiEngine\Tests\Integration;

use Mai_Grid;
use PHPUnit\Framework\Attributes\DataProvider;
use WP_Query;

/**
 * Which grids get the ID tiebreaker, and what it does to the order.
 *
 * Every post grid that does not count rows ends its ORDER BY with `{posts}.ID DESC`, whatever
 * its sort and whether or not it defers its excludes. A grid that counts rows, which is what
 * Mai Load More turns on, is left out, because its second page is built from the grid's
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

	public function test_ascending_sorts_break_ties_newest_first(): void {
		global $wpdb;

		// Every post has menu_order 0, so the sort field decides nothing.
		$query = $this->run_grid( [ 'orderby' => 'menu_order', 'order' => 'ASC' ] );

		$this->assertStringEndsWith( ", {$wpdb->posts}.ID DESC", $this->order_by( $query ), 'a descending tiebreaker on an ascending sort' );

		$expected = $this->post_ids;
		rsort( $expected );

		$this->assertSame( array_slice( $expected, 0, self::PER_PAGE ), wp_list_pluck( $query->posts, 'ID' ), 'the highest IDs show first' );
	}

	public function test_a_deferring_grid_on_an_ascending_sort_breaks_ties_newest_first(): void {
		global $wpdb;

		// The highest ID, so it would lead the grid if it were not excluded.
		$current = $this->post_ids[9];
		$this->go_to( get_permalink( $current ) );

		$query = $this->run_grid( [ 'orderby' => 'menu_order', 'order' => 'ASC', 'excludes' => [ 'exclude_current' ] ] );

		$this->assertStringContainsString( 'LIMIT 0, 4', (string) $query->request, 'must actually have deferred: 3 asked for, plus the 1 excluded' );
		$this->assertStringEndsWith( ", {$wpdb->posts}.ID DESC", $this->order_by( $query ) );

		$expected = array_diff( $this->post_ids, [ $current ] );
		rsort( $expected );

		$this->assertSame( array_slice( $expected, 0, self::PER_PAGE ), wp_list_pluck( $query->posts, 'ID' ) );
	}

	public function test_tied_dates_show_the_highest_ids_first(): void {
		$this->make_the_dates_tie();

		$expected = $this->post_ids;
		rsort( $expected );

		$this->assertSame( array_slice( $expected, 0, self::PER_PAGE ), wp_list_pluck( $this->run_grid()->posts, 'ID' ) );
	}

	public function test_a_sort_that_already_names_the_id_is_left_alone(): void {
		global $wpdb;

		$query = $this->run_grid( [ 'orderby' => 'ID', 'order' => 'ASC' ] );

		$this->assertSame( "{$wpdb->posts}.ID ASC", $this->order_by( $query ) );
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

		// Without these two the checks below pass with the feature off.
		$this->assertNotEmpty( $seen, 'the grid query reached posts_orderby' );
		$this->assertContains( true, $seen, 'the marker was on the query while it ran' );
		$this->assertSame( $defers, str_contains( (string) $query->request, 'LIMIT 0, 4' ), 'the grid deferred, or did not, as asked' );

		// What Mai Load More and custom pagination serialize and run again.
		$this->assertArrayNotHasKey( 'mai_grid_tiebreak', $query->query_vars );
		$this->assertArrayNotHasKey( 'mai_grid_tiebreak', $query->query );
	}
}
