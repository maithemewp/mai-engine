<?php

namespace BizBudding\MaiEngine\Tests\Integration;


/**
 * Verifies the harness itself: WordPress boots, the plugin loader ran, and the database is
 * configured for the content this plugin handles. Kept separate from feature tests so a
 * broken harness fails with an obvious cause.
 */
final class HarnessTest extends MaiIntegrationTestCase {

	public function test_wordpress_is_loaded(): void {
		$this->assertTrue( class_exists( 'WP_HTML_Tag_Processor' ) );
		$this->assertTrue( function_exists( 'mai_isset' ) );
	}

	public function test_plugin_loader_registered_the_filter(): void {
		$this->assertNotFalse( has_filter( 'render_block', 'mai_render_block_handle_link_color' ) );
	}

	public function test_factories_and_utf8mb4(): void {
		$id = self::factory()->post->create( [ 'post_title' => 'Zażółć gęślą 🎉' ] );
		$this->assertSame( 'Zażółć gęślą 🎉', get_post( $id )->post_title );
	}
}
