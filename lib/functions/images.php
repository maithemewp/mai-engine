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
 * Filters an image's 'srcset' sources.
 *
 * @param array  $sources {
 *     One or more arrays of source data to include in the 'srcset'.
 *
 *     @type array $width {
 *         @type string $url        The URL of an image source.
 *         @type string $descriptor The descriptor type used in the image candidate string,
 *                                  either 'w' or 'x'.
 *         @type int    $value      The source width if paired with a 'w' descriptor, or a
 *                                  pixel density value if paired with an 'x' descriptor.
 *     }
 * }
 * @param array $size_array     {
 *     An array of requested width and height values.
 *
 *     @type int $0 The width in pixels.
 *     @type int $1 The height in pixels.
 * }
 * @param string $image_src     The 'src' of the image.
 * @param array  $image_meta    The image meta data as returned by 'wp_get_attachment_metadata()'.
 * @param int    $attachment_id Image attachment ID or 0.
 *
 * @return array
 */
add_filter( 'wp_calculate_image_srcset', 'mai_image_srcset_order', 10, 5 );
function mai_image_srcset_order( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
	// Someone hit a bool $sources error when using svg for site logo.
	if ( is_array( $sources ) && $sources ) {
		ksort( $sources, SORT_NUMERIC );
	}

	return $sources;
}

/**
 * Gets logo markup.
 *
 * @since 2.25.0
 *
 * @return string
 */
function mai_get_logo() {
	static $logo = null;

	if ( ! is_null( $logo ) ) {
		return $logo;
	}

	$logo = get_custom_logo();

	return $logo;
}

/**
 * Gets scroll logo markup.
 *
 * @since 2.25.0
 *
 * @return string
 */
function mai_get_scroll_logo() {
	static $logo = null;

	if ( ! is_null( $logo ) ) {
		return $logo;
	}

	$logo_id = mai_get_scroll_logo_id();
	$atts    = mai_add_logo_attributes(
		[
			'class'          => 'custom-scroll-logo',
			'data-pin-nopin' => 'true',
		]
	);

	$logo = wp_get_attachment_image( $logo_id, 'large', false, $atts );

	return $logo;
}

/**
 * Gets logo ID.
 *
 * @since 2.25.0
 *
 * @return int
 */
function mai_get_logo_id() {
	return absint( get_theme_mod( 'custom_logo' ) );
}

/**
 * Gets scroll logo ID.
 * Prior to 2.24.1 the logo was saved as a url. This makes sure an ID is always returned.
 *
 * @since 2.25.0
 *
 * @return int
 */
function mai_get_scroll_logo_id() {
	static $logo_id = null;

	if ( ! is_null( $logo_id ) ) {
		return $logo_id;
	}

	$logo = mai_get_option( 'logo-scroll' );

	if ( ! $logo ) {
		$logo_id = 0;
		return $logo_id;
	}

	if ( is_numeric( $logo ) ) {
		$logo_id = absint( $logo );
		return $logo_id;
	}

	$logo_id = absint( attachment_url_to_postid( $logo ) );

	return $logo_id;
}

/**
 * Gets term featured image ID.
 *
 * @since 2.25.0
 *
 * @param int|WP_Term
 *
 * @return int
 */
function mai_get_term_image_id( $term ) {
	$image_id = 0;
	$term     = get_term( $term );

	if ( ! $term || is_wp_error( $term ) ) {
		return $image_id;
	}

	$key = 'featured_image';

	// We need to check each term because a grid archive can show multiple taxonomies.
	if ( class_exists( 'WooCommerce' ) && 'product_cat' === $term->taxonomy ) {
		$key = 'thumbnail_id';
	}

	$image_id = get_term_meta( $term->term_id, $key, true );

	return absint( $image_id );
}


/**
 * Adds (forces) logo attributes.
 * This makes sure the correct attributes are used, and match for preloading.
 *
 * @access private
 *
 * @since 2.25.0
 *
 * @param array $attr The existing attributes.
 *
 * @return array
 */
function mai_add_logo_attributes( $attr ) {
	$break     = mai_get_mobile_menu_breakpoint();
	$widths    = mai_get_option( 'logo-width', [] );
	$widths    = array_map( 'absint', $widths );
	$desktop   = isset( $widths['desktop'] ) ? $widths['desktop'] : 0;
	$desktop   = max( $desktop, 1 );
	$mobile    = isset( $widths['mobile'] ) ? $widths['mobile'] : 0;
	$mobile    = max( $mobile, 1 );
	$overrides = [
		'loading' => 'eager',
		'sizes'   => sprintf( '(min-width: %s) %s, %s', $break, mai_get_unit_value( $desktop ), mai_get_unit_value( $mobile ) ),
	];

	return wp_parse_args( $overrides, $attr	);
}

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

	if ( isset( $image_sizes['add'][ $orientation ] ) && is_string( $image_sizes['add'][ $orientation ] ) && mai_has_string( ':', $image_sizes['add'][ $orientation ] ) ) {
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

	$orientations = [];
	$image_sizes  = mai_get_config( 'image-sizes' );

	foreach ( $image_sizes['add'] as $name => $args ) {
		if ( ! ( is_string( $args ) && mai_has_string( ':', $args ) ) ) {
			continue;
		}

		$orientations[] = $name;
	}

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

	$default = [
		'landscape' => esc_html__( 'Landscape', 'mai-engine' ),
		'portrait'  => esc_html__( 'Portrait', 'mai-engine' ),
		'square'    => esc_html__( 'Square', 'mai-engine' ),
	];

	$orientations = mai_get_available_image_orientations();

	foreach ( $orientations as $orientation ) {
		$choices[ $orientation ] = isset( $default[ $orientation ] ) ? $default[ $orientation ] : mai_convert_case( $orientation, 'title' );
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
	$image_size = (string) apply_filters( 'mai_page_header_image_size', 'medium' );
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
	$image_size = (string) apply_filters( 'mai_cover_image_size', 'medium' );
	return $image_size;
}

/**
 * Gets <link> tag with image preloading data.
 *
 * @access private
 *
 * @since 2.25.5
 *
 * @param int    $image_id   The image ID.
 * @param string $image_size The image size.
 *
 * @return string
 */
function mai_get_preload_image_link( $image_id, $image_size = 'full' ) {
	$image_url  = wp_get_attachment_image_url( $image_id, $image_size );

	if ( ! $image_url ) {
		return;
	}

	$attr   = [];
	$atts   = mai_get_image_src_srcset_sizes( $image_id, $image_size );
	$src    = isset( $atts['src'] ) ? $atts['src'] : '';
	$srcset = isset( $atts['srcset'] ) ? $atts['srcset'] : '';
	$sizes  = isset( $atts['sizes'] ) ? $atts['sizes'] : '';

	// Gets smallest image size.
	// if ( $srcset ) {
	// 	$array = explode( ',', $srcset );
	// 	$first = reset( $array );
	// 	$array = explode( ' ', $first );
	// 	$first = reset( $array );
	// 	$src   = esc_url( $first );
	// }

	if ( $src && ! $srcset ) {
		$attr[] = sprintf( 'href="%s"', $src );
	} else {
		// @link https://nostrongbeliefs.com/blog/preloading-responsive-images/
		$attr[] = sprintf( 'href=""' );
	}

	if ( $srcset ) {
		$attr[] = sprintf( 'imagesrcset="%s"', $srcset );
	}

	if ( $sizes ) {
		$attr[] = sprintf( 'imagesizes="%s"', $sizes );
	}

	$attr = array_filter( $attr );

	if ( ! $attr ) {
		return;
	}

	return sprintf( '<link rel="preload" class="mai-preload" %s as="image" />%s', trim( implode( ' ', $attr ) ), "\n" );
}

/**
 * Taken from `wp_get_attachment_image()`.
 *
 * @access private
 *
 * @since 2.25.5
 *
 * @param int $image_id The image ID.
 *
 * @return array
 */
function mai_get_image_src_srcset_sizes( $image_id, $size = 'full' ) {
	$attr = [
		'src'    => '',
		'srcset' => '',
		'sizes'  => '',
	];

	$image      = wp_get_attachment_image_src( $image_id, $size );
	$image_meta = wp_get_attachment_metadata( $image_id );

	if ( $image ) {
		list( $src, $width, $height ) = $image;

		$attr['src'] = $src;
	}

	if ( is_array( $image_meta ) ) {
		$size_array = array( absint( $width ), absint( $height ) );
		$srcset     = wp_calculate_image_srcset( $size_array, $src, $image_meta, $image_id );
		$sizes      = wp_calculate_image_sizes( $size_array, $src, $image_meta, $image_id );

		if ( $srcset && ( $sizes || ! empty( $attr['sizes'] ) ) ) {
			$attr['srcset'] = $srcset;

			if ( empty( $attr['sizes'] ) ) {
				$attr['sizes'] = $sizes;
			}
		}
	}

	/**
	 * Filters the list of attachment image attributes.
	 *
	 * @param string[]     $attr       Array of attribute values for the image markup, keyed by attribute name.
	 *                                 See wp_get_attachment_image().
	 * @param WP_Post      $attachment Image attachment post.
	 * @param string|int[] $size       Requested image size. Can be any registered image size name, or
	 *                                 an array of width and height values in pixels (in that order).
	 */
	$attr = apply_filters( 'wp_get_attachment_image_attributes', $attr, get_post( $image_id ), $size );
	$attr = array_map( 'esc_attr', $attr );

	return $attr;
}

/**
 * No longer used.
 * @see https://github.com/maithemewp/mai-engine/issues/482
 *
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
// add_filter( 'wp_get_attachment_image_src', 'mai_limit_attachment_image_src', 10, 4 );
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
