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

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * Get the colors from Customizer, with fallback to config.
 *
 * @since 2.0.0
 *
 * @return array Colors with name as key and #hex code as value.
 */
function mai_get_colors() {
	static $colors = null;

	if ( ! is_customize_preview() ) {
		if ( ! is_null( $colors ) ) {
			return $colors;
		}
	}

	$colors = wp_parse_args( mai_get_option( 'colors', [] ), mai_get_global_styles( 'colors' ) );
	$colors = array_merge( $colors, mai_get_custom_colors() );

	return $colors;
}

/**
 * Get custom colors as set in Customizer.
 *
 * @since 2.0.0
 *
 * @return array Colors with name as key and #hex code as value.
 */
function mai_get_custom_colors() {
	static $colors = null;

	if ( ! is_null( $colors ) ) {
		return $colors;
	}

	$colors  = [];
	$options = wp_parse_args( mai_get_option( 'custom-colors', [] ), mai_get_global_styles( 'custom-colors' ) );
	$count   = 1;

	foreach ( $options as $index => $option ) {
		$colors[ 'custom-' . $count ] = $option['color'];
		$count++;
	}

	return $colors;
}

/**
 * Returns a color option value with config default fallback.
 *
 * @since 2.0.0
 *
 * @param string $name Name of the color to get.
 *
 * @return string
 */
function mai_get_color_og( $name ) {
	$custom = mai_get_custom_colors();

	if ( isset( $custom[ $name ] ) ) {
		return $custom[ $name ];
	}

	return mai_get_option( 'color-' . $name, mai_get_default_color( $name ) );
}

/**
 * Get a color value from any color name.
 * If not in our stored colors by name, returns original color.
 *
 * @since 2.2.2
 *
 * @param string $color by name or hex, rgb, etc.
 *
 * @return string Color standard value: hex, rgb, etc.
 */
function mai_get_color_value( $color ) {
	$colors = mai_get_colors();
	return mai_isset( $colors, $color, $color );
}

/**
 * Get a color value for CSS.
 * If not in our stored colors by name, returns original color.
 *
 * @since 2.13.0
 *
 * @param string $color by name or hex, rgb, etc.
 *
 * @return string The color custom property or standard value.
 */
function mai_get_color_css( $color ) {
	$name   = false;
	$colors = mai_get_colors();
	if ( isset( $colors[ $color ] ) ) {
		$name = $color;
	} else {
		$name = mai_get_color_name( $color );
	}
	if ( $name ) {
		return sprintf( 'var(--color-%s)', $name );
	}
	return $color;
}

/**
 * Get a color name from hex code.
 *
 * @since 2.13.0
 *
 * @param string $hex
 *
 * @return string|false The color name or false.
 */
function mai_get_color_name( $hex ) {
	$colors = array_flip( mai_get_colors() );
	return mai_isset( $colors, $hex, false );
}

/**
 * Returns the array of colors from the global styles config.
 *
 * @since 2.0.0
 *
 * @return array
 */
function mai_get_default_colors() {
	static $colors = null;

	if ( ! is_null( $colors ) ) {
		return $colors;
	}

	$colors = mai_get_global_styles( 'colors' );

	return $colors;
}

/**
 * Returns a single color hex value from the config.
 *
 * @since 2.0.0
 *
 * @param string $name Name of the color to get.
 *
 * @return string
 */
function mai_get_default_color( $name ) {
	return mai_isset( mai_get_default_colors(), $name, '' );
}

/**
 * Returns the color palette variables.
 *
 * @since 2.0.0
 *
 * @return array
 */
function mai_get_editor_color_palette() {
	static $palette = null;

	if ( ! is_null( $palette ) ) {
		return $palette;
	}

	$colors  = mai_get_colors();
	$values  = [];
	$palette = [];

	if ( ! class_exists( 'ariColor' ) ) {
		return $palette;
	}

	// Remove empty custom colors.
	$colors = array_filter( $colors );

	// Sort colors by lightness.
	$sorted = [];

	foreach ( $colors as $name => $hex ) {
		$sorted[ $name ] = ariColor::newColor( $hex )->lightness;
	}

	asort( $sorted );

	$elements = mai_get_color_elements();

	foreach ( $sorted as $name => $lightness ) {
		$hex = $colors[ $name ];

		// Remove duplicate hex codes.
		if ( in_array( $hex, $values, true ) ) {
			continue;
		}

		$values[] = $hex;

		// Add color.
		$palette[] = [
			'name'  => mai_isset( $elements , $name, mai_convert_case( $name, 'title' ) ),
			'slug'  => mai_convert_case( $name, 'kebab' ),
			'color' => $hex,
		];
	}

	return $palette;
}

/**
 * Get color element names for settings.
 *
 * @since 2.0.0
 *
 * @return array
 */
function mai_get_color_elements() {
	return [
		'background' => __( 'Background', 'mai-engine' ),
		'alt'        => __( 'Background Alt', 'mai-engine' ),
		'header'     => __( 'Site Header', 'mai-engine' ),
		'body'       => __( 'Body', 'mai-engine' ),
		'heading'    => __( 'Heading', 'mai-engine' ),
		'link'       => __( 'Link', 'mai-engine' ),
		'primary'    => __( 'Button Primary', 'mai-engine' ),
		'secondary'  => __( 'Button Secondary', 'mai-engine' ),
	];
}

/**
 * Get color choices for Kirki.
 *
 * @since 2.0.0
 *
 * @return array
 */
function mai_get_color_choices() {
	static $choices = null;

	if ( ! is_null( $choices ) ) {
		return $choices;
	}

	$color_choices = [];
	$color_palette = mai_get_editor_color_palette();

	foreach ( $color_palette as $color ) {
		$color_choices[] = $color['color'];
	}

	$choices = array_flip( array_flip( $color_choices ) );

	return $choices;
}

/**
 * Check if a color is light.
 *
 * This helps with accessibility decisions to determine
 * whether to use a light or dark background or text color.
 *
 * @since 2.0.0
 * @since 2.2.2 Allow colors by name.
 *
 * @link  https://aristath.github.io/ariColor/
 *
 * @param string $color Any color string, including config or settings color names, hex, rgb, rgba, etc.
 *
 * @return bool
 */
function mai_is_light_color( $color ) {
	if ( ! class_exists( 'ariColor' ) ) {
		return false;
	}

	$colors = null;

	if ( is_array( $colors ) ) {
		if ( isset( $colors[ $color ] ) ) {
			return $colors[ $color ];
		}
	} else {
		$colors = [];
	}

	$value            = mai_get_color_value( $color );
	$object           = ariColor::newColor( $value );
	$limit            = mai_get_global_styles( 'contrast-limit' );
	$colors[ $value ] = $object->luminance > $limit;

	return $colors[ $value ];
}

/**
 * Returns light or dark variant of a color.
 *
 * @since 2.2.2 Allow colors by name.
 * @since 1.0.0
 *
 * @param string $color         Color name.
 * @param string $light_or_dark Light or dark variant.
 * @param int    $amount        Amount to darken/lighten
 *
 * @return string
 */
function mai_get_color_variant( $color, $light_or_dark = 'dark', $amount = 7 ) {
	if ( ! class_exists( 'ariColor' ) ) {
		return $color;
	}

	$color   = mai_get_color_value( $color );
	$color   = ariColor::newColor( $color );
	$value   = 'dark' === $light_or_dark ? $color->lightness - $amount : $color->lightness + $amount;
	$variant = $color->getNew( 'lightness', $value );

	return $variant->toCSS( 'hex' );
}
