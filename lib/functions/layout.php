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

// Remove layout filter from Genesis Connect for WooCommerce.
remove_filter( 'genesis_pre_get_option_site_layout', 'genesiswooc_archive_layout' );

add_filter( 'genesis_site_layout', 'mai_site_layout' );
/**
 * Use Mai Engine layout.
 *
 * @since 0.1.0
 *
 * @param bool $use_cache Whether to use memoization or not.
 *
 * @return string
 */
function mai_site_layout( $use_cache = true ) {
	static $site_layout = null;

	// Cache option added to prevent $GLOBALS['content_width'] breaking.
	if ( ! is_null( $site_layout ) && $use_cache ) {
		return esc_attr( $site_layout );
	}

	if ( is_singular() || ( is_home() && ! genesis_is_root_page() ) ) {
		$post_id     = is_home() ? get_option( 'page_for_posts' ) : null;
		$site_layout = genesis_get_custom_field( '_genesis_layout', $post_id );

	} elseif ( is_category() || is_tag() || is_tax() ) {

		/**
		 * WP Query object.
		 *
		 * @var WP_Query $wp_query
		 */
		global $wp_query;

		$term        = $wp_query->get_queried_object();
		$site_layout = $term ? get_term_meta( $term->term_id, 'layout', true ) : '';

	} elseif ( is_post_type_archive() && genesis_has_post_type_archive_support() ) {
		$site_layout = genesis_get_cpt_option( 'layout' );

	} elseif ( is_author() ) {
		$site_layout = get_the_author_meta( 'layout', (int) get_query_var( 'author' ) );
	}

	if ( ! $site_layout ) {
		$layouts  = [];
		$settings = mai_get_option( 'site-layouts', [] );
		$defaults = mai_get_config( 'settings' )['site-layout'];

		foreach ( $defaults as $context => $default ) {
			foreach ( $default as $index => $setting ) {
				$layouts[ $context ][ $index ] = isset( $settings[ $context ][ $index ] ) && ! empty( $settings[ $context ][ $index ] ) ? $settings[ $context ][ $index ] : $defaults[ $context ][ $index ];
			}
		}

		$context  = null;
		$name     = null;

		if ( mai_is_type_archive() ) {
			$context = 'archive';
			$name    = mai_get_archive_args_name();

		} elseif ( mai_is_type_single() ) {
			$context = 'single';
			$name    = mai_get_singular_args_name();
		}

		// Context by content name.
		if ( $context && $name && isset( $layouts[ $context ][ $name ] ) && $layouts[ $context ][ $name ] ) {
			$site_layout = $layouts[ $context ][ $name ];
		}

		// Context default.
		if ( ! $site_layout && $context && isset( $layouts['default'][ $context ] ) && $layouts['default'][ $context ] ) {
			$site_layout = $layouts['default'][ $context ];
		}

		// Site default.
		if ( ! $site_layout ) {
			$site_layout = $layouts['default']['site'];
		}
	}

	return $site_layout;
}

add_action( 'after_setup_theme', 'mai_content_width' );
/**
 * Filter the content width based on the user selected layout.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_content_width() {
	$layout = mai_site_layout( false );

	// Taken from assets/scss/layout/_content.scss.
	$breakpoints = [
		'wide-content'     => 'xl',
		'content-sidebar'  => 'lg',
		'sidebar-content'  => 'lg',
		'standard-content' => 'md',
		'narrow-content'   => 'sm',
	];

	$width = mai_isset( $breakpoints, $layout, 'md' );

	$GLOBALS['content_width'] = mai_get_breakpoint( $width );
}
