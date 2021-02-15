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

	Kirki::add_section(
		$section,
		[
			'title' => __( 'Colors', 'mai-engine' ),
			'panel' => $handle,
		]
	);

	$colors = mai_get_color_elements();

	foreach ( $colors as $id => $label ) {
		$args = [
			'type'     => 'color',
			'settings' => 'color-' . $id,
			'label'    => $label,
			'section'  => $section,
			'default'  => mai_get_default_color( $id ),
			'choices'  => [
				'palettes' => mai_get_color_choices(),
			],
			'output'   => [
				[
					'element'  => ':root',
					'property' => '--color-' . $id,
					'context'  => [ 'front', 'editor' ],
				],
				[
					'element'       => '.has-' . $id . '-color',
					'property'      => 'color',
					'value_pattern' => 'var(--color-' . $id . ') !important',
					'context'       => [ 'front', 'editor' ],
				],
				[
					'element'       => '.has-' . $id . '-background-color',
					'property'      => 'background-color',
					'value_pattern' => 'var(--color-' . $id . ') !important',
					'context'       => [ 'front', 'editor' ],
				],
			],
		];

		Kirki::add_field( $handle, $args );
	}

	Kirki::add_field(
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
