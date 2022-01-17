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

add_filter( 'image_size_names_choose', 'mai_get_media_chooser_sizes' );
/**
 * Add our image sizes to the media chooser.
 *
 * @since 0.1.0
 *
 * @param array $sizes The size options.
 *
 * @return array  Modified size options.
 */
function mai_get_media_chooser_sizes( $sizes ) {
	static $choices = null;

	if ( ! is_null( $choices ) ) {
		return $choices;
	}

	global $_wp_additional_image_sizes;

	if ( ! isset( $_wp_additional_image_sizes ) || 0 === count( $_wp_additional_image_sizes ) ) {
		$choices = $sizes;

		return $choices;
	}

	$custom       = [];
	$orientations = [];
	$image_sizes  = mai_get_config( 'image-sizes' );
	$small        = __( 'Small', 'mai-engine' );
	$medium       = __( 'Medium', 'mai-engine' );
	$large        = __( 'Large', 'mai-engine' );

	foreach ( $image_sizes['add'] as $name => $args ) {
		if ( is_array( $args ) ) {
			$custom[ $name ] = mai_convert_case( $name, 'title' );
		}
		elseif ( is_string( $args ) && mai_has_string( ':', $args ) ) {
			$orientations[ $name . '-sm' ] = mai_convert_case( $name, 'title' ) . sprintf( ' (%s)', $small );
			$orientations[ $name . '-md' ] = mai_convert_case( $name, 'title' ) . sprintf( ' (%s)', $medium );
			$orientations[ $name . '-lg' ] = mai_convert_case( $name, 'title' ) . sprintf( ' (%s)', $large );
		}
	}

	if ( $custom ) {
		foreach ( $custom as $name => $label ) {
			$sizes[ $name ] = $label;
		}
	}

	if ( $orientations ) {
		foreach ( $orientations as $name => $label ) {
			$sizes[ $name ] = $label;
		}
	}

	$choices = $sizes;

	return $choices;
}
