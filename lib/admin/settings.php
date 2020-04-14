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
