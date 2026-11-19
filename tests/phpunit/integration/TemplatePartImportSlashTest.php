<?php

namespace BizBudding\MaiEngine\Tests\Integration;

use WP_Error;

/**
 * Covers the backslashes in a demo content area surviving mai_import_template_part().
 *
 * serialize_block_attributes() saves `--`, `<`, `>`, `&` and `"` in block attributes as `\u` escapes,
 * and wp_insert_post() unslashes its input (wp-includes/post.php:4888). Unslashed content loses
 * every backslash, so a class like `button--primary` comes back as `buttonu002du002dprimary`.
 */
final class TemplatePartImportSlashTest extends MaiIntegrationTestCase {

	public function set_up(): void {
		parent::set_up();

		// wp_insert_post() runs kses on post_content unless the author may post unfiltered HTML.
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		add_filter( 'pre_http_request', [ $this, 'block_http' ], 10, 3 );
	}

	public function tear_down(): void {
		mai_cache( 'demo' )->delete( 'objects' );
		remove_filter( 'pre_http_request', [ $this, 'block_http' ], 10 );

		parent::tear_down();
	}

	/**
	 * Fails the test on any HTTP request.
	 *
	 * @param false|array|WP_Error $response Short-circuit value.
	 * @param array                $args     Request arguments.
	 * @param string               $url      Request URL.
	 *
	 * @return WP_Error
	 */
	public function block_http( $response, $args, $url ) {
		$this->fail( 'The content area import made an HTTP request to ' . $url );

		return new WP_Error( 'blocked', 'HTTP is blocked in this test.' );
	}

	/**
	 * A content area with no images, so mai_localize_blocks() returns it unchanged and never
	 * reaches the network. Built with core's serializer so the attributes carry real escapes.
	 */
	private function content(): string {
		$html = '<div class="wp-block-group button--primary"><p>x</p></div>';

		return serialize_block(
			[
				'blockName'    => 'core/group',
				'attrs'        => [
					'className' => 'button--primary',
					'metadata'  => [ 'name' => 'Tom & Jerry "Live"' ],
				],
				'innerBlocks'  => [],
				'innerHTML'    => $html,
				'innerContent' => [ $html ],
			]
		);
	}

	public function test_import_keeps_escaped_block_attributes(): void {
		$content = $this->content();

		$this->assertStringContainsString( 'button\\u002d\\u002dprimary', $content, 'The fixture must carry escapes.' );

		mai_cache( 'demo' )->set( 'objects', [ 'footer' => $content ], HOUR_IN_SECONDS );

		$result = mai_import_template_part( 'footer', 'always' );

		$this->assertIsInt( $result['id'], (string) $result['message'] );
		$this->assertSame( $content, get_post_field( 'post_content', $result['id'], 'raw' ) );
	}
}
