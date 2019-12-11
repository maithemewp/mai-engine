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

// Reposition singular image.
remove_action( 'genesis_entry_content', 'genesis_do_singular_image', 8 );
add_action( 'genesis_entry_header', 'genesis_do_singular_image' );

// Disables the post edit link.
add_filter( 'edit_post_link', '__return_empty_string' );

add_filter( 'post_class', 'mai_single_post_class' );
/**
 * Add column class to single posts.
 *
 * @since 0.1.0
 *
 * @param array $classes Array of post classes.
 *
 * @return array
 */
function mai_single_post_class( $classes ) {
	if ( ! mai_is_type_single() ) {
		return $classes;
	}

	if ( class_exists( 'WooCommerce' ) && is_woocommerce() ) {
		return $classes;
	}

	if ( did_action( 'genesis_before_sidebar_widget_area' ) ) {
		return $classes;
	}

	return $classes;
}
