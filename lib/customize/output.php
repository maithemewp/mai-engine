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

add_filter( 'kirki_mai-engine_styles', 'mai_add_breakpoint_custom_properties' );
/**
 * Output breakpoint custom property.
 *
 * @since 2.0.0
 *
 * @param $css
 *
 * @return mixed
 */
function mai_add_breakpoint_custom_properties( $css ) {
	$breakpoints = mai_get_breakpoints();

	foreach ( $breakpoints as $name => $size ) {
		$css['global'][':root'][ '--breakpoint-' . $name ] = $size . 'px';
	}

	return $css;
}

add_filter( 'kirki_mai-engine_styles', 'mai_add_colors_css' );
/**
 * Output named (non-element) color css.
 *
 * @since 2.0.0
 *
 * @param $css
 *
 * @return mixed
 */
function mai_add_colors_css( $css ) {
	$defaults = mai_get_global_styles( 'colors' );
	$colors   = array_diff_key( $defaults, array_flip(
		[
			'link',
			'primary',
			'secondary',
			'heading',
			'body',
			'background',
		]
	) );

	if ( $colors ) {
		foreach ( $colors as $name => $color ) {
			if ( $color ) {
				$css['global'][':root'][ '--color-' . $name ]                               = $color;
				$css['global'][ '.has-' . $name . '-color' ]['color']                       = 'var( --color-' . $name . ')';
				$css['global'][ '.has-' . $name . '-background-color' ]['background-color'] = 'var( --color-' . $name . ')';
			}
		}
	}

	return $css;
}

add_filter( 'kirki_mai-engine_styles', 'mai_add_custom_color_css' );
/**
 * Output breakpoint custom property.
 *
 * @since 2.0.0
 *
 * @param array $css Kirki CSS array.
 *
 * @return mixed
 */
function mai_add_custom_color_css( $css ) {
	$custom_colors = mai_get_option( 'custom-colors', [] );
	$count         = 1;

	foreach ( $custom_colors as $custom_color ) {
		if ( isset( $custom_color['color'] ) ) {
			$css['global'][':root'][ '--color-custom-' . $count ] = $custom_color['color'];

			$css['global'][ '.has-custom-' . $count . '-color' ]['color'] = $custom_color['color'];

			$css['global'][ '.has-custom-' . $count . '-background-color' ]['background-color'] = $custom_color['color'];

			$count++;
		}
	}

	return $css;
}

add_filter( 'kirki_mai-engine_styles', 'mai_add_button_text_colors' );
/**
 * Output breakpoint custom property.
 *
 * @since 2.0.0
 *
 * @param $css
 *
 * @return mixed
 */
function mai_add_button_text_colors( $css ) {
	$buttons = [
		'primary'   => '',
		'secondary' => 'secondary-',
	];

	foreach ( $buttons as $button => $suffix ) {
		$color  = mai_get_color( $button );
		$darker = mai_get_color_variant( $color, 'dark', 40 );
		$text   = mai_is_light_color( $color ) ? $darker : mai_get_color( 'white' );

		$css['global'][':root'][ '--button-' . $suffix . 'color' ] = $text;
	}

	return $css;
}

add_filter( 'kirki_mai-engine_styles', 'mai_add_page_header_content_type_css' );
/**
 * Add page header styles to kirki output.
 *
 * @since 2.0.0
 *
 * @param array $css Kirki CSS output.
 *
 * @return array
 */
function mai_add_page_header_content_type_css( $css ) {
	$config = mai_get_config( 'page-header' );

	$settings = [
		'page-header-background-color' => mai_get_color( $config['background-color'] ),
		'page-header-overlay-opacity'  => (string) $config['overlay-opacity'],
	];

	foreach ( $settings as $id => $default ) {
		$global   = mai_get_option( $id, $default );
		$template = mai_get_template_arg( $id, false );
		$value    = $template ? $template : $global;

		if ( $value ) {
			$css['global'][':root'][ '--' . $id ] = $value;
		}
	}

	return $css;
}
