<?php

namespace BizBudding\MaiEngine\Tests\Integration;

use ProteusThemes\WPContentImporter2\Importer;
use ProteusThemes\WPContentImporter2\WPImporterLogger;
use ReflectionClass;
use WP_Error;

/**
 * Covers the wizard importer's post-processing pass keeping block attribute escapes.
 *
 * The vendored WXRImporter::post_process_posts() rewrites image URLs in stored post_content and
 * hands it to wp_update_post() unslashed. wp_insert_post() unslashes its input
 * (wp-includes/post.php:4888), so every backslash in the content is lost and a block attribute
 * saved with `<` comes back as `u003c`.
 */
final class SetupWizardWxrImporterTest extends MaiIntegrationTestCase {

	private const OLD_URL = 'https://demo.example/wp-content/uploads/2024/01/a.jpg';

	private const NEW_URL = 'https://example.org/wp-content/uploads/2026/11/a.jpg';

	public function set_up(): void {
		parent::set_up();

		// The wizard loads this before the importer; the vendored WXRImporter extends it.
		if ( ! class_exists( 'WP_Importer' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-importer.php';
		}

		// wp_insert_post() runs kses on post_content unless the author may post unfiltered HTML.
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		add_filter( 'pre_http_request', [ $this, 'block_http' ], 10, 3 );
	}

	public function tear_down(): void {
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
		$this->fail( 'The importer made an HTTP request to ' . $url );

		return new WP_Error( 'blocked', 'HTTP is blocked in this test.' );
	}

	/**
	 * Content with an image URL to remap and an attribute that serializes with `\u` escapes.
	 * Built with core's serializer so the escapes are real.
	 */
	private function content( string $url ): string {
		$content = serialize_block(
			[
				'blockName'    => 'core/group',
				'attrs'        => [
					'className' => 'button--primary',
					'metadata'  => [ 'name' => 'Tom & <b>Jerry</b>' ],
				],
				'innerBlocks'  => [],
				'innerHTML'    => '<div class="wp-block-group"><img src="' . $url . '" alt=""/></div>',
				'innerContent' => [ '<div class="wp-block-group"><img src="' . $url . '" alt=""/></div>' ],
			]
		);

		$this->assertStringContainsString( chr( 92 ), $content, 'The fixture must carry backslash escapes.' );

		return $content;
	}

	/**
	 * Inserts the post as the importer does, queues it for remapping, and runs post_process().
	 *
	 * @return string The stored post_content afterwards.
	 */
	private function run_post_process( Importer $importer, int &$post_id ): string {
		$post_id = wp_insert_post(
			wp_slash(
				[
					'post_title'   => 'Imported',
					'post_content' => $this->content( self::OLD_URL ),
					'post_status'  => 'publish',
					'post_type'    => 'page',
				]
			),
			true
		);

		$this->assertIsInt( $post_id, 'wp_insert_post() failed' );
		$this->assertSame( $this->content( self::OLD_URL ), get_post( $post_id )->post_content, 'The insert must keep the escapes.' );

		add_post_meta( $post_id, '_wxr_import_has_attachment_refs', true );

		$class = new ReflectionClass( Importer::class );

		$url_remap = $class->getProperty( 'url_remap' );
		$url_remap->setValue( $importer, [ self::OLD_URL => self::NEW_URL ] );

		$remapping = $class->getProperty( 'requires_remapping' );
		$remapping->setValue( $importer, [ 'post' => [ $post_id => true ] ] );

		// Reflect on the instance's own class so the subclass override runs, not the parent's method.
		( new ReflectionClass( $importer ) )->getMethod( 'post_process' )->invoke( $importer );

		clean_post_cache( $post_id );

		return get_post( $post_id )->post_content;
	}

	public function test_wizard_importer_keeps_escapes_when_remapping_urls(): void {
		$importer = new \Mai_Setup_Wizard_Wxr_Importer( [ 'fetch_attachments' => false ], new WPImporterLogger() );
		$post_id  = 0;
		$stored   = $this->run_post_process( $importer, $post_id );

		$this->assertSame( $this->content( self::NEW_URL ), $stored );
		$this->assertSame( '', get_post_meta( $post_id, '_wxr_import_has_attachment_refs', true ) );
	}

	/**
	 * Documents the bug in the parent importer and proves the fixture would catch it.
	 */
	public function test_parent_importer_loses_escapes_when_remapping_urls(): void {
		$importer = new Importer( [ 'fetch_attachments' => false ], new WPImporterLogger() );
		$post_id  = 0;
		$stored   = $this->run_post_process( $importer, $post_id );

		$this->assertStringContainsString( self::NEW_URL, $stored );
		$this->assertStringNotContainsString( chr( 92 ), $stored );
		$this->assertNotSame( $this->content( self::NEW_URL ), $stored );
	}
}
