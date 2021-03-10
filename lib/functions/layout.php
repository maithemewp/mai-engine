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
 * @since 2.6.0 Removed $use_cache param since it's no longer necessary.
 * @since 2.11.0 Make sure a layout is allowed before returning it.
 *
 * @return string
 */
function mai_site_layout() {
	static $site_layout = null;

	if ( ! is_null( $site_layout ) ) {
		return esc_attr( $site_layout );
	}

	$allowed = genesis_get_layouts();
	$name    = null;

	if ( is_admin() ) {
		global $pagenow;

		$site_layout = 'wide-content';

		if ( 'post.php' !== $pagenow ) {
			return $site_layout;
		}

		$post_id = filter_input( INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT );

		if ( ! $post_id ) {
			return $site_layout;
		}

		$single_layout = genesis_get_custom_field( '_genesis_layout', $post_id );

		if ( $single_layout && isset( $allowed[ $single_layout ] ) ) {
			$site_layout = $single_layout;
			return $single_layout;
		}

		$name = get_post_type( $post_id );

	} elseif ( is_singular() || ( is_home() && ! genesis_is_root_page() ) ) {
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

	if ( ! ( $site_layout && isset( $allowed[ $site_layout ] ) ) ) {
		$layouts  = [];
		$defaults = mai_get_config( 'settings' )['site-layouts'];
		$settings = mai_get_option( 'site-layouts', [] );

		// Remove empty values from settings, so wp_parse_args works correctly.
		foreach ( $settings as $context => $values ) {
			$settings[ $context ] = array_filter( $settings[ $context ] );
		}

		// Loop through defaults, and parse settings if available.
		foreach ( $defaults as $context => $default ) {
			$layouts[ $context ] = isset( $settings[ $context ] ) ? wp_parse_args( $settings[ $context ], $defaults[ $context ] ) : $defaults[ $context ];
		}

		$context = null;

		if ( ! $name ) {
			if ( mai_is_type_archive() ) {
				$context = 'archive';
				$name    = mai_get_archive_args_name();

			} elseif ( mai_is_type_single() ) {
				$context = 'single';
				$name    = mai_get_singular_args_name();
			}
		}

		// Context by content name.
		if ( $context && $name && isset( $layouts[ $context ][ $name ] ) && $layouts[ $context ][ $name ] ) {
			$site_layout = $layouts[ $context ][ $name ];
		}

		// Context default.
		if ( ! ( $site_layout && isset( $allowed[ $site_layout ] ) ) && $context && isset( $layouts['default'][ $context ] ) && $layouts['default'][ $context ] ) {
			$site_layout = $layouts['default'][ $context ];
		}
	}

	// Site default.
	if ( ! ( $site_layout && isset( $allowed[ $site_layout ] ) ) ) {
		$site_layout = $layouts['default']['site'];
	}

	return $site_layout;
}

add_action( 'template_redirect', 'mai_content_width' );
/**
 * Filter the content width based on the user selected layout.
 *
 * @since 0.1.0
 * @since 2.6.0 Change to template_redirect since after_setup_theme was too early for mai_site_layout() function.
 *
 * @return void
 */
function mai_content_width() {
	global $GLOBALS;

	// Taken from assets/scss/layout/_content.scss.
	$breakpoints = [
		'wide-content'     => 'xl',
		'content-sidebar'  => 'lg',
		'sidebar-content'  => 'lg',
		'standard-content' => 'md',
		'narrow-content'   => 'sm',
	];

	$width = mai_isset( $breakpoints, mai_site_layout(), 'md' );

	$GLOBALS['content_width'] = mai_get_breakpoint( $width );
}
