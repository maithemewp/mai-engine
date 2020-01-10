<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2019 BizBudding
 * @license   GPL-2.0-or-later
 */

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * Returns the plugin directory.
 *
 * @since 0.1.0
 *
 * @return string
 */
function mai_dir() {
	static $dir = null;

	if ( is_null( $dir ) ) {
		$dir = trailingslashit( dirname( dirname( __DIR__ ) ) );
	}

	return $dir;
}

/**
 * Returns the plugin URL.
 *
 * @since 0.1.0
 *
 * @return string
 */
function mai_url() {
	static $url = null;

	if ( is_null( $url ) ) {
		$url = trailingslashit( plugins_url( basename( mai_dir() ) ) );
	}

	return $url;
}

/**
 * Returns an array of plugin data from the main plugin file.
 *
 * @since 0.1.0
 *
 * @param string $header Optionally return one key.
 *
 * @return array|string|null
 */
function mai_plugin_data( $header = '' ) {
	static $data = null;

	if ( is_null( $data ) ) {
		$data = get_file_data( mai_dir() . 'mai-engine.php', [
			'name'        => 'Plugin Name',
			'version'     => 'Version',
			'plugin-uri'  => 'Plugin URI',
			'text-domain' => 'Text Domain',
			'description' => 'Description',
			'author'      => 'Author',
			'author-uri'  => 'Author URI',
			'domain-path' => 'Domain Path',
			'network'     => 'Network',
		], 'plugin' );
	}

	if ( array_key_exists( $header, $data ) ) {
		return $data[ $header ];
	}

	return $data;
}

/**
 * Returns the plugin name.
 *
 * @since 0.1.0
 *
 * @return string
 */
function mai_name() {
	static $name = null;

	if ( is_null( $name ) ) {
		$name = mai_plugin_data( 'name' );
	}

	return $name;
}

/**
 * Returns the plugin handle/text domain.
 *
 * @since 0.1.0
 *
 * @return string
 */
function mai_handle() {
	static $handle = null;

	if ( is_null( $handle ) ) {
		$handle = mai_plugin_data( 'text-domain' );
	}

	return $handle;
}

/**
 * Returns the plugin version.
 *
 * @since 0.1.0
 *
 * @return string
 */
function mai_version() {
	static $version = null;

	if ( is_null( $version ) ) {
		$version = mai_plugin_data( 'version' );
	}

	return $version;
}

/**
 * Returns the active child theme's config.
 *
 * @since 0.1.0
 *
 * @param string $sub_config Name of config to get.
 *
 * @return array
 */
function mai_config( $sub_config = 'default' ) {
	$config  = [];
	$default = require mai_dir() . "config/default/config.php";
	$active  = mai_active_theme();
	$theme   = mai_dir() . "config/$active/config.php";

	if ( is_readable( $theme ) ) {
		$config = array_replace_recursive( $default, require $theme );
	}

	$data = $config[ $sub_config ];

	// Allow users to override from within actual child theme.
	$child = get_stylesheet_directory() . "/config/$sub_config.php";

	if ( is_readable( $child ) ) {
		$data = require $child;
	}

	return apply_filters( "mai_{$sub_config}_config", $data );
}

/**
 * Returns the active theme key.
 *
 * Checks multiple places to find a match.
 *
 * @since 0.1.0
 *
 * @return string
 */
function mai_active_theme() {
	static $theme = null;

	if ( is_null( $theme ) ) {

		if ( ! $theme ) {
			$theme = get_theme_support( 'mai' );
		}

		if ( ! $theme ) {
			$theme = genesis_get_theme_handle();
		}

		if ( ! $theme ) {
			$theme = wp_get_theme()->get( 'TextDomain' );
		}

		if ( ! $theme ) {
			$onboarding_file = get_stylesheet_directory() . '/config/onboarding.php';

			if ( is_readable( $onboarding_file ) ) {
				$onboarding_config = require $onboarding_file;

				if ( isset( $onboarding_config['dependencies']['mai'] ) ) {
					$theme = $onboarding_config['dependencies']['mai'];
				}
			}
		}

		if ( ! $theme || ! in_array( $theme, mai_child_themes(), true ) ) {
			$theme = 'default';
		}
	}

	return str_replace( 'mai-', '', $theme );
}

/**
 * Returns an array of all BizBudding child themes.
 *
 * @since 0.1.0
 *
 * @return array
 */
function mai_child_themes() {
	$child_themes = [];
	$files        = glob( mai_dir() . 'config/*', GLOB_ONLYDIR );

	foreach ( $files as $file ) {
		$child_themes[] = 'mai-' . basename( $file, '.php' );
	}

	return $child_themes;
}

/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @return array
 */
function mai_default_colors() {
	static $colors = null;

	if ( is_null( $colors ) ) {
		$theme  = mai_active_theme();
		$file   = mai_dir() . "config/$theme/config.json";
		$colors = is_readable( $file ) ? json_decode( file_get_contents( $file ), true ) : [];
	}

	return $colors;
}

/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @param string $color
 *
 * @return string
 */
function mai_default_color( $color = null ) {
	$colors = mai_default_colors();

	return isset( $colors[ 'color-' . $color ] ) ? $colors[ 'color-' . $color ] : '';
}

/**
 * Check if were on any type of singular page.
 *
 * @since 0.1.0
 *
 * @return bool
 */
function mai_is_type_single() {
	return is_front_page() || is_single() || is_page() || is_404() || is_attachment() || is_singular();
}

/**
 * Check if were on any type of archive page.
 *
 * @since 0.1.0
 *
 * @return bool
 */
function mai_is_type_archive() {
	return is_home() || is_post_type_archive() || is_category() || is_tag() || is_tax() || is_author() || is_date() || is_year() || is_month() || is_day() || is_time() || is_archive() || is_search();
}

/**
 * Checks if current page has the hero section enabled.
 *
 * @since 0.1.0
 *
 * @return bool
 */
function mai_has_hero_section() {
	return in_array( 'has-hero-section', get_body_class(), true );
}

/**
 * Checks if given sidebar contains a certain widget.
 *
 * @since  0.1.0
 *
 * @uses   $sidebars_widgets
 *
 * @param  string $sidebar Name of sidebar, e.g `primary`.
 * @param  string $widget  Widget ID to check, e.g `custom_html`.
 *
 * @return bool
 */
function mai_sidebar_has_widget( $sidebar, $widget ) {
	global $sidebars_widgets;

	if ( isset( $sidebars_widgets[ $sidebar ][0] ) && strpos( $sidebars_widgets[ $sidebar ][0], $widget ) !== false && is_active_sidebar( $sidebar ) ) {
		return true;
	}

	return false;
}

/**
 * Checks if the Hero Section is active.
 *
 * @since 0.1.0
 *
 * @return bool
 */
function mai_hero_section_active() {
	$active    = false;
	$post_type = get_post_type();

	if ( mai_is_type_archive() && post_type_supports( $post_type, 'hero-section-archive' ) ) {
		$active = true;
	}

	if ( mai_is_type_single() && post_type_supports( $post_type, 'hero-section-single' ) ) {
		$active = true;
	}

	if ( ! $post_type && class_exists( 'WooCommerce' ) && is_shop() && post_type_supports( 'product', 'hero-section-archive' ) ) {
		$active = true;
	}

	return $active;
}
