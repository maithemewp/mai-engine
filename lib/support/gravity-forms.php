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

add_filter( 'gform_default_styles', 'mai_gform_default_styles' );
/**
 * Styles every Gravity Form from the site's Mai colors, radius and font size.
 *
 * Gravity Forms merges a form block's own Style settings over these, key by key,
 * and uses them as the block's defaults in the editor. Values another plugin set
 * on this filter earlier win over Mai's. Change one value with the
 * `mai_gform_default_styles` filter.
 *
 * @since 2.41.0
 *
 * @link https://docs.gravityforms.com/gform_default_styles/
 *
 * @param array|false $styles Default styles, false when none are set.
 *
 * @return array
 */
function mai_gform_default_styles( $styles ) {
	$styles = is_array( $styles ) ? $styles : [];
	$colors = mai_get_colors();
	$base   = mai_gform_get_root_px( 'font-size-base', 16 );
	$scale  = mai_gform_get_root_number( 'font-scale', mai_gform_get_root_number( 'font-scale-desktop', 1.25 ) );

	$mai = [
		'theme'                        => 'orbital',
		'inputSize'                    => 'md',
		'inputBorderRadius'            => (string) mai_gform_get_root_px( 'border-radius', 4 ),
		// Mai has no border color, so use a slightly darker alt.
		'inputBorderColor'             => mai_get_color_variant( $colors['alt'], 'dark', 7 ),
		'inputBackgroundColor'         => $colors['background'],
		'inputColor'                   => $colors['heading'],
		'inputPrimaryColor'            => $colors['primary'],
		'labelFontSize'                => (string) round( $base ),
		'labelColor'                   => $colors['heading'],
		'descriptionFontSize'          => (string) round( $base / $scale ),
		'descriptionColor'             => $colors['body'],
		'buttonPrimaryBackgroundColor' => $colors['primary'],
		'buttonPrimaryColor'           => $colors['background'],
	];

	$mai = apply_filters( 'mai_gform_default_styles', $mai );

	// Drop empty values so Gravity Forms falls back to its own. Keep a 0 radius.
	$mai = array_filter( (array) $mai, fn( $value ) => '' !== $value && null !== $value && false !== $value );

	return array_merge( $mai, $styles );
}

/**
 * Gets a Mai root size in px, as a number.
 *
 * Gravity Forms adds "px" itself and writes its values into a style block scoped
 * to the form's ID, so a CSS variable can't reach it. This reads the value the way
 * the page gets it: the theme config's `extra` custom properties first, then the
 * active Mai theme's stylesheet. A child theme stylesheet isn't read, so set the
 * value in config's `extra`, or use the `mai_gform_default_styles` filter.
 *
 * @since 2.41.0
 *
 * @access private
 *
 * @param string    $property The custom property name without the leading dashes.
 * @param int|float $fallback Mai's default, in px.
 *
 * @return int|float
 */
function mai_gform_get_root_px( $property, $fallback ) {
	$value = mai_gform_get_root_value( $property );

	if ( '' === $value ) {
		return $fallback;
	}

	if ( preg_match( '/^(\d*\.?\d+)(px)?$/', $value, $matches ) ) {
		return (float) $matches[1];
	}

	// Mai sizes rem and em from a 16px root.
	if ( preg_match( '/^(\d*\.?\d+)r?em$/', $value, $matches ) ) {
		return (float) $matches[1] * 16;
	}

	return $fallback;
}

/**
 * Gets a unitless Mai root value, as a number.
 *
 * @since 2.41.0
 *
 * @access private
 *
 * @param string    $property The custom property name without the leading dashes.
 * @param int|float $fallback Mai's default.
 *
 * @return int|float
 */
function mai_gform_get_root_number( $property, $fallback ) {
	$value = mai_gform_get_root_value( $property );

	return is_numeric( $value ) && (float) $value > 0 ? (float) $value : $fallback;
}

/**
 * Gets a custom property as the config or the active Mai theme sets it on :root.
 *
 * @since 2.41.0
 *
 * @access private
 *
 * @param string $property The custom property name without the leading dashes.
 *
 * @return string The value, or an empty string when neither sets it.
 */
function mai_gform_get_root_value( $property ) {
	static $root = null;

	$extra = (array) mai_get_global_styles( 'extra' );

	if ( isset( $extra[ $property ] ) && '' !== trim( (string) $extra[ $property ] ) ) {
		return trim( (string) $extra[ $property ] );
	}

	if ( is_null( $root ) ) {
		$root = '';
		$file = mai_get_dir() . 'assets/css/themes/' . sanitize_file_name( mai_get_active_theme() ) . '.min.css';

		if ( is_readable( $file ) && preg_match( '/:root\s*\{([^}]*)\}/', (string) file_get_contents( $file ), $matches ) ) {
			$root = $matches[1];
		}
	}

	if ( $root && preg_match( '/--' . preg_quote( $property, '/' ) . '\s*:\s*([^;}]+)/', $root, $matches ) ) {
		return trim( $matches[1] );
	}

	return '';
}
