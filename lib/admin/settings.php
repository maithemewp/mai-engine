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

add_action( 'admin_menu', 'mai_admin_menu_page', 0 );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_admin_menu_page() {
	add_menu_page(
		mai_get_name(),
		mai_get_name(),
		'manage_options',
		mai_get_handle(),
		'mai_render_admin_menu_page',
		mai_get_url() . 'assets/svg/mai-icon-white.svg',
		59
	);
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_render_admin_menu_page() {
	global $title;

	echo '<div class="wrap">';
	echo "<h1>$title</h1>";
	echo '</div>';
}

add_action( 'admin_menu', 'mai_show_reusable_blocks_admin_menu' );
/**
 * Expose Reusable Blocks UI in backend.
 *
 * @link  https://www.billerickson.net/reusable-blocks-accessible-in-wordpress-admin-area
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_show_reusable_blocks_admin_menu() {
	$title = __( 'Reusable Blocks', 'mai-engine' );

	add_menu_page(
		$title,
		$title,
		'edit_posts',
		'edit.php?post_type=wp_block',
		'',
		'dashicons-editor-table',
		22
	);
}
