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

add_action( 'init', 'mai_colors_customizer_settings' );
/**
 * Add Customizer color settings.
 *
 * @since 2.0.0
 *
 * @return void
 */
function mai_colors_customizer_settings() {
	$handle  = mai_get_handle();
	$section = $handle . '-colors';

	\Kirki::add_section(
		$section,
		[
			'title' => __( 'Colors', 'mai-engine' ),
			'panel' => $handle,
		]
	);

	// Get original colors for backwards compatibility.
	$elements = [
		'body-background'             => 'lightest',
		'body'                        => 'dark',
		'heading'                     => 'darkest',
		'link'                        => 'primary',
		'button-background'           => 'primary',
		'button-secondary-background' => 'secondary',
	];

	$defaults = mai_get_global_styles( 'colors' );

	foreach ( $elements as $element => $default ) {
		$args = [
			'type'     => 'color',
			'settings' => $element . '-color',
			'label'    => mai_convert_case( $element, 'title' ),
			'section'  => $section,
			'default'  => mai_get_option( 'color-' . $default, $defaults[ $element ] ),
			'choices'  => [
				'alpha'    => true,
				'palettes' => mai_get_color_choices(),
			],
			'output'   => [
				[
					'element'  => ':root',
					'property' => '--' . $element . '-color',
					'context'  => [ 'front', 'editor' ],
				],
			],
		];

		\Kirki::add_field( $handle, $args );
	}

	\Kirki::add_field(
		$handle,
		[
			'type'         => 'repeater',
			'label'        => '', // No label.
			'section'      => $section,
			'button_label' => __( 'Add New Color ', 'mai-engine' ),
			'settings'     => 'custom-colors',
			'default'      => [],
			'row_label'    => [
				'type'  => 'text',
				'value' => __( 'Custom Color', 'mai-engine' ),
			],
			'fields'       => [
				'color' => [
					'type'    => 'color',
					'label'   => '',
					'default' => '',
					'alpha'   => true,
					'choices' => [
						'alpha'    => true,
						'palettes' => mai_get_color_choices(),
					],
				],
			],
		]
	);
}
