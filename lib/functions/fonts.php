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
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param $fonts
 *
 * @return mixed
 */
function mai_add_body_font_variants( $fonts ) {
	$default = mai_get_global_styles( 'fonts' )['body'];
	$option  = mai_get_option( 'body-typography' );
	$body    = mai_isset( $option, 'font-family', $default );

	if ( is_string( $body ) && isset( $fonts[ $body ] ) ) {

		/**
		 * @var Kirki_Fonts $kirki_fonts
		 */
		$kirki_fonts  = Kirki_Fonts::get_instance();
		$google_fonts = $kirki_fonts::get_google_fonts();

		if ( isset( $google_fonts[ $body ] ) ) {
			$variants       = $google_fonts[ $body ]['variants'];
			$chosen_variant = $fonts[ $body ][0];

			// Load italic font variant.
			if ( 'regular' !== $chosen_variant && isset( $variants[ $chosen_variant . 'italic' ] ) ) {
				$fonts[ $body ][] = $chosen_variant . 'italic';
			} else {
				$fonts[ $body ][] = 'italic';
			}

			// List or bold variants in order of least importance.
			$bold_variants = [
				'900',
				'800',
				'500',
				'700',
				'600',
			];

			foreach ( $bold_variants as $bold_variant ) {
				if ( isset( $variants[ $bold_variant ] ) ) {
					$fonts[ $body ][] = $bold_variant;
				}
			}
		}
	}

	return $fonts;
}

add_filter( 'kirki/enqueue_google_fonts', 'mai_add_extra_google_fonts' );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param $fonts
 *
 * @return mixed
 */
function mai_add_extra_google_fonts( $fonts ) {
	$fonts_config = mai_get_global_styles( 'fonts' );
	$font_weights = mai_get_global_styles( 'font-weights' );

	foreach ( $fonts_config as $font ) {
		if ( 'body' === $font || 'heading' === $font ) {
			continue;
		}

		/**
		 * @var Kirki_Fonts $kirki_fonts
		 */
		$kirki_fonts  = Kirki_Fonts::get_instance();
		$google_fonts = $kirki_fonts::get_google_fonts();

		if ( isset( $google_fonts[ $font ] ) ) {
			$variants = $google_fonts[ $font ]['variants'];

			if ( isset( $font_weights[ $font ] ) && isset( $variants[ $font_weights[ $font ] ] ) ) {
				$fonts[ $font ] = [
					$font_weights[ $font ],
				];
			} else {
				$fonts[ $font ] = [
					'regular',
				];
			}
		}
	}

	return $fonts;
}
