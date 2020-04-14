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

/**
 * Set the first version number.
 *
 * @since 1.2.1
 *
 * @return void
 */
add_action( 'admin_init', 'mai_update_first_version' );
function mai_update_first_version() {
	// Bail if first version is already set.
	if ( false !== get_option( 'mai_first_version' ) ) {
		return;
	}

	// Update the first version.
	update_option( 'mai_first_version', MAI_THEME_ENGINE_VERSION );
}

if ( function_exists( 'acf_add_options_page' ) ) {
	acf_add_options_page(
		[
			'page_title' => mai_get_name(),
			'menu_title' => mai_get_name(),
			'menu_slug'  => mai_get_handle(),
			'capability' => 'edit_posts',
			'redirect'   => false,
		]
	);

	acf_add_options_sub_page(
		[
			'page_title'  => 'Addons',
			'menu_title'  => 'Addons',
			'parent_slug' => mai_get_handle(),
		]
	);

	acf_add_options_sub_page(
		[
			'page_title'  => 'Courses',
			'menu_title'  => 'Courses',
			'parent_slug' => mai_get_handle(),
		]
	);
}
