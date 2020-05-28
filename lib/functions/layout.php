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

	// Maybe use layout via mai customizer settings.
	if ( ! $site_layout ) {
		$layouts = wp_parse_args( mai_get_option( 'site-layouts', [] ), mai_get_config( 'site-layouts' ) );

		if ( mai_is_type_archive() ) {
			$name    = mai_get_archive_args_name();
			$context = 'archive';

		} elseif ( mai_is_type_single() ) {
			$name    = mai_get_singular_args_name();
			$context = 'single';
		}

		if ( isset( $name, $context ) ) {
			if ( isset( $layouts[ $context ][ $name ] ) && ! empty( $layouts[ $context ][ $name ] ) ) {
				$site_layout = $layouts[ $context ][ $name ];
			}
		}

		if ( ! $site_layout && isset( $context ) ) {
			if ( isset( $layouts['default'][ $context ] ) && ! empty( $layouts['default'][ $context ] ) ) {
				$site_layout = $layouts['default'][ $context ];
			}
		}
	}

	// Use site default.
	if ( ! $site_layout ) {
		if ( isset( $layouts['default']['site'] ) && ! empty( $layouts['default']['site'] ) ) {
			$site_layout = $layouts['default']['site'];
		}
	}

	// Hard-code fallback. This should never happen.
	if ( ! $site_layout ) {
		$site_layout = 'standard-content';
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

	$GLOBALS['content_width'] = mai_get_breakpoint( $width);
}
