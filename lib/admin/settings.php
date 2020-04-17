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
 * @since 1.2.1
 *
 * @return void
 */
function mai_update_first_version() {
	if ( false !== get_option( 'mai_first_version' ) ) {
		return;
	}

	update_option( 'mai_first_version', mai_get_plugin_data( 'version' ) );
}

add_action( 'admin_init', 'mai_update_database_version', 20 );
/**
 * Maybe run the version updater.
 * Mostly taken from G core. Some original inspiration from link below.
 *
 * @link   https://www.sitepoint.com/wordpress-plugin-updates-right-way/
 *
 * @return void
 */
function mai_update_database_version() {
	if ( version_compare( get_option( 'mai_db_version' ), mai_get_plugin_data( 'db-version' ), '>=' ) ) {
		return;
	}

	update_option( 'mai_db_version', mai_get_plugin_data( 'db-version' ) );
}
