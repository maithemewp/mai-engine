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
	$sizes  = [];
	$config = mai_get_global_styles()['extra'];
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
	$bold            = '700'; // Need default for instances where Google Fonts are not used in Customizer.
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
		if ( in_array( (string) $weight, $bold_variants, true ) ) {

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
