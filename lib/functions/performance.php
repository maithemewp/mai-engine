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

add_action( 'init', 'mai_disable_emojis' );
/**
 * Disable the emoji's
 */
function mai_disable_emojis() {
	if ( ! mai_get_option( 'disable-emojis', true ) ) {
		return;
	}

	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	add_filter( 'tiny_mce_plugins', 'mai_disable_emojis_tinymce' );
	add_filter( 'wp_resource_hints', 'mai_disable_emojis_remove_dns_prefetch', 10, 2 );
}

/**
 * Filter function used to remove the tinymce emoji plugin.
 *
 * @param array $plugins TinyMCE plugins.
 *
 * @return array
 */
function mai_disable_emojis_tinymce( $plugins ) {
	if ( is_array( $plugins ) ) {
		return array_diff( $plugins, [ 'wpemoji' ] );
	} else {
		return [];
	}
}

/**
 * Remove emoji CDN hostname from DNS prefetching hints.
 *
 * @param array  $urls          URLs to print for resource hints.
 * @param string $relation_type The relation type the URLs are printed for.
 *
 * @return array Difference betwen the two arrays.
 */
function mai_disable_emojis_remove_dns_prefetch( $urls, $relation_type ) {
	if ( 'dns-prefetch' === $relation_type ) {

		// This filter is documented in wp-includes/formatting.php.
		$emoji_svg_url = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/2/svg/' );

		$urls = array_diff( $urls, [ $emoji_svg_url ] );
	}

	return $urls;
}

add_action( 'widgets_init', 'mai_remove_recent_comments_style' );
/**
 * Removes recent comments widget CSS.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_remove_recent_comments_style() {
	if ( ! mai_get_option( 'remove-recent-comments-css', true ) ) {
		return;
	}

	global $wp_widget_factory;

	// Perfmatters and other plugins may remove this altogether.
	if ( ! isset( $wp_widget_factory->widgets['WP_Widget_Recent_Comments'] ) ) {
		return;
	}

	remove_action( 'wp_head', [ $wp_widget_factory->widgets['WP_Widget_Recent_Comments'], 'recent_comments_style' ] );
}

add_filter( 'wp_calculate_image_srcset_meta', 'mai_limit_max_srcset_image', 10, 4 );
/**
 * Limits srcset from using an image larger than the actual src image.
 *
 * @since TBD
 *
 * @param array  $image_meta    The image meta data as returned by 'wp_get_attachment_metadata()'.
 * @param int[]  $size_array    {
 *     An array of requested width and height values.
 *
 *     @type int $0 The width in pixels.
 *     @type int $1 The height in pixels.
 * }
 * @param string $image_src     The 'src' of the image.
 * @param int    $attachment_id The image attachment ID or 0 if not supplied.
 *
 * @return array
 */
function mai_limit_max_srcset_image( $image_meta, $size_array, $image_src, $attachment_id ) {
	if ( ! isset( $size_array[0] ) ) {
		return $image_meta;
	}
	$width = $size_array[0];
	if ( is_array( $image_meta['sizes'] ) && $image_meta['sizes'] ) {
		foreach ( $image_meta['sizes'] as $name => $value ) {
			if ( ! isset( $value['width'] ) ) {
				continue;
			}
			if ( $value['width'] > $width ) {
				unset( $image_meta['sizes'][ $name ] );
			}
		}
	}

	return $image_meta;
}

// add_action( 'wp_head', 'mai_preload_logo', 0 );
/**
 * Preloads logo.
 *
 * @since TBD
 *
 * @return void
 */
function mai_preload_logo() {
	$image_id = get_theme_mod( 'custom_logo' );

	if ( ! $image_id ) {
		return;
	}

	if ( mai_is_element_hidden( 'header' ) ) {
		return;
	}

	echo mai_get_preload_image_link( $image_id, 'full' );
}

add_action( 'wp_head', 'mai_preload_page_header_image', 0 );
/**
 * Preloads page header image.
 *
 * @since TBD
 *
 * @return void
 */
function mai_preload_page_header_image() {
	if ( ! mai_has_page_header() ) {
		return;
	}

	$image_id   = mai_get_page_header_image_id();
	$image_size = mai_get_page_header_image_size();

	if ( ! ( $image_id && $image_size ) ) {
		return;
	}

	echo mai_get_preload_image_link( $image_id, $image_size );
}

add_action( 'wp_head', 'mai_preload_featured_image', 0 );
/**
 * Preloads featured image on single posts.
 *
 * @since TBD
 *
 * @return void
 */
function mai_preload_featured_image() {
	if ( ! is_singular() ) {
		return;
	}

	if ( mai_has_page_header() && mai_get_page_header_image_id() ) {
		return;
	}

	$image_id = get_post_thumbnail_id();

	if ( ! $image_id ) {
		return;
	}

	$args = mai_get_template_args();

	if ( ! ( isset( $args['show'] ) && in_array( 'image', $args['show'], true ) ) ) {
		return;
	}

	if ( mai_is_element_hidden( 'featured_image' ) ) {
		return;
	}

	switch ( $args['image_orientation'] ) {
		case 'landscape':
		case 'portrait':
		case 'square':
			$fw_content = ( 'full-width-content' === genesis_site_layout() ) ? true : false;
			$image_size = $fw_content ? 'lg' : 'md';
			$image_size = sprintf( '%s-%s', $args['image_orientation'], $image_size );
		break;
		default:
			$image_size = $args['image_size'];
	}

	echo mai_get_preload_image_link( $image_id, $image_size );
}

add_action( 'wp_head', 'mai_preload_cover_block', 0 );
/**
 * Preloads the first cover block on single posts.
 *
 * @since TBD
 *
 * @return void
 */
function mai_preload_cover_block() {
	if ( ! is_singular() ) {
		return;
	}

	if ( mai_has_page_header() && mai_get_page_header_image_id() ) {
		return;
	}

	$first = mai_get_first_block();

	if ( ! $first ) {
		return;
	}

	$block_name  = isset( $first['blockName'] ) ? $first['blockName'] : '';

	if ( 'core/cover' !== $block_name ) {
		return;
	}

	$image_id = isset( $first['attrs']['id'] ) && $first['attrs']['id'] ? $first['attrs']['id'] : 0;

	if ( ! $image_id ) {
		return;
	}

	$image_size = mai_get_cover_image_size();

	if ( ! $image_size ) {
		return;
	}

	echo mai_get_preload_image_link( $image_id, $image_size );
}

/**
 * Gets <link> tag with image preloading data.
 *
 * @since TBD
 *
 * @param int    $image_id   The image ID.
 * @param string $image_size The image size.
 *
 * @return string
 */
function mai_get_preload_image_link( $image_id, $image_size ) {
	$image_url  = wp_get_attachment_image_url( $image_id, $image_size );

	if ( ! $image_url ) {
		return;
	}

	$image_srcset = wp_get_attachment_image_srcset( $image_id, $image_size );
	$image_srcset = $image_srcset ? sprintf( ' imagesrcset="%s"', $image_srcset ) : '';

	printf( '<link class="mai-preload" rel="preload" as="image" href="%s"%s />', $image_url, $image_srcset );
}

/**
 * Prime post thumbnail cache.
 *
 * Automatically primes the cache for the main loop on the blog page,
 * archives, and instances of Mai Post Grid.
 *
 * The cache can be activated for additional queries using the 'mai_cache_featured_images' filter.
 *
 * Custom queries can also pass a 'mai_cache_featured_images' flag to WP_Query via the
 * query args to prime the cache.
 *
 * Props Brady Vercher.
 *
 * @since 2.11.0
 *
 * @param array    $posts    List of posts in the query.
 * @param WP_Query $wp_query WP Query object.
 *
 * @return array
 */
add_action( 'the_posts', 'mai_prime_featured_images_cache', 10, 2 );
function mai_prime_featured_images_cache( $posts, $wp_query ) {
	if ( ! ( $wp_query->is_main_query() && mai_is_type_archive() ) ) {
		return $posts;
	}

	update_post_thumbnail_cache( $wp_query );

	return $posts;
}
