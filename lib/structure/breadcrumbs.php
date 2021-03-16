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

// Disables Genesis Hide Breadcrumbs option.
add_filter( 'genesis_breadcrumbs_toggle_enabled', '__return_false' );

// Remove default Genesis breadcrumb output.
remove_action( 'genesis_before_loop', 'genesis_do_breadcrumbs' );

add_action( 'genesis_before_content_sidebar_wrap', 'mai_do_breadcrumbs', 12 );
/**
 * Displays breadcrumbs if not hidden.
 *
 * @since TBD
 *
 * @return void
 */
function mai_do_breadcrumbs() {
	if ( mai_is_element_hidden( 'breadcrumbs' ) ) {
		return;
	}

	genesis_do_breadcrumbs();
}
