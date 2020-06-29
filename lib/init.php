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
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @return bool|string
 */
function mai_get_engine_theme() {
	static $theme = null;

	if ( is_null( $theme ) ) {
		$current_theme = defined( 'CHILD_THEME_NAME' ) ? CHILD_THEME_NAME : null;

		if ( ! $current_theme ) {
			$current_theme = wp_get_theme()->get( 'Name' );
		}

		if ( ! $current_theme ) {
			$current_theme = wp_get_theme()->get( 'TextDomain' );
		}

		if ( ! $current_theme ) {
			$current_theme = basename( get_stylesheet_directory() );
		}

		$configs       = glob( dirname( __DIR__ ) . '/config/*.php' );
		$current_theme = str_replace( 'mai-', '', sanitize_title_with_dashes( $current_theme ) );
		$engine_themes = [];

		foreach ( $configs as $index => $config ) {
			$base = basename( $config, '.php' );

			if ( in_array( $base, [ '_default', '_settings' ] ) ) {
				continue;
			}

			$engine_themes[] = $base;
		}

		if ( in_array( $current_theme, $engine_themes, true ) ) {
			$theme = $current_theme;

		} elseif ( current_theme_supports( 'mai-engine' ) ) {
			$theme_support = get_theme_support( 'mai-engine' )[0];

			if ( in_array( $theme_support, $engine_themes, true ) ) {
				$theme = $theme_support;
			} else {
				$theme = 'default';
			}
		}
	}

	return $theme;
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
	add_theme_support( 'genesis-breadcrumbs' );
}

add_action( 'genesis_setup', 'mai_remove_genesis_default_widget_areas', 8 );
/**
 * Remove Genesis default widget areas.
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

	if ( function_exists( 'genesis' ) && mai_get_engine_theme() ) {
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
		'functions/defaults',
		'functions/deprecated',
		'functions/enqueue',
		'functions/entries',
		'functions/fonts',
		'functions/grid',
		'functions/helpers',
		'functions/images',
		'functions/layout',
		'functions/loop',
		'functions/markup',
		'functions/performance',
		'functions/plugins',
		'functions/setup',
		'functions/shortcodes',
		'functions/templates',
		'functions/utilities',
		'functions/widgets',

		// Structure.
		'structure/amp',
		'structure/archive',
		'structure/breadcrumbs',
		'structure/comments',
		'structure/footer',
		'structure/header',
		'structure/login',
		'structure/loop',
		'structure/menus',
		'structure/page-header',
		'structure/pagination',
		'structure/search-form',
		'structure/sidebar',
		'structure/single',
		'structure/template-parts',
		'structure/widget-areas',
		'structure/wrap',

		// Blocks.
		'blocks/button',
		'blocks/cover',
		'blocks/divider',
		'blocks/grid',
		'blocks/heading',
		'blocks/icon',
		'blocks/image',
		'blocks/social-links',

		// Customizer.
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
		'customize/upsell',
	];

	if ( is_admin() ) {
		$files = array_merge(
			$files,
			[
				'admin/acf',
				'admin/blog',
				'admin/child-theme-updater',
				'admin/dependencies',
				'admin/editor',
				'admin/hide-elements',
				'admin/images',
				'admin/page-header',
				'admin/settings',
				'admin/setup-wizard',
				'admin/term-image',
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
		$filename = __DIR__ . "/$file.php";

		if ( is_readable( $filename ) ) {
			require_once $filename;
		}
	}
}
