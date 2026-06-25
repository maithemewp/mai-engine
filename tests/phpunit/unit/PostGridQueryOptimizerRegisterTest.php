<?php
// tests/phpunit/unit/PostGridQueryOptimizerRegisterTest.php
namespace BizBudding\MaiEngine\Tests\Unit;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use BizBudding\MaiEngine\Tests\TestCase;
use Mai_Post_Grid_Query_Optimizer;

final class PostGridQueryOptimizerRegisterTest extends TestCase {
	public function test_register_adds_filters_when_enabled(): void {
		Functions\when( 'apply_filters' )->alias( fn( $tag, $value ) => $value ); // optimize_query stays true

		( new Mai_Post_Grid_Query_Optimizer() )->register();

		$this->assertNotFalse( has_filter( 'mai_post_grid_query_args' ) );
		$this->assertNotFalse( has_filter( 'posts_where' ) );
		$this->assertNotFalse( has_filter( 'posts_orderby' ) );
	}

	public function test_register_is_noop_when_disabled(): void {
		Functions\when( 'apply_filters' )->alias(
			fn( $tag, $value ) => 'mai_post_grid_optimize_query' === $tag ? false : $value
		);

		( new Mai_Post_Grid_Query_Optimizer() )->register();

		$this->assertFalse( has_filter( 'mai_post_grid_query_args' ) );
	}
}
