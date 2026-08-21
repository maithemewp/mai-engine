<?php

namespace BizBudding\MaiEngine\Tests\Integration;

use Mai_Grid;
use Mai_Query_Cache;

/**
 * Real WP_Query equivalence for the deferred-exclude path.
 *
 * Contract: a grid that defers must render the same ordered posts as one that does not, and
 * must hand back a query object that still describes the original request. The second half is
 * what keeps Mai Load More and any custom pagination working, since they serialize query_vars.
 */
final class GridDeferredExcludesTest extends MaiIntegrationTestCase {

	/** @var int[] Newest first. */
	private $post_ids = [];

	/** @var int */
	private $term_id;

	public function set_up() {
		parent::set_up();

		$this->term_id = self::factory()->term->create( [ 'taxonomy' => 'category' ] );

		// Ten posts one day apart. Index 0 is the newest, so post_date DESC returns them in
		// creation order. Distinct dates, so no tie ambiguity in these tests.
		for ( $i = 0; $i < 10; $i++ ) {
			$this->post_ids[] = self::factory()->post->create( [
				'post_status'   => 'publish',
				'post_date'     => gmdate( 'Y-m-d H:i:s', strtotime( "-{$i} days" ) ),
				'post_category' => [ $this->term_id ],
			] );
		}
	}

	private function grid_args( array $overrides = [] ): array {
		return array_merge( [
			'type'           => 'post',
			'post_type'      => [ 'post' ],
			'query_by'       => 'tax_meta',
			'posts_per_page' => 3,
			'excludes'       => [ 'exclude_current' ],
			'taxonomies'     => [
				[ 'taxonomy' => 'category', 'terms' => [ $this->term_id ], 'current' => false, 'operator' => 'IN' ],
			],
		], $overrides );
	}

	private function ids( \WP_Query $query ): array {
		return wp_list_pluck( $query->posts, 'ID' );
	}

	/** Runs the same grid with deferring switched off, for a like-for-like comparison. */
	private function undeferred( array $args ): \WP_Query {
		add_filter( 'mai_post_grid_defer_excludes', '__return_false' );
		$query = ( new Mai_Grid( $args ) )->get_query();
		remove_filter( 'mai_post_grid_defer_excludes', '__return_false' );

		return $query;
	}

	// ---- The red test for this task ----

	public function test_deferred_grid_does_not_send_the_ids_to_the_database(): void {
		$this->go_to( get_permalink( $this->post_ids[0] ) );
		$this->assertTrue( is_singular(), 'exclude_current only applies on singular' );

		$query = ( new Mai_Grid( $this->grid_args() ) )->get_query();

		$this->assertStringNotContainsString( 'NOT IN', $query->request );
		$this->assertStringContainsString( 'LIMIT 0, 4', $query->request, 'asked for 3 plus the 1 excluded' );
	}

	// ---- Equivalence ----

	public function test_deferred_result_matches_the_undeferred_result(): void {
		$current = $this->post_ids[0]; // newest, so it would otherwise lead the grid.
		$this->go_to( get_permalink( $current ) );

		$deferred = ( new Mai_Grid( $this->grid_args() ) )->get_query();

		$this->assertStringNotContainsString( 'NOT IN', $deferred->request, 'must actually have deferred' );
		$this->assertSame( $this->ids( $this->undeferred( $this->grid_args() ) ), $this->ids( $deferred ) );
		$this->assertCount( 3, $deferred->posts );
		$this->assertNotContains( $current, $this->ids( $deferred ) );
	}

	public function test_short_result_matches(): void {
		$current = $this->post_ids[0];
		$this->go_to( get_permalink( $current ) );

		$args = $this->grid_args( [ 'excludes' => [ 'exclude_current', 'exclude_displayed' ] ] );

		Mai_Grid::$existing_post_ids = [ 'post' => array_slice( $this->post_ids, 1, 7 ) ];
		$deferred = ( new Mai_Grid( $args ) )->get_query();

		Mai_Grid::$existing_post_ids = [ 'post' => array_slice( $this->post_ids, 1, 7 ) ];
		$plain = $this->undeferred( $args );

		Mai_Grid::$existing_post_ids = [];

		$this->assertStringNotContainsString( 'NOT IN', $deferred->request );
		$this->assertSame( $this->ids( $plain ), $this->ids( $deferred ) );
		$this->assertCount( 2, $deferred->posts, 'only two posts survive the exclusions' );
	}

	public function test_empty_result_matches(): void {
		$this->go_to( get_permalink( $this->post_ids[0] ) );

		$args = $this->grid_args( [ 'excludes' => [ 'exclude_current', 'exclude_displayed' ] ] );

		Mai_Grid::$existing_post_ids = [ 'post' => $this->post_ids ];
		$deferred = ( new Mai_Grid( $args ) )->get_query();
		Mai_Grid::$existing_post_ids = [];

		$this->assertSame( [], $deferred->posts );
		$this->assertSame( 0, $deferred->post_count );
		$this->assertFalse( $deferred->have_posts(), 'the no_results message depends on this' );
		$this->assertNull( $deferred->post );
	}

	// ---- The restore ----

	public function test_query_vars_are_restored_for_downstream_consumers(): void {
		$current = $this->post_ids[0];
		$this->go_to( get_permalink( $current ) );

		$query = ( new Mai_Grid( $this->grid_args() ) )->get_query();

		// Mai Load More serializes exactly these and rebuilds a WP_Query from them.
		$this->assertSame( 3, $query->query_vars['posts_per_page'], 'padded page size must not leak' );
		$this->assertContains( $current, $query->query_vars['post__not_in'], 'excluded ids must still be described' );
		$this->assertSame( 3, $query->query['posts_per_page'], 'the raw args copy must be restored too' );
		$this->assertContains( $current, $query->query['post__not_in'], 'the raw args copy must describe the exclusion too' );
		$this->assertArrayNotHasKey( 'mai_grid_tiebreak', $query->query_vars, 'the tiebreak marker must not leak to downstream consumers' );
		$this->assertArrayNotHasKey( 'mai_grid_tiebreak', $query->query, 'the tiebreak marker must not leak into the raw args copy' );
	}

	// ---- Guards ----

	public function test_offset_grid_is_left_alone(): void {
		$this->go_to( get_permalink( $this->post_ids[0] ) );

		$query = ( new Mai_Grid( $this->grid_args( [ 'offset' => 2 ] ) ) )->get_query();

		$this->assertStringContainsString( 'NOT IN', $query->request );
		$this->assertSame( $this->ids( $this->undeferred( $this->grid_args( [ 'offset' => 2 ] ) ) ), $this->ids( $query ) );
	}

	public function test_choice_grid_is_left_alone(): void {
		$this->go_to( get_permalink( $this->post_ids[0] ) );

		$args  = $this->grid_args( [ 'query_by' => 'id', 'post__in' => array_slice( $this->post_ids, 0, 3 ) ] );
		$query = ( new Mai_Grid( $args ) )->get_query();

		// The exclude block is skipped entirely for Choice grids, so the current post is
		// still shown. That is today's behavior and must not change.
		$this->assertContains( $this->post_ids[0], $this->ids( $query ) );
	}

	// ---- Ties ----

	/** Makes every post share a post_date, so only the ID tiebreaker can settle the order. */
	private function make_everything_tie(): void {
		$same = gmdate( 'Y-m-d H:i:s', strtotime( '-1 hour' ) );

		foreach ( $this->post_ids as $id ) {
			wp_update_post( [ 'ID' => $id, 'post_date' => $same, 'post_date_gmt' => $same ] );
		}
	}

	/**
	 * What the tiebreaker actually buys: the same answer every time.
	 *
	 * Do NOT assert equality with the undeferred path here. The tiebreaker is added only to
	 * the deferred query, so on tied rows the two paths legitimately return different posts.
	 * That is the accepted behavior change, pinned by the next test.
	 */
	public function test_tied_posts_come_back_in_a_stable_order(): void {
		$this->make_everything_tie();
		$this->go_to( get_permalink( $this->post_ids[0] ) );

		$first  = $this->ids( ( new Mai_Grid( $this->grid_args() ) )->get_query() );
		$second = $this->ids( ( new Mai_Grid( $this->grid_args() ) )->get_query() );

		$this->assertSame( $first, $second );
		$this->assertCount( 3, $first );

		// Highest ids win, because the sort is DESC and the tiebreaker follows it.
		$expected = array_slice( array_reverse( array_diff( $this->post_ids, [ $this->post_ids[0] ] ) ), 0, 3 );
		sort( $expected );
		$actual = $first;
		sort( $actual );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * Pins the accepted behavior change so a future reader sees it was deliberate.
	 *
	 * On rows that tie, a deferring grid can show different posts than the same grid with
	 * deferring off. Today's order among tied rows is whatever MySQL felt like and can vary
	 * between page loads; the deferred order is fixed. This test asserts only that both
	 * return a full grid of valid posts, not that they match.
	 */
	public function test_tied_posts_may_differ_from_the_undeferred_path(): void {
		$this->make_everything_tie();
		$this->go_to( get_permalink( $this->post_ids[0] ) );

		$deferred = $this->ids( ( new Mai_Grid( $this->grid_args() ) )->get_query() );
		$plain    = $this->ids( $this->undeferred( $this->grid_args() ) );

		$this->assertCount( 3, $deferred );
		$this->assertCount( 3, $plain );
		$this->assertNotContains( $this->post_ids[0], $deferred );
		$this->assertNotContains( $this->post_ids[0], $plain );
		$this->assertSame( [], array_diff( $deferred, $this->post_ids ) );
	}

	public function test_tiebreaker_follows_the_requested_direction(): void {
		$this->go_to( get_permalink( $this->post_ids[0] ) );

		$query = ( new Mai_Grid( $this->grid_args( [ 'order' => 'ASC' ] ) ) )->get_query();

		$this->assertStringContainsString( '.ID ASC', $query->request );
	}

	public function test_tiebreaker_is_not_applied_to_an_undeferred_grid(): void {
		$this->go_to( get_permalink( $this->post_ids[0] ) );

		$this->assertStringNotContainsString( '.ID DESC', $this->undeferred( $this->grid_args() )->request );
	}

	/**
	 * The remove_filter after the WP_Query constructor must actually run, not just be dead code.
	 *
	 * Checking a subsequent plain WP_Query's request would not catch a broken remove_filter:
	 * add_deferred_orderby_tiebreaker() itself no-ops unless query_vars['mai_grid_tiebreak'] is
	 * set, which a plain query never sets. So this checks filter registration directly.
	 */
	public function test_tiebreaker_filter_does_not_survive_the_grid_that_added_it(): void {
		$this->go_to( get_permalink( $this->post_ids[0] ) );

		$grid = new Mai_Grid( $this->grid_args() );
		$grid->get_query();

		$this->assertFalse(
			has_filter( 'posts_orderby', [ $grid, 'add_deferred_orderby_tiebreaker' ] ),
			'the tiebreaker filter must not survive the grid that added it'
		);
	}

	// ---- The cache key ----

	/**
	 * Captures the key exactly as Mai_Query_Cache::pre_query() computes it.
	 *
	 * This must run DURING the query. get_query() restores query_vars once the constructor
	 * returns, so reading them afterwards shows the original request, complete with the
	 * excluded id, and every key would look shattered. posts_pre_query fires with
	 * $query->request already built, and the cache's own callback sits at priority 10.
	 */
	private function key_for( int $current ): string {
		$this->go_to( get_permalink( $current ) );

		$key     = '';
		$capture = static function ( $posts, $query ) use ( &$key ) {
			$key = ( new Mai_Query_Cache() )->cache_key( $query->query_vars, (string) $query->request );

			return $posts;
		};

		add_filter( 'posts_pre_query', $capture, 9, 2 );
		( new Mai_Grid( $this->grid_args() ) )->get_query();
		remove_filter( 'posts_pre_query', $capture, 9 );

		$this->assertNotSame( '', $key, 'the capture filter did not fire' );

		return $key;
	}

	/** The reason the whole change exists. */
	public function test_two_posts_in_the_same_category_share_a_cache_key(): void {
		$this->assertSame(
			$this->key_for( $this->post_ids[0] ),
			$this->key_for( $this->post_ids[5] ),
			'the excluded id must not reach the key'
		);
	}

	public function test_an_undeferred_grid_still_shatters(): void {
		add_filter( 'mai_post_grid_defer_excludes', '__return_false' );

		$a = $this->key_for( $this->post_ids[0] );
		$b = $this->key_for( $this->post_ids[5] );

		remove_filter( 'mai_post_grid_defer_excludes', '__return_false' );

		$this->assertNotSame( $a, $b, 'this is the behavior being fixed' );
	}
}
