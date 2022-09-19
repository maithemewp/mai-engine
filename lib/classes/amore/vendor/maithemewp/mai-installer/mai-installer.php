<?php
/**
 * Mai Installer.
 *
 * @package   BizBudding\MaiInstaller
 * @link      https://bizbuding.com
 * @author    BizBudding
 * @copyright Copyright © 2020 BizBudding
 * @license   GPL-2.0-or-later
 */

/**
 * Add theme support for Mai Engine.
 *
 * Default Mai Engine themes are already supported so let's check first.
 *
 * @since 1.1.0
 *
 * @return void
 */
if ( ! current_theme_supports( 'mai-engine' ) ) {
	add_theme_support( 'mai-engine' );
}

add_action( 'after_setup_theme', 'mai_plugin_dependencies' );
/**
 * Pass config to WP Dependency Installer.
 *
 * @since 1.2.0
 *
 * @return void
 */
function mai_plugin_dependencies() {
	if ( ! ( class_exists( 'WP_Dependency_Installer' ) && class_exists( 'Mai_Engine' ) ) ) {
		require_once __DIR__ . '/vendor/autoload.php';
	}

	if ( ! class_exists( 'WP_Dependency_Installer' ) ) {
		return;
	}

	if ( ! ( is_admin() && current_user_can( 'install_plugins' ) ) ) {
		return;
	}

	$config = [
		[
			'name'     => 'Mai Engine',
			'host'     => 'github',
			'slug'     => 'mai-engine/mai-engine.php',
			'uri'      => 'maithemewp/mai-engine',
			'branch'   => 'master',
			'optional' => false,
		]
	];

	WP_Dependency_Installer::instance( __DIR__ )->register( $config )->run();
}

add_action( 'admin_init', 'mai_theme_redirect', 100 );
/**
 * Redirect after activation.
 *
 * @since 1.2.0
 *
 * @return void
 */
function mai_theme_redirect() {
	global $pagenow;

	if ( function_exists( 'mai_get_engine_theme' ) && 'themes.php' === $pagenow && is_admin() && isset( $_GET['activated'] ) ) {
		exit( wp_redirect( admin_url( 'admin.php?page=mai-setup-wizard' ) ) );
	}
}
