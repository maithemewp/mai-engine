<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2019 BizBudding
 * @license   GPL-2.0-or-later
 */

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param string $image_size Image size to get.
 *
 * @return string
 */
function mai_get_image_aspect_ratio( $image_size ) {
	$all_sizes = mai_get_available_image_sizes();
	$sizes     = isset( $all_sizes[ $image_size ] ) ? $all_sizes[ $image_size ] : false;

	// TODO: Get default landscape aspect ratio.
	return $sizes ? sprintf( '%s/%s', $sizes['height'], $sizes['width'] ) : '4/3';
}

/**
 * Get a combined list of default and custom registered image sizes.
 *
 * Originally taken from CMB2. Static variable added here.
 *
 * We can't use `genesis_get_image_sizes()` because we need it earlier than Genesis is loaded for Kirki.
 *
 * @since  0.1.0
 *
 * @link   http://core.trac.wordpress.org/ticket/18947
 * @global array $_wp_additional_image_sizes All image sizes.
 *
 * @return array
 */
function mai_get_available_image_sizes() {
	static $image_sizes = [];

	if ( ! empty( $image_sizes ) ) {
		return $image_sizes;
	}

	// Get image sizes.
	global $_wp_additional_image_sizes;
	$default_image_sizes = [ 'thumbnail', 'medium', 'large' ];

	foreach ( $default_image_sizes as $size ) {
		$image_sizes[ $size ] = [
			'height' => intval( get_option( "{$size}_size_h" ) ),
			'width'  => intval( get_option( "{$size}_size_w" ) ),
			'crop'   => get_option( "{$size}_crop" ) ? get_option( "{$size}_crop" ) : false,
		];
	}

	if ( isset( $_wp_additional_image_sizes ) && count( $_wp_additional_image_sizes ) ) {
		$image_sizes = array_merge( $image_sizes, $_wp_additional_image_sizes );
	}

	return $image_sizes;
}

/**
 * Get available image orientations.
 *
 * @since  0.1.0
 *
 * @return array
 */
function mai_get_available_image_orientations() {
	static $orientations = null;
	if ( is_null( $orientations ) ) {
		$image_sizes  = mai_get_config( 'image-sizes' );
		$orientations = array_intersect( array_keys( $image_sizes['add'] ), [ 'landscape', 'portrait', 'square' ] );
		$orientations = array_values( array_diff( $orientations, array_keys( $image_sizes['remove'] ) ) );
	}
	return $orientations;
}

/**
 * Check if we have a specific image orientations.
 *
 * @since  0.1.0
 *
 * @return bool
 */
function mai_has_image_orientiation( $orientation ) {
	$orientations = mai_get_availalbe_image_orientations();
	return isset( $orientations[ $orientation ] );
}

/**
 * Get cover image HTML by ID,
 * with srcset for our registered image sizes.
 *
 * @param   int   $image_id The image ID.
 * @param   array $atts     Any image attributes to add to the attachment.
 *
 * @return  string|HTML  The image markup.
 */
function mai_get_cover_image_html( $image_id, $atts = [] ) {

	// Setup atts.
	$atts = wp_parse_args(
		$atts,
		[
			'sizes' => '100vw',
		]
	);

	// Build srcset array.
	$image_sizes = mai_get_available_image_sizes();
	$srcset      = [];
	$sizes       = [
		'landscape-sm',
		'landscape-md',
		'landscape-lg',
		'cover',
	];
	foreach ( $sizes as $size ) {
		if ( ! isset( $image_sizes[ $size ] ) ) {
			continue;
		}
		$url = wp_get_attachment_image_url( $image_id, $size );
		if ( ! $url ) {
			continue;
		}
		$srcset[] = sprintf( '%s %sw', $url, $image_sizes[ $size ]['width'] );
	}

	// Convert to string.
	$atts['srcset'] = implode( ',', $srcset );

	// Get the image HTML.
	return wp_get_attachment_image( $image_id, 'cover', false, $atts );

}
