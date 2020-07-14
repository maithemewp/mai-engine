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
 * Registers plugin admin menu page.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_admin_menu_page() {
	add_menu_page(
		esc_html__( 'Mai Theme', 'mai-engine' ),
		esc_html__( 'Mai Theme', 'mai-engine' ),
		'manage_options',
		'mai-theme',
		'mai_render_admin_menu_page',
		mai_get_url() . 'assets/svg/mai-icon-white.svg',
		59
	);
}

/**
 * Renders admin settings page markup.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_render_admin_menu_page() {
	global $title;

	echo '<div class="wrap">';
	echo "<h1>$title</h1>";

	$theme = mai_get_active_theme();
	$theme = 'default' === $theme ? __( 'Theme', 'mai-engine' ) : mai_convert_case( $theme, 'title' );

	echo sprintf(
		'<p>%s %s.</p><h2>%s</h2>',
		__( 'Hi! Thanks for choosing Mai', 'mai-engine' ),
		$theme,
		__( 'Quick Links', 'mai-engine' )
	);

	echo '<ul>';

	echo '<li>· <a href="https://support.bizbudding.com/" target="_blank" rel="noopener nofollow">' . __( 'Support', 'mai-engine' ) . '</a></li>';
	echo '<li>· <a href="https://bizbudding.com/" target="_blank" rel="noopener nofollow">' . __( 'BizBudding', 'mai-engine' ) . '</a></li>';
	echo '<li>· <a href="https://demo.bizbudding.com/" target="_blank" rel="noopener nofollow">' . __( 'Theme Demos', 'mai-engine' ) . '</a></li>';

	echo '<ul>';

	echo '</div>';
}

add_action( 'admin_menu', 'mai_show_reusable_blocks_admin_menu' );
/**
 * Exposes Reusable Blocks UI in backend.
 *
 * @link  https://www.billerickson.net/reusable-blocks-accessible-in-wordpress-admin-area
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_show_reusable_blocks_admin_menu() {
	$title = esc_html__( 'Reusable Blocks', 'mai-engine' );

	add_submenu_page(
		'themes.php',
		$title,
		$title,
		'edit_posts',
		'edit.php?post_type=wp_block',
		'',
		22
	);
}
