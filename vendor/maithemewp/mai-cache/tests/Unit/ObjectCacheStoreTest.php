<?php
namespace Mai\Cache\Tests\Unit;

use Brain\Monkey\Functions;
use Mai\Cache\ObjectCacheStore;
use Mai\Cache\Tests\TestCase;

final class ObjectCacheStoreTest extends TestCase {
	public function test_reads_via_wp_cache_get_in_group(): void {
		Functions\expect( 'wp_cache_get' )->once()->with( 'k', 'mai_cache' )->andReturn( 'v' );
		$this->assertSame( 'v', ( new ObjectCacheStore() )->read( 'k' ) );
	}

	public function test_writes_via_wp_cache_set_in_group(): void {
		Functions\expect( 'wp_cache_set' )->once()->with( 'k', 'v', 'mai_cache', 60 )->andReturn( true );
		$this->assertTrue( ( new ObjectCacheStore() )->write( 'k', 'v', 60 ) );
	}

	public function test_removes_via_wp_cache_delete_in_group(): void {
		Functions\expect( 'wp_cache_delete' )->once()->with( 'k', 'mai_cache' )->andReturn( true );
		$this->assertTrue( ( new ObjectCacheStore() )->remove( 'k' ) );
	}

	public function test_available_reflects_ext_object_cache(): void {
		Functions\when( 'wp_using_ext_object_cache' )->justReturn( true );
		$this->assertTrue( ( new ObjectCacheStore() )->available() );

		Functions\when( 'wp_using_ext_object_cache' )->justReturn( false );
		$this->assertFalse( ( new ObjectCacheStore() )->available() );
	}
}
