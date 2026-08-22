<?php
// tests/phpunit/unit/GridDeferredExcludesTest.php
namespace BizBudding\MaiEngine\Tests\Unit;

use Brain\Monkey\Functions;
use BizBudding\MaiEngine\Tests\TestCase;
use Mai_Grid;
use ReflectionProperty;

final class GridDeferredExcludesTest extends TestCase {

	/**
	 * Builds a Mai_Grid without its constructor, then injects args. The constructor pulls in
	 * the whole field layer via get_sanitized_args() and get_defaults(); this suite is about
	 * the exclude logic only.
	 */
	private function grid( array $args ): Mai_Grid {
		$grid = ( new \ReflectionClass( Mai_Grid::class ) )->newInstanceWithoutConstructor();

		$prop = new ReflectionProperty( Mai_Grid::class, 'args' );
		$prop->setAccessible( true );
		$prop->setValue( $grid, $args + [
			'post_type'      => [ 'post' ],
			'posts_per_page' => 6,
			'offset'         => 0,
			'query_by'       => 'tax_meta',
			'orderby'        => 'date',
			'order'          => 'DESC',
			'post__not_in'   => '',
			'excludes'       => '',
			'taxonomies'     => '',
			'meta_keys'      => '',
			'date_after'     => '',
			'date_before'    => '',
		] );

		return $grid;
	}

	private function read( Mai_Grid $grid, string $name ) {
		$prop = new ReflectionProperty( Mai_Grid::class, $name );
		$prop->setAccessible( true );

		return $prop->getValue( $grid );
	}

	private function stub_wp(): void {
		Functions\when( 'apply_filters' )->alias( fn( $tag, $value ) => $value );
		Functions\when( 'is_user_logged_in' )->justReturn( false );
		Functions\when( 'current_user_can' )->justReturn( false );
	}

	public function test_exclude_current_is_recorded_and_query_args_are_unchanged(): void {
		$this->stub_wp();
		Functions\when( 'is_singular' )->justReturn( true );
		Functions\when( 'get_the_ID' )->justReturn( 99 );

		$grid = $this->grid( [ 'excludes' => [ 'exclude_current' ] ] );
		$args = $grid->get_post_query_args();

		$this->assertSame( [ 99 ], $args['post__not_in'], 'this task must not change behavior' );
		$this->assertSame( [ 99 ], $this->read( $grid, 'deferred_excludes' ) );
	}

	public function test_author_set_exclusions_are_not_recorded(): void {
		$this->stub_wp();
		Functions\when( 'is_singular' )->justReturn( true );
		Functions\when( 'get_the_ID' )->justReturn( 99 );

		$grid = $this->grid( [ 'excludes' => [ 'exclude_current' ], 'post__not_in' => [ 7, 8 ] ] );
		$args = $grid->get_post_query_args();

		$this->assertSame( [ 7, 8, 99 ], $args['post__not_in'] );
		$this->assertSame( [ 99 ], $this->read( $grid, 'deferred_excludes' ), 'author-set ids are stable per grid and belong in the key' );
	}

	public function test_exclude_displayed_is_recorded(): void {
		$this->stub_wp();
		Functions\when( 'is_singular' )->justReturn( false );

		Mai_Grid::$existing_post_ids = [ 'post' => [ 11, 12 ] ];

		$grid = $this->grid( [ 'excludes' => [ 'exclude_displayed' ] ] );
		$args = $grid->get_post_query_args();

		Mai_Grid::$existing_post_ids = [];

		$this->assertSame( [ 11, 12 ], $args['post__not_in'] );
		$this->assertSame( [ 11, 12 ], $this->read( $grid, 'deferred_excludes' ) );
	}

	public function test_nothing_recorded_without_dynamic_excludes(): void {
		$this->stub_wp();
		Functions\when( 'is_singular' )->justReturn( true );
		Functions\when( 'get_the_ID' )->justReturn( 99 );

		$grid = $this->grid( [ 'post__not_in' => [ 7 ] ] );
		$args = $grid->get_post_query_args();

		$this->assertSame( [ 7 ], $args['post__not_in'] );
		$this->assertSame( [], $this->read( $grid, 'deferred_excludes' ) );
	}

	public function test_choice_grids_record_nothing(): void {
		$this->stub_wp();
		Functions\when( 'is_singular' )->justReturn( true );
		Functions\when( 'get_the_ID' )->justReturn( 99 );

		// query_by 'id' skips the whole exclude block at class-mai-grid.php:584.
		$grid = $this->grid( [ 'query_by' => 'id', 'post__in' => [ 3, 4 ], 'excludes' => [ 'exclude_current' ] ] );
		$args = $grid->get_post_query_args();

		$this->assertArrayNotHasKey( 'post__not_in', $args );
		$this->assertSame( [], $this->read( $grid, 'deferred_excludes' ) );
	}

	public function test_repeat_calls_do_not_accumulate(): void {
		$this->stub_wp();
		Functions\when( 'is_singular' )->justReturn( true );
		Functions\when( 'get_the_ID' )->justReturn( 99 );

		// get_post_query_args() is public. Two calls must not double the list.
		$grid = $this->grid( [ 'excludes' => [ 'exclude_current' ] ] );
		$grid->get_post_query_args();
		$grid->get_post_query_args();

		$this->assertSame( [ 99 ], $this->read( $grid, 'deferred_excludes' ) );
	}

	private function effective( array $query_args, array $recorded = [ 99 ] ): array {
		$grid = $this->grid( [] );

		$prop = new ReflectionProperty( Mai_Grid::class, 'deferred_excludes' );
		$prop->setAccessible( true );
		$prop->setValue( $grid, $recorded );

		$method = new \ReflectionMethod( Mai_Grid::class, 'effective_excludes' );
		$method->setAccessible( true );

		return $method->invoke( $grid, $query_args );
	}

	private function can_defer( array $query_args, array $effective = [ 99 ] ): bool {
		Functions\when( 'apply_filters' )->alias( fn( $tag, $value ) => $value );

		$grid   = $this->grid( [] );
		$method = new \ReflectionMethod( Mai_Grid::class, 'can_defer_excludes' );
		$method->setAccessible( true );

		return $method->invoke( $grid, $query_args + [
			'posts_per_page' => 6,
			'offset'         => 0,
			'no_found_rows'  => true,
			'mai_cache'      => true,
		], $effective );
	}

	// ---- effective_excludes() ----

	public function test_effective_normalizes_and_intersects(): void {
		$this->assertSame(
			[ 11, 12 ],
			$this->effective( [ 'post__not_in' => [ 7, 11, 12 ] ], [ 11, 12, 11 ] )
		);
	}

	public function test_effective_drops_an_id_a_filter_removed(): void {
		// A site filtered post__not_in to let the current post back in. Honour that.
		$this->assertSame( [], $this->effective( [ 'post__not_in' => [ 7 ] ], [ 99 ] ) );
	}

	public function test_effective_drops_a_falsy_id(): void {
		// get_the_ID() returns int|false on a malformed singular request.
		$this->assertSame( [], $this->effective( [ 'post__not_in' => [ 0 ] ], [ false ] ) );
	}

	public function test_effective_is_empty_when_post_not_in_is_missing(): void {
		// A filter unset the key. Must not fatal.
		$this->assertSame( [], $this->effective( [], [ 99 ] ) );
	}

	public function test_effective_is_empty_when_post_not_in_is_not_an_array(): void {
		// A filter set the comma string form WordPress also accepts. Leave the grid alone.
		$this->assertSame( [], $this->effective( [ 'post__not_in' => '12,34' ], [ 12 ] ) );
	}

	// ---- can_defer_excludes() ----

	public function test_defers_for_an_ordinary_grid(): void {
		$this->assertTrue( $this->can_defer( [] ) );
	}

	public function test_does_not_defer_with_nothing_effective(): void {
		$this->assertFalse( $this->can_defer( [], [] ) );
	}

	public function test_does_not_defer_with_an_offset(): void {
		// The database skips rows before we can filter, so filtering after returns a
		// different set. Measured: differs for roughly 1 article in 150.
		$this->assertFalse( $this->can_defer( [ 'offset' => 3 ] ) );
	}

	public function test_does_not_defer_when_paged(): void {
		// Same mechanism as offset. LIMIT start is (paged - 1) * posts_per_page, so padding
		// the page size multiplies the start row and silently skips posts.
		$this->assertFalse( $this->can_defer( [ 'paged' => 2 ] ) );
	}

	public function test_does_not_defer_when_nopaging(): void {
		// No LIMIT is emitted at all, so padding does nothing and the slice would truncate.
		$this->assertFalse( $this->can_defer( [ 'nopaging' => true ] ) );
	}

	public function test_does_not_defer_when_found_rows_are_wanted(): void {
		$this->assertFalse( $this->can_defer( [ 'no_found_rows' => false ] ) );
	}

	public function test_does_not_defer_when_found_rows_key_is_absent(): void {
		// WP_Query's own default is false, meaning counting is ON. Absent must be treated
		// the same as false, not as true.
		$args = [ 'posts_per_page' => 6, 'offset' => 0, 'mai_cache' => true ];

		Functions\when( 'apply_filters' )->alias( fn( $tag, $value ) => $value );

		$grid   = $this->grid( [] );
		$method = new \ReflectionMethod( Mai_Grid::class, 'can_defer_excludes' );
		$method->setAccessible( true );

		$this->assertFalse( $method->invoke( $grid, $args, [ 99 ] ) );
	}

	public function test_does_not_defer_when_posts_per_page_is_absent(): void {
		// No default merged in here, unlike the can_defer() helper.
		$args = [ 'offset' => 0, 'no_found_rows' => true, 'mai_cache' => true ];

		Functions\when( 'apply_filters' )->alias( fn( $tag, $value ) => $value );

		$grid   = $this->grid( [] );
		$method = new \ReflectionMethod( Mai_Grid::class, 'can_defer_excludes' );
		$method->setAccessible( true );

		$this->assertFalse( $method->invoke( $grid, $args, [ 99 ] ) );
	}

	public function test_does_not_defer_when_posts_per_page_is_below_one(): void {
		$this->assertFalse( $this->can_defer( [ 'posts_per_page' => 0 ] ) );
	}

	public function test_does_not_defer_when_posts_per_page_is_not_numeric(): void {
		// A shortcode att or a filter can hand over a string. PHP 8 compares it false against
		// 1 and then throws a TypeError on the padding arithmetic, so the guard has to catch
		// the type, not just the size. Failing here is a fatal on every post grid, including
		// grids with no excludes at all, so assert it does not throw as well as the result.
		$this->assertFalse( $this->can_defer( [ 'posts_per_page' => 'all' ] ) );
	}

	public function test_does_not_defer_for_facetwp(): void {
		$this->assertFalse( $this->can_defer( [ 'facetwp' => true ] ) );
	}

	public function test_does_not_defer_when_the_grid_is_not_cached(): void {
		$this->assertFalse( $this->can_defer( [ 'mai_cache' => false ] ) );
	}

	public function test_does_not_defer_for_random_order(): void {
		// Mai_Query_Cache::is_cacheable() refuses a random order because caching it would
		// defeat the point of randomness.
		$this->assertFalse( $this->can_defer( [ 'orderby' => 'rand' ] ) );
	}

	public function test_does_not_defer_for_elasticpress(): void {
		// Mai_Query_Cache::is_cacheable() refuses ep_integrate grids; that interaction with
		// the result cache is unverified.
		$this->assertFalse( $this->can_defer( [ 'ep_integrate' => true ] ) );
	}

	public function test_does_not_defer_when_filters_are_suppressed(): void {
		// WP_Query runs posts_orderby and the_posts inside `if ( ! suppress_filters )`, so the
		// padded LIMIT would get no tiebreaker and the superset would never be stored.
		$this->assertFalse( $this->can_defer( [ 'suppress_filters' => true ] ) );
	}

	public function test_does_not_defer_for_an_ids_query(): void {
		// Core returns before the_posts and before it sets $this->post.
		$this->assertFalse( $this->can_defer( [ 'fields' => 'ids' ] ) );
	}

	public function test_does_not_defer_for_an_id_parent_query(): void {
		$this->assertFalse( $this->can_defer( [ 'fields' => 'id=>parent' ] ) );
	}

	public function test_still_defers_for_the_default_fields_value(): void {
		// The guard is only for the two shapes core short-circuits. Everything else, including
		// the empty string WP_Query defaults to, gets full post objects and still defers.
		$this->assertTrue( $this->can_defer( [ 'fields' => '' ] ) );
	}

	public function test_does_not_defer_past_the_show_all_ceiling(): void {
		// A show-all grid resolves to 1000. Padding it would step over the ceiling that
		// exists so one editor setting cannot take a site down. One id over the ceiling, not
		// several, so an off-by-a-few comparison bug cannot slip through.
		$this->assertFalse( $this->can_defer( [ 'posts_per_page' => 1000 ], range( 1, 1 ) ) );
	}

	public function test_pads_right_up_to_the_ceiling(): void {
		// The default ceiling is 1000, which now always loses to the 500-row guard below.
		// A lowered ceiling isolates this guard so the boundary is still under test on its own.
		Functions\when( 'apply_filters' )->alias(
			fn( $tag, $value ) => 'mai_post_grid_max_posts_per_page' === $tag ? 100 : $value
		);

		$grid   = $this->grid( [] );
		$method = new \ReflectionMethod( Mai_Grid::class, 'can_defer_excludes' );
		$method->setAccessible( true );

		$args = [ 'posts_per_page' => 95, 'offset' => 0, 'no_found_rows' => true, 'mai_cache' => true ];

		$this->assertTrue( $method->invoke( $grid, $args, range( 1, 5 ) ) );
	}

	public function test_pads_right_up_to_the_split_query_threshold(): void {
		// posts_per_page 498 + the default 1 effective id = 499, one below the 500 where
		// core swaps its query shape.
		$this->assertTrue( $this->can_defer( [ 'posts_per_page' => 498 ] ) );
	}

	public function test_does_not_defer_at_the_split_query_threshold(): void {
		// posts_per_page 499 + the default 1 effective id = 500, where WP_Query::get_posts()
		// drops $split_the_query and selects whole rows instead of ids-then-hydrate.
		$this->assertFalse( $this->can_defer( [ 'posts_per_page' => 499 ] ) );
	}

	public function test_split_query_threshold_guard_does_not_fatal_on_non_numeric_posts_per_page(): void {
		// Same protection this guard borrows from the ceiling guard above: a non-numeric
		// value must not reach the addition here either, or PHP 8 throws a TypeError.
		$this->assertFalse( $this->can_defer( [ 'posts_per_page' => 'all' ] ) );
	}

	public function test_filter_can_switch_it_off(): void {
		Functions\when( 'apply_filters' )->alias(
			fn( $tag, $value ) => 'mai_post_grid_defer_excludes' === $tag ? false : $value
		);

		$grid   = $this->grid( [] );
		$method = new \ReflectionMethod( Mai_Grid::class, 'can_defer_excludes' );
		$method->setAccessible( true );

		$args = [ 'posts_per_page' => 6, 'offset' => 0, 'no_found_rows' => true, 'mai_cache' => true ];

		$this->assertFalse( $method->invoke( $grid, $args, [ 99 ] ) );
	}

	public function test_filter_cannot_switch_it_on_past_a_guard(): void {
		// The filter is an opt-out only. Returning true must not defeat the offset guard.
		Functions\when( 'apply_filters' )->alias(
			fn( $tag, $value ) => 'mai_post_grid_defer_excludes' === $tag ? true : $value
		);

		$grid   = $this->grid( [] );
		$method = new \ReflectionMethod( Mai_Grid::class, 'can_defer_excludes' );
		$method->setAccessible( true );

		$args = [ 'posts_per_page' => 6, 'offset' => 3, 'no_found_rows' => true, 'mai_cache' => true ];

		$this->assertFalse( $method->invoke( $grid, $args, [ 99 ] ) );
	}
}
