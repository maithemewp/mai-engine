<?php
namespace Mai\Cache\Tests\Unit;

use Brain\Monkey\Functions;
use Mai\Cache\Cache;
use Mai\Cache\Tests\Support\ArrayStore;
use Mai\Cache\Tests\TestCase;

final class CacheVersioningTest extends TestCase {
	private function allowCaching(): void {
		Functions\when( 'apply_filters' )->alias( fn( $tag, $value = null ) => $value );
		Functions\when( 'is_wp_error' )->justReturn( false );
	}

	public function test_key_includes_a_version_token(): void {
		$this->allowCaching();
		$cache = new Cache( 'mai', new ArrayStore() );
		$this->assertMatchesRegularExpression( '/^mai_[0-9a-f]{12}_thing$/', $cache->key( 'thing' ) );
	}

	public function test_grouped_key_includes_group_and_its_token(): void {
		$this->allowCaching();
		$cache = ( new Cache( 'mai', new ArrayStore() ) )->group( 'menu' );
		$this->assertMatchesRegularExpression( '/^mai_[0-9a-f]{12}_menu_[0-9a-f]{12}_primary$/', $cache->key( 'primary' ) );
	}

	public function test_token_is_stable_across_reads(): void {
		$this->allowCaching();
		$cache = new Cache( 'mai', new ArrayStore() );
		$this->assertSame( $cache->key( 'thing' ), $cache->key( 'thing' ) );
	}

	public function test_prefix_flush_busts_all_keys(): void {
		$this->allowCaching();
		$cache = new Cache( 'mai', new ArrayStore() );
		$cache->set( 'k', 'v', 60 );
		$this->assertSame( 'v', $cache->get( 'k' ) );

		$this->assertTrue( $cache->flush() );
		$this->assertFalse( $cache->get( 'k' ) ); // key changed -> miss
	}

	public function test_group_flush_busts_only_that_group(): void {
		$this->allowCaching();
		$store  = new ArrayStore();
		$base   = new Cache( 'mai', $store );
		$menu   = $base->group( 'menu' );
		$header = $base->group( 'header' );

		$menu->set( 'a', 'M', 60 );
		$header->set( 'b', 'H', 60 );

		$menu->flush();
		$this->assertFalse( $menu->get( 'a' ) );     // busted
		$this->assertSame( 'H', $header->get( 'b' ) ); // intact
	}

	public function test_prefix_flush_also_busts_grouped_keys(): void {
		$this->allowCaching();
		$store = new ArrayStore();
		$base  = new Cache( 'mai', $store );
		$base->group( 'menu' )->set( 'a', 'M', 60 );
		$this->assertSame( 'M', $base->group( 'menu' )->get( 'a' ) );

		$base->flush();
		$this->assertFalse( $base->group( 'menu' )->get( 'a' ) );
	}
}
