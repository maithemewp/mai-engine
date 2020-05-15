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
 * @since 1.0.0
 *
 * @return bool|string
 */
function mai_engine_support() {
	static $theme = null;

	if ( is_null( $theme ) ) {
		$theme = get_theme_support( 'mai-engine' )[0];

		if ( ! $theme ) {
			$theme = defined( 'CHILD_THEME_NAME' ) ? CHILD_THEME_NAME : null;
		}

		if ( ! $theme ) {
			$theme = wp_get_theme()->get( 'Name' );
		}

		if ( ! $theme ) {
			$theme = wp_get_theme()->get( 'TextDomain' );
		}

		if ( ! $theme ) {
			$theme = basename( get_stylesheet_directory() );
		}

		$configs = glob( dirname( __DIR__ ) . '/config/*', GLOB_ONLYDIR );
		$themes  = [];
		$theme   = str_replace( 'mai-', '', sanitize_title_with_dashes( $theme ) );

		foreach ( $configs as $config ) {
			$themes[] = basename( $config, '.php' );
		}

		if ( ! in_array( $theme, $themes, true ) ) {
			$theme = 'default';
		}
	}

	return $theme;
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_deactivate_plugin() {
	deactivate_plugins( basename( dirname( __DIR__ ) ) . '/mai-engine.php' );
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
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

add_action( 'genesis_init', 'mai_remove_genesis_theme_supports', 5 );
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
function mai_remove_genesis_theme_supports() {
	remove_action( 'genesis_init', 'genesis_theme_support' );
	add_theme_support( 'genesis-breadcrumbs' );
	add_filter( 'genesis_initial_layouts', '__return_empty_array' );
}

add_action( 'after_setup_theme', 'mai_load_files', 0 );
/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_load_files() {
	$deactivate = true;

	if ( current_theme_supports( 'mai-engine' ) ) {
		$deactivate = false;
	}

	if ( mai_engine_support() && 'default' !== mai_engine_support() ) {
		$deactivate = false;
	}

	if ( ! function_exists( 'genesis' ) ) {
		$deactivate = true;
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
		'functions/helpers',
		'functions/utilities',
		'functions/autoload',
		'functions/layout',
		'functions/images',
		'functions/loop',
		'functions/setup',
		'functions/enqueue',
		'functions/markup',
		'functions/entries',
		'functions/grid',
		'functions/widgets',
		'functions/defaults',
		'functions/plugins',
		'functions/shortcodes',

		// Structure.
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
		'structure/widget-areas',
		'structure/wrap',

		// Blocks.
		'blocks/button',
		'blocks/cover',
		'blocks/divider',
		'blocks/grid',
		'blocks/heading',
		'blocks/icon',

		// Widgets.
		'widgets/reusable-block',

		// Customizer.
		'customize/setup',
		'customize/logo',
		'customize/beta-tester',
		'customize/upsell',
		'customize/loop',
		'customize/page-header',
	];

	if ( is_admin() ) {
		$files = array_merge(
			$files,
			[
				'admin/blog',
				'admin/images',
				'admin/settings',
				'admin/term-image',
				'admin/page-header',
				'admin/hide-elements',
				'admin/acf',
				'admin/update-checker',
				'admin/child-theme-updater',
				'admin/setup-wizard',
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
