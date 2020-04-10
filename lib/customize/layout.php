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

add_action( 'after_setup_theme', 'mai_boxed_container_customizer_settings' );
/**
 * Add logo customizer settings.
 *
 * @return  void
 */
function mai_boxed_container_customizer_settings() {
	$config_id = mai_get_handle();

	Kirki::add_field(
		$config_id,
		[
			'type'     => 'custom',
			'settings' => 'layout_divider',
			'section'  => 'genesis_layout',
			'default'  => '<hr>',
			'priority' => 60,
		]
	);

	Kirki::add_field(
		$config_id,
		[
			'type'     => 'checkbox',
			'settings' => 'boxed_container',
			'label'    => __( 'Enable boxed container layout?', 'mai-engine' ),
			'section'  => 'genesis_layout',
			'default'  => current_theme_supports( 'boxed-container' ),
			'priority' => 70,
		]
	);
}
