<?php

namespace BizBudding\MaiEngine\Tests\Integration;

/**
 * Covers mai_setup_wizard_slash_data() in lib/admin/setup-wizard.php.
 *
 * The two WXR importers Mai has to coexist with disagree about who slashes:
 *
 * - proteusthemes/wp-content-importer-v2 (vendored, used by the setup wizard) fires
 *   wp_import_post_data_processed and then calls wp_insert_post() directly, with no
 *   wp_slash(). See vendor/proteusthemes/wp-content-importer-v2/src/WXRImporter.php:853
 *   and :864. Without Mai's filter, block attribute JSON is eaten.
 *
 * - The official WordPress Importer plugin fires the same filter and then applies its own
 *   wp_slash( $postdata ). See class-wp-import.php:870-872, unchanged from 0.8 to 0.9.5.
 *   With Mai's filter also applied, the data is slashed twice and wp_insert_post()'s single
 *   wp_unslash() (wp-includes/post.php:4888) leaves one layer in the database.
 *
 * So the filter is correct for one importer and corrupting for the other. It has to be
 * scoped to the wizard's own import run, not registered for the whole admin.
 */
final class SetupWizardSlashDataTest extends MaiIntegrationTestCase {

	/**
	 * Block markup carrying both symptoms from the support report: JSON attributes on the
	 * delimiter, and an apostrophe in the body.
	 */
	private const CONTENT = '<!-- wp:heading {"level":3,"className":"is-style-fancy"} --><h3>It\'s a "test"</h3><!-- /wp:heading -->';

	/**
	 * wp_insert_post() runs post_content through kses unless the author may post unfiltered
	 * HTML, which would strip the block delimiters for reasons unrelated to slashing.
	 */
	public function set_up(): void {
		parent::set_up();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	/**
	 * Runs the given content through a filter-then-insert sequence and returns what actually
	 * landed in the database.
	 *
	 * @param bool $importer_slashes Whether the importer applies its own wp_slash() after the
	 *                               filter, as the official WordPress Importer does.
	 */
	private function import( bool $importer_slashes ): string {
		$postdata = [
			'post_title'   => 'Imported',
			'post_content' => self::CONTENT,
			'post_status'  => 'publish',
			'post_type'    => 'page',
		];

		$postdata = apply_filters( 'wp_import_post_data_processed', $postdata, [] );

		if ( $importer_slashes ) {
			$postdata = wp_slash( $postdata );
		}

		$post_id = wp_insert_post( $postdata, true );

		$this->assertIsInt( $post_id, 'wp_insert_post() failed' );

		return get_post( $post_id )->post_content;
	}

	/**
	 * The reported bug. Mai must not be slashing on behalf of an importer that already does.
	 */
	public function test_official_importer_content_survives_intact(): void {
		$this->assertSame( self::CONTENT, $this->import( true ) );
	}

	/**
	 * The reason the filter exists. Inside the wizard's own import the vendored importer does
	 * not slash, so Mai has to.
	 */
	public function test_wizard_import_content_survives_intact(): void {
		// Only the opening hook. mai_setup_wizard_after_import needs Genesis, which this suite
		// deliberately does not load, and the wizard's AJAX request ends after the import
		// anyway, so nothing unregisters the filter in production either.
		do_action( 'mai_setup_wizard_before_import', 'demo' );

		$this->assertSame( self::CONTENT, $this->import( false ) );
	}

	/**
	 * Direct lock on the scoping itself, so a future refactor cannot quietly re-register the
	 * filter globally and still pass the two round-trip tests by luck.
	 */
	public function test_filter_is_not_registered_outside_a_wizard_import(): void {
		$this->assertFalse( has_filter( 'wp_import_post_data_processed', 'mai_setup_wizard_slash_data' ) );
	}
}
