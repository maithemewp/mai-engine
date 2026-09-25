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
		mai_reset_options_cache();
		update_option( 'sidebars_widgets', [ 'wp_inactive_widgets' => [], 'sidebar' => [], 'array_version' => 3 ] );
		unset( $GLOBALS['mai_test_config'] );

		// Genesis turns the editor off at 0 on every Mai site.
		add_filter( 'use_widgets_block_editor', '__return_false', 0 );
	}

	/**
	 * Logs in as a new user with the given role.
	 *
	 * @param string $role The role.
	 *
	 * @return int The user ID.
	 */
	private function log_in_as( string $role ): int {
		$user = self::factory()->user->create( [ 'role' => $role ] );
		wp_set_current_user( $user );

		return $user;
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

	/**
	 * Saved choices, and widgets that would suggest the other one.
	 *
	 * @return array
	 */
	public static function saved_choices(): array {
		return [
			'saved blocks, classic widgets' => [ true, [ 'search-2' ] ],
			'saved classic, block widgets'  => [ false, [ 'block-1' ] ],
		];
	}

	/**
	 * @dataProvider saved_choices
	 *
	 * @param bool     $saved   The saved choice.
	 * @param string[] $widgets The widgets in the sidebar.
	 */
	public function test_the_upgrade_leaves_a_saved_choice_alone( bool $saved, array $widgets ): void {
		update_option( 'mai-engine', [ 'widgets-block-editor' => $saved ] );
		$this->use_widgets( $widgets );

		mai_upgrade_2_41_0_widgets();

		$this->assertSame( $saved, get_option( 'mai-engine' )['widgets-block-editor'] );
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
		$this->log_in_as( 'administrator' );
		$this->use_widgets( [ 'search-2' ] );

		$this->assertStringContainsString( 'Widget areas can now hold blocks.', $this->notice_html() );
	}

	public function test_the_block_screen_has_no_notice(): void {
		$this->log_in_as( 'administrator' );

		$this->assertSame( '', $this->notice_html() );
	}

	public function test_the_classic_notice_hides_once_dismissed(): void {
		$user = $this->log_in_as( 'administrator' );
		$this->use_widgets( [ 'search-2' ] );
		update_user_meta( $user, 'mai_widgets_block_editor_notice_dismissed', 1 );

		$this->assertSame( '', $this->notice_html() );
	}

	public function test_the_help_tab_offers_the_switch_on_the_classic_screen(): void {
		$this->log_in_as( 'administrator' );
		$this->use_widgets( [ 'search-2' ] );
		set_current_screen( 'widgets' );

		mai_widgets_editor_help_tab();

		$tab = get_current_screen()->get_help_tab( 'mai-block-widgets' );
		$this->assertNotNull( $tab );
		$this->assertStringContainsString( 'Switch to blocks', $tab['content'] );
		get_current_screen()->remove_help_tab( 'mai-block-widgets' );
	}

	public function test_there_is_no_help_tab_on_the_block_screen(): void {
		$this->log_in_as( 'administrator' );
		set_current_screen( 'widgets' );

		mai_widgets_editor_help_tab();

		$this->assertNull( get_current_screen()->get_help_tab( 'mai-block-widgets' ) );
	}

	/**
	 * Runs the switch handler and returns where it redirected.
	 *
	 * @param bool $nonce Whether to send a valid nonce.
	 *
	 * @return string
	 */
	private function run_switch_action( bool $nonce = true ): string {
		$_REQUEST['_wpnonce'] = $nonce ? wp_create_nonce( 'mai_switch_widgets_editor' ) : 'bad';

		add_filter(
			'wp_redirect',
			static function ( $location ) {
				throw new \RuntimeException( $location );
			}
		);

		try {
			mai_switch_widgets_editor_action();
		} catch ( \RuntimeException $redirect ) {
			return $redirect->getMessage();
		} finally {
			unset( $_REQUEST['_wpnonce'] );
		}

		return '';
	}

	public function test_the_switch_link_turns_blocks_on_and_leaves_widgets_alone(): void {
		$this->log_in_as( 'administrator' );

		// Classic widgets would make the default classic, so only the saved choice can give blocks.
		$this->use_widgets( [ 'mai_reusable_block_widget-2', 'search-2' ] );

		// Warm Mai's option cache first, the way mai_do_upgrade() does, to prove the save is seen.
		mai_get_option( 'first-version' );

		$this->assertSame( admin_url( 'widgets.php' ), $this->run_switch_action() );
		$this->assertTrue( wp_use_widgets_block_editor() );
		$this->assertSame( [ 'mai_reusable_block_widget-2', 'search-2' ], get_option( 'sidebars_widgets' )['sidebar'] );
	}

	public function test_the_switch_link_needs_a_nonce(): void {
		$this->log_in_as( 'administrator' );
		$this->use_widgets( [ 'search-2' ] );

		$this->expectException( \WPDieException::class );

		try {
			$this->run_switch_action( false );
		} finally {
			$this->assertFalse( wp_use_widgets_block_editor() );
		}
	}

	public function test_the_switch_link_needs_widget_permissions(): void {
		$this->log_in_as( 'editor' );
		$this->use_widgets( [ 'search-2' ] );

		$this->expectException( \WPDieException::class );

		try {
			$this->run_switch_action();
		} finally {
			$this->assertFalse( wp_use_widgets_block_editor() );
		}
	}

	public function test_when_code_forces_classic_the_switch_says_so_and_is_no_longer_offered(): void {
		$this->log_in_as( 'administrator' );
		$this->use_widgets( [ 'search-2' ] );
		add_filter( 'use_widgets_block_editor', '__return_false' );

		$this->assertStringContainsString( 'mai-widgets=forced', $this->run_switch_action() );

		$_GET['mai-widgets'] = 'forced';
		$this->assertStringContainsString( 'Code on this site keeps the classic Widgets screen', $this->notice_html() );
		unset( $_GET['mai-widgets'] );

		$this->assertStringNotContainsString( 'Switch to blocks', $this->notice_html() );

		set_current_screen( 'widgets' );
		mai_widgets_editor_help_tab();
		$this->assertNull( get_current_screen()->get_help_tab( 'mai-block-widgets' ) );
	}

	public function test_the_notice_needs_widget_permissions(): void {
		$this->log_in_as( 'editor' );
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

	public function test_the_help_tab_needs_widget_permissions(): void {
		$this->log_in_as( 'editor' );
		$this->use_widgets( [ 'search-2' ] );
		set_current_screen( 'widgets' );

		mai_widgets_editor_help_tab();

		$this->assertNull( get_current_screen()->get_help_tab( 'mai-block-widgets' ) );
	}

	public function test_hide_forever_needs_widget_permissions(): void {
		$user = $this->log_in_as( 'editor' );
		$_REQUEST['nonce'] = wp_create_nonce( 'mai_dismiss_widgets_block_editor_notice' );
		// Inside an AJAX request, wp_send_json_error() ends with wp_die() rather than a bare die.
		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', static fn() => static function () { throw new \WPDieException(); } );

		ob_start();

		try {
			mai_dismiss_widgets_block_editor_notice();
		} catch ( \WPDieException $die ) {
		} finally {
			ob_end_clean();
			unset( $_REQUEST['nonce'] );
		}

		$this->assertEmpty( get_user_meta( $user, 'mai_widgets_block_editor_notice_dismissed', true ) );
	}

	public function test_an_upgrade_from_2_40_saves_the_choice_through_the_real_routine(): void {
		update_option( 'mai-engine', [ 'db-version' => '2.40.0', 'first-version' => '2.30.0' ] );
		$this->use_widgets( [ 'search-2' ] );

		mai_do_upgrade();

		$options = get_option( 'mai-engine' );
		$this->assertFalse( $options['widgets-block-editor'] );
		$this->assertSame( '2.41.0', $options['db-version'] );
	}

	public function test_the_content_areas_notice_only_shows_on_the_classic_screen(): void {
		remove_all_actions( 'admin_notices' );
		mai_widgets_template_parts_admin_notice( \WP_Screen::get( 'widgets' ) );
		$this->assertFalse( has_action( 'admin_notices' ) );

		$this->use_widgets( [ 'search-2' ] );
		mai_widgets_template_parts_admin_notice( \WP_Screen::get( 'widgets' ) );
		$this->assertTrue( has_action( 'admin_notices' ) );
	}

	public function test_the_default_config_turns_blocks_on(): void {
		$config = (string) file_get_contents( dirname( __DIR__, 3 ) . '/config/_default.php' );

		$this->assertMatchesRegularExpression( "/'widgets'\\s*=>\\s*\\[\\s*'block-editor'\\s*=>\\s*true,/", $config );
	}
}
