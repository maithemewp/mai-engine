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
	if ( ! function_exists( 'genesis' ) ) {
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
		'blocks/icon',
		'blocks/cover',
		'blocks/divider',
		'blocks/grid',

		// Shortcodes.
		'shortcodes/icon',

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
