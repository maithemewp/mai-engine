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
 * @param array $css Kirki CSS.
 *
 * @return array
 */
function mai_add_breakpoint_custom_properties( $css ) {
	$breakpoints = mai_get_breakpoints();

	foreach ( $breakpoints as $name => $size ) {
		$css['global'][':root'][ '--breakpoint-' . $name ] = $size . 'px';
	}

	return $css;
}

add_filter( 'kirki_mai-engine_styles', 'mai_add_additional_colors_css' );
/**
 * Output named (non-element) color css.
 *
 * @since 2.2.1 Added important rules for button hover state.
 * @since 2.0.0 Added.
 *
 * @param array $css Kirki CSS.
 *
 * @return array
 */
function mai_add_additional_colors_css( $css ) {
	$defaults = mai_get_global_styles( 'colors' );

	// Exclude settings out put by Kirki.
	$colors = array_diff_key( $defaults, mai_get_color_elements() );

	if ( $colors ) {
		foreach ( $colors as $name => $color ) {
			if ( $color ) {
				$css['global'][':root'][ '--color-' . $name ]                               = $color;
				$css['global'][ '.has-' . $name . '-color' ]['color']                       = 'var(--color-' . $name . ') !important';
				$css['global'][ '.has-' . $name . '-color' ]['--heading-color']             = 'var(--color-' . $name . ')';
				$css['global'][ '.has-' . $name . '-background-color' ]['background-color'] = 'var(--color-' . $name . ') !important';
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
 * @return array
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
 * Output contrast button text custom property.
 *
 * @since 2.0.0
 *
 * @param array $css Kirki CSS.
 *
 * @return array
 */
function mai_add_button_text_colors( $css ) {
	$buttons = [
		'primary'   => '',
		'secondary' => 'secondary-',
	];

	foreach ( $buttons as $button => $suffix ) {
		$color   = mai_get_color( $button );
		$text    = mai_is_light_color( $color ) ? mai_get_color_variant( $color, 'dark', 60 ) : mai_get_color( 'white' );
		$white   = mai_get_color( 'white' );
		$heading = mai_get_color( 'heading' );
		$text    = $white === $color ? $heading : $text;

		$css['global'][':root'][ '--button-' . $suffix . 'color' ] = $text;
	}

	return $css;
}

add_filter( 'kirki_mai-engine_styles', 'mai_add_fonts_custom_properties' );
/**
 * Add typography settings custom properties to Kirki output.
 *
 * @since 2.0.0
 *
 * @param array $css Kirki CSS output array.
 *
 * @return array
 */
function mai_add_fonts_custom_properties( $css ) {
	$global_styles    = mai_get_global_styles();
	$fonts            = $global_styles['fonts'];
	$font_weight_bold = mai_get_bold_variant( 'body' );

	if ( $font_weight_bold ) {
		$css['global'][':root']['--font-weight-bold'] = $font_weight_bold;
	}

	foreach ( $fonts as $element => $string ) {
		if ( 'body' === $element || 'heading' === $element ) {
			continue;
		}

		$css['global'][':root'][ '--' . $element . '-font-family' ] = mai_get_default_font_family( $element );
		$css['global'][':root'][ '--' . $element . '-font-weight' ] = mai_get_default_font_weight( $element );
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
	$types = array_merge( mai_get_page_header_types( 'archive' ), mai_get_page_header_types( 'single' ) );
	if ( empty( $types ) ) {
		return $css;
	}

	$config  = mai_get_config( 'settings' )['page-header'];
	$color   = mai_get_template_arg( 'page-header-background-color', mai_get_option( 'page-header-background-color', mai_get_color( $config['background-color'] ) ) );
	$opacity = mai_get_template_arg( 'page-header-overlay-opacity', mai_get_option( 'page-header-overlay-opacity', (string) $config['overlay-opacity'] ) );

	if ( $color ) {
		$css['global'][':root']['--page-header-background-color'] = $color;
	}

	if ( '' !== $opacity ) {
		$css['global'][':root']['--page-header-overlay-opacity'] = (string) $opacity;
	}

	$spacing = mai_get_option( 'page-header-spacing', $config['spacing'] );
	$top     = isset( $spacing['top'] ) && '' !== $spacing['top'] ? $spacing['top'] : $config['spacing']['top'];
	$bottom  = isset( $spacing['bottom'] ) && '' !== $spacing['bottom'] ? $spacing['bottom'] : $config['spacing']['bottom'];

	$css['global'][':root']['--page-header-padding-top']    = mai_get_unit_value( $top );
	$css['global'][':root']['--page-header-padding-bottom'] = mai_get_unit_value( $bottom );

	return $css;
}

add_filter( 'kirki_mai-engine_styles', 'mai_add_extra_custom_properties' );
/**
 * Add any other custom properties defined in config to output.
 *
 * @since 2.0.0
 *
 * @param array $css Kirki CSS array.
 *
 * @return mixed
 */
function mai_add_extra_custom_properties( $css ) {
	$extra = mai_get_global_styles( 'extra' );

	foreach ( $extra as $property => $value ) {
		$css['global'][':root'][ '--' . $property ] = $value;
	}

	return $css;
}
