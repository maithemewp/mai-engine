<?php
namespace Mai\Cache\Tests\Unit;

use Brain\Monkey\Functions;
use Mai\Cache\Tests\TestCase;
use Mai\Cache\TransientStore;

final class TransientStoreTest extends TestCase {
	public function test_reads_via_get_transient(): void {
		Functions\expect( 'get_transient' )->once()->with( 'k' )->andReturn( 'v' );
		$this->assertSame( 'v', ( new TransientStore() )->read( 'k' ) );
	}

	public function test_writes_via_set_transient(): void {
		Functions\expect( 'set_transient' )->once()->with( 'k', 'v', 60 )->andReturn( true );
		$this->assertTrue( ( new TransientStore() )->write( 'k', 'v', 60 ) );
	}

	public function test_removes_via_delete_transient(): void {
		Functions\expect( 'delete_transient' )->once()->with( 'k' )->andReturn( true );
		$this->assertTrue( ( new TransientStore() )->remove( 'k' ) );
	}

	public function test_is_always_available(): void {
		$this->assertTrue( ( new TransientStore() )->available() );
	}
}
