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
 * Gets aspect ratio from orientation.
 *
 * @since 0.1.0
 *
 * @param string $orientation Orientation type.
 *
 * @return bool|mixed
 */
function mai_get_aspect_ratio_from_orientation( $orientation ) {
	static $ratios = null;

	if ( is_array( $ratios ) && isset( $ratios[ $orientation ] ) ) {
		return $ratios[ $orientation ];
	}

	$ratios      = [];
	$image_sizes = mai_get_config( 'image-sizes' );

	if ( isset( $image_sizes['add'][ $orientation ] ) ) {
		$ratios[ $orientation ] = str_replace( ':', '/', $image_sizes['add'][ $orientation ] );
	} else {
		$ratios[ $orientation ] = false;
	}

	return $ratios[ $orientation ];
}

/**
 * Gets aspect ration from a registered image width/height.
 *
 * @since 0.1.0
 *
 * @param string $image_size Image size to get.
 *
 * @return string
 */
function mai_get_image_aspect_ratio( $image_size ) {
	static $ratios = null;

	if ( is_array( $ratios ) && isset( $ratios[ $image_size ] ) ) {
		return $ratios[ $image_size ];
	}

	$ratios                = [];
	$all_sizes             = mai_get_available_image_sizes();
	$sizes                 = isset( $all_sizes[ $image_size ] ) ? $all_sizes[ $image_size ] : false;
	$ratios[ $image_size ] = $sizes ? sprintf( '%s/%s', $sizes['width'], $sizes['height'] ) : '4/3';

	return $ratios[ $image_size ];
}

/**
 * Gets an image width by size name.
 *
 * @since 2.7.0
 *
 * @param string $image_size The image size name.
 *
 * @return string
 */
function mai_get_image_width( $image_size ) {
	$sizes = mai_get_available_image_sizes();
	return isset( $sizes[ $image_size ]['width'] ) ? $sizes[ $image_size ]['width'] : 0;
}

/**
 * Gets an image height by size name.
 *
 * @since 2.13.0
 *
 * @param string $image_size The image size name.
 *
 * @return string
 */
function mai_get_image_height( $image_size ) {
	$sizes = mai_get_available_image_sizes();
	return isset( $sizes[ $image_size ]['height'] ) ? $sizes[ $image_size ]['height'] : 0;
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
	static $image_sizes = null;

	if ( ! is_null( $image_sizes ) ) {
		return $image_sizes;
	}

	$image_sizes = [];

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

	if ( ! is_null( $orientations ) ) {
		return $orientations;
	}

	$image_sizes  = mai_get_config( 'image-sizes' );
	$orientations = array_intersect( array_keys( $image_sizes['add'] ), [ 'landscape', 'portrait', 'square' ] );
	$orientations = array_values( array_diff( $orientations, array_keys( $image_sizes['remove'] ) ) );

	return $orientations;
}

/**
 * Check if we have a specific image orientations.
 *
 * @since  0.1.0
 *
 * @param  string $orientation Orientation type.
 *
 * @return bool
 */
function mai_has_image_orientiation( $orientation ) {
	$orientations = mai_get_available_image_orientations();

	return in_array( $orientation, $orientations, true );
}

/**
 * Gets the image size choices.
 *
 * @since 0.1.0
 *
 * @return array
 */
function mai_get_image_size_choices() {
	$choices = [];
	if ( ! ( is_admin() || is_customize_preview() ) ) {
		return $choices;
	}
	$sizes = mai_get_available_image_sizes();
	foreach ( $sizes as $index => $value ) {
		$choices[ $index ] = sprintf( '%s (%s x %s)', $index, $value['width'], $value['height'] );
	}

	return $choices;
}

/**
 * Gets the image orientation choices.
 *
 * @since 0.1.0
 *
 * @return array
 */
function mai_get_image_orientation_choices() {
	static $choices = null;

	if ( ! ( is_admin() || is_customize_preview() ) ) {
		return $choices;
	}

	if ( ! is_null( $choices ) ) {
		return $choices;
	}

	$all = [
		'landscape' => esc_html__( 'Landscape', 'mai-engine' ),
		'portrait'  => esc_html__( 'Portrait', 'mai-engine' ),
		'square'    => esc_html__( 'Square', 'mai-engine' ),
	];

	$orientations = mai_get_available_image_orientations();

	foreach ( $orientations as $orientation ) {
		$choices[ $orientation ] = $all[ $orientation ];
	}

	$choices['custom'] = esc_html__( 'Custom', 'mai-engine' );

	return $choices;
}

/**
 * Gets the image size from an aspect ratio.
 *
 * @since 0.1.0
 *
 * @param string $size  Image size.
 * @param string $ratio Aspect ratio.
 *
 * @return array
 */
function mai_get_image_sizes_from_aspect_ratio( $size = 'md', $ratio = '16:9' ) {
	$ratio       = explode( ':', $ratio );
	$x           = $ratio[0];
	$y           = $ratio[1];
	$breakpoints = mai_get_breakpoints();
	$width       = isset( $breakpoints[ $size ] ) ? (int) mai_get_breakpoint( $size ) : 0;
	$height      = $width / $x * $y;

	return [ $width, $height, true ];
}

/**
 * Get the page header image size.
 * This is also used for preloading in performance.php.
 *
 * @since 2.13.0
 *
 * @return string
 */
function mai_get_page_header_image_size() {
	$image_size = null;
	if ( ! is_null( $image_size ) ) {
		return $image_size;
	}
	$image_size = (string) apply_filters( 'mai_page_header_image_size', 'cover' );
	return $image_size;
}

/**
 * Get the cover image size.
 * This is also used for preloading in performance.php.
 *
 * @since 2.13.0
 *
 * @return string
 */
function mai_get_cover_image_size() {
	$image_size = null;
	if ( ! is_null( $image_size ) ) {
		return $image_size;
	}
	$image_size = (string) apply_filters( 'mai_cover_image_size', 'cover' );
	return $image_size;
}

/**
 * Limits the largest image size served.
 * Prevents cover blocks and page header from serving huge images.
 *
 * @param array|false  $image         {
 *     Array of image data, or boolean false if no image is available.
 *
 *     @type string $0 Image source URL.
 *     @type int    $1 Image width in pixels.
 *     @type int    $2 Image height in pixels.
 *     @type bool   $3 Whether the image is a resized image.
 * }
 * @param int          $attachment_id Image attachment ID.
 * @param string|int[] $size          Requested image size. Can be any registered image size name, or
 *                                    an array of width and height values in pixels (in that order).
 * @param bool         $icon          Whether the image should be treated as an icon.
 */
add_filter( 'wp_get_attachment_image_src', 'mai_limit_attachment_image_src', 10, 4 );
function mai_limit_attachment_image_src( $image, $attachment_id, $size, $icon ) {
	if ( 'full' !== $size ) {
		return $image;
	}

	if ( ! $image ) {
		return $image;
	}

	remove_filter( 'wp_get_attachment_image_src', 'mai_limit_attachment_image_src', 10, 4 );

	$available = mai_get_available_image_sizes();
	$size      = isset( $available['cover'] ) ? 'cover' : 'large';
	$src       = wp_get_attachment_image_src( $attachment_id, $size );

	add_filter( 'wp_get_attachment_image_src', 'mai_limit_attachment_image_src', 10, 4 );

	return $src ?: $image;
}
