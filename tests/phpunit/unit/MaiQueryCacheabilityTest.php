<?php
namespace BizBudding\MaiEngine\Tests\Unit;

use Brain\Monkey\Functions;
use BizBudding\MaiEngine\Tests\TestCase;
use Mai_Query_Cache;

final class MaiQueryCacheabilityTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'apply_filters' )->alias( fn( $tag, $value ) => $value );
	}

	/**
	 * query_by lives on Mai_Grid::$args and is never copied into the query vars, so the
	 * guard that used to test for it here could not fire. Caching these is fine anyway:
	 * they are post__in with orderby post__in, already a primary key lookup.
	 */
	public function test_caches_query_by_id_because_the_var_never_reaches_the_query(): void {
		$this->assertTrue( ( new Mai_Query_Cache() )->is_cacheable( [ 'query_by' => 'id', 'post_type' => 'post' ] ) );
	}

	public function test_skips_ep_integrate(): void {
		$this->assertFalse( ( new Mai_Query_Cache() )->is_cacheable( [ 'ep_integrate' => true, 'post_type' => 'post' ] ) );
	}

	public function test_skips_active_optimizer_marker_without_meta(): void {
		$this->assertFalse( ( new Mai_Query_Cache() )->is_cacheable( [ 'mai_post_grid_tt_ids' => [ 5 ], 'post_type' => 'post' ] ) );
	}

	public function test_caches_optimizer_marker_with_meta(): void {
		$this->assertTrue( ( new Mai_Query_Cache() )->is_cacheable( [ 'mai_post_grid_tt_ids' => [ 5 ], 'meta_query' => [ [ 'key' => 'x' ] ], 'post_type' => 'post' ] ) );
	}

	public function test_skips_rand_orderby(): void {
		$this->assertFalse( ( new Mai_Query_Cache() )->is_cacheable( [ 'orderby' => 'RAND()', 'post_type' => 'post' ] ) );
	}

	/**
	 * Mai_Grid passes the bare string through from the Order By setting, which is the form
	 * that actually reaches the cache; only the seeded RAND() form was matched before.
	 */
	public function test_skips_bare_rand_orderby(): void {
		$this->assertFalse( ( new Mai_Query_Cache() )->is_cacheable( [ 'orderby' => 'rand', 'post_type' => 'post' ] ) );
	}

	public function test_caches_orderby_containing_rand_as_a_substring(): void {
		$this->assertTrue( ( new Mai_Query_Cache() )->is_cacheable( [ 'orderby' => 'brand_name', 'post_type' => 'post' ] ) );
	}

	/**
	 * serve() returns post objects, and core turns those into 1s for an ids query.
	 */
	public function test_fields_ids_not_cacheable(): void {
		$this->assertFalse( ( new Mai_Query_Cache() )->is_cacheable( [ 'fields' => 'ids', 'post_type' => 'post' ] ) );
	}

	public function test_fields_id_parent_not_cacheable(): void {
		$this->assertFalse( ( new Mai_Query_Cache() )->is_cacheable( [ 'fields' => 'id=>parent', 'post_type' => 'post' ] ) );
	}

	public function test_caches_a_full_fields_query(): void {
		$this->assertTrue( ( new Mai_Query_Cache() )->is_cacheable( [ 'fields' => '', 'post_type' => 'post' ] ) );
		$this->assertTrue( ( new Mai_Query_Cache() )->is_cacheable( [ 'fields' => 'all', 'post_type' => 'post' ] ) );
	}

	public function test_caches_a_normal_tax_grid(): void {
		$this->assertTrue( ( new Mai_Query_Cache() )->is_cacheable( [ 'post_type' => 'post', 'tax_query' => [ [ 'taxonomy' => 'category' ] ] ] ) );
	}
}
