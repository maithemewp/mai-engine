<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2019 BizBudding
 * @license   GPL-2.0-or-later
 */

add_action( 'after_setup_theme', 'mai_header_customizer_settings' );
/**
 * Add header customizer settings.
 *
 * @return  void
 */
function mai_header_customizer_settings() {
	$config_id  = mai_get_handle();
	$panel_id   = $config_id;
	$section_id = $panel_id . '-site-header';

	Kirki::add_section(
		$section_id,
		[
			'title'       => esc_attr__( 'Site Header', 'mai-engine' ),
			'description' => '',
			'priority'    => 50,
			'panel'       => $panel_id,
		]
	);

	Kirki::add_field(
		$config_id,
		[
			'type'              => 'text',
			'label'             => esc_html__( 'Mobile Menu Breakpoint', 'mai-engine' ),
			'description'       => esc_html__( 'The largest screen width at which the mobile menu becomes active, in pixels.', 'mai-engine' ),
			'settings'          => 'breakpoint',
			'section'           => $section_id,
			'sanitize_callback' => 'absint',
			'default'           => mai_get_breakpoint(),
		]
	);
}
