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

add_filter( 'kirki_mai-engine_styles', 'mai_add_custom_color_custom_properties' );
/**
 * Output breakpoint custom property.
 *
 * @since 2.0.0
 *
 * @param $css
 *
 * @return mixed
 */
function mai_add_custom_color_custom_properties( $css ) {
	$custom_colors = mai_get_option( 'custom-colors', [] );
	$count         = 1;

	foreach ( $custom_colors as $custom_color ) {
		if ( isset( $custom_color['color'] ) ) {
			$css['global'][':root'][ '--custom-color-' . $count ] = $custom_color['color'];

			$css['global'][ '.has-custom-' . $count . '-color' ]['color'] = $custom_color['color'];

			$css['global'][ '.has-custom-' . $count . '-background-color' ]['background-color'] = $custom_color['color'];

			$count++;
		}
	}

	return $css;
}

add_filter( 'kirki_mai-engine_styles', 'mai_add_page_header_content_type_css' );
/**
 * Add page header styles to kirki output.
 *
 * @since 0.1.0
 *
 * @param array $css Kirki CSS output.
 *
 * @return array
 */
function mai_add_page_header_content_type_css( $css ) {
	$config  = mai_get_config( 'page-header' );
	$args    = mai_get_template_args();
	$color   = isset( $args['page-header-background-color'] ) && ! empty( $args['page-header-background-color'] ) ? $args['page-header-background-color'] : mai_get_option( 'page-header-background-color', $config['background-color'] );
	$opacity = isset( $args['page-header-overlay-opacity'] ) && ! empty( $args['page-header-overlay-opacity'] ) ? $args['page-header-overlay-opacity'] : mai_get_option( 'page-header-overlay-opacity', $config['overlay-opacity'] );

	$css['global'][':root']['--page-header-background-color'] = $color;
	$css['global'][':root']['--page-header-overlay-opacity']  = $opacity;

	return $css;
}
