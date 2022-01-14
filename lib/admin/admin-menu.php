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

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

add_action( 'admin_menu', 'mai_admin_menu_pages' );
/**
 * Registers plugin admin menu pages.
 * Exposes Reusable Blocks UI in backend.
 *
 * @link  https://www.billerickson.net/reusable-blocks-accessible-in-wordpress-admin-area
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_admin_menu_pages() {
	add_menu_page(
		esc_html__( 'Mai Theme', 'mai-engine' ),
		esc_html__( 'Mai Theme', 'mai-engine' ),
		'edit_posts',
		'mai-theme',
		'mai_render_admin_menu_page',
		'data:image/svg+xml;base64,' . base64_encode( file_get_contents( mai_get_dir() . 'assets/svg/mai-logo-icon.svg' ) ),
		'58.995' // This only works as a string for some reason.
	);

	// Changes first menu name. Otherwise above has Mai Theme as the first child too.
	add_submenu_page(
		'mai-theme',
		esc_html__( 'Plugins', 'mai-engine' ),
		esc_html__( 'Plugins', 'mai-engine' ),
		'edit_posts',
		'mai-theme',
		'',
		null
	);

	add_submenu_page(
		'mai-theme',
		esc_html__( 'Content Areas', 'mai-engine' ),
		esc_html__( 'Content Areas', 'mai-engine' ),
		'edit_posts',
		'edit.php?post_type=mai_template_part',
		'',
		10
	);

	add_submenu_page(
		'mai-theme',
		esc_html__( 'Reusable Blocks', 'mai-engine' ),
		esc_html__( 'Reusable Blocks', 'mai-engine' ),
		'edit_posts',
		'edit.php?post_type=wp_block',
		'',
		20
	);

	add_submenu_page(
		'themes.php',
		esc_html__( 'Reusable Blocks', 'mai-engine' ),
		esc_html__( 'Reusable Blocks', 'mai-engine' ),
		'edit_posts',
		'edit.php?post_type=wp_block',
		'',
		22
	);
}

add_action( 'init', 'mai_plugins_setup' );
/**
 * Setup plugins admin page class.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_plugins_setup() {
	$page = new Mai_Plugins;
}

/**
 * Renders admin settings page markup.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_render_admin_menu_page() {
	do_action( 'mai_plugins_page' );
	// echo '<style>
	// :root {
	// 	--mai-admin-toolbar: 32px;
	// 	--mai-admin-content-left: 20px;
	// }
	// @media screen and (max-width: 782px) {
	// 	:root {
	// 		--mai-admin-toolbar: 46px;
	// 	}
	// 	.auto-fold {
	// 		--mai-admin-content-left: 10px;
	// 	}
	// }
	// </style>';
	// echo '<iframe style="display:block;width:calc(100% + var(--mai-admin-content-left));height:calc(100vh - var(--mai-admin-toolbar));position:absolute;top:0;left:calc(var(--mai-admin-content-left) * -1);z-index: 9999;" width="400" height="800" frameborder="0" scrolling="yes" seamless="seamless" src="https://bizbudding.com/mai-engine-admin/"></iframe>';
}

add_action( 'admin_menu', 'mai_admin_menu_subpages', 30 );
/**
 * Add docs and support admin submenu items to end of submenu.
 *
 * @since 2.6.0
 *
 * @return void
 */
function mai_admin_menu_subpages() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	global $submenu;

	$submenu['mai-theme'][] = [
		__( 'Documentation', 'mai-engine' ),
		'edit_posts',
		'https://docs.bizbudding.com/',
	];

	$submenu['mai-theme'][] = [
		__( 'Support', 'mai-engine' ),
		'edit_posts',
		'https://docs.bizbudding.com/support/',
	];
}

add_filter( 'plugin_action_links_mai-engine/mai-engine.php', 'mai_add_plugins_link', 10, 4 );
/**
 * Return the plugin action links. This will only be called if the plugin is active.
 *
 * @since TBD
 *
 * @param array  $actions     Associative array of action names to anchor tags
 * @param string $plugin_file Plugin file name, ie my-plugin/my-plugin.php
 * @param array  $plugin_data Associative array of plugin data from the plugin file headers
 * @param string $context     Plugin status context, ie 'all', 'active', 'inactive', 'recently_active'
 *
 * @return array Associative array of plugin action links
 */
function mai_add_plugins_link( $actions, $plugin_file, $plugin_data, $context ) {
	$actions['settings'] = sprintf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=mai-theme' ), __( 'Plugins', 'mai-engine' ) );

	return $actions;
}
