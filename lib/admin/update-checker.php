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

add_action( 'admin_init', 'mai_plugin_update_checker' );
/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_plugin_update_checker() {
	if ( ! current_user_can( 'install_plugins' ) ) {
		return;
	}

	if ( ! class_exists( 'Puc_v4_Factory' ) ) {
		return;
	}

	$updater = Puc_v4_Factory::buildUpdateChecker(
		'https://github.com/maithemewp/mai-engine',
		realpath( __DIR__ . '/../..' ) . '/mai-engine.php',
		'mai-engine'
	);

	// Get the branch. If checking for beta releases.
	$branch = function_exists( 'genesis_get_option' ) && genesis_get_option( 'mai_tester' );
	$branch = $branch ? 'beta' : 'master';

	// Allow branch and updater object manipulation.
	$branch = apply_filters( 'mai_updater_branch', $branch );

	// Set the branch.
	$updater->setBranch( $branch );

	// Allow tokens to be used to bypass GitHub rate limit.
	// if ( defined( 'MAI_UPDATER_TOKEN' ) ) {
	// $updater->setAuthentication( MAI_UPDATER_TOKEN );
	// }

	// Add icons for Dashboard > Updates screen.
	$updater->addResultFilter(
		function ( $info, $response = null ) {
			$info->icons = [
				'1x' => mai_get_url() . 'assets/img/icon-128x128.png',
				'2x' => mai_get_url() . 'assets/img/icon-256x256.png',
			];

			return $info;
		}
	);
}

add_action( 'admin_init', 'mai_child_theme_update_checker' );
/**
 * Maybe check for child theme updates.
 * Checkbox must be checked in Customizer > Theme Settings Updates
 * to allow child theme updates.
 *
 * Only checks non-default engine themes, which is our premium themes.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_child_theme_update_checker() {
	if ( ! current_user_can( 'install_plugins' ) ) {
		return;
	}

	if ( ! class_exists( 'Puc_v4_Factory' ) ) {
		return;
	}

	$enabled = genesis_get_option( 'mai_child_theme_updates' );

	if ( ! $enabled ) {
		return;
	}

	$child_theme = mai_get_active_theme();

	if ( 'default' === $child_theme ) {
		return;
	}

	$updater = Puc_v4_Factory::buildUpdateChecker(
		"https://github.com/maithemewp/mai-$child_theme",
		get_stylesheet_directory() . '/functions.php',
		"mai-$child_theme"
	);

	$updater->setBranch( 'master' );
}
