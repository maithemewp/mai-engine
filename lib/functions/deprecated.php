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
 * Returns a color option value with config default fallback.
 *
 * @since      2.0.0
 * @deprecated 2.13.0 Use mai_get_color_value()
 * @see        mai_get_color_value()
 *
 * @param string $name Name of the color to get.
 *
 * @return string
 */
function mai_get_color( $name ) {
	_deprecated_function( __FUNCTION__, '2.13.0', 'mai_get_color_value()' );
	mai_get_color_value( $name );
}


/**
 * Get cover image HTML by ID,
 * with srcset for our registered image sizes.
 *
 * @access private Do not use. Left here for back compat.
 *
 * @since  2.0.0
 * @since  2.6.0 Added $max_width parameter.
 * @since  2.12.0 No longer used. Deprecated.
 *
 * @param  mixed      $image_id  The image ID or URL.
 * @param  array      $atts      Any image attributes to add to the attachment.
 * @param  string|int $max_width The image max width. Either '100vw' or an integer pixel value.
 *
 * @return string  The image markup.
 */
function mai_get_cover_image_html( $image_id, $atts = [], $max_width = '100vw' ) {
	_deprecated_function( __FUNCTION__, '2.12.0', 'No longer supported' );

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

add_action( 'after_setup_theme', 'mai_do_deprecated_functionality', 4 );
/**
 * Run deprecated functionality on older installs.
 *
 * `first-version` was not set correctly pre-2.0.0
 * so we need to do checks for active sidebars.
 *
 * @link https://github.com/maithemewp/mai-engine/issues/170
 *
 * @since 2.0.0
 *
 * @return void
 */
function mai_do_deprecated_functionality() {
	$has_deprecated_sidebar = false;
	$deprecated_sidebars    = [
		'before-header',
		'header-left',
		'header-right',
		'sidebar',
		'after-entry',
		'mobile-menu',
		'before-footer',
		'footer',
		'footer-credits',
	];

	foreach ( $deprecated_sidebars as $sidebar ) {
		if ( is_active_sidebar( $sidebar ) ) {
			$has_deprecated_sidebar = true;
		}
		break;
	}

	if ( ! $has_deprecated_sidebar ) {
		return;
	}

	add_filter( 'mai_config', 'mai_deprecated_2_0_0' );
}

/**
 * Run functionality deprecated since 2.0.0.
 *
 * @since 2.0.0
 *
 * @param array $config Theme config.
 *
 * @return array
 */
function mai_deprecated_2_0_0( $config ) {
	$config['scripts-and-styles']['add'][] = [
		'handle' => mai_get_handle() . '-deprecated',
		'src'    => mai_get_url() . 'assets/css/deprecated.min.css',
	];

	$config['widget-areas'] = [
		'add'    => [
			'before-header' => [
				'name'        => __( 'Before Header', 'mai-engine' ),
				'description' => __( 'The Before Header widget area.', 'mai-engine' ),
				'location'    => 'genesis_before_header',
			],
			'header-left' => [
				'name'        => __( 'Header Left', 'mai-engine' ),
				'description' => __( 'The Header Left widget area.', 'mai-engine' ),
				'location'    => 'mai_header_left',
				'args'        => [
					'before' => '<div class="header-widget-area">',
					'after'  => '</div>',
				],
			],
			'header-right' => [
				'name'        => __( 'Header Right', 'mai-engine' ),
				'description' => __( 'The Header Right widget area.', 'mai-engine' ),
				'location'    => 'mai_header_right',
				'args'        => [
					'before' => '<div class="header-widget-area">',
					'after'  => '</div>',
				],
			],
			'sidebar' => [
				'name'        => __( 'Sidebar', 'mai-engine' ),
				'description' => __( 'The Sidebar widget area.', 'mai-engine' ),
				'location'    => '',
			],
			'after-entry' => [
				'name'        => __( 'After Entry', 'mai-engine' ),
				'description' => __( 'The After Entry widget area.', 'mai-engine' ),
				'location'    => '',
			],
			'mobile-menu' => [
				'name'        => __( 'Mobile Menu', 'mai-engine' ),
				'description' => __( 'The Mobile Menu widget area.', 'mai-engine' ),
				'location'    => 'mai_after_header_wrap',
			],
			'before-footer' => [
				'name'        => __( 'Before Footer', 'mai-engine' ),
				'description' => __( 'The Before Footer widget area.', 'mai-engine' ),
				'location'    => 'genesis_footer',
				'priority'    => 5,
			],
			'footer' => [
				'name'        => __( 'Footer', 'mai-engine' ),
				'description' => __( 'The Footer widget area.', 'mai-engine' ),
				'location'    => 'genesis_footer',
			],
			'footer-credits' => [
				'name'        => __( 'Footer Credits', 'mai-engine' ),
				'description' => __( 'The Footer Credits widget area.', 'mai-engine' ),
				'location'    => 'genesis_footer',
				'priority'    => 12,
				'default'     => sprintf(
					'%s [footer_copyright] · [footer_home_link] · %s · %s <a target="_blank" rel="nofollow noopener sponsored" href="https://bizbudding.com/mai-theme/">%s</a>',
					__( 'Copyright', 'mai-engine' ),
					__( 'All Rights Reserved', 'mai-engine' ),
					__( 'Powered by', 'mai-engine' ),
					__( 'Mai Theme', 'mai-engine' )
				),
			],
		],
		'remove' => [],
	];

	return $config;
}
