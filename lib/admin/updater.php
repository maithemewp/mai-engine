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

add_action( 'admin_init', 'mai_plugin_updater' );
/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_plugin_updater() {
	// Bail if current user cannot manage plugins.
	if ( ! current_user_can( 'install_plugins' ) ) {
		return;
	}

	if ( ! class_exists( 'Puc_v4_Factory' ) ) {
		return;
	}

	$updater = Puc_v4_Factory::buildUpdateChecker(
		'https://github.com/maithemewp/mai-engine',
		__FILE__,
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
	// 	$updater->setAuthentication( MAI_UPDATER_TOKEN );
	// }

	// Add icons for Dashboard > Updates screen.
	$updater->addResultFilter( function( $info, $response = null ) {
		$info->icons = array(
			'1x' => mai_get_url() . 'assets/img/icon-128x128.png',
			'2x' => mai_get_url() . 'assets/img/icon-256x256.png',
		);
		return $info;
	});
}
