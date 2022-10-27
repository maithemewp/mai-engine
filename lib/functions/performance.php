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

add_action( 'init', 'mai_disable_emojis' );
/**
 * Disable the emoji's
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_disable_emojis() {
	if ( ! mai_get_option( 'disable-emojis', mai_get_performance_default( 'disable-emojis' ) ) ) {
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

/**
 * Remove inline duotone svgs.
 *
 * @since 2.22.0
 *
 * @link https://github.com/WordPress/gutenberg/issues/38299
 * @link https://github.com/WordPress/gutenberg/issues/36834
 *
 * @return void
 */
add_action( 'init', 'mai_remove_wp_global_styles' );
function mai_remove_wp_global_styles() {
	if ( ! mai_get_option( 'remove-global-styles', mai_get_performance_default( 'remove-global-styles' ) ) ) {
		return;
	}

	// Global styles.
	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
	remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
	// Inline SVGs.
	remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );

	// Editor.
	remove_action( 'enqueue_block_editor_assets', 'wp_enqueue_global_styles_css_custom_properties' );
	remove_action( 'in_admin_header', 'wp_global_styles_render_svg_filters' );
}

add_action( 'wp_default_scripts', 'mai_remove_jquery_migrate' );
/**
 * Remove jQuery Migrate script.
 *
 * @since 2.24.0
 *
 * @param WP_Scripts $scripts The existing scripts.
 *
 * @return void
 */
function mai_remove_jquery_migrate( $scripts ) {
	if ( is_admin() ) {
		return;
	}

	if ( ! mai_get_option( 'remove-jquery-migrate', mai_get_performance_default( 'remove-jquery-migrate' ) ) ) {
		return;
	}

	if ( ! isset( $scripts->registered['jquery'] ) ) {
		return;
	}

	$script = $scripts->registered['jquery'];

	if ( ! $script->deps ) {
		return;
	}

	$script->deps = array_diff( $script->deps, [ 'jquery-migrate' ] );
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
	if ( ! mai_get_option( 'remove-recent-comments-css', mai_get_performance_default( 'remove-recent-comments-css' ) ) ) {
		return;
	}

	global $wp_widget_factory;

	// Perfmatters and other plugins may remove this altogether.
	if ( ! isset( $wp_widget_factory->widgets['WP_Widget_Recent_Comments'] ) ) {
		return;
	}

	remove_action( 'wp_head', [ $wp_widget_factory->widgets['WP_Widget_Recent_Comments'], 'recent_comments_style' ] );
}

add_action( 'wp_print_scripts', 'mai_dequeue_comment_reply', 99 );
/**
 * Dequeue comment reply script if there are no comments to reply to.
 * This should be dealt with in Genesis core IMO ¯\_(ツ)_/¯
 *
 * @link https://github.com/studiopress/genesis/issues/2684
 *
 * @since 2.22.0
 *
 * @return void
 */
function mai_dequeue_comment_reply() {
	if ( ! ( is_singular() && get_option( 'thread_comments' ) && comments_open() && (int) get_comments_number() <= 0 ) ) {
		return;
	}

	wp_dequeue_script( 'comment-reply' );
}

add_action( 'admin_head', 'mai_preload_fonts', 2 );
add_action( 'wp_head',    'mai_preload_fonts', 2 );
/**
 * Adds preload links early in the head for Google fonts from Kirki.
 *
 * @since 2.25.0
 *
 * @return void
 */
function mai_preload_fonts() {
	if ( ! mai_get_option( 'preload-fonts', mai_get_performance_default( 'preload-fonts' ) ) ) {
		return;
	}

	$urls     = [];
	$contents = get_transient( 'kirki_remote_url_contents' ); // This is rebuilt in Kirki's `Downloader` class `get_cached_url_contents()` method.
	// $fonts = get_option( 'kirki_downloaded_font_files' ); // These are all of the available local fonts, not just the ones loaded on the front end.

	if ( ! $contents ) {
		return;
	}

	foreach ( $contents as $css ) {
		$urls = array_merge( $urls, mai_get_font_preload_urls_from_css( $css ) );
	}

	if ( ! $urls ) {
		return;
	}

	foreach ( $urls as $url ) {
		$info = pathinfo( $url );
		$type = isset( $info['extension'] ) ? $info['extension'] : 'woff2';

		printf( '<link class="mai-preload" rel="preload" href="%s" as="font" type="font/%s" crossorigin />', $url, $type );
	}
}

add_action( 'wp_head', 'mai_preload_logo', 2 );
/**
 * Preloads logo.
 *
 * @link https://www.stefanjudis.com/today-i-learned/how-to-preload-responsive-images-with-imagesizes-and-imagesrcset/
 *
 * @since 2.25.0
 *
 * @return void
 */
function mai_preload_logo() {
	if ( mai_is_element_hidden( 'header' ) ) {
		return;
	}

	$images  = [
		mai_get_logo(),
		mai_get_scroll_logo(),
	];

	foreach ( $images as $image ) {
		if ( ! $image ) {
			continue;
		}

		$attr   = [];
		$sizes  = mai_get_string_between_strings( $image, 'sizes="', '"' );
		$srcset = mai_get_string_between_strings( $image, 'srcset="', '"' );

		if ( $sizes ) {
			$attr[] = sprintf( 'imagesizes="%s"', $sizes );
		}

		if ( $srcset ) {
			$attr[] = sprintf( 'imagesrcset="%s"', $srcset );
		}

		printf( '<link class="mai-preload" rel="preload" as="image" %s/>%s', trim( implode( ' ', $attr ) ), "\n" );
	}
}

// add_action( 'wp_head', 'mai_preload_page_header_image', 2 );
/**
 * Preloads page header image.
 *
 * @since 2.13.0
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

// add_action( 'wp_head', 'mai_preload_featured_image', 2 );
/**
 * Preloads featured image on single posts.
 *
 * @since 2.13.0
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
			$fw_content = ( 'wide-content' === genesis_site_layout() ) ? true : false;
			$image_size = $fw_content ? 'lg' : 'md';
			$image_size = sprintf( '%s-%s', $args['image_orientation'], $image_size );
		break;
		default:
			$image_size = $args['image_size'];
	}

	echo mai_get_preload_image_link( $image_id, $image_size );
}

// add_action( 'wp_head', 'mai_preload_cover_block', 2 );
/**
 * Preloads the first cover block on single posts.
 *
 * @since 2.13.0
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
 * @since 2.13.0
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

	printf( '<link class="mai-preload" rel="preload" as="image" href="%s"%s />', $image_url );
}

/**
 * Gets default performance setting.
 * Falls back to true if key is not available.
 *
 * @since 2.25.0
 *
 * @param string $setting The setting to check.
 *
 * @return bool
 */
function mai_get_performance_default( $setting ) {
	$settings    = mai_get_config( 'settings' );
	$performance = isset( $settings['performance'] ) ? $settings['performance'] : [];

	return isset( $performance['disable-emojis'] ) ? $performance['disable-emojis'] : true;
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
