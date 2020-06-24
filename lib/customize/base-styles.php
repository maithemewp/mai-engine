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
 * Add page header customizer fields.
 * This needs to be on 'init' so custom post types and custom taxonomies are available.
 *
 * @since 0.3.0
 *
 * @return void
 */
function mai_base_styles_customizer_settings() {
	$handle          = mai_get_handle();
	$section         = $handle . '-base-styless';

	$body_default = $heading_default = [
		'font-family'    => '',
		'variant'        => '',
		// 'font-size'      => '', // Don't use, we have fluid scaling.
		// 'line-height'    => '', // Don't use, we have fluid scaling.
		'letter-spacing' => '',
		'color'          => '',
		'text-transform' => '',
		// 'text-align'     => '', // Don't use, too aggressive.
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
			'row_label' => [
				'type'     => 'color',
				'value'    => esc_html__( 'Color', 'mai-engine' ),
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


}
