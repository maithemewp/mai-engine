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

/* @noinspection PhpUnhandledExceptionInspection */
spl_autoload_register( 'mai_autoload_register' );
/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @param string $class Class to check.
 *
 * @return void
 */
function mai_autoload_register( $class ) {
	$namespace = 'Mai';

	if ( strpos( $class, $namespace ) === false ) {
		return;
	}

	$dir  = mai_get_dir() . 'lib/classes/';
	$file = strtolower( str_replace( '_', '-', $class ) );

	/* @noinspection PhpIncludeInspection */
	require_once "{$dir}class-{$file}.php";
}

add_action( 'genesis_setup', 'mai_autoload_files', 90 );
/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_autoload_files() {
	$files = [

		// Functions.
		'functions/enqueue',
		'functions/css',
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
		'structure/page-header',
		'structure/home',
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

		// Plugins.
		'plugins/acf',
		'plugins/woocommerce',

		// Admin.
		'admin/settings',
		'admin/page-header',

		// Customizer.
		'customize/kirki',
		'customize/panels',
		'customize/sections',
		'customize/fields',

		// Grid.
		'grid/setup',
		'grid/functions',
		'grid/field-groups',
		'grid/loop',
		'grid/rest-api',
		'grid/customizer',
	];

	foreach ( $files as $file ) {
		$filename = dirname( __DIR__ ) . "/$file.php";

		if ( is_readable( $filename ) ) {
			require_once $filename;
		}
	}
}
