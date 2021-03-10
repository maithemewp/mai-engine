<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2020 BizBudding
 * @license   GPL-2.0-or-later
 */

add_action( 'init', 'mai_site_header_customizer_settings' );
/**
 * Add base styles customizer settings.
 *
 * @since 0.3.0
 *
 * @return void
 */
function mai_site_header_customizer_settings() {
	$handle  = mai_get_handle();
	$section = $handle . '-site-header';

	Kirki::add_section(
		$section,
		[
			'title' => esc_html__( 'Site Header', 'mai-engine' ),
			'panel' => $handle,
		]
	);

	Kirki::add_field(
		$handle,
		[
			'type'     => 'checkbox',
			'settings' => 'site-header-sticky',
			'section'  => $section,
			'label'    => __( 'Enable sticky header?', 'mai-engine' ),
			'default'  => current_theme_supports( 'sticky-header' ),
		]
	);

	Kirki::add_field(
		$handle,
		[
			'type'     => 'checkbox',
			'settings' => 'site-header-transparent',
			'section'  => $section,
			'label'    => __( 'Enable transparent header?', 'mai-engine' ),
			'default'  => current_theme_supports( 'transparent-header' ),
		]
	);

	Kirki::add_field(
		$handle,
		[
			'type'        => 'sortable',
			'settings'    => 'site-header-mobile',
			'section'     => $section,
			'label'       => __( 'Mobile Header', 'mai-engine' ),
			'description' => __( 'Show/hide and re-order mobile header elements.', 'mai-engine' ),
			'default'     => mai_get_config( 'settings')['site-header-mobile'],
			'choices'     => [
				// These keys are used to build function names in mai_do_header().
				'title_area'     => has_custom_logo() ? esc_html__( 'Logo', 'mai-engine' ) : esc_html__( 'Site Title', 'mai-engine' ),
				'menu_toggle'    => esc_html__( 'Menu Toggle', 'mai-engine' ),
				'header_search'  => esc_html__( 'Search', 'mai-engine' ),
				'header_content' => esc_html__( 'Custom Content', 'mai-engine' ),
			],
		]
	);

	Kirki::add_field(
		$handle,
		[
			'type'     => 'textarea',
			'settings' => 'site-header-mobile-content',
			'section'  => $section,
			'label'    => __( 'Custom Content', 'mai-engine' ),
			'active_callback' => [
				[
					'setting'  => 'site-header-mobile',
					'operator' => 'contains',
					'value'    => 'header_content',
				],
			],
		]
	);
}
