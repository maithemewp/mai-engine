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
 * @link  https://github.com/maithemewp/mai-engine/issues/361
 *
 * @return string
 */
add_filter( 'genesis_pre_load_favicon', 'mai_load_default_favicon' );
function mai_load_default_favicon( $favicon ) {
	if ( function_exists( 'mai_get_url' ) ) {
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
	delete_transient( 'mai_template_parts' );
}

/**
 * Writes variable data to a file.

 * This function for testing & debuggin only.
 * Do not leave this function working on your site.
 *
 * @since 2.11.0
 *
 * @param mixed  $value    The value to write to a file.
 * @param string $filename The filename to create/write.
 *
 * @return void
 */
function mai_write_to_file( $value, $filename = '__debug' ) {
	$file   = dirname( __FILE__ ) . sprintf( '/%s.txt', $filename );
	$handle = fopen( $file, 'a' );
	ob_start();
	if ( is_array( $value ) || is_object( $value ) ) {
		print_r( $value );
	} elseif ( is_bool( $value ) ) {
		var_dump( $value );
	} else {
		echo $value;
	}
	echo "\r\n\r\n";
	fwrite( $handle, ob_get_clean() );
	fclose( $handle );
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

		// Composer.
		'../vendor/autoload',

		// Functions.
		'functions/autoload',
		'functions/colors',
		'functions/columns',
		'functions/defaults',
		'functions/deprecated',
		'functions/enqueue',
		'functions/entries',
		'functions/fonts',
		'functions/grid',
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

		// Blocks.
		'blocks/button',
		'blocks/columns',
		'blocks/cover',
		'blocks/divider',
		'blocks/grid',
		'blocks/group',
		'blocks/heading',
		'blocks/icon',
		'blocks/paragraph',
		'blocks/search',
		'blocks/settings',
		'blocks/social-links',

		// Customizer.
		'customize/plugins',
		'customize/beta-tester',
		'customize/colors',
		'customize/content-archives',
		'customize/logo',
		'customize/loop',
		'customize/menus',
		'customize/output',
		'customize/page-header',
		'customize/performance',
		'customize/setup',
		'customize/single-content',
		'customize/site-header',
		'customize/site-layouts',
		'customize/typography',
		'customize/updates',
	];

	if ( is_admin() ) {
		$files = array_merge(
			$files,
			[
				'admin/dependencies',
				'admin/editor',
				'admin/hide-elements',
				'admin/images',
				'admin/notices',
				'admin/page-header',
				'admin/settings',
				'admin/setup-wizard',
				'admin/term-image',
				'admin/template-parts',
				'admin/update-checker',
				'admin/upgrade',
			]
		);
	}

	if ( ! class_exists( 'acf_pro' ) ) {
		$files[] = '../vendor/advanced-custom-fields/advanced-custom-fields-pro/acf';
	}

	if ( ! class_exists( 'Kirki' ) ) {
		$files[] = '../vendor/aristath/kirki/kirki';
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
