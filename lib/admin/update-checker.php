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
 * Initialize plugin update checker.
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

	// Add icons for Dashboard > Updates screen.
	$updater->addResultFilter(
		function ( $info ) {
			$info->icons = [
				'1x' => mai_get_url() . 'assets/img/icon-128x128.png',
				'2x' => mai_get_url() . 'assets/img/icon-256x256.png',
			];

			return $info;
		}
	);
}
