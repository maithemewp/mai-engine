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

add_action( 'admin_init', 'mai_update_first_version' );
/**
 * Set the first version number.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_update_first_version() {
	if ( false !== mai_get_option( 'first-version' ) ) {
		return;
	}

	mai_update_option( 'first-version', mai_get_version() );
}

add_action( 'admin_init', 'mai_update_database_version' );
/**
 * Maybe run the version updater.
 *
 * Mostly taken from G core. Some original inspiration from link below.
 *
 * @since  1.0.0
 *
 * @link   https://www.sitepoint.com/wordpress-plugin-updates-right-way/
 *
 * @return void
 */
function mai_update_database_version() {
	$db_version = mai_get_plugin_data( 'db-version' );

	if ( version_compare( mai_get_option( 'db-version' ), $db_version, '<=' ) ) {
		mai_update_option( 'db-version', $db_version );
	}
}
