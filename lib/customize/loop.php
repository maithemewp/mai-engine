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

add_action( 'init', 'mai_archive_settings_customizer_settings' );
/**
 * Add archive content types customizer fields.
 * This needs to be on 'init' so custom post types and custom taxonomies are available.
 *
 * @since 0.2.0
 *
 * @return void
 */
function mai_archive_settings_customizer_settings() {
	$handle  = mai_get_handle();
	$section = $handle . '-content-archives';
	$choices = mai_get_loop_content_type_choices( true );
	$default = mai_get_config( 'archive-settings' );

	\Kirki::add_section(
		$section,
		[
			'title'    => __( 'Enable Content Types', 'mai-engine' ),
			'panel'    => $section,
			'priority' => 0,
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'        => 'multicheck',
			'settings'    => 'archive-settings',
			'section'     => $section,
			'label'       => __( 'Archive content types', 'mai-engine' ),
			'description' => __( 'Custom post types must support "mai-archive-settings" to be available here.', 'mai-engine' ),
			'default'     => $default,
			'choices'     => $choices,
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'        => 'custom',
			'settings'    => 'archive-settings-refresh',
			'section'     => $section,
			'label'       => esc_html__( 'Refresh after updating!', 'mai-engine' ),
			'description' => sprintf( '<p>%s</p>', esc_html__( 'In order to show/hide panels for the updated values you must reload the Customizer after saving any changes.', 'mai-engine' ) ),
		]
	);

}

add_action( 'init', 'mai_single_settings_customizer_settings' );
/**
 * Add single content types customizer fields.
 * This needs to be on 'init' so custom post types and custom taxonomies are available.
 *
 * @since 0.2.0
 *
 * @return void
 */
function mai_single_settings_customizer_settings() {
	$handle  = mai_get_handle();
	$section = $handle . '-single-content';
	$choices = mai_get_loop_content_type_choices( false );
	$default = mai_get_config( 'single-settings' );

	\Kirki::add_section(
		$section,
		[
			'title'    => __( 'Enable Content Types', 'mai-engine' ),
			'panel'    => $section,
			'priority' => 0,
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'        => 'multicheck',
			'settings'    => 'single-settings',
			'section'     => $section,
			'label'       => __( 'Single content types', 'mai-engine' ),
			'description' => __( 'Custom post types must support "mai-single-settings" to be available here.', 'mai-engine' ),
			'default'     => $default,
			'choices'     => $choices,
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'        => 'custom',
			'settings'    => 'single-settings-refresh',
			'section'     => $section,
			'label'       => esc_html__( 'Refresh after updating!', 'mai-engine' ),
			'description' => sprintf( '<p>%s</p>', esc_html__( 'In order to show/hide panels for the updated values you must reload the Customizer after saving any changes.', 'mai-engine' ) ),
		]
	);

}
