<?php

namespace BizBudding\MaiEngine\Tests\Integration;

/**
 * Covers mai_has_widget_area() in lib/functions/helpers.php.
 *
 * Sites upgraded from Mai Theme v1 can still have widgets saved to `header-right`
 * after Mai Engine stops registering that area. `is_active_sidebar()` reads only the
 * saved option, so the header opened an empty Header Right section and the logo
 * moved off center. The helper only counts areas that are registered.
 */
final class StaleWidgetAreaTest extends MaiIntegrationTestCase {

	public function set_up(): void {
		parent::set_up();

		$this->save_widgets( [ 'text-2' ] );
	}

	/**
	 * Saves widgets to `header-right` and drops core's front-end copy of the option.
	 *
	 * @param string[] $widget_ids The widget IDs.
	 */
	private function save_widgets( array $widget_ids ): void {
		update_option(
			'sidebars_widgets',
			[
				'wp_inactive_widgets' => [],
				'header-right'        => $widget_ids,
				'array_version'       => 3,
			]
		);

		$GLOBALS['_wp_sidebars_widgets'] = [];
	}

	public function tear_down(): void {
		unregister_sidebar( 'header-right' );

		parent::tear_down();
	}

	public function test_saved_widgets_in_an_unregistered_area_do_not_count(): void {
		$this->assertTrue( is_active_sidebar( 'header-right' ), 'Core still sees the stale widgets.' );
		$this->assertFalse( mai_has_widget_area( 'header-right' ) );
	}

	public function test_a_registered_area_with_widgets_counts(): void {
		register_sidebar( [ 'id' => 'header-right' ] );

		$this->assertTrue( mai_has_widget_area( 'header-right' ) );
	}

	public function test_a_registered_empty_area_does_not_count(): void {
		register_sidebar( [ 'id' => 'header-right' ] );
		$this->save_widgets( [] );

		$this->assertFalse( mai_has_widget_area( 'header-right' ) );
	}
}
