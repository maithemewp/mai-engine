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

add_action( 'after_setup_theme', 'mai_single_customizer_settings' );
/**
 * Add single customizer settings from post_types in config.
 *
 * @return  void
 */
function mai_single_customizer_settings() {
	$config     = mai_get_config( 'loop' );
	$post_types = isset( $config['single'] ) ? $config['single'] : [];

	if ( empty( $post_types ) ) {
		return;
	}

	Kirki::add_panel(
		mai_get_handle() . '-singular-content',
		[
			'title'       => esc_attr__( 'Singular Content', 'mai-engine' ),
			'description' => '',
			'priority'    => 80,
			'panel'       => mai_get_handle(),
		]
	);

	foreach ( $post_types as $post_type ) {
		if ( ! post_type_exists( $post_type ) ) {
			continue;
		}

		mai_add_single_customizer_settings( $post_type );
	}
}

/**
 * Add single customizer settings.
 *
 * @param  string $name The registered post type name.
 */
function mai_add_single_customizer_settings( $name ) {
	$panel_id   = mai_get_handle() . '-singular-content';
	$section_id = mai_get_handle() . '-single-' . $name;
	$post_type  = get_post_type_object( $name );
	$label      = $post_type->labels->name;
	$fields     = mai_get_config( 'single-settings' );

	Kirki::add_section(
		$section_id,
		[
			'title' => $label,
			'panel' => $panel_id,
		]
	);

	foreach ( $fields as $field ) {
		mai_add_customizer_field( $field, 'single', $name, $name );
		// Kirki::add_field(
		// 	mai_get_handle(),
		// 	mai_get_kirki_field_data( $field, $section_id, $name )
		// );
	}
}
