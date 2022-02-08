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

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

add_action( 'init', 'mai_performance_customizer_settings' );
/**
 * Add performance customizer settings.
 *
 * @since 2.4.0 Moved defaults to config.
 * @since 1.0.0
 *
 * @return  void
 */
function mai_performance_customizer_settings() {
	$config_id  = mai_get_handle();
	$section_id = $config_id . '-performance';
	$defaults   = mai_get_config( 'settings' )['performance'];

	new \Kirki\Section(
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
			'default'  => $defaults['genesis-style-trump'],
		]
	);

	Kirki::add_field(
		$config_id,
		[
			'type'     => 'checkbox',
			'settings' => 'remove-menu-item-classes',
			'label'    => esc_html__( 'Remove menu item id and additional classes', 'mai-engine' ),
			'section'  => $section_id,
			'default'  => $defaults['remove-menu-item-classes'],
		]
	);

	Kirki::add_field(
		$config_id,
		[
			'type'     => 'checkbox',
			'settings' => 'remove-template-classes',
			'label'    => esc_html__( 'Remove additional page template body classes', 'mai-engine' ),
			'section'  => $section_id,
			'default'  => $defaults['remove-template-classes'],
		]
	);

	Kirki::add_field(
		$config_id,
		[
			'type'     => 'checkbox',
			'settings' => 'disable-emojis',
			'label'    => esc_html__( 'Disable emojis', 'mai-engine' ),
			'section'  => $section_id,
			'default'  => $defaults['disable-emojis'],
		]
	);

	Kirki::add_field(
		$config_id,
		[
			'type'     => 'checkbox',
			'settings' => 'remove-recent-comments-css',
			'label'    => esc_html__( 'Remove recent comments CSS', 'mai-engine' ),
			'section'  => $section_id,
			'default'  => $defaults['remove-recent-comments-css'],
		]
	);
}
