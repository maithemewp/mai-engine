<?php

namespace BizBudding\MaiEngine\Tests\Integration;

/**
 * The block widget editor setting (#672).
 */
class WidgetsBlockEditorTest extends MaiIntegrationTestCase {

	/**
	 * Starts each test with no saved setting, no widgets and the default config.
	 */
	public function set_up(): void {
		parent::set_up();

		delete_option( 'mai-engine' );
		update_option( 'sidebars_widgets', [ 'wp_inactive_widgets' => [], 'sidebar' => [], 'array_version' => 3 ] );
		unset( $GLOBALS['mai_test_config'] );

		// Genesis turns the editor off at 0 on every Mai site.
		add_filter( 'use_widgets_block_editor', '__return_false', 0 );
	}

	/**
	 * Puts widgets in the sidebar.
	 *
	 * @param string[] $widget_ids The widget IDs.
	 */
	private function use_widgets( array $widget_ids ): void {
		update_option( 'sidebars_widgets', [ 'wp_inactive_widgets' => [ 'text-9' ], 'sidebar' => $widget_ids, 'array_version' => 3 ] );
	}

	public function test_a_new_site_uses_blocks_over_genesis(): void {
		$this->assertTrue( wp_use_widgets_block_editor() );
	}

	public function test_a_site_with_classic_widgets_keeps_the_classic_screen(): void {
		$this->use_widgets( [ 'search-2', 'block-1' ] );

		$this->assertFalse( wp_use_widgets_block_editor() );
	}

	public function test_block_and_inactive_widgets_do_not_count_as_classic(): void {
		$this->use_widgets( [ 'block-1', 'block-2' ] );

		$this->assertTrue( wp_use_widgets_block_editor() );
	}

	public function test_the_saved_setting_wins_over_the_widgets(): void {
		$this->use_widgets( [ 'search-2' ] );
		update_option( 'mai-engine', [ 'widgets-block-editor' => true ] );
		$this->assertTrue( wp_use_widgets_block_editor() );

		$this->use_widgets( [] );
		update_option( 'mai-engine', [ 'widgets-block-editor' => false ] );
		$this->assertFalse( wp_use_widgets_block_editor() );
	}

	public function test_config_can_turn_the_default_off(): void {
		$GLOBALS['mai_test_config'] = [ 'settings' => [ 'widgets' => [ 'block-editor' => false ] ] ];

		$this->assertFalse( wp_use_widgets_block_editor() );
	}

	public function test_code_at_the_default_priority_still_wins(): void {
		add_filter( 'use_widgets_block_editor', '__return_false' );
		$this->assertFalse( wp_use_widgets_block_editor() );

		remove_filter( 'use_widgets_block_editor', '__return_false' );
		update_option( 'mai-engine', [ 'widgets-block-editor' => false ] );
		add_filter( 'use_widgets_block_editor', '__return_true' );
		$this->assertTrue( wp_use_widgets_block_editor() );
	}

	public function test_the_upgrade_saves_classic_for_a_site_with_classic_widgets(): void {
		$this->use_widgets( [ 'search-2' ] );

		mai_upgrade_2_41_0_widgets();

		$this->assertFalse( get_option( 'mai-engine' )['widgets-block-editor'] );

		// The choice sticks after the widgets change.
		$this->use_widgets( [] );
		$this->assertFalse( wp_use_widgets_block_editor() );
	}

	public function test_the_upgrade_is_seen_in_the_same_request(): void {
		$this->use_widgets( [ 'search-2' ] );

		// Warm Mai's option cache first, the way mai_do_upgrade() does.
		mai_get_option( 'first-version' );
		mai_upgrade_2_41_0_widgets();

		$this->assertFalse( wp_use_widgets_block_editor() );
	}

	public function test_the_upgrade_leaves_a_saved_value_alone(): void {
		$this->use_widgets( [ 'search-2' ] );
		update_option( 'mai-engine', [ 'widgets-block-editor' => true ] );

		mai_upgrade_2_41_0_widgets();

		$this->assertTrue( get_option( 'mai-engine' )['widgets-block-editor'] );
	}

	public function test_the_genesis_notice_is_removed(): void {
		add_action( 'admin_notices', 'genesis_block_widgets_optin_notification' );

		mai_remove_genesis_block_widgets_notice();

		$this->assertFalse( has_action( 'admin_notices', 'genesis_block_widgets_optin_notification' ) );
	}

	/**
	 * Renders the widget editor notice for the Widgets screen, as the current user.
	 *
	 * @return string
	 */
	private function notice_html(): string {
		remove_all_actions( 'admin_notices' );
		mai_widgets_block_editor_notice( \WP_Screen::get( 'widgets' ) );

		ob_start();
		do_action( 'admin_notices' );

		return (string) ob_get_clean();
	}

	public function test_the_notice_shows_on_the_classic_screen(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$this->use_widgets( [ 'search-2' ] );

		$this->assertStringContainsString( 'Widget areas can now hold blocks.', $this->notice_html() );
	}

	public function test_the_notice_hides_once_blocks_are_on_or_it_is_dismissed(): void {
		$user = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user );

		$this->assertSame( '', $this->notice_html() );

		$this->use_widgets( [ 'search-2' ] );
		update_user_meta( $user, 'mai_widgets_block_editor_notice_dismissed', 1 );
		$this->assertSame( '', $this->notice_html() );
	}

	public function test_the_notice_needs_widget_permissions(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );
		$this->use_widgets( [ 'search-2' ] );

		$this->assertSame( '', $this->notice_html() );
	}

	public function test_a_new_install_saves_its_choice_so_it_cannot_flip(): void {
		mai_do_upgrade();

		$this->assertTrue( get_option( 'mai-engine' )['widgets-block-editor'] );

		// A plugin or import adds a classic widget later.
		$this->use_widgets( [ 'search-2' ] );
		$this->assertTrue( wp_use_widgets_block_editor() );
	}
}
