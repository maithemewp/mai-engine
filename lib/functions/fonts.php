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
 * Returns an array of font sizes based on the font scale.
 *
 * @since 2.0.0
 *
 * @return array
 */
function mai_get_font_sizes() {
	static $sizes = null;

	if ( ! is_null( $sizes ) ) {
		return $sizes;
	}

	$sizes  = [];
	$config = mai_get_config( 'global-styles' );
	$scale  = isset( $config['font-scale'] ) ? (int) $config['font-scale'] : 1.25;
	$base   = isset( $config['font-size-base'] ) ? (int) $config['font-size-base'] : 16;
	$sm     = $base / $scale;
	$xs     = $sm / $scale;
	$lg     = $base * $scale;
	$xl     = $lg * $scale;
	$xxl    = $xl * $scale;
	$xxxl   = $xxl * $scale;
	$xxxxl  = $xxxl * $scale;

	$scale = [
		'xs'    => $xs,
		'sm'    => $sm,
		'md'    => $base,
		'lg'    => $lg,
		'xl'    => $xl,
		'xxl'   => $xxl,
		'xxxl'  => $xxxl,
		'xxxxl' => $xxxxl,
	];

	foreach ( $scale as $slug => $size ) {
		$sizes[] = [
			'slug' => $slug,
			'size' => $size,
			'name' => strtoupper( $slug ),
		];
	}

	return $sizes;
}

/**
 * Returns the default font family for an element from the config.
 *
 * @since 2.0.0
 *
 * @param string $element Element to check.
 *
 * @return string
 */
function mai_get_default_font_family( $element ) {
	static $families = null;

	if ( is_array( $families ) && isset( $families[ $element ] ) ) {
		return $families[ $element ];
	}

	$fonts = mai_get_global_styles( 'fonts' );

	if ( ! mai_has_string( ':', $fonts[ $element ] ) ) {
		$families[ $element ] = $fonts[ $element ];
		return $families[ $element ];
	}

	$families[ $element ] = explode( ':', $fonts[ $element ] )[0];

	return $families[ $element ];
}

/**
 * Returns default font weights for an element from the config.
 *
 * @since 2.0.0
 *
 * @param string $element Element to check.
 *
 * @return array
 */
function mai_get_default_font_weights( $element ) {
	static $weights = null;

	if ( is_array( $weights ) && isset( $weights[ $element ] ) ) {
		return $weights[ $element ];
	}

	$fallback = [ 'regular' ];
	$fonts    = mai_get_global_styles( 'fonts' );
	$string   = explode( ':', $fonts[ $element ] );
	$weights  = isset( $string[1] ) ? explode( ',', $string[1] ) : $fallback;

	// Convert 400 to regular for Kirki compatibility.
	foreach ( $weights as $index => $weight ) {
		if ( '400' === $weight ) {
			$weights[ $index ] = 'regular';
		}
	}

	return $weights;
}

/**
 * Returns the default font weight for an element from the config.
 *
 * @since 2.0.0
 *
 * @param string $element Element to check.
 *
 * @return string
 */
function mai_get_default_font_weight( $element ) {
	return mai_get_default_font_weights( $element )[0];
}

/**
 * Returns chosen font family for an element with config fallback.
 *
 * @since 2.0.0
 *
 * @param string $element Element to check.
 *
 * @return string
 */
function mai_get_font_family( $element ) {
	static $families = null;

	if ( is_array( $families ) && isset( $families[ $element ] ) ) {
		return $families[ $element ];
	}

	$default = mai_get_default_font_family( $element );
	$option  = mai_get_option( $element . '-typography' );
	$family  = mai_isset( $option, 'font-family', $default );

	$families[ $element ] = $family ?: 'unset';

	return $families[ $element ];
}

/**
 * Returns chosen font weight for an element with config fallback.
 *
 * @since 2.0.0
 *
 * @param string $element Element to check.
 *
 * @return string
 */
function mai_get_font_weight( $element ) {
	static $weights = null;

	if ( is_array( $weights ) && isset( $weights[ $element ] ) ) {
		return $weights[ $element ];
	}

	$default = mai_get_default_font_weight( $element );
	$option  = mai_get_option( $element . '-typography' );

	$weights[ $element ] = mai_isset( $option, 'variant', $default );

	return $weights[ $element ];
}

/**
 * Gets font weight value for CSS.
 * Converts regular to 400 and removes italic.
 *
 * @since TBD
 *
 * @param string $weight The font weight to sanitize.
 *
 * @return string
 */
function mai_get_font_weight_for_css( $weight ) {
	$weight = (string) $weight;

	// Convert regular or italic to default font weight.
	if ( in_array( $weight, [ 'regular', 'italic' ] ) ) {
		$weight = '400';
	}

	// Remove italic from 300italic.
	$weight = str_replace( 'italic', '', $weight );

	return $weight;
}

/**
 * Gets font variant value for Kirki.
 * Converts 400 to regular.
 *
 * @since TBD
 *
 * @param string $variant The font variant to sanitize.
 *
 * @return string
 */
function mai_get_font_variant_for_kirki( $variant ) {
	$variant = (string) $variant;

	// Convert 400 to regular.
	if ( in_array( $variant, [ '400' ] ) ) {
		$variant = 'regular';
	}

	return $variant;
}

/**
 * Gets available font weights for an element.
 * Uses naming for use directly in CSS.
 *
 * @since TBD
 *
 * @param string $element Element to check.
 *
 * @return array
 */
function mai_get_font_weights( $element ) {
	static $weights = null;

	if ( is_array( $weights ) && isset( $weights[ $element ] ) ) {
		return $weights[ $element ];
	}

	if ( ! is_array( $weights ) ) {
		$weights = [];
	}

	$variants = mai_get_font_variants( $element );

	foreach ( $variants as $name => $value ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $index => $variant ) {
				$variants[ $name ][ $index ] = mai_get_font_weight_for_css( $variant );
			}
		} else {
			$variants[ $name ] = mai_get_font_weight_for_css( $value );
		}
	}

	$weights[ $element ] = $variants;

	return $weights[ $element ];
}

/**
 * Gets available font variants for an element.
 * Uses naming for Kirki.
 *
 * @since TBD
 *
 * @param string $element Element to check.
 *
 * @return array
 */
function mai_get_font_variants( $element ) {
	static $variants = null;

	if ( is_array( $variants ) && isset( $variants[ $element ] ) ) {
		return $variants[ $element ];
	}

	if ( ! is_array( $variants ) ) {
		$variants = [];
	}

	$google_fonts = mai_get_kirki_google_fonts();
	$config       = mai_get_global_styles( 'font-variants' );
	$font_family  = mai_get_font_family( $element );
	$font_weight  = mai_get_font_weight( $element );
	$defaults     = [
		'default'    => $font_weight,
		'light'      => '',
		'bold'       => '',
		'italic'     => '',
		'bolditalic' => '',
		'add'        => '',
	];

	// Bail if no config for element.
	if ( ! isset( $config[ $element ] ) ) {
		$variants[ $element ] = $defaults;
		return $variants[ $element ];
	}

	// Bail not a google font.
	if ( ! isset( $google_fonts[ $font_family ] ) ) {
		$variants[ $element ] = $defaults;
		return $variants[ $element ];
	}

	$available = isset( $google_fonts[ $font_family ]['variants'] ) ? $google_fonts[ $font_family ]['variants'] : [];

	// Bail if no variants available.
	if ( ! $available ) {
		$variants[ $element ] = $defaults;
		return $variants[ $element ];
	}

	// Set all attributes.
	$values = wp_parse_args( $config[ $element ], $defaults );

	// Convert to strings, and change 400 to regular for Kirki comparison.
	foreach ( $values as $name => $value ) {
		if ( $value ) {
			$array = explode( ',', $value );
			$array = array_map( 'strval', $array );

			if ( $array ) {
				foreach ( $array as $index => $variant ) {
					$array[ $index ] = mai_get_font_variant_for_kirki( $variant );
				}

				$values[ $name ] = implode( ',', $array );
			}
		}
	}

	// Default. Don't check if available because we need this output in CSS,
	// It should be available if chosen from Custmoizer.
	if ( $values['default'] ) {
		$variants[ $element ]['default'] = $values['default'];
	}

	// Light.
	if ( $values['light'] && in_array( $values['light'], $available ) ) {
		$variants[ $element ]['light'] = $values['light'];
	}

	// Bold.
	if ( $values['bold'] ) {
		$bold = mai_maybe_get_bold_variant( $values['bold'], $available );

		if ( $bold ) {
			$variants[ $element ]['bold'] = $bold;
		}
	} else {
		$bold = false; // For later conditions.
	}

	// Italic custom.
	if ( $values['italic'] ) {
		$italic = mai_maybe_get_italic_variant( $values['italic'], $available );
	}
	// Italic default.
	elseif ( 'body' === $element ) {
		$italic = mai_maybe_get_italic_variant( $font_weight, $available );
	}
	// Set empty var.
	else {
		$italic = '';
	}

	if ( $italic ) {
		$variants[ $element ]['italic'] = $italic;
	}

	// Bold-Italic custom.
	if ( $values['bolditalic'] && in_array( $values['bolditalic'] . 'italic', $available ) ) {
		$variants[ $element ]['bolditalic'] = $values['bolditalic'] . 'italic';
	}
	// Bold-Italic default.
	elseif ( 'body' === $element && $bold && in_array( $bold . 'italic', $available ) ) {
		$variants[ $element ][ 'bolditalic' ] = $bold . 'italic';
	}

	// Additional.
	if ( $values['add'] ) {
		$add = explode( ',', $values['add'] );

		foreach ( $add as $weight ) {
			if ( ! in_array( $weight, $available ) ) {
				continue;
			}

			$variants[ $element ]['add'][] = $weight;
		}
	}

	return mai_isset( $variants, $element, $defaults );
}

/**
 * Recursively attempts to return a valid bold font weight from available weights.
 *
 * @access private
 *
 * @since TBD
 *
 * @param int|string $variant   The variant to check.
 * @param array      $available The available font variants from Kirki.
 *
 * @return int|string false;
 */
function mai_maybe_get_bold_variant( $variant, $available ) {
	if ( '400' === $variant ) {
		$variant = 'regular';
	}

	if ( in_array( $variant, $available ) ) {
		return $variant;
	}

	if ( 'regular' === $variant ) {
		$variant = '400';
	}

	if ( is_numeric( $variant ) ) {
		// Prevent infinite loops.
		if ( (int) $variant > 900 ) {
			return false;
		}

		$variant = (string) ((int) $variant + 100);

		return mai_maybe_get_bold_variant( $variant, $available );
	}

	return false;
}

/**
 * Attempts to return a valid italic font weight from available weights.
 *
 * @access private
 *
 * @since TBD
 *
 * @param int|string $variant   The variant to check.
 * @param array      $available The available font variants from Kirki.
 *
 * @return int|string false;
 */
function mai_maybe_get_italic_variant( $variant, $available ) {
	if ( in_array( $variant, [ 'regular', 'italic', '400', '400italic' ] ) ) {
		$variant = 'italic';
	}

	if ( in_array( $variant, $available ) ) {
		return $variant;
	}

	if ( in_array( $variant . 'italic', $available ) ) {
		return $variant . 'italic';
	}

	if ( in_array( 'italic', $available ) ) {
		return 'italic';
	}

	return false;
}

/**
 * Gets google fonts from kirki.
 *
 * @since 2.11.0
 *
 * @return array
 */
function mai_get_kirki_google_fonts() {
	static $fonts = null;

	if ( ! is_null( $fonts ) ) {
		return $fonts;
	}

	$kirki_fonts = Kirki_Fonts::get_instance();
	$fonts       = $kirki_fonts::get_google_fonts();

	return $fonts;
}
