<?php

namespace BizBudding\MaiEngine\Tests\Integration;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Covers mai_upgrade_2_42_0_widget_areas() in lib/admin/upgrade.php and the v1
 * fallback check in lib/functions/deprecated.php.
 *
 * Widgets saved to Mai Theme v1 areas that Mai Engine does not register are moved to
 * Inactive Widgets. Registered areas, and areas outside the v1 list, are left alone.
 *
 * Each test runs in its own process: deprecated.php defines functions other tests
 * assert are gone, such as mai_setup_wizard_add_slash_data().
 */
#[RunTestsInSeparateProcesses]
final class DeprecatedWidgetAreasUpgradeTest extends MaiIntegrationTestCase {

	public function set_up(): void {
		parent::set_up();

		require_once dirname( __DIR__, 3 ) . '/lib/functions/deprecated.php';
	}

	public function tear_down(): void {
		unregister_sidebar( 'header-right' );
		remove_filter( 'mai_config', 'mai_deprecated_2_0_0' );

		parent::tear_down();
	}

	/**
	 * Saves widget areas and drops core's front-end copy of the option.
	 *
	 * @param array $areas Widget IDs keyed by area ID.
	 */
	private function save_widgets( array $areas ): void {
		update_option( 'sidebars_widgets', $areas + [ 'array_version' => 3 ] );

		$GLOBALS['_wp_sidebars_widgets'] = [];
	}

	public function test_unregistered_v1_areas_move_to_inactive(): void {
		$this->save_widgets(
			[
				'wp_inactive_widgets' => [ 'text-9' ],
				'header-right'        => [ 'text-2', 'search-3' ],
				'footer-credits'      => [ 'text-4' ],
				'mobile-menu'         => [],
			]
		);

		mai_upgrade_2_42_0_widget_areas();

		$saved = get_option( 'sidebars_widgets' );

		$this->assertSame( [ 'text-9', 'text-2', 'search-3', 'text-4' ], $saved['wp_inactive_widgets'] );
		$this->assertArrayNotHasKey( 'header-right', $saved );
		$this->assertArrayNotHasKey( 'footer-credits', $saved );
		$this->assertArrayNotHasKey( 'mobile-menu', $saved );
		$this->assertSame( 3, $saved['array_version'] );
	}

	public function test_registered_and_non_v1_areas_are_left_alone(): void {
		register_sidebar( [ 'id' => 'header-right' ] );

		$this->save_widgets(
			[
				'wp_inactive_widgets' => [],
				'header-right'        => [ 'text-2' ],
				'some-plugin-area'    => [ 'text-5' ],
			]
		);

		mai_upgrade_2_42_0_widget_areas();

		$saved = get_option( 'sidebars_widgets' );

		$this->assertSame( [ 'text-2' ], $saved['header-right'] );
		$this->assertSame( [ 'text-5' ], $saved['some-plugin-area'] );
		$this->assertSame( [], $saved['wp_inactive_widgets'] );
	}

	public function test_nothing_is_written_when_there_is_nothing_to_move(): void {
		$this->save_widgets( [ 'wp_inactive_widgets' => [] ] );

		$writes = 0;
		$count  = function () use ( &$writes ) {
			++$writes;
		};

		add_action( 'update_option_sidebars_widgets', $count );
		mai_upgrade_2_42_0_widget_areas();
		remove_action( 'update_option_sidebars_widgets', $count );

		$this->assertSame( 0, $writes );
	}

	public function test_only_before_header_turns_the_fallback_on(): void {
		$this->save_widgets( [ 'header-right' => [ 'text-2' ], 'footer' => [ 'text-3' ] ] );
		mai_do_deprecated_functionality();
		$this->assertFalse( has_filter( 'mai_config', 'mai_deprecated_2_0_0' ) );

		$this->save_widgets( [ 'before-header' => [ 'text-1' ] ] );
		mai_do_deprecated_functionality();
		$this->assertNotFalse( has_filter( 'mai_config', 'mai_deprecated_2_0_0' ) );
	}

	public function test_the_v1_area_list_is_unchanged(): void {
		$this->assertSame(
			[
				'before-header',
				'header-left',
				'header-right',
				'sidebar',
				'after-entry',
				'mobile-menu',
				'before-footer',
				'footer',
				'footer-credits',
			],
			array_keys( mai_get_deprecated_widget_areas() )
		);
	}
}
