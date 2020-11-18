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
 * Gets aspect ratio from orientation.
 *
 * @since 0.1.0
 *
 * @param string $orientation Orientation type.
 *
 * @return bool|mixed
 */
function mai_get_aspect_ratio_from_orientation( $orientation ) {
	$image_sizes = mai_get_config( 'image-sizes' );

	if ( isset( $image_sizes['add'][ $orientation ] ) ) {
		return str_replace( ':', '/', $image_sizes['add'][ $orientation ] );
	}

	return false;
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
	$all_sizes = mai_get_available_image_sizes();
	$sizes     = isset( $all_sizes[ $image_size ] ) ? $all_sizes[ $image_size ] : false;

	return $sizes ? sprintf( '%s/%s', $sizes['width'], $sizes['height'] ) : '4/3';
}

/**
 * Gets an image width by size name.
 *
 * @since TBD
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
 * @param  string $orientation Orientation type.
 *
 * @return bool
 */
function mai_has_image_orientiation( $orientation ) {
	$orientations = mai_get_available_image_orientations();

	return in_array( $orientation, $orientations, true );
}

/**
 * Get cover image HTML by ID,
 * with srcset for our registered image sizes.
 *
 * @since  2.0.0
 * @since  2.6.0 Added $max_width parameter.
 *
 * @param  mixed      $image_id  The image ID or URL.
 * @param  array      $atts      Any image attributes to add to the attachment.
 * @param  string|int $max_width The image max width. Either '100vw' or an integer pixel value.
 *
 * @return string  The image markup.
 */
function mai_get_cover_image_html( $image_id, $atts = [], $max_width = '100vw' ) {
	$html = '';

	// Setup atts.
	$atts = wp_parse_args(
		$atts,
		[
			'sizes'   => '100vw',
			'loading' => 'lazy', // Add native lazy loading.
		]
	);

	// Bail if not an image id or url.
	if ( ! ( is_numeric( $image_id ) || filter_var( $image_id, FILTER_VALIDATE_URL ) ) ) {
		return;
	}

	$full = '100vw' === $max_width;

	if ( is_numeric( $image_id ) ) {
		// Build srcset array.
		$image_sizes    = mai_get_available_image_sizes();
		$max_image_size = 'cover';
		$srcset         = [];
		$sizes          = [
			'landscape-sm',
			'landscape-md',
			'landscape-lg',
			'cover',
		];

		foreach ( $sizes as $size ) {
			if ( ! isset( $image_sizes[ $size ] ) ) {
				continue;
			}

			$url = '';

			if ( is_numeric( $image_id ) ) {
				if ( $full || ( $image_sizes[ $size ]['width'] <= (int) $max_width ) ) {
					$url            = wp_get_attachment_image_url( $image_id, $size );
					$max_image_size = $size;
				}
			} elseif ( filter_var( $image_id, FILTER_VALIDATE_URL) ) {
				$url = $image_id;
			}

			if ( ! $url ) {
				continue;
			}

			$srcset[] = sprintf( '%s %sw', $url, $image_sizes[ $size ]['width'] );
		}

		// Convert to string.
		$atts['srcset'] = implode( ',', $srcset );

		// Get the image HTML.
		$html .= wp_get_attachment_image( $image_id, $max_image_size, false, $atts );

	} elseif ( filter_var( $image_id, FILTER_VALIDATE_URL ) ) {

		$output = '';

		foreach ( $atts as $key => $value ) {

			if ( ! $value ) {
				continue;
			}

			if ( true === $value ) {
				$output .= esc_html( $key ) . ' ';
			} else {
				$output .= sprintf( '%s="%s" ', esc_html( $key ), esc_attr( $value ) );
			}
		}

		// TODO: Use `<figure>`?
		$html = sprintf( '<img src="%s"%s>', $image_id, $output );
	}

	return $html;
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

	if ( is_null( $choices ) ) {
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
	}

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
