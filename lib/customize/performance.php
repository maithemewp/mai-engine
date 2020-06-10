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


add_action( 'init', 'mai_performance_customizer_settings' );
/**
 * Add logo customizer settings.
 *
 * @return  void
 */
function mai_performance_customizer_settings() {
	$config_id  = mai_get_handle();
	$section_id = $config_id . '-performance';

	\Kirki::add_section(
		$section_id,
		[
			'title' => __( 'Performance', 'mai-engine' ),
			'panel' => $config_id,
		]
	);

	Kirki::add_field(
		$config_id,
		[
			'type'     => 'checkbox',
			'settings' => 'genesis-style-trump',
			'label'    => esc_html__( 'Load child theme stylesheet in footer', 'mai-engine' ),
			'section'  => $section_id,
			'default'  => true,
		]
	);

	Kirki::add_field(
		$config_id,
		[
			'type'     => 'checkbox',
			'settings' => 'remove-menu-item-classes',
			'label'    => esc_html__( 'Remove extra menu item classes', 'mai-engine' ),
			'section'  => $section_id,
			'default'  => true,
		]
	);
}
