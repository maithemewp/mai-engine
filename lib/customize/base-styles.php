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

add_action( 'init', 'mai_base_styles_customizer_settings' );
/**
 * Add base styles customizer settings.
 *
 * @since 0.3.0
 *
 * @return void
 */
function mai_base_styles_customizer_settings() {
	$handle  = mai_get_handle();
	$section = $handle . '-base-styles';

	$body_default = $heading_default = [
		'font-family'    => '',
		'variant'        => '',
		'letter-spacing' => '',
		'color'          => '',
		'text-transform' => '',
	];

	foreach ( mai_get_custom_properties( 'body', true ) as $key => $value ) {
		if ( 'color' === $key ) {
			$body_default[ $key ] = mai_get_color( $value );
		} else {
			$body_default[ $key ] = $value;
		}
	}

	$heading_output = [];

	foreach ( mai_get_custom_properties( 'heading', true ) as $key => $value ) {
		if ( 'color' === $key ) {
			$heading_default[ $key ] = mai_get_color( $value );
		} else {
			$heading_default[ $key ] = $value;
		}

		$heading_output[] = [
			'element'  => ':root',
			'property' => '--heading-' . $key,
			'choice'   => $key,
		];
	}

	\Kirki::add_section(
		$section,
		[
			'title' => esc_html__( 'Base Styles', 'mai-engine' ),
			'panel' => $handle,
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'     => 'typography',
			'settings' => 'body-typography',
			'section'  => $section,
			'label'    => esc_html__( 'Body Typography', 'mai-engine' ),
			'default'  => $body_default,
			'output'   => [
				[
					// TODO: This should output body font properties, not direct like this.
					'element' => 'body',
				],
			],
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'     => 'typography',
			'settings' => 'heading-typography',
			'section'  => $section,
			'label'    => esc_html__( 'Heading Typography', 'mai-engine' ),
			'default'  => $heading_default,
			'output'   => $heading_output,
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'         => 'repeater',
			'settings'     => 'color-palette',
			'section'      => $section,
			'label'        => esc_html__( 'Color palette', 'mai-engine' ),
			'description'  => esc_html__( 'Provides the default choices for color pickers in blocks and Customizer settings.', 'mai-engine' ),
			'row_label'    => [
				'type'  => 'color',
				'value' => esc_html__( 'Color', 'mai-engine' ),
			],
			'button_label' => esc_html__( 'Add new color', 'mai-engine' ),
			'fields'       => [
				'color' => [
					'type'    => 'color',
					'label'   => '',
					'default' => '',
				],
			],
		]
	);

	/*
	 * Buttons
	 */

	$button_elements = [
		'.button',
		'[type=button]',
		'[type=reset]',
		'[type=submit]',
		'button',
	];

	$button_elements_hover = [];

	foreach ( $button_elements as $button_element ) {
		$button_elements_hover[] = $button_element . ':hover';
		$button_elements_hover[] = $button_element . ':focus';
	}

	\Kirki::add_field(
		$handle,
		[
			'type'     => 'slider',
			'settings' => 'button-border-radius',
			'section'  => $section,
			'label'    => __( 'Button border radius', 'mai-engine' ),
			'default'  => mai_get_integer_value( mai_get_custom_property( 'button-border-radius' ) ),
			'choices'  => [
				'min'  => 0,
				'max'  => 100,
				'step' => 1,
			],
			'output'   => [
				[
					'element'  => ':root',
					'property' => '--button-border-radius',
					'units'    => 'px',
				],
			],
		]
	);

	/*
	 * Links
	 */


	/*
	 * Inputs
	 */

	\Kirki::add_field(
		$handle,
		[
			'type'     => 'slider',
			'settings' => 'input-border-radius',
			'section'  => $section,
			'label'    => __( 'Input border radius', 'mai-engine' ),
			'default'  => mai_get_integer_value( mai_get_custom_property( 'input-border-radius' ) ),
			'choices'  => [
				'min'  => 0,
				'max'  => 100,
				'step' => 1,
			],
			'output'   => [
				[
					'element'  => ':root',
					'property' => '--input-border-radius',
					'units'    => 'px',
				],
			],
		]
	);

}
