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

add_action( 'init', 'mai_page_header_customizer_settings', 99 );
/**
 * Add page header customizer fields.
 * This needs to be on 'init' so custom post types and custom taxonomies are available.
 *
 * Priority must match for all instances of mai_get_content_type_(context)_choices()
 * because they are statically cached.
 *
 * @since 2.4.0 Moved defaults to config.
 * @since 0.3.0
 *
 * @return void
 */
function mai_page_header_customizer_settings() {
	$handle   = mai_get_handle();
	$section  = $handle . '-page-header';
	$defaults = mai_get_config( 'settings' )['page-header'];
	$single   = mai_get_content_type_single_choices();
	$archive  = mai_get_content_type_archive_choices();

	if ( '*' === $defaults['single'] ) {
		$defaults['single'] = [];

		foreach ( $single as $name => $object ) {
			$defaults['single'][] = $name;
		}
	}

	if ( '*' === $defaults['archive'] ) {
		$defaults['archive'] = [];

		foreach ( $archive as $name => $object ) {
			$defaults['archive'][] = $name;
		}
	}

	Kirki::add_section(
		$section,
		[
			'title' => __( 'Page Header', 'mai-engine' ),
			'panel' => $handle,
		]
	);

	Kirki::add_field(
		$handle,
		[
			'type'        => 'multicheck',
			'settings'    => 'page-header-single',
			'section'     => $section,
			'label'       => __( 'Enable on singular content', 'mai-engine' ),
			'description' => __( 'These settings can be overridden on a per post basis.', 'mai-engine' ),
			'default'     => $defaults['single'],
			'choices'     => $single,
		]
	);

	Kirki::add_field(
		$handle,
		[
			'type'     => 'multicheck',
			'settings' => 'page-header-archive',
			'section'  => $section,
			'label'    => __( 'Enable on content archives', 'mai-engine' ),
			'default'  => $defaults['archive'],
			'choices'  => $archive,
		]
	);

	Kirki::add_field(
		$handle,
		[
			'type'        => 'dimensions',
			'settings'    => 'page-header-spacing',
			'section'     => $section,
			'label'       => __( 'Vertical Spacing', 'mai-engine' ),
			'description' => __( 'Accepts all unit values (px, rem, em, vw, etc).', 'mai-engine' ),
			'default'     => $defaults['spacing'],
			'choices'     => [
				'top'    => __( 'Top', 'mai-engine' ),
				'bottom' => __( 'Bottom', 'mai-engine' ),
			],
			'input_attrs' => [
				'placeholder' => '10vw',
			],
		]
	);

	Kirki::add_field(
		$handle,
		[
			'type'     => 'radio-buttonset',
			'settings' => 'page-header-content-width',
			'section'  => $section,
			'label'    => __( 'Content Width', 'mai-engine' ),
			'default'  => $defaults['content-width'],
			'choices'  => [
				'xs' => __( 'XS', 'mai-engine' ),
				'sm' => __( 'S', 'mai-engine' ),
				'md' => __( 'M', 'mai-engine' ),
				'lg' => __( 'L', 'mai-engine' ),
				'xl' => __( 'XL', 'mai-engine' ),
			],
		]
	);

	Kirki::add_field(
		$handle,
		[
			'type'     => 'radio-buttonset',
			'settings' => 'page-header-content-align',
			'section'  => $section,
			'label'    => __( 'Content Alignment', 'mai-engine' ),
			'default'  => $defaults['content-align'],
			'choices'  => [
				'start'  => __( 'Start', 'mai-engine' ),
				'center' => __( 'Center', 'mai-engine' ),
				'end'    => __( 'End', 'mai-engine' ),
			],
		]
	);

	Kirki::add_field(
		$handle,
		[
			'type'     => 'radio-buttonset',
			'settings' => 'page-header-text-align',
			'section'  => $section,
			'label'    => __( 'Text Alignment', 'mai-engine' ),
			'default'  => $defaults['text-align'],
			'choices'  => [
				'start'  => __( 'Start', 'mai-engine' ),
				'center' => __( 'Center', 'mai-engine' ),
				'end'    => __( 'End', 'mai-engine' ),
			],
		]
	);

	Kirki::add_field(
		$handle,
		[
			'type'        => 'image',
			'settings'    => 'page-header-image',
			'section'     => $section,
			'label'       => __( 'Default image', 'mai-engine' ),
			'description' => __( 'This can be overridden on a per post basis.', 'mai-engine' ),
			'default'     => $defaults['image'],
			'choices'     => [
				'save_as' => 'id',
			],
		]
	);

	Kirki::add_field(
		$handle,
		[
			'type'     => 'color',
			'settings' => 'page-header-background-color',
			'section'  => $section,
			'label'    => __( 'Background/overlay color', 'mai-engine' ),
			'default'  => mai_get_color_value( $defaults['background-color'] ),
			'choices'  => [
				'palettes' => mai_get_color_choices(),
			],
		]
	);

	Kirki::add_field(
		$handle,
		[
			'type'        => 'slider',
			'settings'    => 'page-header-overlay-opacity',
			'section'     => $section,
			'label'       => __( 'Overlay opacity', 'mai-engine' ),
			'description' => __( 'The background color opacity when page header has an image. Use 0 for none, and 1 for theme default.', 'mai-engine' ),
			'default'     => $defaults['overlay-opacity'],
			'choices'     => [
				'min'  => 0,
				'max'  => 1,
				'step' => 0.01,
			],
		]
	);

	Kirki::add_field(
		$handle,
		[
			'settings' => 'page-header-text-color',
			'section'  => $section,
			'label'    => __( 'Default text color', 'mai-engine' ),
			'type'     => 'radio-buttonset',
			'default'  => $defaults['text-color'],
			'choices'  => [
				'light' => __( 'Light', 'mai-engine' ),
				'dark'  => __( 'Dark', 'mai-engine' ),
			],
		]
	);

	Kirki::add_field(
		$handle,
		[
			'type'     => 'select',
			'settings' => 'page-header-divider',
			'section'  => $section,
			'label'    => __( 'Divider style', 'mai-engine' ),
			'default'  => $defaults['divider'],
			'choices'  => [
				''      => __( 'None', 'mai-engine' ),
				'angle' => __( 'Angle', 'mai-engine' ),
				'curve' => __( 'Curve', 'mai-engine' ),
				'point' => __( 'Point', 'mai-engine' ),
				'round' => __( 'Round', 'mai-engine' ),
				'wave'  => __( 'Wave', 'mai-engine' ),
			],
		]
	);

	Kirki::add_field(
		$handle,
		[
			'type'            => 'radio-buttonset',
			'settings'        => 'page-header-divider-height',
			'section'         => $section,
			'label'           => __( 'Divider height', 'mai-engine' ),
			'default'         => $defaults['divider-height'],
			'choices'         => [
				'xs' => __( 'XS', 'mai-engine' ),
				'sm' => __( 'S', 'mai-engine' ),
				'md' => __( 'M', 'mai-engine' ),
				'lg' => __( 'L', 'mai-engine' ),
				'xl' => __( 'XL', 'mai-engine' ),
			],
			'active_callback' => [
				[
					'setting'  => 'page-header-divider',
					'operator' => '!==',
					'value'    => '',
				],
			],
		]
	);

	Kirki::add_field(
		$handle,
		[
			'type'            => 'toggle',
			'settings'        => 'page-header-divider-flip-horizontal',
			'section'         => $section,
			'label'           => __( 'Flip divider horizontally', 'mai-engine' ),
			'default'         => $defaults['divider-flip-horizontal'],
			'active_callback' => [
				[
					'setting'  => 'page-header-divider',
					'operator' => '!==',
					'value'    => '',
				],
				[
					'setting'  => 'page-header-divider',
					'operator' => '!==',
					'value'    => 'point',
				],
				[
					'setting'  => 'page-header-divider',
					'operator' => '!==',
					'value'    => 'round',
				],
			],
		]
	);

	Kirki::add_field(
		$handle,
		[
			'type'            => 'toggle',
			'settings'        => 'page-header-divider-flip-vertical',
			'section'         => $section,
			'label'           => __( 'Flip divider vertically', 'mai-engine' ),
			'default'         => $defaults['divider-flip-vertical'],
			'active_callback' => [
				[
					'setting'  => 'page-header-divider',
					'operator' => '!==',
					'value'    => '',
				],
			],
		]
	);

	Kirki::add_field(
		$handle,
		[
			'type'            => 'color',
			'settings'        => 'page-header-divider-color',
			'section'         => $section,
			'label'           => __( 'Divider color', 'mai-engine' ),
			'description'     => __( 'This should match your body background color', 'mai-engine' ),
			'default'         => mai_get_color_value( $defaults['divider-color'] ),
			'choices'         => [
				'alpha'    => true,
				'palettes' => mai_get_color_choices(),
			],
			'active_callback' => [
				[
					[
						'setting'  => 'page-header-divider',
						'operator' => '!==',
						'value'    => '',
					],
				],
			],
		]
	);
}
