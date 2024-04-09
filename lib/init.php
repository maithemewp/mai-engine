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
defined( 'ABSPATH' ) || die();

// Must be at the top of the file.
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

/**
 * Deactivate Mai Engine plugin.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_deactivate_plugin() {
	deactivate_plugins( basename( dirname( __DIR__ ) ) . '/mai-engine.php' );
}

/**
 * Show notice that Mai Engine has been deactivated.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_deactivate_plugin_notice() {
	printf(
		'<div class="notice notice-error is-dismissible"><p>%s%s%s%s%s</p></div>',
		__( 'Your theme does not support the ', 'mai-engine' ),
		'Mai Engine',
		__( ' plugin. As a result, ', 'mai-engine' ),
		'Mai Engine',
		__( ' has been deactivated.', 'mai-engine' )
	);

	if ( isset( $_GET['activate'] ) ) {
		unset( $_GET['activate'] );
	}
}

add_action( 'setup_theme', 'mai_load_genesis', 100 );
/**
 * Starts the engine.
 *
 * Enables the use of `genesis_*` functions in the child theme functions.php file,
 * without the need for require_once get_template_directory() . '/lib/init.php'.
 * This allows us to provide a truly blank child theme for users to work with.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_load_genesis() {
	$init = get_template_directory() . '/lib/init.php';

	if ( is_readable( $init ) ) {
		require_once $init;
	}
}

/**
 * Removes deprecated stuff from Genesis.
 *
 * @since 2.22.0
 *
 * @return bool
 */
add_filter( 'genesis_load_deprecated', '__return_false' );

add_action( 'genesis_init', 'mai_modify_genesis_defaults', 5 );
/**
 * Removes all Genesis functions that use the is_child_theme() function.
 *
 * Since we are loading Genesis on behalf of the child theme, functions won't
 * work correctly. This workaround will fix the issue by removing functions
 * that contain the check and adds theme support that is required early.
 *
 * Also adds breadcrumb support back before genesis init.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_modify_genesis_defaults() {
	remove_action( 'genesis_init', 'genesis_theme_support' );
	add_filter( 'genesis_initial_layouts', '__return_empty_array' );
	add_theme_support( 'genesis-breadcrumbs' ); // TODO: This is in config already right?
}

add_action( 'genesis_setup', 'mai_remove_genesis_default_widget_areas', 8 );
/**
 * Removes Genesis default widget areas.
 *
 * We'll re-register them via our config.
 *
 * @since 0.3.7
 *
 * @return void
 */
function mai_remove_genesis_default_widget_areas() {
	remove_action( 'genesis_setup', 'genesis_register_default_widget_areas' );
}

add_filter( 'genesis_theme_settings_menu_ops', 'mai_genesis_theme_settings_menu_ops' );
/**
 * Makes room for our Mai Theme admin menu.
 * These only worked when the value was a string.
 *
 * @since 2.10.0
 *
 * @param array $options The existing options from Genesis.
 *
 * @return array
 */
function mai_genesis_theme_settings_menu_ops( $options ) {
	if ( isset( $options['main_menu']['sep']['sep_position'] ) ) {
		$options['main_menu']['sep']['sep_position'] = '58.994';
	}

	if ( isset( $options['main_menu']['position'] ) ) {
		$options['main_menu']['position'] = '58.996';
	}

	return $options;
}

/**
 * Load default favicon.
 *
 * @since 2.4.3
 * @since 2.6.0 Changed function name to avoid clash when switching from v1 to v2.
 * @since 2.6.0 Check mai_get_url() function exists. We saw this run too early and fail.
 * @since 2.18.0 Added `get_site_icon_url` filter because it was throwing errors in admin for missing favicon.
 * @link  https://github.com/maithemewp/mai-engine/issues/361
 *
 * @return string
 */
add_filter( 'get_site_icon_url', 'mai_load_default_favicon' );
add_filter( 'genesis_pre_load_favicon', 'mai_load_default_favicon' );
function mai_load_default_favicon( $favicon ) {
	if ( ! $favicon && function_exists( 'mai_get_url' ) ) {
		return mai_get_url() . 'assets/img/icon-256x256.png';
	}

	return $favicon;
}

/**
 * Clears the transient on post type save/update.
 * This was running too late in mai_load_files().
 *
 * @since 2.11.0
 *
 * @param int     $post_id The template part ID.
 * @param WP_Post $post    The template part post object.
 * @param bool    $update  Whether this is an existing post being updated.
 *
 * @return void
 */
add_action( 'save_post_mai_template_part', 'mai_save_template_part_delete_transient', 20, 3 );
function mai_save_template_part_delete_transient( $post_id, $post, $update ) {
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	delete_transient( 'mai_template_parts' );
}

add_action( 'plugins_loaded', 'mai_plugin_update_checker' );
/**
 * Initialize plugin update checker.
 *
 * @since 0.1.0
 * @since 2.24.0 Moved to plugins_loaded hook. See #607.
 *
 * @return void
 */
function mai_plugin_update_checker() {
	// Bail if plugin updater is not loaded.
	if ( ! class_exists( 'YahnisElsts\PluginUpdateChecker\v5\PucFactory' ) ) {
		return;
	}

	// Setup udpater.
	$updater = PucFactory::buildUpdateChecker( 'https://github.com/maithemewp/mai-engine', realpath( __DIR__ . '/..' ) . '/mai-engine.php', 'mai-engine' );

	// Get the branch. If checking for beta releases.
	$options = get_option( 'genesis-settings' ); // Too early to use `genesis_get_option()` and GENESIS_SETTINGS_FIELD.
	$branch  = isset( $options['mai_tester'] ) && $options['mai_tester'] ? 'beta' : 'master';

	// Allow branch and updater object manipulation.
	$branch = apply_filters( 'mai_updater_branch', $branch );

	// Set the branch.
	$updater->setBranch( $branch );

	// Maybe set github api token.
	if ( defined( 'MAI_GITHUB_API_TOKEN' ) ) {
		$updater->setAuthentication( MAI_GITHUB_API_TOKEN );
	}

	// Add icons for Dashboard > Updates screen.
	$updater->addResultFilter(
		function ( $info ) {
			$info->icons = [
				'1x' => mai_get_url() . 'assets/img/icon-128x128.png',
				'2x' => mai_get_url() . 'assets/img/icon-256x256.png',
			];

			return $info;
		}
	);
}

add_action( 'plugins_loaded', 'mai_load_vendor_plugins' );
/**
 * Load mai-engine included plugin files.
 * This needs to run earlier than the other files.
 *
 * @access private
 *
 * @since 2.18.0
 *
 * @return void
 */
function mai_load_vendor_plugins() {
	$files = [];

	if ( mai_needs_mai_acf_pro() ) {
		$files[] = '../vendor/wpengine/advanced-custom-fields-pro/acf';
	}

	if ( ! class_exists( 'Kirki' ) ) {
		$files[] = '../vendor/kirki-framework/kirki/kirki';
	}

	if ( ! $files ) {
		return;
	}

	foreach ( $files as $file ) {
		require_once __DIR__ . "/$file.php";
	}
}

add_action( 'after_setup_theme', 'mai_load_files', 0 );
/**
 * Load mai-engine files, or deactivate if active theme is not supported.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_load_files() {
	$deactivate = true;

	if ( function_exists( 'genesis' ) && current_theme_supports( 'mai-engine' ) ) {
		$deactivate = false;
	}

	if ( $deactivate && current_user_can( 'activate_plugins' ) ) {
		add_action( 'admin_init', 'mai_deactivate_plugin' );
		add_action( 'admin_notices', 'mai_deactivate_plugin_notice' );
	}

	if ( $deactivate ) {
		return;
	}

	$files = [
		// Functions.
		'functions/autoload',
		'functions/colors',
		'functions/columns',
		'functions/customizer',
		'functions/defaults',
		'functions/deprecated',
		'functions/enqueue',
		'functions/entries',
		'functions/fonts',
		'functions/helpers',
		'functions/icons',
		'functions/images',
		'functions/layout',
		'functions/loop',
		'functions/performance',
		'functions/plugins',
		'functions/setup',
		'functions/shortcodes',
		'functions/templates',
		'functions/utilities',
		'functions/widgets',

		// Structure.
		'structure/404-page',
		'structure/amp',
		'structure/archive',
		'structure/breadcrumbs',
		'structure/comments',
		'structure/footer',
		'structure/header',
		'structure/layout',
		'structure/login',
		'structure/loop',
		'structure/menus',
		'structure/page-header',
		'structure/pagination',
		'structure/post',
		'structure/search-form',
		'structure/sidebar',
		'structure/single',
		'structure/template-parts',
		'structure/widget-areas',
		'structure/wrap',

		// Fields.
		'fields/clone', // Must be first.
		'fields/functions', // Load functions so they are available.
		'fields/columns',
		'fields/grid-display',
		'fields/grid-layout',
		'fields/grid-tabs',
		'fields/icons',
		'fields/wp-query',
		'fields/wp-term-query',

		// Blocks.
		'blocks/general',
		'blocks/button',
		'blocks/cover',
		'blocks/group',
		'blocks/heading',
		'blocks/in-the-loop',
		'blocks/paragraph',
		'blocks/search',
		'blocks/settings',
		'blocks/site-logo',
		'blocks/social-links',
		'blocks/mai-columns/block',
		'blocks/mai-column/block',
		'blocks/mai-divider/block',
		'blocks/mai-icon/block',
		'blocks/mai-grid/functions',
		'blocks/mai-grid/blocks',
		'blocks/mai-grid/post-block',
		'blocks/mai-grid/term-block',
	];

	// Customizer.
	if ( class_exists( 'Kirki' ) ) {
		$files = array_merge(
			$files,
			[
				'customize/setup', // Setup first.
				'customize/beta-tester',
				'customize/colors',
				'customize/content-archives',
				'customize/logo',
				'customize/loop',
				'customize/menus',
				'customize/page-header',
				'customize/performance',
				'customize/single-content',
				'customize/site-header',
				'customize/site-layouts',
				'customize/typography',
				'customize/updates',
				'customize/upsell',
				'customize/output', // Output last.
			]
		);
	}

	if ( is_admin() ) {
		$files = array_merge(
			$files,
			[
				'admin/admin-menu',
				'admin/dependencies',
				'admin/editor',
				'admin/hide-elements',
				'admin/images',
				'admin/notices',
				'admin/page-header',
				'admin/setup-wizard',
				'admin/term-image',
				'admin/template-parts',
				'admin/upgrade',
			]
		);
	}

	if ( class_exists( 'bbPress' ) ) {
		$files[] = 'support/bbpress';
	}

	if ( class_exists( 'Easy_Digital_Downloads' ) ) {
		$files[] = 'support/easy-digital-downloads';
	}

	if ( class_exists( 'FacetWP' ) ) {
		$files[] = 'support/facetwp';
	}

	if ( class_exists( 'SFWD_LMS' ) ) {
		$files[] = 'support/learndash';
	}

	if ( class_exists( 'Polylang' ) ) {
		$files[] = 'support/polylang';
	}

	if ( class_exists( 'RankMath' ) ) {
		$files[] = 'support/rankmath';
	}

	if ( function_exists( 'ss_get_podcast' ) ) {
		$files[] = 'support/seriously-simple-podcasting';
	}

	if ( class_exists( 'SitePress' ) ) {
		$files[] = 'support/wpml';
	}

	if ( class_exists( 'WooCommerce' ) ) {
		$files[] = 'support/woocommerce';
	}

	if ( class_exists( 'WPForms' ) || function_exists( 'wpforms' ) ) {
		$files[] = 'support/wpforms';
	}

	if ( class_exists( 'WP_Recipe_Maker' ) && (bool) apply_filters( 'mai_enable_wprm_support', false ) ) {
		$files[] = 'support/wp-recipe-maker';
	}

	foreach ( $files as $file ) {
		require_once __DIR__ . "/$file.php";
	}

	// Loads CLI command.
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		WP_CLI::add_command( 'mai generate', 'Mai_Cli_Generate_Command' );
		WP_CLI::add_command( 'mai flush', function() {
			$message = mai_typography_flush_local_fonts();
			WP_CLI::success( $message );
		});
	}
}

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
		$dir = trailingslashit( dirname( __DIR__ ) );
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

	if ( ! is_null( $url ) ) {
		return $url;
	}

	$url = trailingslashit( plugins_url( basename( mai_get_dir() ) ) );

	return $url;
}

/**
 * Gets the plugin basename.
 *
 * @since 0.1.0
 *
 * @return string
 */
function mai_get_base() {
	static $base = null;

	if ( ! is_null( $base ) ) {
		return $base;
	}

	$dir  = basename( dirname( dirname( __DIR__ ) ) );
	$file = mai_get_handle() . '.php';
	$base = $dir . DIRECTORY_SEPARATOR . $file;

	return $base;
}

/**
 * Returns an array of plugin data from the main plugin file.
 *
 * @since 0.1.0
 *
 * @param string $key Optionally return one key.
 *
 * @return array|string|null
 */
function mai_get_plugin_data( $key = '' ) {
	static $data = null;

	if ( is_null( $data ) ) {
		$data = get_file_data(
			mai_get_dir() . 'mai-engine.php',
			[
				'name'        => 'Plugin Name',
				'version'     => 'Version',
				'plugin-uri'  => 'Plugin URI',
				'text-domain' => 'Text Domain',
				'description' => 'Description',
				'author'      => 'Author',
				'author-uri'  => 'Author URI',
				'domain-path' => 'Domain Path',
				'network'     => 'Network',
			],
			'plugin'
		);
	}

	if ( array_key_exists( $key, $data ) ) {
		return $data[ $key ];
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
 * Get the active engine theme. Defaults to the default theme.
 *
 * @since 0.1.0
 *
 * @return bool|string
 */
function mai_get_engine_theme() {
	static $theme = null;

	if ( ! is_null( $theme ) ) {
		return $theme;
	}

	$engine_themes = mai_get_engine_themes();

	if ( current_theme_supports( 'mai-engine' ) ) {
		// Custom themes can load a specific theme default via `add_theme_support( 'mai-engine', 'success' );`.
		$theme_support = get_theme_support( 'mai-engine' );

		if ( $theme_support && is_array( $theme_support ) && in_array( $theme_support[0], $engine_themes, true ) ) {
			$theme = $theme_support[0];
		}
	}

	if ( ! $theme ) {
		$current_theme = defined( 'CHILD_THEME_NAME' ) ? CHILD_THEME_NAME : null;

		if ( $current_theme ) {
			$current_theme = str_replace( 'mai-', '', sanitize_title_with_dashes( $current_theme ) );

			if ( in_array( $current_theme, $engine_themes, true ) ) {
				$theme = $current_theme;
			}
		}
	}

	if ( ! $theme ) {
		$current_theme = wp_get_theme()->get( 'Name' );

		if ( $current_theme ) {
			$current_theme = str_replace( 'mai-', '', sanitize_title_with_dashes( $current_theme ) );

			if ( in_array( $current_theme, $engine_themes, true ) ) {
				$theme = $current_theme;
			}
		}
	}

	if ( ! $theme ) {
		$current_theme = wp_get_theme()->get( 'TextDomain' );

		if ( $current_theme ) {
			$current_theme = str_replace( 'mai-', '', sanitize_title_with_dashes( $current_theme ) );

			if ( in_array( $current_theme, $engine_themes, true ) ) {
				$theme = $current_theme;
			}
		}
	}

	if ( ! $theme ) {
		$current_theme = basename( get_stylesheet_directory() );

		if ( $current_theme ) {
			$current_theme = str_replace( 'mai-', '', sanitize_title_with_dashes( $current_theme ) );

			if ( in_array( $current_theme, $engine_themes, true ) ) {
				$theme = $current_theme;
			}
		}
	}

	$theme = $theme ?: 'default';

	return $theme;
}

/**
 * Get available engine themes.
 *
 * @since 2.0.0
 *
 * @return array
 */
function mai_get_engine_themes() {
	static $themes = null;

	if ( ! is_null( $themes ) ) {
		return $themes;
	}

	$configs = glob( dirname( __DIR__ ) . '/config/*.php' );
	$themes  = [];

	foreach ( $configs as $index => $config ) {
		$base = basename( $config, '.php' );

		if ( in_array( $base, [ '_default', '_settings' ], true ) ) {
			continue;
		}

		$themes[] = $base;
	}

	return $themes;
}


/**
 * Returns asset version with filetime.
 *
 * @since 0.1.0
 *
 * @param string $file File path.
 *
 * @return string
 */
function mai_get_asset_version( $file ) {
	$file    = str_replace( content_url(), WP_CONTENT_DIR, $file );
	$version = mai_get_version();

	if ( file_exists( $file ) ) {
		$version .= '.' . date( 'njYHi', filemtime( $file ) );
	}

	return $version;
}

/**
 * Returns minified version of asset if in dev mode.
 *
 * @since 0.1.0
 * @since 2.4.0 Removed min dir if CSS file. Always return minified CSS.
 *
 * @param string $file File base name (relative to type directory).
 *
 * @return string
 */
function mai_get_asset_url( $file ) {
	$type    = false !== strpos( $file, '.js' ) ? 'js' : 'css';
	$name    = str_replace( [ '.js', '.css' ], '', $file );
	$uri     = mai_get_url();
	$default = "{$uri}assets/{$type}/{$name}.{$type}";
	$dir     = 'js' === $type ? '/min/' : '/';
	$min     = "{$uri}assets/{$type}{$dir}{$name}.min.{$type}";

	return mai_is_in_dev_mode() && 'js' === $type ? $default : $min;
}

/**
 * Returns the active child theme's sub config.
 *
 * @since 0.1.0
 * @since 2.11.0 Add static caching.
 *
 * @param string $sub_config Name of config to get.
 *
 * @return array
 */
function mai_get_config( $sub_config ) {
	static $configs = null;

	if ( is_array( $configs ) && isset( $configs[ $sub_config ] ) ) {
		return $configs[ $sub_config ];
	}

	if ( ! is_array( $configs ) ) {
		$configs = [];
	}

	$config                 = mai_get_full_config();
	$value                  = isset( $config[ $sub_config ] ) ? $config[ $sub_config ] : [];
	$configs[ $sub_config ] = apply_filters( "mai_{$sub_config}_config", $value );

	return $configs[ $sub_config ];
}

/**
 * Returns the active child theme's full config.
 *
 * @access private
 *
 * @since 2.11.0
 *
 * @return array
 */
function mai_get_full_config() {
	static $config = null;

	if ( ! is_null( $config ) ) {
		return $config;
	}

	$config = require mai_get_dir() . 'config/_default.php';
	$theme  = mai_get_active_theme();
	$theme  = ( 'default' === $theme ) ? '_default' : $theme;
	$path   = mai_get_dir() . 'config/' . $theme . '.php';

	if ( is_readable( $path ) ) {
		$new    = require $path;
		$config = array_replace_recursive( $config, $new );
		if ( isset( $new['settings']['content-archives'] ) ) {
			foreach ( $new['settings']['content-archives'] as $key => $settings ) {
				if ( ! ( isset( $new['settings']['content-archives'][ $key ]['show'] ) && isset( $config['settings']['content-archives'][ $key ]['show'] ) ) ) {
					continue;
				}
				$config['settings']['content-archives'][ $key ]['show'] = $new['settings']['content-archives'][ $key ]['show'];
			}
		}
		if ( isset( $new['settings']['single-content'] ) ) {
			foreach ( $new['settings']['single-content'] as $key => $settings ) {
				if ( ! ( isset( $new['settings']['single-content'][ $key ]['show'] ) && isset( $config['settings']['single-content'][ $key ]['show'] ) ) ) {
					continue;
				}
				$config['settings']['single-content'][ $key ]['show'] = $new['settings']['single-content'][ $key ]['show'];
			}
		}
	}

	// Allow users to override from within actual child theme.
	$child = get_stylesheet_directory() . '/config.php';

	if ( is_readable( $child ) ) {
		$new    = require $child;
		$config = array_replace_recursive( $config, $new );
		if ( isset( $new['settings']['content-archives'] ) ) {
			foreach ( $new['settings']['content-archives'] as $key => $settings ) {
				if ( ! ( isset( $new['settings']['content-archives'][ $key ]['show'] ) && isset( $config['settings']['content-archives'][ $key ]['show'] ) ) ) {
					continue;
				}
				$config['settings']['content-archives'][ $key ]['show'] = $new['settings']['content-archives'][ $key ]['show'];
			}
		}
		if ( isset( $new['settings']['single-content'] ) ) {
			foreach ( $new['settings']['single-content'] as $key => $settings ) {
				if ( ! ( isset( $new['settings']['single-content'][ $key ]['show'] ) && isset( $config['settings']['single-content'][ $key ]['show'] ) ) ) {
					continue;
				}
				$config['settings']['single-content'][ $key ]['show'] = $new['settings']['single-content'][ $key ]['show'];
			}
		}
	}

	$config = apply_filters( 'mai_config', $config );

	return $config;
}

/**
 * Gets the plugin updater icons.
 * This may be used in additiona Mai Plugins.
 *
 * @since 2.11.0
 *
 * @return array
 */
function mai_get_updater_icons() {
	$icons    = [];
	$standard = mai_get_logo_icon_1x();
	$retina   = mai_get_logo_icon_2x();
	if ( $standard && $retina ) {
		$icons = [
			'1x' => $standard,
			'2x' => $retina,
		];
	}
	return $icons;
}

/**
 * Gets the Mai Theme logo icon @1x for plugin updater.
 *
 * @since 2.11.0
 *
 * @return string
 */
function mai_get_logo_icon_1x() {
	static $icon = null;
	if ( ! is_null( $icon ) ) {
		return $icon;
	}
	$file = 'assets/img/icon-128x128.png';
	$icon = file_exists( mai_get_dir() . $file ) ? mai_get_url() . $file : '';
	return $icon;
}

/**
 * Gets the Mai Theme logo icon @1x for plugin updater.
 *
 * @since 2.11.0
 *
 * @return string
 */
function mai_get_logo_icon_2x() {
	static $icon = null;
	if ( ! is_null( $icon ) ) {
		return $icon;
	}
	$file = 'assets/img/icon-256x256.png';
	$icon = file_exists( mai_get_dir() . $file ) ? mai_get_url() . $file : '';
	return $icon;
}

/**
 * Checks if Mai needs to load ACF Pro.
 *
 * @access private
 *
 * @since 2.25.0
 *
 * @return bool
 */
function mai_needs_mai_acf_pro() {
	static $needs = null;

	if ( ! is_null( $needs ) ) {
		return $needs;
	}

	$needs = false;

	// No ACF Pro.
	if ( ! class_exists( 'acf_pro' ) ) {
		$needs = true;
	}
	// Has ACF Pro.
	else {
		$version = acf_get_setting( 'version' );
		$data    = mai_get_mai_acf_plugin_data();

		if ( ! $version || version_compare( $version, $data['Version'], '<' ) ) {
			$needs = true;
		}
	}

	return $needs;
}

/**
 * Gets ACF plugin data from the version loaded in Mai.
 *
 * @access private
 *
 * @since 2.25.0
 *
 * @return bool
 */
function mai_get_mai_acf_plugin_data() {
	static $data = null;

	if ( ! is_null( $data ) ) {
		return $data;
	}

	$data = get_plugin_data( trailingslashit( dirname( __DIR__ ) ) . 'vendor/wpengine/advanced-custom-fields-pro/acf.php' );

	return $data;
}
