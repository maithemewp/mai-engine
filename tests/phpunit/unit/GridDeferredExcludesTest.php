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
}
