<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2019 BizBudding
 * @license   GPL-2.0-or-later
 */

add_action( 'genesis_before', 'mai_no_sidebar' );
/**
 * Remove sidebars on no-sidebar layouts.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_no_sidebar() {
	if ( mai_has_string('sidebar', genesis_site_layout())) {
		return;
	}

	add_filter( 'genesis_markup_sidebar-primary_open', '__return_false' );
	remove_all_actions( 'genesis_before_sidebar_widget_area' );
	remove_all_actions( 'genesis_sidebar' );
	remove_all_actions( 'genesis_after_sidebar_widget_area' );
	add_filter( 'genesis_markup_sidebar-primary_close', '__return_false' );
	add_filter( 'genesis_markup_sidebar-secondary_open', '__return_false' );
	remove_all_actions( 'genesis_before_sidebar_alt_widget_area' );
	remove_all_actions( 'genesis_sidebar_alt' );
	remove_all_actions( 'genesis_after_sidebar_alt_widget_area' );
	add_filter( 'genesis_markup_sidebar-secondary_close', '__return_false' );
}
