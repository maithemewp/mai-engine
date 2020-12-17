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
				$css['global'][ '.has-' . $name . '-color' ]['--caption-color']             = 'var(--color-' . $name . ')';
				$css['global'][ '.has-' . $name . '-color' ]['--cite-color']                = 'var(--color-' . $name . ')';
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
	$props       = [];
	$breakpoints = mai_get_breakpoints();

	foreach ( $breakpoints as $name => $size ) {
		$props[ '--breakpoint-' . $name ] = $size . 'px';
	}

	// Add breakpoints to beginning of array cause that's how Mike likes to see them.
	$css['global'][':root'] = array_merge( $props, $css['global'][':root'] );

	return $css;
}

add_filter( 'kirki_mai-engine_styles', 'mai_add_title_area_custom_properties' );
/**
 * Outputs title area custom properties.
 *
 * @since 2.8.0
 *
 * @param array $css Kirki CSS.
 *
 * @return array
 */
function mai_add_title_area_custom_properties( $css ) {
	$css['global'][':root']['--header-shrink-offset'] = mai_get_unit_value( mai_get_header_shrink_offset() );

	return $css;
}

add_filter( 'kirki_enqueue_google_fonts', 'mai_add_body_font_variants', 99 );
/**
 * Loads italic and bold variations of body font family.
 *
 * @since 2.0.0
 *
 * @param array $fonts All fonts to be enqueued.
 *
 * @return mixed
 */
function mai_add_body_font_variants( $fonts ) {
	if ( ! $fonts ) {
		return $fonts;
	}

	$font_family = mai_get_font_family( 'body' );

	// Return early if body font family not chosen.
	if ( ! isset( $fonts[ $font_family ] ) ) {
		return $fonts;
	}

	// Set variants if they exist.
	$bold        = mai_get_bold_variant( 'body' );
	$italic      = mai_get_italic_variant( 'body' );
	$bold_italic = mai_get_bold_italic_variant( 'body' );

	if ( $bold ) {
		$fonts[ $font_family ][] = $bold;
	}

	if ( $italic ) {
		$fonts[ $font_family ][] = $italic;
	}

	if ( $bold_italic ) {
		$fonts[ $font_family ][] = $bold_italic;
	}

	// Remove duplicates.
	$fonts[ $font_family ] = array_flip( array_flip( $fonts[ $font_family ] ) );

	// Make all variants strings.
	foreach ( $fonts as $name => $variants ) {
		$fonts[ $name ] = array_map( 'strval', $variants );
	}

	return $fonts;
}

add_filter( 'kirki_enqueue_google_fonts', 'mai_add_extra_google_fonts', 99 );
/**
 * Load any other Google font families defined in the config.
 *
 * @since 2.0.0
 *
 * @param array $fonts All Google Fonts to be enqueued.
 *
 * @return mixed
 */
function mai_add_extra_google_fonts( $fonts ) {
	if ( ! $fonts ) {
		return $fonts;
	}

	// Convert to strings for later comparison.
	foreach ( $fonts as $family => $weights ) {
		$fonts[ $family ] = array_map( 'strval', $weights );
	}

	$fonts_config = mai_get_global_styles( 'fonts' );

	unset( $fonts_config['body'] );
	unset( $fonts_config['heading'] );

	if ( ! $fonts_config ) {
		return $fonts;
	}

	foreach ( $fonts_config as $element => $args ) {
		$font_family  = mai_get_default_font_family( $element );
		$font_weights = mai_get_default_font_weights( $element );

		/**
		 * Kirki Fonts.
		 *
		 * @var Kirki_Fonts $kirki_fonts Kirki fonts.
		 */
		$kirki_fonts  = Kirki_Fonts::get_instance();
		$google_fonts = $kirki_fonts::get_google_fonts();

		// Return early if not a Google Font.
		if ( ! isset( $google_fonts[ $font_family ] ) ) {
			continue;
		}

		$variants = $google_fonts[ $font_family ]['variants'];

		foreach ( $font_weights as $font_weight ) {

			// Skip if config weight is not a variant in this family.
			if ( ! in_array( $font_weight, $variants, true ) ) {
				continue;
			}

			// Skip if variant already registered.
			if ( isset( $fonts[ $font_family ] ) && in_array( $font_weight, $fonts[ $font_family ], true ) ) {
				continue;
			}

			$fonts[ $font_family ][] = (string) $font_weight;
		}
	}

	foreach ( $fonts as $font_family => $font_weights ) {

		// If we have 400 and regular, remove 400. Kikri uses regular.
		if ( count( array_intersect( $font_weights, [ '400', 'regular' ] ) ) > 1 ) {
			$index = array_search( '400', $font_weights, true );
			unset( $fonts[ $font_family ][ $index ] );
		}

		// Remove any leftover duplicates.
		$fonts[ $font_family ] = array_unique( $fonts[ $font_family ] );
	}

	return $fonts;
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
	$body_font_family    = mai_get_font_family( 'body' );
	$body_font_weight    = mai_get_font_weight( 'body' );
	$body_font_bold      = mai_get_bold_variant( 'body' );
	$heading_font_family = mai_get_font_family( 'heading' );
	$heading_font_weight = mai_get_font_weight( 'heading' );
	$fonts_config        = mai_get_global_styles( 'fonts' );

	if ( $body_font_family ) {
		$css['global'][':root']['--body-font-family'] = $body_font_family;
	}

	if ( $body_font_weight ) {
		$body_weight = in_array( $body_font_weight, [ 'regular', 'italic' ] ) ? '400' : $body_font_weight; // Could only be italic.
		$body_weight = str_replace( 'italic', '', $body_weight ); // Could be 300italic.

		$css['global'][':root']['--body-font-weight'] = $body_weight;
	}

	if ( $body_font_bold ) {
		$css['global'][':root']['--body-font-weight-bold'] = $body_font_bold;
	}

	if ( mai_has_string( 'italic', $body_font_weight ) ) {
		$css['global'][':root'][ '--body-font-style' ] = 'italic';
	}

	if ( $heading_font_family ) {
		$css['global'][':root']['--heading-font-family'] = $heading_font_family;
	}

	if ( $heading_font_weight ) {
		$heading_weight = in_array( $heading_font_weight, [ 'regular', 'italic' ] ) ? '400' : $heading_font_weight; // Could only be italic.
		$heading_weight = str_replace( 'italic', '', $heading_weight ); // Could be 300italic.

		$css['global'][':root']['--heading-font-weight'] = $heading_weight;
	}

	if ( mai_has_string( 'italic', $heading_font_weight ) ) {
		$css['global'][':root'][ '--heading-font-style' ] = 'italic';
	}

	unset( $fonts_config['body'] );
	unset( $fonts_config['heading'] );

	if ( $fonts_config ) {
		foreach ( $fonts_config as $element => $string ) {
			$extra_font_family = mai_get_default_font_family( $element );
			$extra_font_weight = mai_get_default_font_weight( $element );
			$extra_weight      = in_array( $extra_font_weight, [ 'regular', 'italic' ] ) ? '400' : $extra_font_weight; // Could only be italic.
			$extra_weight      = str_replace( 'italic', '', $extra_weight ); // Could be 300italic.

			$css['global'][':root'][ '--' . $element . '-font-family' ] = $extra_font_family;
			$css['global'][':root'][ '--' . $element . '-font-weight' ] = $extra_weight;

			if ( mai_has_string( 'italic', $extra_font_weight ) ) {
				$css['global'][':root'][ '--' . $element . '-font-style' ] = 'italic';
			}
		}
	}

	return $css;
}

add_filter( 'kirki_mai-engine_styles', 'mai_add_page_header_content_type_css', 12 );
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

	$config     = mai_get_config( 'settings' )['page-header'];
	$text       = (string) mai_get_template_arg( 'page-header-text-color', mai_get_option( 'page-header-text-color', mai_get_color( $config['text-color'] ) ) );
	$background = (string) mai_get_template_arg( 'page-header-background-color', mai_get_option( 'page-header-background-color', mai_get_color( $config['background-color'] ) ) );
	$opacity    = (string) mai_get_page_header_overlay_opacity();

	if ( $text ) {
		$css['global'][':root']['--page-header-color'] = $text;
	}

	if ( $background ) {
		$css['global'][':root']['--page-header-background'] = $background;
	}

	if ( '' !== $opacity ) {
		$css['global'][':root']['--page-header-overlay-opacity'] = $opacity;
	}

	$spacing = mai_get_option( 'page-header-spacing', $config['spacing'] );
	$top     = isset( $spacing['top'] ) && '' !== $spacing['top'] ? $spacing['top'] : $config['spacing']['top'];
	$bottom  = isset( $spacing['bottom'] ) && '' !== $spacing['bottom'] ? $spacing['bottom'] : $config['spacing']['bottom'];

	$css['global'][':root']['--page-header-padding-top']    = mai_get_unit_value( $top );
	$css['global'][':root']['--page-header-padding-bottom'] = mai_get_unit_value( $bottom );

	$text_align = mai_get_option( 'page-header-text-align', $config['text-align'] );

	if ( $text_align ) {
		$css['global'][':root']['--page-header-text-align'] = esc_html( $text_align );
	}

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
