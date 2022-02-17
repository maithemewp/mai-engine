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

add_filter( 'kirki_default_color_swatches', 'mai_kirki_color_swatches' );
/**
 * Adds selected colors as default palette to all kirki color pickers.
 *
 * @since TBD
 *
 * @param array $swatches The existing colors.
 *
 * @return array
 */
function mai_kirki_color_swatches( $swatches ) {
	return mai_get_color_choices();
}

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

	new \Kirki\Section(
		$section,
		[
			'title' => __( 'Colors', 'mai-engine' ),
			'panel' => $handle,
		]
	);

	$colors = mai_get_color_elements();

	foreach ( $colors as $id => $label ) {
		$args = [
			'settings' => mai_get_kirki_setting( 'color-' . $id ),
			'label'    => $label,
			'section'  => $section,
			'default'  => mai_get_default_color( $id ),
		];

		// Kirki::add_field( $handle, $args );
		new \Kirki\Field\Color( mai_parse_kirki_args( $args ) );
	}

	new \Kirki\Field\Repeater(
		mai_parse_kirki_args(
			[
				'label'        => __( 'Custom Colors', 'mai-engine' ),
				'description'  => sprintf( '%s var(--color-custom-#)', __( 'Use in CSS via:', 'mai-engine' ) ),
				'section'      => $section,
				'button_label' => __( 'Add New Color ', 'mai-engine' ),
				'settings'     => mai_get_kirki_setting( 'custom-colors' ),
				'default'      => mai_get_option( 'custom-colors', mai_get_global_styles( 'custom-colors' ) ),
				'row_label'    => [
					'type'  => 'text',
					'value' => __( 'Custom Color', 'mai-engine' ),
				],
				'fields'       => [
					'color' => [
						'type'     => 'color',
						'label'    => '',
						'alpha'    => true,
						'choices'  => [
							'alpha'    => true,
							'palettes' => mai_get_color_choices(), // Not working since v4.
						],
					],
				],
			]
		)
	);
}
