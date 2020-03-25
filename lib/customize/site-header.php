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

add_action( 'init', 'mai_header_customizer_settings' );
/**
 * Add header customizer settings.
 *
 * @return  void
 */
function mai_header_customizer_settings() {

	// Bail if no Kirki.
	if ( ! class_exists( 'Kirki' ) ) {
		return;
	}

	$config_id = 'mai_site_header';

	/**
	 * Kirki Config.
	 */
	Kirki::add_config(
		$config_id,
		[
			'capability'  => 'edit_theme_options',
			'option_type' => 'option',
			'option_name' => $config_id,
		]
	);

	// TODO: Header style: sticky, transparent, conceal, etc.
	// TODO: Mobile menu width/breakpoint.

	/**
	 * Kirki Config.
	 */
	Kirki::add_config(
		$config_id,
		[
			'capability'  => 'edit_theme_options',
			'option_type' => 'option',
			'option_name' => $config_id,
		]
	);

	Kirki::add_section(
		$config_id,
		[
			'title'       => esc_attr__( 'Site Header', 'mai-engine' ),
			'description' => '',
			'priority'    => 50,
		]
	);

	Kirki::add_field(
		$config_id,
		[
			'type'              => 'text',
			'label'             => esc_html__( 'Mobile Menu Breakpoint', 'mai-engine' ),
			'description'       => esc_html__( 'The largest screen width at which the mobile menu becomes active, in pixels.', 'mai-engine' ),
			'settings'          => 'mobile_breakpoint',
			'section'           => $config_id,
			'sanitize_callback' => 'absint',
			'default'           => '800',
		]
	);


}
