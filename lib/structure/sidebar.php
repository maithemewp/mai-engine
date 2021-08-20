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

add_action( 'genesis_before', 'mai_no_sidebar' );
/**
 * Remove sidebars on no-sidebar layouts.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_no_sidebar() {
	if ( mai_has_string( 'sidebar', genesis_site_layout() ) ) {
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

add_filter( 'genesis_skip_links_output', 'mai_remove_sidebar_skip_link' );
/**
 * Removes the sidebar skip link if not on page.
 *
 * @since 2.1.1
 *
 * @param array $links Skip links.
 *
 * @return array
 */
function mai_remove_sidebar_skip_link( $links ) {
	$layout = mai_site_layout();

	if ( 'sidebar-content' !== $layout || 'content-sidebar' !== $layout ) {
		unset( $links['genesis-sidebar-primary'] );
	}

	return $links;
}

add_action( 'genesis_sidebar', 'mai_remove_default_sidebar_content', 8 );
/**
 * Removes default sidebar content.
 * This will show up if using Sidebar template part.
 *
 * @since TBD
 *
 * @return void
 */
function mai_remove_default_sidebar_content() {
	if ( dynamic_sidebar( 'sidebar' ) ) {
		return;
	}

	remove_action( 'genesis_sidebar', 'genesis_do_sidebar' );
}
