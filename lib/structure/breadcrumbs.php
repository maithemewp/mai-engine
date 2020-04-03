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

// Disables Genesis Hide Breadcrumbs option.
add_filter( 'genesis_breadcrumbs_toggle_enabled', '__return_false' );

// Reposition the breadcrumbs.
remove_action( 'genesis_before_loop', 'genesis_do_breadcrumbs' );

add_action( 'genesis_before', 'mai_maybe_hide_breadcrumbs' );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_maybe_hide_breadcrumbs() {
	if ( ! mai_is_element_hidden( 'breadcrumbs' ) ) {
		add_action( 'genesis_before_content_sidebar_wrap', 'genesis_do_breadcrumbs', 12 );
	}
}
