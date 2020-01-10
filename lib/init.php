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
 * @since 0.1.0
 *
 * @return void
 */
function mai_remove_genesis_theme_supports() {

	// Remove all default theme supports added by Genesis.
	remove_action( 'genesis_init', 'genesis_theme_support' );

	// Add support for breadcrumbs (needs to be added on genesis_init).
	add_theme_support( 'genesis-breadcrumbs' );
}

add_action( 'genesis_setup', 'mai_load_files', 90 );
/**
 * Load plugin files.
 *
 * Loads all plugin includes between Genesis and the child theme functions.php
 * file. It is loaded in this order so that users can customize the plugins
 * functionality from within their child theme's own functions.php file.
 *
 * Also, hooking all of the plugin file loading requirements to the genesis_setup
 * hook means that no further plugin files will be loaded if the Genesis theme
 * is not already installed and activated, preventing any issues occurring.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_load_files() {

	// Array of files to load.
	$files = [

		// Composer.
		'../vendor/autoload',

		// Dependencies.
		'../vendor/advanced-custom-fields/advanced-custom-fields-pro/acf',

		// Functions.
		'functions/helpers',
		'functions/autoload',
		'functions/setup',
		'functions/enqueue',
		'functions/markup',
		'functions/header',
		'functions/widgets',
		'functions/defaults',
		'functions/onboarding',

		// Structure.
		'structure/archive',
		'structure/comments',
		'structure/footer',
		'structure/header',
		'structure/hero',
		'structure/home',
		'structure/menus',
		'structure/pagination',
		'structure/sidebar',
		'structure/single',
		'structure/wrap',

		// Plugins.
		'plugins/woocommerce',

		// Admin.
		'admin/settings',

		// Customizer.
		'customize/register',
		'customize/header',
		'customize/widgets',
		'customize/colors',
	];

	// Loop through and load each file in array.
	foreach ( $files as $file ) {
		$filename = __DIR__ . "/$file.php";

		if ( is_readable( $filename ) ) {
			require_once $filename;
		}
	}
}



