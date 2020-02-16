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
function mai_get_dir() {
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
function mai_get_url() {
	static $url = null;

	if ( is_null( $url ) ) {
		$url = trailingslashit( plugins_url( basename( mai_get_dir() ) ) );
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
function mai_get_plugin_data( $header = '' ) {
	static $data = null;

	if ( is_null( $data ) ) {
		$data = get_file_data( mai_get_dir() . 'mai-engine.php', [
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
function mai_get_name() {
	static $name = null;

	if ( is_null( $name ) ) {
		$name = mai_get_plugin_data( 'name' );
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
function mai_get_handle() {
	static $handle = null;

	if ( is_null( $handle ) ) {
		$handle = mai_get_plugin_data( 'text-domain' );
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
function mai_get_version() {
	static $version = null;

	if ( is_null( $version ) ) {
		$version = mai_get_plugin_data( 'version' );
	}

	return $version;
}

/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @return string
 */
function mai_get_asset_version( $file ) {
	$file = str_replace( mai_get_url(), mai_get_dir(), $file );

	return mai_is_in_dev_mode() && mai_has_string( mai_get_dir(), $file ) ? filemtime( $file ) : mai_get_version();
}

/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @return string
 */
function mai_get_asset_path( $file ) {
	$type     = mai_has_string( '.css', $file ) ? 'css' : 'js';
	$url      = mai_get_url();
	$filename = explode( '.', $file )[0];
	$default  = "${url}assets/$type/$file";
	$minified = "${url}assets/$type/$filename.min.$type";

	return mai_is_in_dev_mode() || 'css' === $type ? $default : $minified;
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
function mai_get_config( $sub_config = 'default' ) {
	$config = require mai_get_dir() . "config/default.php";
	$theme  = mai_get_dir() . 'config/' . mai_get_active_theme() . '.php';

	if ( is_readable( $theme ) ) {
		$config = array_replace_recursive( $config, require $theme );
	}

	$data = isset( $config[ $sub_config ] ) ? $config[ $sub_config ] : [];

	// Allow users to override from within actual child theme.
	$child = get_stylesheet_directory() . "/config.php";

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
function mai_get_active_theme() {
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

		if ( ! $theme || ! in_array( $theme, mai_get_child_themes(), true ) ) {
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
function mai_get_child_themes() {
	$child_themes = [];
	$files        = glob( mai_get_dir() . 'config/*.php' );

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
 * @return object WP_Filesystem_Base
 */
function mai_get_filesystem() {
	static $wp_filesystem = null;

	if ( is_null( $wp_filesystem ) ) {
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		WP_Filesystem();
		global $wp_filesystem;
	}

	return $wp_filesystem;
}

/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @param string $css       CSS to read.
 * @param string $selectors Selectors.
 *
 * @return array
 */
function mai_get_css_rules( $css, $selectors = '(?ims)([a-z0-9\s\.\:#_\-@,]+)' ) {
	preg_match_all( '/(' . $selectors . ')\{([^\}]*)\}/', $css, $matches );

	$result = [];
	foreach ( $matches[0] as $i => $x ) {
		$selector  = trim( $matches[1][ $i ] );
		$rules     = explode( ';', trim( $matches[2][ $i ] ) );
		$rules_arr = [];
		foreach ( $rules as $strRule ) {
			if ( ! empty( $strRule ) ) {
				$rule                          = explode( ":", $strRule );
				$rules_arr[ trim( $rule[0] ) ] = trim( $rule[1] );
			}
		}

		$selectors = explode( ',', trim( $selector ) );
		foreach ( $selectors as $strSel ) {
			$result[ $strSel ] = $rules_arr;
		}
	}

	return $result;
}

/**
 * Returns an array of the theme variables.
 *
 * @since 0.1.0
 *
 * @return array
 */
function mai_get_css_variables() {
	static $variables = null;

	if ( is_null( $variables ) ) {
		$wp_filesystem = mai_get_filesystem();
		$default       = $wp_filesystem->get_contents( mai_get_dir() . '/assets/css/critical.css' );
		$theme         = $wp_filesystem->get_contents( mai_get_dir() . '/assets/css/themes/' . mai_get_active_theme() . '.css' );
		$variables     = array_replace_recursive( mai_get_css_rules( $default, ':root' ), mai_get_css_rules( $theme, ':root' ) );
	}

	return $variables[':root'];
}

/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @return array
 */
function mai_get_colors() {
	static $colors = [];

	if ( empty( $colors ) ) {
		$vars = mai_get_css_variables();

		if ( is_array( $vars ) ) {
			foreach ( $vars as $name => $value ) {
				if ( mai_has_string( '--color-', $name ) && ! mai_has_string( 'social', $name ) ) {
					$name            = str_replace( '--color-', '', $name );
					$colors[ $name ] = $value;
				}
			}
		}
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
function mai_get_color( $color = null ) {
	$colors = mai_get_colors();

	return isset( $colors[ $color ] ) ? $colors[ $color ] : '';
}

/**
 * Returns the color palette variables.
 *
 * @since 0.1.0
 *
 * @return array
 */
function mai_get_color_palette() {
	$colors  = mai_get_colors();
	$palette = [];

	foreach ( $colors as $color => $hex ) {
		$palette[] = [
			'name'  => ucwords( $color ),
			'slug'  => $color,
			'color' => $hex,
		];
	}

	return $palette;
}

/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @return array
 */
function mai_get_breakpoints() {
	static $breakpoints = [];

	if ( empty( $breakpoints ) ) {
		$vars = [];

		foreach ( $vars as $var => $hex ) {
			if ( substr( $var, 0, 6 ) === 'screen-' ) {
				continue;
			}

			$name                 = str_replace( 'screen-', '', $var );
			$breakpoints[ $name ] = $hex;
		}
	}

	return $breakpoints;
}

/**
 * Returns the default breakpoint for the theme.
 *
 * @param string $size
 *
 * @return mixed
 */
function mai_get_breakpoint( $size = 'm' ) {
	$breakpoints = mai_get_breakpoints();

	return intval( $breakpoints[ $size ] );
}

