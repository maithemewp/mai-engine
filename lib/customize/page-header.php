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

add_action( 'init', 'mai_page_header_customizer_settings' );
/**
 * Add page header customizer fields.
 * This needs to be on 'init' so custom post types and custom taxonomies are available.
 *
 * @since 0.3.0
 *
 * @return void
 */
function mai_page_header_customizer_settings() {
	$handle          = mai_get_handle();
	$section         = $handle . '-page-header';
	$config          = mai_get_config( 'page-header' );
	$archives        = mai_get_content_type_choices( $archive = true );
	$singles         = mai_get_content_type_choices( $archive = false );
	$archive_default = [];
	$single_default  = [];

	if ( isset( $config['archive'] ) && ! empty( $config['archive'] && is_array( $config['archive'] ) ) ) {
		$archive_default = $config['archive'];
	}

	if ( isset( $config['single'] ) && ! empty( $config['single'] && is_array( $config['single'] ) ) ) {
		$single_default = $config['single'];
	}

	if ( $archives ) {
		foreach ( $archives as $name => $object ) {
			if ( ( '*' === $config ) || ( isset( $config['archive'] ) && '*' === $config['archive'] ) ) {
				$archive_default[] = $name;
			}
		}
	}

	if ( $singles ) {
		foreach ( $singles as $name => $object ) {
			if ( ( '*' === $config ) || ( isset( $config['single'] ) && '*' === $config['single'] ) ) {
				$single_default[] = $name;
			}
		}
	}

	\Kirki::add_section(
		$section,
		[
			'title' => __( 'Page Header', 'mai-engine' ),
			'panel' => $handle,
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'        => 'multicheck',
			'settings'    => 'page-header-single',
			'section'     => $section,
			'label'       => __( 'Enable on singular content', 'mai-engine' ),
			'description' => __( 'These settings can be overridden on a per post basis.', 'mai-engine' ),
			'default'     => $single_default,
			'choices'     => $singles,
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'     => 'multicheck',
			'settings' => 'page-header-archive',
			'section'  => $section,
			'label'    => __( 'Enable on content archives', 'mai-engine' ),
			'default'  => $archive_default,
			'choices'  => $archives,
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'        => 'dimensions',
			'settings'    => 'page-header-spacing',
			'section'     => $section,
			'label'       => __( 'Vertical Spacing', 'mai-engine' ),
			'description' => __( 'Accepts all unit values (px, rem, em, vw, etc).', 'mai-engine' ),
			'default'     => $config['spacing'],
			'choices'     => [
				'top'    => __( 'Top', 'mai-engine' ),
				'bottom' => __( 'Bottom', 'mai-engine' ),
			],
			'output'      => [
				[
					'choice'   => 'top',
					'element'  => ':root',
					'property' => '--page-header-padding-top',
				],
				[
					'choice'   => 'bottom',
					'element'  => ':root',
					'property' => '--page-header-padding-bottom',
				],
			],
			'input_attrs' => [
				'placeholder' => '10vw',
			],
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'     => 'radio-buttonset',
			'settings' => 'page-header-text-align',
			'section'  => $section,
			'label'    => __( 'Text Alignment', 'mai-engine' ),
			'default'  => $config['text-align'],
			'choices'  => [
				'start'  => __( 'Start', 'mai-engine' ),
				'center' => __( 'Center', 'mai-engine' ),
				'end'    => __( 'End', 'mai-engine' ),
			],
			'output'   => [
				[
					'choice'   => [ 'start', 'center', 'end' ],
					'element'  => ':root',
					'property' => '--page-header-text-align',
				],
			],
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'        => 'image',
			'settings'    => 'page-header-image',
			'section'     => $section,
			'label'       => __( 'Default image', 'mai-engine' ),
			'description' => __( 'This can be overridden on a per post basis.', 'mai-engine' ),
			'default'     => $config['image'],
			'choices'     => [
				'save_as' => 'id',
			],
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'     => 'color',
			'settings' => 'page-header-background-color',
			'section'  => $section,
			'label'    => esc_html__( 'Background/overlay color', 'mai-engine' ),
			'default'  => $config['background-color'],
			'choices'  => [
				'palettes' => mai_get_color_choices(),
			],
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'        => 'slider',
			'settings'    => 'page-header-overlay-opacity',
			'section'     => $section,
			'label'       => __( 'Overlay opacity', 'mai-engine' ),
			'description' => esc_html__( 'The background color opacity when page header has an image', 'mai-engine' ),
			'default'     => $config['overlay-opacity'],
			'choices'     => [
				'min'  => 0,
				'max'  => 1,
				'step' => 0.01,
			],
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'settings' => 'page-header-text-color',
			'section'  => $section,
			'label'    => esc_html__( 'Default text color', 'mai-engine' ),
			'type'     => 'radio-buttonset',
			'default'  => $config['text-color'],
			'choices'  => [
				'light' => __( 'Light', 'mai-engine' ),
				'dark'  => __( 'Dark', 'mai-engine' ),
			],
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'     => 'select',
			'settings' => 'page-header-divider',
			'section'  => $section,
			'label'    => __( 'Divider style', 'mai-engine' ),
			'default'  => $config['divider'],
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

	\Kirki::add_field(
		$handle,
		[
			'type'            => 'toggle',
			'settings'        => 'page-header-divider-flip-horizontal',
			'section'         => $section,
			'label'           => __( 'Flip divider horizontally', 'mai-engine' ),
			'default'         => $config['divider-flip-horizontal'],
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

	\Kirki::add_field(
		$handle,
		[
			'type'            => 'toggle',
			'settings'        => 'page-header-divider-flip-vertical',
			'section'         => $section,
			'label'           => __( 'Flip divider vertically', 'mai-engine' ),
			'default'         => $config['divider-flip-vertical'],
			'active_callback' => [
				[
					'setting'  => 'page-header-divider',
					'operator' => '!==',
					'value'    => '',
				],
			],
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'            => 'color',
			'settings'        => 'page-header-divider-color',
			'section'         => $section,
			'label'           => esc_html__( 'Divider color', 'mai-engine' ),
			'description'     => esc_html__( 'This should match your body background color', 'mai-engine' ),
			'default'         => mai_get_color( $config['divider-color'] ),
			'choices'         => [
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
