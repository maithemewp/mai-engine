<?php
namespace BizBudding\MaiEngine\Tests\Unit;

use Brain\Monkey\Functions;
use BizBudding\MaiEngine\Tests\TestCase;
use Mai_Grid_Cache;

final class MaiGridCacheSingleFlightTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		// Default: filters pass the value through unchanged.
		Functions\when( 'apply_filters' )->alias( fn( $tag, $value ) => $value );
	}

	/**
	 * Invoke a private method.
	 */
	private function call( object $obj, string $method, ...$args ) {
		// Private methods are invocable via reflection without setAccessible() on PHP 8.1+.
		return ( new \ReflectionMethod( $obj, $method ) )->invoke( $obj, ...$args );
	}

	public function test_single_flight_off_without_persistent_object_cache(): void {
		Functions\when( 'wp_using_ext_object_cache' )->justReturn( false );
		$this->assertFalse( $this->call( new Mai_Grid_Cache(), 'use_single_flight' ) );
	}

	public function test_single_flight_on_with_persistent_object_cache(): void {
		Functions\when( 'wp_using_ext_object_cache' )->justReturn( true );
		$this->assertTrue( $this->call( new Mai_Grid_Cache(), 'use_single_flight' ) );
	}

	public function test_single_flight_kill_switch_filter(): void {
		Functions\when( 'wp_using_ext_object_cache' )->justReturn( true );
		Functions\when( 'apply_filters' )->alias(
			fn( $tag, $value ) => 'mai_post_grid_cache_single_flight' === $tag ? false : $value
		);
		$this->assertFalse( $this->call( new Mai_Grid_Cache(), 'use_single_flight' ) );
	}

	public function test_wait_for_fill_returns_value_once_fresh(): void {
		// Cache returns stale on the first poll, fresh on the second.
		$cache = new class {
			public int $calls = 0;
			public function read_swr( $key, $version ) {
				$this->calls++;
				return $this->calls >= 2
					? [ 'value' => [ 'ids' => [ 1, 2 ], 'found' => 2 ], 'fresh' => true ]
					: [ 'value' => null, 'fresh' => false ];
			}
		};
		$out = $this->call( new Mai_Grid_Cache(), 'wait_for_fill', $cache, 'k', 'v' );
		$this->assertSame( [ 'ids' => [ 1, 2 ], 'found' => 2 ], $out );
	}

	public function test_wait_for_fill_times_out_to_null(): void {
		// Short cap so the timeout path is fast; cache never goes fresh.
		Functions\when( 'apply_filters' )->alias(
			fn( $tag, $value ) => 'mai_post_grid_cache_wait_ms' === $tag ? 60 : $value
		);
		$cache = new class {
			public function read_swr( $key, $version ) {
				return [ 'value' => null, 'fresh' => false ];
			}
		};
		$this->assertNull( $this->call( new Mai_Grid_Cache(), 'wait_for_fill', $cache, 'k', 'v' ) );
	}

	public function test_serve_sets_found_pages_and_preserves_order(): void {
		Functions\when( '_prime_post_caches' )->justReturn( null );
		Functions\when( 'get_post' )->alias( fn( $id ) => (object) [ 'ID' => $id, 'post_status' => 'publish' ] );

		$query = (object) [ 'query_vars' => [ 'posts_per_page' => 10, 'post_status' => 'publish' ] ];
		$posts = $this->call( new Mai_Grid_Cache(), 'serve', $query, [ 'ids' => [ 7, 5, 6 ], 'found' => 53 ] );

		$this->assertSame( 53, $query->found_posts );
		$this->assertSame( 6, $query->max_num_pages ); // ceil(53/10)
		$this->assertSame( [ 7, 5, 6 ], array_map( fn( $p ) => $p->ID, $posts ) );
	}
}
