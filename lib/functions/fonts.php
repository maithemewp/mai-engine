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

add_filter( 'kirki/enqueue_google_fonts', 'mai_add_body_font_variants' );
/**
 * Automatically load italic and bold variations of body font family.
 *
 * @since 1.0.0
 *
 * @param $fonts
 *
 * @return mixed
 */
function mai_add_body_font_variants( $fonts ) {
	$default_family  = mai_get_font_family( 'body' );
	$default_weights = mai_get_font_weights( 'body' );
	$font_option     = mai_get_option( 'body-typography' );
	$font_family     = mai_isset( $font_option, 'font-family', $default_family );

	// Return early if family not chosen.
	if ( ! isset( $fonts[ $font_family ] ) ) {
		return $fonts;
	}

	/**
	 * @var Kirki_Fonts $kirki_fonts
	 */
	$kirki_fonts  = Kirki_Fonts::get_instance();
	$google_fonts = $kirki_fonts::get_google_fonts();

	// Return early if not a Google font.
	if ( ! isset( $google_fonts[ $font_family ] ) ) {
		return $fonts;
	}

	$variants = $google_fonts[ $font_family ]['variants'];
	$chosen   = isset( $fonts[ $font_family ][0] ) ? $fonts[ $font_family ][0] : 'regular';
	$italic   = 'italic';

	if ( isset( $variants[ $chosen . 'italic' ] ) ) {
		$italic = $chosen . 'italic';

	} else if ( ! empty( $default_weights ) ) {
		foreach ( $default_weights as $weight ) {
			if ( mai_has_string( 'italic', $weight ) ) {
				$italic = $weight;
			}
		}
	}

	$fonts[ $font_family ][] = $italic;

	/*
	 * List bold variants in order of importance.
	 * We try to use 600 first, if not available then try 700 and so on.
	 */
	$bold_variants = [
		'600',
		'700',
		'500',
		'800',
		'900',
	];

	// Prioritize bold set in config (if it exists).
	foreach ( $default_weights as $weight ) {
		if ( in_array( $weight, $bold_variants, true ) ) {
			$bold_variants = array_merge( [ $weight ], $bold_variants );
		}
	}

	$bold = false;

	// Check if variant is available for family.
	foreach ( $bold_variants as $bold_variant ) {
		if ( ! $bold && in_array( $bold_variant, $variants, true ) ) {
			$bold = $bold_variant;
		}
	}

	$fonts[ $font_family ][] = $bold;

	return $fonts;
}

add_filter( 'kirki/enqueue_google_fonts', 'mai_add_extra_google_fonts' );
/**
 * Load any other Google font families defined in the config.
 *
 * @since 1.0.0
 *
 * @param $fonts
 *
 * @return mixed
 */
function mai_add_extra_google_fonts( $fonts ) {
	$fonts_config = mai_get_global_styles( 'fonts' );

	foreach ( $fonts_config as $element => $args ) {
		$font_family  = mai_get_font_family( $element );
		$font_weights = mai_get_font_weights( $element );

		/**
		 * @var Kirki_Fonts $kirki_fonts
		 */
		$kirki_fonts  = Kirki_Fonts::get_instance();
		$google_fonts = $kirki_fonts::get_google_fonts();

		// Return early if not a Google Font.
		if ( ! isset( $google_fonts[ $font_family ] ) ) {
			return $fonts;
		}

		$variants = $google_fonts[ $font_family ]['variants'];

		foreach ( $font_weights as $font_weight ) {
			if ( in_array( $font_weight, $variants, true ) ) {
				$fonts[ $font_family ][] = $font_weight;
			}
		}
	}

	return $fonts;
}
