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

add_action( 'genesis_setup', 'mai_load_files', 90 );
/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_load_files() {
	$files = [

		// Composer.
		'../vendor/autoload.php',
		'../vendor/advanced-custom-fields/advanced-custom-fields-pro/acf',
		'../vendor/wpackagist-plugin/advanced-custom-fields-font-awesome/acf-font-awesome',
		'../vendor/aristath/kirki/kirki',

		// Functions.
		'functions/helpers',
		'functions/getters',
		'functions/autoload',
		'functions/layout',
		'functions/images',
		'functions/setup',
		'functions/enqueue',
		'functions/markup',
		'functions/entries',
		'functions/widgets',
		'functions/defaults',
		'functions/onboarding',
		'functions/customizer',
		'functions/plugins',

		// Structure.
		'structure/archive',
		'structure/breadcrumbs',
		'structure/comments',
		'structure/footer',
		'structure/header',
		'structure/page-header',
		'structure/menus',
		'structure/pagination',
		'structure/sidebar',
		'structure/single',
		'structure/wrap',
		'structure/widget-areas',

		// Shortcodes.
		'shortcodes/icon',

		// Blocks.
		'blocks/icon',
		'blocks/cover',

		// Grid.
		'grid/setup',
		'grid/functions',
		'grid/field-groups',
		'grid/loop',
		'grid/customizer',
	];

	if ( is_admin() ) {
		$files = array_merge(
			$files,
			[
				'admin/images',
				'admin/settings',
				'admin/page-header',
				'admin/hide-elements',
				'admin/acf',
			]
		);
	}

	if ( is_customize_preview() ) {
		$files = array_merge(
			$files,
			[
				'customize/archives',
				'customize/kirki',
				'customize/logo',
				'customize/site-header',
				'customize/page-header',
				'customize/singular',
			]
		);
	}

	foreach ( $files as $file ) {
		$filename = __DIR__ . "/$file.php";

		if ( is_readable( $filename ) ) {
			require_once $filename;
		}
	}
}
