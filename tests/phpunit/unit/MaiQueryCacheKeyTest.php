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

	public function test_a_datetime_in_the_sql_is_truncated_to_the_hour(): void {
		$c = new Mai_Query_Cache();
		$this->assertSame(
			$c->cache_key( [ 'post_type' => 'post' ], "SELECT wp_posts.* FROM wp_posts WHERE wp_posts.post_date > '2026-09-30 15:24:24'" ),
			$c->cache_key( [ 'post_type' => 'post' ], "SELECT wp_posts.* FROM wp_posts WHERE wp_posts.post_date > '2026-09-30 15:59:59'" )
		);
	}

	public function test_a_datetime_in_the_query_vars_is_truncated_to_the_hour(): void {
		// The Events Calendar writes "now" into the meta_query of every event query, to the
		// second. With only the SQL coarsened, the key still changed every second.
		$c    = new Mai_Query_Cache();
		$vars = static fn( string $now ) => [
			'post_type'  => 'tribe_events',
			'meta_query' => [
				'tec_event_end_date' => [ 'key' => '_EventEndDate', 'value' => $now, 'compare' => '>=', 'type' => 'DATETIME' ],
			],
		];

		$this->assertSame(
			$c->cache_key( $vars( '2026-09-30 15:24:24' ), 'SELECT 1' ),
			$c->cache_key( $vars( '2026-09-30 15:24:26' ), 'SELECT 1' )
		);
		$this->assertNotSame(
			$c->cache_key( $vars( '2026-09-30 15:24:24' ), 'SELECT 1' ),
			$c->cache_key( $vars( '2026-09-30 16:00:00' ), 'SELECT 1' ),
			'another hour is another key'
		);
	}

	public function test_a_relative_date_in_the_query_vars_still_changes_the_key(): void {
		// Two grids whose relative dates resolve within the same hour still differ in the vars.
		$c = new Mai_Query_Cache();
		$this->assertNotSame(
			$c->cache_key( [ 'post_type' => 'post', 'date_query' => [ 'after' => '3 months ago' ] ], 'SELECT 1' ),
			$c->cache_key( [ 'post_type' => 'post', 'date_query' => [ 'after' => '90 days ago' ] ], 'SELECT 1' )
		);
	}

	public function test_only_a_whole_datetime_value_is_truncated(): void {
		// A value that merely contains a datetime is not one, and stays as it is.
		$c = new Mai_Query_Cache();
		$this->assertNotSame(
			$c->cache_key( [ 'post_type' => 'post', 's' => 'at 2026-09-30 15:24:24 sharp' ], 'SELECT 1' ),
			$c->cache_key( [ 'post_type' => 'post', 's' => 'at 2026-09-30 15:24:26 sharp' ], 'SELECT 1' )
		);
	}
}
