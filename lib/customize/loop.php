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

use Kirki\Util\Helper;

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

add_action( 'init', 'mai_archive_settings_customizer_settings', 99 );
/**
 * Add archive content types customizer fields.
 * This needs to be on 'init' so custom post types and custom taxonomies are available.
 *
 * Priority must match for all instances of mai_get_content_type_(context)_choices()
 * because they are statically cached.
 *
 * @since 0.2.0
 *
 * @return void
 */
function mai_archive_settings_customizer_settings() {
	$handle  = mai_get_handle();
	$section = $handle . '-content-archives';
	$choices = mai_get_loop_content_type_choices( true );

	new \Kirki\Section(
		$section,
		[
			'title'    => __( 'Enable Content Types', 'mai-engine' ),
			'panel'    => $section,
			'priority' => 0,
		]
	);

	new \Kirki\Field\Multicheck(
		mai_parse_kirki_args(
			[
				'settings'    => mai_get_kirki_setting( 'archive-settings' ),
				'section'     => $section,
				'label'       => __( 'Archive content types', 'mai-engine' ),
				'description' => __( 'Custom post types must support "mai-archive-settings" to be available here.', 'mai-engine' ),
				'default'     => mai_get_config( 'settings' )['content-archives']['enable'],
				'choices'     => $choices,
			]
		)
	);

	new \Kirki\Field\Custom(
		mai_parse_kirki_args(
			[
				'settings'    => mai_get_kirki_setting( 'archive-settings-refresh' ),
				'section'     => $section,
				'label'       => esc_html__( 'Refresh after updating!', 'mai-engine' ),
				'description' => sprintf( '<p>%s</p>', esc_html__( 'In order to show/hide panels for the updated values you must reload the Customizer after saving any changes.', 'mai-engine' ) ),
			]
		)
	);
}

add_action( 'init', 'mai_single_settings_customizer_settings', 99 );
/**
 * Add single content types customizer fields.
 * This needs to be on 'init' so custom post types and custom taxonomies are available.
 *
 * Priority must match for all instances of mai_get_content_type_(context)_choices()
 * because they are statically cached.
 *
 * @since 0.2.0
 *
 * @return void
 */
function mai_single_settings_customizer_settings() {
	$handle  = mai_get_handle();
	$section = $handle . '-single-content';
	$choices = mai_get_loop_content_type_choices( false );

	new \Kirki\Section(
		$section,
		[
			'title'    => __( 'Enable Content Types', 'mai-engine' ),
			'panel'    => $section,
			'priority' => 0,
		]
	);

	new \Kirki\Field\Multicheck(
		mai_parse_kirki_args(
			[
				'settings'    => mai_get_kirki_setting( 'single-settings' ),
				'section'     => $section,
				'label'       => __( 'Single content types', 'mai-engine' ),
				'description' => __( 'Custom post types must support "mai-single-settings" to be available here.', 'mai-engine' ),
				'default'     => mai_get_config( 'settings' )['single-content']['enable'],
				'choices'     => $choices,
			]
		)
	);

	new \Kirki\Field\Custom(
		mai_parse_kirki_args(
			[
				'settings'    => mai_get_kirki_setting( 'single-settings-refresh' ),
				'section'     => $section,
				'label'       => esc_html__( 'Refresh after updating!', 'mai-engine' ),
				'description' => sprintf( '<p>%s</p>', esc_html__( 'In order to show/hide panels for the updated values you must reload the Customizer after saving any changes.', 'mai-engine' ) ),
			]
		)
	);
}
