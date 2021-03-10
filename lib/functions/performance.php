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
