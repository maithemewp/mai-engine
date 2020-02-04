<?php

/**
 * Helper function that returns list of shortcode attributes.
 *
 * @since 1.0.0
 *
 * @param $atts
 * @param $id
 *
 * @return array
 */
function mai_icon_shortcode_atts( $id = 1 ) {
	return [
		'class'            => 'mai-icon-' . $id,
		'style'            => 'regular',
		'icon'             => 'address-book',
		'display'          => 'flex',
		'align'            => 'center',
		'size'             => '40',
		'color_icon'       => mai_default_color( 'primary' ),
		'color_background' => '',
		'color_border'     => '',
		'margin_top'       => '10',
		'margin_right'     => '10',
		'margin_left'      => '10',
		'margin_bottom'    => '10',
		'padding_top'      => '10',
		'padding_right'    => '10',
		'padding_left'     => '10',
		'padding_bottom'   => '10',
		'border_width'     => '',
		'border_radius'    => '',
		'x_offset'         => '',
		'y_offset'         => '',
		'spread'           => '',
		'blur'             => '',
		'inset'            => '',
	];
}

add_shortcode( 'mai_icon', 'mai_icon_shortcode' );
/**
 * Render the icon shortcode.
 *
 * @since 2.0.0
 *
 * @param array $atts Shortcode attributes.
 *
 * @return string
 */
function mai_icon_shortcode( $atts ) {
	static $id = 1;

	$atts = shortcode_atts(
		mai_icon_shortcode_atts( $id++ ),
		$atts,
		'mai_icon'
	);

	$file = mai_dir() . 'assets/svg/' . $atts['style'] . '/' . $atts['icon'] . '.svg';

	if ( ! file_exists( $file ) ) {
		return $file;
	}

	$margin = implode( 'px ', [
			$atts['margin_top'],
			$atts['margin_right'],
			$atts['margin_bottom'],
			$atts['margin_left'],
		] ) . 'px;';

	$padding = implode( 'px ', [
			$atts['padding_top'],
			$atts['padding_right'],
			$atts['padding_bottom'],
			$atts['padding_left'],
		] ) . 'px;';

	$shadow = '';

	$css = '';
	$css .= $atts['display'] ? 'display:' . $atts['display'] . ';' : '';
	$css .= $atts['align'] ? 'justify-content:' . $atts['align'] . ';' : '';
	$css .= $atts['color_background'] ? 'background-color:' . $atts['color_background'] . ';' : '';
	$css .= $atts['color_border'] ? 'border-color:' . $atts['color_border'] . ';' : '';
	$css .= 'margin:' . $margin;
	$css .= 'padding:' . $padding;

	$css = sprintf(
		'.%s{%s}',
		$atts['class'],
		$css
	);

	return sprintf(
		'<style>%s</style><span class="mai-icon %s">%s</span>',
		mai_minify_css( $css ),
		$atts['class'],
		str_replace(
			'><path',
			sprintf(
				' fill="%s" width="%s"><path',
				$atts['color_icon'],
				$atts['size']
			),
			file_get_contents( $file )
		)
	);
}
