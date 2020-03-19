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

/**
 * Helper function that returns list of shortcode attributes.
 *
 * @since 0.1.0
 *
 * @return array
 */
function mai_icon_shortcode_atts() {
	return [
		'style'            => 'regular',
		'icon'             => 'address-book',
		'display'          => 'flex',
		'align'            => 'center',
		'size'             => '40',
		'color_icon'       => mai_get_color( 'primary' ),
		'color_background' => '',
		'color_border'     => '',
		'color_shadow'     => '',
		'margin_top'       => '',
		'margin_right'     => '',
		'margin_left'      => '',
		'margin_bottom'    => '',
		'padding_top'      => '',
		'padding_right'    => '',
		'padding_left'     => '',
		'padding_bottom'   => '',
		'border_width'     => '',
		'border_radius'    => '',
		'x_offset'         => '',
		'y_offset'         => '',
		'blur'             => '',
	];
}

add_shortcode( 'mai_icon', 'mai_icon_shortcode' );
/**
 * Render the icon shortcode.
 *
 * @since 2.0.0
 *
 * @param array $atts The shortcode attributes.
 *
 * @return string
 */
function mai_icon_shortcode( $atts ) {
	static $id = 0;

	$id++;

	$atts = shortcode_atts(
		mai_icon_shortcode_atts(),
		$atts,
		'mai_icon'
	);

	$svg = mai_get_icon( $atts['icon'], $atts['style'] );

	if ( ! $svg ) {
		return '';
	}

	$margin = implode(
		'px ',
		[
			$atts['margin_top'],
			$atts['margin_right'],
			$atts['margin_bottom'],
			$atts['margin_left'],
		]
	);

	$padding = implode(
		'px ',
		[
			$atts['padding_top'],
			$atts['padding_right'],
			$atts['padding_bottom'],
			$atts['padding_left'],
		]
	);

	$shadow = implode(
		' ',
		[
			$atts['x_offset'] . 'px',
			$atts['y_offset'] . 'px',
			$atts['blur'] . 'px',
			$atts['color_shadow'],
		]
	);

	$css = '';
	$css .= $atts['display'] ? 'display:' . $atts['display'] . ';' : '';
	$css .= $atts['align'] ? 'justify-content:' . $atts['align'] . ';' : '';
	$css .= $atts['color_background'] ? 'background-color:' . $atts['color_background'] . ';' : '';
	$css .= $atts['color_border'] ? 'border-color:' . $atts['color_border'] . ';' : '';
	$css .= 'margin:' . $margin . 'px;';
	$css .= 'padding:' . $padding . 'px;';

	$css = sprintf(
		'.mai-icon-%s{%s}',
		$id,
		$css
	);

	$svg_css = '-webkit-filter: drop-shadow(' . $shadow . ');';
	$svg_css .= 'filter: drop-shadow(' . $shadow . ')';

	$css .= sprintf(
		'.mai-icon-%s svg{%s}',
		$id,
		$svg_css
	);

	return sprintf(
		'<style>%s</style><span class="mai-icon mai-icon-%s">%s</span>',
		mai_minify_css( $css ),
		$id,
		str_replace(
			'><path',
			sprintf(
				' fill="%s" width="%s" class="align%s"><path',
				$atts['color_icon'],
				$atts['size'],
				$atts['align']
			),
			$svg
		)
	);
}
