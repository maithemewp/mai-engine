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

/**
 * Returns an array of font sizes based on the font scale.
 *
 * @since 2.0.0
 *
 * @return array
 */
function mai_get_font_sizes() {
	$font_sizes    = [];
	$global_styles = mai_get_global_styles();
	$scale         = $global_styles['font-scale'];
	$base          = $global_styles['font-sizes']['base'];
	$sm            = $base / $scale;
	$xs            = $sm / $scale;
	$lg            = $base * $scale;
	$xl            = $lg * $scale;
	$xxl           = $xl * $scale;
	$xxxl          = $xxl * $scale;
	$xxxxl         = $xxxl * $scale;

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
		$font_sizes[] = [
			'slug' => $slug,
			'size' => $size,
			'name' => strtoupper( $slug ),
		];
	}

	return $font_sizes;
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
	$fonts = mai_get_global_styles( 'fonts' );

	if ( ! mai_has_string( ':', $fonts[ $element ] ) ) {
		return $fonts[ $element ];
	}

	return explode( ':', $fonts[ $element ] )[0];
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
	$default = mai_get_default_font_family( $element );
	$option  = mai_get_option( $element . '-typography' );

	return mai_isset( $option, 'font-family', $default );
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
	$default = mai_get_default_font_weight( $element );
	$option  = mai_get_option( $element . '-typography' );

	return mai_isset( $option, 'variant', $default );
}

/**
 * Returns the best match italic variant of a given element.
 *
 * @since 2.0.0
 *
 * @param string $element Element to check.
 *
 * @return string
 */
function mai_get_italic_variant( $element ) {
	$italic          = '';
	$kirki_fonts     = Kirki_Fonts::get_instance();
	$google_fonts    = $kirki_fonts::get_google_fonts();
	$font_family     = mai_get_font_family( $element );
	$regular_weight  = mai_get_font_weight( $element );
	$default_weights = mai_get_default_font_weights( $element );

	if ( ! isset( $google_fonts[ $font_family ] ) ) {
		return $italic;
	}

	$variants = array_flip( $google_fonts[ $font_family ]['variants'] );

	if ( isset( $variants[ $regular_weight . 'italic' ] ) ) {
		$italic = $regular_weight . 'italic';

	} elseif ( isset( $variants['italic'] ) ) {
		$italic = 'italic';

	} elseif ( ! empty( $default_weights ) ) {
		foreach ( $default_weights as $weight ) {
			if ( mai_has_string( 'i', $weight ) ) {
				$italic = $weight;
			}
		}
	}

	return $italic;
}

/**
 * Returns the best match bold variant of a given element.
 *
 * @since 2.0.0
 *
 * @param string $element Element to check.
 *
 * @return string
 */
function mai_get_bold_variant( $element ) {
	$bold            = '';
	$kirki_fonts     = Kirki_Fonts::get_instance();
	$google_fonts    = $kirki_fonts::get_google_fonts();
	$font_family     = mai_get_font_family( $element );
	$default_weights = mai_get_default_font_weights( $element );
	$bold_variants   = [ '600', '500', '700', '800', '900' ];

	if ( ! isset( $google_fonts[ $font_family ] ) ) {
		return $bold;
	}

	$variants = $google_fonts[ $font_family ]['variants'];

	// Prioritize bold weights set in config (if it exists).
	foreach ( $default_weights as $weight ) {
		if ( in_array( (int) $weight, $bold_variants, true ) ) {

			// If any exist in the config, move them to the top of the array.
			$bold_variants = array_merge( [ $weight ], $bold_variants );
		}
	}

	// Reverse variants so the highest priority is looped through last.
	$bold_variants = array_reverse( array_unique( $bold_variants ) );

	// Check if variant is actually available for family.
	foreach ( $bold_variants as $bold_variant ) {
		if ( in_array( $bold_variant, $variants, true ) ) {
			$bold = $bold_variant;
		}
	}

	return $bold;
}

add_filter( 'kirki_enqueue_google_fonts', 'mai_add_body_font_variants', 99 );
/**
 * Automatically load italic and bold variations of body font family.
 *
 * @since 2.0.0
 *
 * @param array $fonts All fonts to be enqueued.
 *
 * @return mixed
 */
function mai_add_body_font_variants( $fonts ) {
	$font_family = mai_get_font_family( 'body' );

	// Return early if body font family not chosen.
	if ( ! isset( $fonts[ $font_family ] ) ) {
		return $fonts;
	}

	// Set variants if they exist.
	$italic = mai_get_italic_variant( 'body' );
	$bold   = mai_get_bold_variant( 'body' );

	if ( $italic ) {
		$fonts[ $font_family ][] = $italic;
	}

	if ( $bold ) {
		$fonts[ $font_family ][] = $bold;
	}

	// Remove duplicates.
	$fonts[ $font_family ] = array_flip( array_flip( $fonts[ $font_family ] ) );

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
	// Convert to strings for later comparison.
	foreach ( $fonts as $family => $weights ) {
		$fonts[ $family ] = array_map( 'strval', $weights );
	}
	$fonts_config = mai_get_global_styles( 'fonts' );

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
			return $fonts;
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
