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

	$allowed = (array) genesis_get_layouts();
	$default = 'standard-content';
	$name    = null;
	$context = null;

	// Set default.
	foreach ( $allowed as $layout_name => $layout_data ) {
		if ( isset( $layout_data['default'] ) && $layout_data['default'] ) {
			$default = $layout_name;
			break;
		}
	}

	// Admin.
	if ( is_admin() ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || 'post' !== $screen->base ) {
			return $default;
		}

		// Can't get_the_ID() on pages like Page for Posts.
		$post_id = filter_input( INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT );

		if ( $post_id ) {
			$single_layout = genesis_get_custom_field( '_genesis_layout', $post_id );

			if ( $single_layout && isset( $allowed[ $single_layout ] ) ) {
				$site_layout = $single_layout;
			}
		}

		$name    = $screen->post_type;
		$context = 'single';
	}
	// Front end.
	else {
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
	}

	if ( ! ( $site_layout && isset( $allowed[ $site_layout ] ) ) ) {
		$layouts = (array) mai_get_site_layouts();

		if ( ! ( $name && $context ) ) {
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
		if ( ! ( $site_layout && isset( $allowed[ $site_layout ] ) ) ) {
			if ( $context && isset( $layouts['default'][ $context ] ) && $layouts['default'][ $context ] ) {
				$site_layout = $layouts['default'][ $context ];
			}
		}
	}

	// Site default.
	if ( ! ( $site_layout && isset( $allowed[ $site_layout ] ) ) ) {
		if ( isset( $layouts['default']['site'] ) && $layouts['default']['site'] ) {
			$site_layout = $layouts['default']['site'];
		}

		if ( ! ( $site_layout && isset( $allowed[ $site_layout ] ) ) ) {
			$site_layout = $default;
		}
	}

	return esc_attr( $site_layout );
}

/**
 * Gets all site layouts.
 * Uses Customizer settings with fallbacks to config.
 *
 * @since 2.19.2
 *
 * @return array
 */
function mai_get_site_layouts() {
	static $layouts = null;

	if ( ! is_null( $layouts ) ) {
		return $layouts;
	}

	$layouts  = [];
	$allowed  = (array) genesis_get_layouts();
	$settings = (array) mai_get_option( 'site-layouts', [] );
	$defaults = (array) mai_get_config( 'settings' )['site-layouts'];

	// Remove dividers from settings.
	foreach ( $settings as $context => $values ) {
		foreach ( $values as $key => $value ) {
			if ( mai_has_string( '-divider', $key ) ) {
				unset( $settings[ $context ][ $key ] );
			}
		}
	}

	// Loop through defaults, and parse settings if available.
	foreach ( $defaults as $context => $default ) {
		$layouts[ $context ] = isset( $settings[ $context ] ) ? wp_parse_args( $settings[ $context ], $defaults[ $context ] ) : $defaults[ $context ];
	}

	// Make sure all layouts are allowed.
	foreach ( $layouts as $context => $types ) {
		foreach ( $types as $type => $layout ) {
			if ( $layout && ! isset( $allowed[ $layout ] ) ) {
				$layouts[ $context ][ $type ] = '';
			}
		}
	}

	return $layouts;
}

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @since 0.1.0
 * @since 2.6.0  Change to template_redirect since after_setup_theme was too early for mai_site_layout() function.
 * @since 2.19.0 Change back to after_setup_theme after digging a bit deeper.
 *
 * @link https://github.com/studiopress/genesis/issues/77
 *
 * @global int $content_width Content width.
 *
 * @return void
 */
add_action( 'after_setup_theme', 'mai_content_width', 5 );
function mai_content_width() {
	// Open WPCS issue: {@link https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards/issues/1043}.
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
	$GLOBALS['content_width'] = apply_filters( 'mai_content_width', mai_get_breakpoint( 'md' ) );
}
