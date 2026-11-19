<?php

namespace BizBudding\MaiEngine\Tests\Integration;

use ProteusThemes\WPContentImporter2\Importer;
use ProteusThemes\WPContentImporter2\WPImporterLogger;
use ReflectionClass;

/**
 * Guards the slashing contract between the setup wizard and its vendored importer.
 *
 * History: Mai used to register its own wp_slash() on wp_import_post_data_processed for the
 * wizard's import, because proteusthemes/wp-content-importer-v2 (up to 2.1.2) handed the data to
 * wp_insert_post() unslashed, and the single wp_unslash() inside wp_insert_post() ate block
 * attribute JSON. The official WordPress Importer plugin already slashes after that filter, so
 * a global Mai filter double-slashed its imports (`{\"level\":3}` and `\'` in the database).
 * Mai then scoped the filter to the wizard run.
 *
 * Importer 2.1.4 slashes the post data itself, right after the filter, and in the
 * post-processing update too. Mai's filter would now slash wizard imports twice, so it is gone.
 * These tests keep it gone and prove the importer alone stores the content exactly as exported.
 */
final class SetupWizardSlashDataTest extends MaiIntegrationTestCase {

	public function set_up(): void {
		parent::set_up();

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
	 * @param mixed  $response Short-circuit value.
	 * @param array  $args     Request arguments.
	 * @param string $url      Request URL.
	 *
	 * @return \WP_Error
	 */
	public function block_http( $response, $args, $url ) {
		$this->fail( 'The importer made an HTTP request to ' . $url );

		return new \WP_Error( 'blocked', 'HTTP is blocked in this test.' );
	}

	/**
	 * Block with a `\u` escape in an attribute (built by core's serializer so it is real), an
	 * apostrophe and a double quote in the body, and no image URL so no remap pass is queued.
	 */
	private function content(): string {
		$content = serialize_block(
			[
				'blockName'    => 'core/heading',
				'attrs'        => [
					'level'     => 3,
					'className' => 'is-style-fancy',
					'metadata'  => [ 'name' => 'Tom & <b>Jerry</b>' ],
				],
				'innerBlocks'  => [],
				'innerHTML'    => '<h3>It\'s a "test"</h3>',
				'innerContent' => [ '<h3>It\'s a "test"</h3>' ],
			]
		);

		$this->assertStringContainsString( chr( 92 ), $content, 'The fixture must carry backslash escapes.' );
		$this->assertStringContainsString( "'", $content, 'The fixture must carry an apostrophe.' );

		return $content;
	}

	/**
	 * Nothing Mai registers may slash on the importer's filter, or wizard imports are slashed twice.
	 */
	public function test_wizard_registers_nothing_on_the_post_data_filter(): void {
		do_action( 'mai_setup_wizard_before_import', 'x' );

		$this->assertFalse( has_filter( 'wp_import_post_data_processed' ), 'No callback should be on wp_import_post_data_processed.' );
		$this->assertFalse( function_exists( 'mai_setup_wizard_slash_data' ) );
		$this->assertFalse( function_exists( 'mai_setup_wizard_add_slash_data' ) );
	}

	/**
	 * Runs the vendored importer's real process_post() and checks what landed in the database.
	 */
	public function test_vendored_importer_stores_content_unslashed_exactly_once(): void {
		do_action( 'mai_setup_wizard_before_import', 'x' );

		$content  = $this->content();
		$importer = new Importer( [ 'fetch_attachments' => false ], new WPImporterLogger() );
		$class    = new ReflectionClass( Importer::class );
		$method   = $class->getMethod( 'process_post' );

		// import() normally sets this. Left at zero, the importer's pre-process filter thinks the
		// request has run for decades and ends it with a "new AJAX request" response.
		$class->getProperty( 'start_time' )->setValue( $importer, microtime( true ) );

		$method->invoke(
			$importer,
			[
				'post_id'      => 9001,
				'post_type'    => 'page',
				'post_title'   => 'Slash contract',
				'post_author'  => '',
				'post_content' => $content,
				'post_status'  => 'publish',
				'post_date'    => '2024-01-01 00:00:00',
				'guid'         => 'https://demo.example/?page_id=9001',
				'is_sticky'    => '0',
			],
			[],
			[],
			[]
		);

		$posts = get_posts(
			[
				'post_type'   => 'page',
				'title'       => 'Slash contract',
				'post_status' => 'any',
			]
		);

		$this->assertCount( 1, $posts, 'The importer should have inserted the page.' );
		$this->assertSame( $content, $posts[0]->post_content );
	}
}
