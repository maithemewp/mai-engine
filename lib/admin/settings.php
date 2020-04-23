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

/**
 * Expose Reusable Blocks UI in backend.
 * @link https://www.billerickson.net/reusable-blocks-accessible-in-wordpress-admin-area
 *
 * @since 0.1.0
 *
 * @return void
 */
add_action( 'admin_menu', 'mai_show_reusable_blocks_admin_menu' );
function mai_show_reusable_blocks_admin_menu() {
	add_menu_page( 'Reusable Blocks', 'Reusable Blocks', 'edit_posts', 'edit.php?post_type=wp_block', '', 'dashicons-editor-table', 22 );
}
