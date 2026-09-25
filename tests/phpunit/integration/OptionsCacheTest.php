<?php

namespace BizBudding\MaiEngine\Tests\Integration;

/**
 * Mai's option caches empty whenever the Mai option is written.
 */
class OptionsCacheTest extends MaiIntegrationTestCase {

	public function set_up(): void {
		parent::set_up();

		update_option( 'mai-engine', [ 'color' => 'red' ] );
		mai_reset_options_cache();
	}

	public function test_mai_update_option_is_seen_in_the_same_request(): void {
		$this->assertSame( 'red', mai_get_option( 'color' ) );

		mai_update_option( 'color', 'blue' );

		$this->assertSame( 'blue', mai_get_option( 'color' ) );
		$this->assertSame( 'blue', mai_get_options()['color'] );
	}

	public function test_a_direct_save_like_the_customizer_is_seen_in_the_same_request(): void {
		mai_get_option( 'color' );

		update_option( 'mai-engine', [ 'color' => 'green' ] );

		$this->assertSame( 'green', mai_get_option( 'color' ) );
	}

	public function test_adding_and_deleting_the_option_are_seen(): void {
		mai_get_option( 'color' );

		delete_option( 'mai-engine' );
		$this->assertSame( 'none', mai_get_option( 'color', 'none' ) );

		add_option( 'mai-engine', [ 'color' => 'teal' ] );
		$this->assertSame( 'teal', mai_get_option( 'color' ) );
	}

	public function test_other_options_leave_the_cache_alone(): void {
		mai_get_option( 'color' );

		// Written behind the cache's back, so only a reset would reveal it.
		global $wpdb;
		$wpdb->update( $wpdb->options, [ 'option_value' => maybe_serialize( [ 'color' => 'gray' ] ) ], [ 'option_name' => 'mai-engine' ] );
		wp_cache_delete( 'alloptions', 'options' );
		wp_cache_delete( 'mai-engine', 'options' );

		update_option( 'blogname', 'Something else' );

		$this->assertSame( 'red', mai_get_option( 'color' ) );
	}
}
