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
 * Remove sidebars on narrow content layout.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_no_sidebar() {
	if ( ! in_array( genesis_site_layout(), [ 'narrow-content', 'standard-content' ], true ) ) {
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

add_action( 'genesis_setup', 'mai_remove_sidebar_content' );
/**
 * Remove default widget area content if sidebar is not registered or if a no-sidebar layout.
 * Unregistering isn't enough in some scenarios where an existing site already has content/widgets in the sidebar.
 *
 * @since 0.1.0
 *
 * @todo  Is this function the same as above?
 *
 * @return void
 */
function mai_remove_sidebar_content() {
	$widget_areas = mai_get_config( 'widget-areas' )['remove'];
	$layouts      = [
		'wide-content',
		'standard-content',
		'narrow-content',
	];

	if ( in_array( 'sidebar', $widget_areas, true ) || in_array( genesis_site_layout(), $layouts, true ) ) {
		remove_action( 'genesis_sidebar', 'genesis_do_sidebar' );
	}

	if ( in_array( 'sidebar-alt', $widget_areas['remove'], true ) ) {
		remove_action( 'genesis_sidebar_alt', 'genesis_do_sidebar_alt' );
	}
}
