<?php

namespace BizBudding\MaiEngine\Tests\Integration;

use ProteusThemes\WPContentImporter2\Importer;
use ProteusThemes\WPContentImporter2\WPImporterLogger;
use ReflectionClass;
use WP_Error;

/**
 * Guards the vendored importer's post-processing pass keeping block attribute escapes.
 *
 * Importer 2.1.2 rewrote image URLs in stored post_content with an unslashed wp_update_post().
 * wp_insert_post() unslashes its input (wp-includes/post.php:4888), so every backslash was lost
 * and a block attribute saved with `<` came back as `u003c`. Mai carried a subclass to redo that
 * rewrite slashed. Importer 2.1.4 wraps the update in wp_slash() itself, so the subclass is gone
 * and these tests make sure an update to the vendored library cannot bring the loss back.
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
	private function run_post_process( int &$post_id, array $meta = [], ?array $mapping = null ): string {
		$importer = new Importer( [ 'fetch_attachments' => false ], new WPImporterLogger() );

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

		foreach ( $meta as $key => $value ) {
			add_post_meta( $post_id, $key, $value );
		}

		$class = new ReflectionClass( Importer::class );

		$class->getProperty( 'url_remap' )->setValue( $importer, [ self::OLD_URL => self::NEW_URL ] );
		$class->getProperty( 'requires_remapping' )->setValue( $importer, [ 'post' => [ $post_id => true ] ] );

		if ( null !== $mapping ) {
			$class->getProperty( 'mapping' )->setValue( $importer, $mapping );
		}

		$class->getMethod( 'post_process' )->invoke( $importer );

		clean_post_cache( $post_id );

		return get_post( $post_id )->post_content;
	}

	public function test_importer_keeps_escapes_when_remapping_urls(): void {
		$post_id = 0;
		$stored  = $this->run_post_process( $post_id );

		$this->assertSame( $this->content( self::NEW_URL ), $stored );
		$this->assertSame( '', get_post_meta( $post_id, '_wxr_import_has_attachment_refs', true ) );
	}

	public function test_importer_still_remaps_the_parent(): void {
		$target  = self::factory()->post->create( [ 'post_type' => 'page' ] );
		$post_id = 0;
		$stored  = $this->run_post_process(
			$post_id,
			[ '_wxr_import_parent' => 999 ],
			[
				'post'      => [ 999 => $target ],
				'user_slug' => [],
			]
		);

		$this->assertSame( $this->content( self::NEW_URL ), $stored );
		$this->assertSame( $target, (int) get_post( $post_id )->post_parent );
	}
}
