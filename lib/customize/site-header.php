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

	\Kirki::add_section(
		$section,
		[
			'title' => esc_html__( 'Site Header', 'mai-engine' ),
			'panel' => $handle,
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'     => 'checkbox',
			'settings' => 'site-header-sticky',
			'section'  => $section,
			'label'    => __( 'Enable sticky header?', 'mai-engine' ),
			'default'  => current_theme_supports( 'sticky-header' ),
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'     => 'checkbox',
			'settings' => 'site-header-transparent',
			'section'  => $section,
			'label'    => __( 'Enable transparent header?', 'mai-engine' ),
			'default'  => current_theme_supports( 'transparent-header' ),
		]
	);
}
