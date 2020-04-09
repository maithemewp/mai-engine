<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2019 BizBudding
 * @license   GPL-2.0-or-later
 */

add_action( 'after_setup_theme', 'mai_color_palette_customizer_settings' );
/**
 * Add color palette customizer settings.
 *
 * @return  void
 */
function mai_color_palette_customizer_settings() {
	$handle = mai_get_handle();
	$colors = mai_get_colors();

	Kirki::add_section(
		$handle . '-color-palette',
		[
			'title' => __( 'Color Palette', 'mai-engine' ),
			'panel' => $handle,
		]
	);

	foreach ( $colors as $name => $hex ) {
		Kirki::add_field(
			$handle,
			[
				'type'     => 'color',
				'settings' => 'color-' . $name,
				'label'    => mai_convert_case( $name, 'title' ) . __( ' Color', 'mai-customizer' ),
				'default'  => $hex,
				'section'  => $handle . '-color-palette',
				'output'   => [
					[
						'element'  => ':root',
						'property' => '--color-' . $name,
					],
				],
			]
		);
	}
}
