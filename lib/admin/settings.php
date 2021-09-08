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
		esc_html__( 'Mai Design Pack', 'mai-engine' ),
		esc_html__( 'Mai Design Pack', 'mai-engine' ),
		'edit_posts',
		'mai-theme',
		'',
		null
	);

	add_submenu_page(
		'mai-theme',
		esc_html__( 'Template Parts', 'mai-engine' ),
		esc_html__( 'Template Parts', 'mai-engine' ),
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

/**
 * Renders admin settings page markup.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_render_admin_menu_page() {
	echo '<style>
	:root {
		--mai-admin-toolbar: 32px;
		--mai-admin-content-left: 20px;
	}
	@media screen and (max-width: 782px) {
		:root {
			--mai-admin-toolbar: 46px;
		}
		.auto-fold {
			--mai-admin-content-left: 10px;
		}
	}
	</style>';
	echo '<iframe style="display:block;width:calc(100% + var(--mai-admin-content-left));height:100%;position:absolute;top:0;left:calc(var(--mai-admin-content-left) * -1);z-index: 9999;" width="400" height="800" frameborder="0" scrolling="yes" seamless="seamless" src="https://bizbudding.com/mai-engine-admin/"></iframe>';
}

/**
 * Add docs and support admin submenu items.
 *
 * @since 2.6.0
 *
 * @return void
 */
add_action( 'admin_menu', 'mai_admin_menu_subpages', 30 );
function mai_admin_menu_subpages() {
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
