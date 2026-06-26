<?php
namespace BizBudding\MaiEngine\Tests\Unit;

use BizBudding\MaiEngine\Tests\TestCase;
use Mai_Grid_Cache;

final class MaiGridCacheKeyTest extends TestCase {
	public function test_arg_order_and_volatile_vars_do_not_change_the_key(): void {
		$c = new Mai_Grid_Cache();
		$a = $c->cache_key( [ 'post_type' => 'post', 'post__in' => [ 3, 1, 2 ], 'fields' => 'ids', 'cache_results' => true, 'update_post_meta_cache' => false ], 'SELECT 1' );
		$b = $c->cache_key( [ 'post__in' => [ 1, 2, 3 ], 'post_type' => 'post' ], 'SELECT 1' );
		$this->assertSame( $a, $b );
	}

	public function test_different_sql_changes_the_key(): void {
		$c = new Mai_Grid_Cache();
		$this->assertNotSame(
			$c->cache_key( [ 'post_type' => 'post' ], 'SELECT 1' ),
			$c->cache_key( [ 'post_type' => 'post' ], 'SELECT 2' )
		);
	}

	public function test_custom_args_are_kept_in_the_key(): void {
		$c = new Mai_Grid_Cache();
		$this->assertNotSame(
			$c->cache_key( [ 'post_type' => 'post', 'my_custom' => 'x' ], 'SELECT 1' ),
			$c->cache_key( [ 'post_type' => 'post', 'my_custom' => 'y' ], 'SELECT 1' )
		);
	}
}
