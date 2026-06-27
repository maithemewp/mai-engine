<?php
namespace Mai\Cache\Tests\Unit;

use Brain\Monkey\Functions;
use Mai\Cache\Cache;
use Mai\Cache\Store;
use Mai\Cache\Tests\TestCase;

final class SwrTest extends TestCase {
	private function cache(): Cache {
		Functions\when( 'apply_filters' )->alias( fn( $tag, $value = null ) => $value );

		// In-memory Store so version/read/write hit a real key/value map.
		$store = new class implements Store {
			public array $data = [];
			public function read( string $key ): mixed { return $this->data[ $key ] ?? false; }
			public function write( string $key, mixed $value, int $expire ): bool { $this->data[ $key ] = $value; return true; }
			public function remove( string $key ): bool { unset( $this->data[ $key ] ); return true; }
			public function available(): bool { return true; }
		};

		return new Cache( 'mai', $store );
	}

	public function test_read_cold_is_null(): void {
		$c = $this->cache();
		$this->assertNull( $c->read_swr( 'k', $c->version( [ 'post' ] ) ) );
	}

	public function test_write_then_read_is_fresh(): void {
		$c = $this->cache();
		$v = $c->version( [ 'post' ] );
		$c->write_swr( 'k', [ 'ids' => [ 1, 2 ] ], $v, 3600 );
		$r = $c->read_swr( 'k', $v );
		$this->assertSame( [ 'ids' => [ 1, 2 ] ], $r['value'] );
		$this->assertTrue( $r['fresh'] );
	}

	public function test_bump_makes_value_stale_not_gone(): void {
		$c = $this->cache();
		$v = $c->version( [ 'post' ] );
		$c->write_swr( 'k', 'X', $v, 3600 );
		$c->bump( 'post' );
		$r = $c->read_swr( 'k', $c->version( [ 'post' ] ) );
		$this->assertSame( 'X', $r['value'] ); // still reachable
		$this->assertFalse( $r['fresh'] );      // but stale
	}

	public function test_version_is_per_scope(): void {
		$c = $this->cache();
		$v1 = $c->version( [ 'post' ] );
		$c->bump( 'page' );                       // bumping page must not change post's version
		$this->assertSame( $v1, $c->version( [ 'post' ] ) );
	}
}
