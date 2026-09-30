<?php
namespace BizBudding\MaiEngine\Tests\Unit;

use BizBudding\MaiEngine\Tests\TestCase;
use Mai_Query_Cache;

final class MaiQueryCacheKeyTest extends TestCase {
	public function test_arg_order_and_volatile_vars_do_not_change_the_key(): void {
		$c = new Mai_Query_Cache();
		$a = $c->cache_key( [ 'post_type' => 'post', 'post__in' => [ 3, 1, 2 ], 'fields' => 'ids', 'cache_results' => true, 'update_post_meta_cache' => false ], 'SELECT 1' );
		$b = $c->cache_key( [ 'post__in' => [ 1, 2, 3 ], 'post_type' => 'post' ], 'SELECT 1' );
		$this->assertSame( $a, $b );
	}

	public function test_different_sql_changes_the_key(): void {
		$c = new Mai_Query_Cache();
		$this->assertNotSame(
			$c->cache_key( [ 'post_type' => 'post' ], 'SELECT 1' ),
			$c->cache_key( [ 'post_type' => 'post' ], 'SELECT 2' )
		);
	}

	public function test_custom_args_are_kept_in_the_key(): void {
		$c = new Mai_Query_Cache();
		$this->assertNotSame(
			$c->cache_key( [ 'post_type' => 'post', 'my_custom' => 'x' ], 'SELECT 1' ),
			$c->cache_key( [ 'post_type' => 'post', 'my_custom' => 'y' ], 'SELECT 1' )
		);
	}

	public function test_post_status_order_does_not_change_the_key(): void {
		$c = new Mai_Query_Cache();
		$this->assertSame(
			$c->cache_key( [ 'post_type' => 'post', 'post_status' => [ 'publish', 'private' ] ], 'SELECT 1' ),
			$c->cache_key( [ 'post_type' => 'post', 'post_status' => [ 'private', 'publish' ] ], 'SELECT 1' )
		);
	}

	public function test_unset_orderby_matches_explicit_date(): void {
		$c = new Mai_Query_Cache();
		$this->assertSame(
			$c->cache_key( [ 'post_type' => 'post' ], 'SELECT 1' ),
			$c->cache_key( [ 'post_type' => 'post', 'orderby' => 'date' ], 'SELECT 1' )
		);
	}

	public function test_select_field_list_is_normalized_out(): void {
		$c     = new Mai_Query_Cache();
		$split = 'SELECT wp_posts.ID FROM wp_posts WHERE 1=1 ORDER BY wp_posts.post_date DESC LIMIT 0, 12';
		$full  = 'SELECT wp_posts.* FROM wp_posts WHERE 1=1 ORDER BY wp_posts.post_date DESC LIMIT 0, 12';
		$this->assertSame(
			$c->cache_key( [ 'post_type' => 'post' ], $split ),
			$c->cache_key( [ 'post_type' => 'post' ], $full )
		);
	}

	public function test_where_still_changes_the_key_after_field_normalization(): void {
		$c = new Mai_Query_Cache();
		$this->assertNotSame(
			$c->cache_key( [ 'post_type' => 'post' ], 'SELECT wp_posts.ID FROM wp_posts WHERE a=1' ),
			$c->cache_key( [ 'post_type' => 'post' ], 'SELECT wp_posts.* FROM wp_posts WHERE a=2' )
		);
	}
}
