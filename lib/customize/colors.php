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
 * Add base styles customizer settings.
 *
 * @since 0.3.0
 *
 * @return void
 */
function mai_colors_customizer_settings() {
	$handle        = mai_get_handle();
	$section       = $handle . '-colors';
	$global_styles = mai_get_colors();

	\Kirki::add_section(
		$section,
		[
			'title' => esc_html__( 'Colors', 'mai-engine' ),
			'panel' => $handle,
		]
	);

	$colors = [
		'background',
		'body',
		'heading',
		'link',
		'button',
		'button-secondary',
	];

	foreach ( $colors as $color ) {
		$args             = [];
		$args['type']     = 'color';
		$args['settings'] = 'color-' . $color;
		$args['label']    = mai_convert_case( $color, 'title' ) . __( ' Color', 'mai-engine' );
		$args['default']  = $global_styles[ $color ];
		$args['section']  = $section;
		$args['choices']  = [
			'palettes' => mai_get_color_choices(),
		];
		$args['output']   = [
			[
				'element'  => ':root',
				'property' => '--color-' . $color,
				'context'  => [ 'front', 'editor' ],
			],
			[
				'element'  => '.has-' . $color . '-color',
				'property' => 'color',
				'context'  => [ 'front', 'editor' ],
			],
			[
				'element'  => '.has-' . $color . '-background-color',
				'property' => 'background-color',
				'context'  => [ 'front', 'editor' ],
			],
		];

		\Kirki::add_field( $handle, $args );
	}
}
