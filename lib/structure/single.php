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

// Disable Genesis Title Toggle setting.
add_filter( 'genesis_title_toggle_enabled', '__return_false' );

// Reposition singular image.
remove_action( 'genesis_entry_content', 'genesis_do_singular_image', 8 );
add_action( 'genesis_entry_header', 'genesis_do_singular_image' );

// Disables the post edit link.
add_filter( 'edit_post_link', '__return_empty_string' );

add_action( 'genesis_before', 'mai_maybe_hide_entry_title' );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_maybe_hide_entry_title() {
	if ( ! did_action( 'genesis_entry_content' ) && mai_is_element_hidden( 'entry_title' ) ) {
		remove_action( 'genesis_entry_header', 'genesis_do_post_title' );
	}
}

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

add_filter( 'genesis_markup_entry-title_content', 'mai_feature_posts_widget_entry_title_link' );
/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @param string $default Default string.
 *
 * @return string
 */
function mai_feature_posts_widget_entry_title_link( $default ) {
	$permalink = get_permalink();
	$search    = sprintf( '<a href="%s">', $permalink );
	$replace   = sprintf( '<a href="%s" class="entry-title-link">', $permalink );

	return str_replace( $search, $replace, $default );
}
