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
 * Also adds breadcrumb support back before genesis init.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_remove_genesis_theme_supports() {
	remove_action( 'genesis_init', 'genesis_theme_support' );
	add_theme_support( 'genesis-breadcrumbs' );
}

/**
 * Empty Genesis initial layouts.
 * All layouts are handled in the config now.
 *
 * @since 0.1.0
 *
 * @return array
 */
add_filter( 'genesis_initial_layouts', '__return_empty_array' );

/**
 * Use mai engine layout.
 *
 * @since 0.1.0
 *
 * @return array
 */
add_filter( 'genesis_site_layout', 'mai_site_layout' );
function mai_site_layout( $layout ) {
	/**
	 * Remove layout filter from Genesis Connect for WooCommerce.
	 * Mai Engine handles this instead.
	 *
	 * TODO: Check if products are supported? Or do we do that in the config by default?
	 */
	remove_filter( 'genesis_pre_get_option_site_layout', 'genesiswooc_archive_layout' );

	$site_layout = mai_get_site_layout();

	return $site_layout ?: $layout;
}
