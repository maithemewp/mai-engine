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

add_filter( 'woocommerce_enqueue_styles', 'mai_dequeue_woocommerce_styles' );
/**
 * Disable WooCommerce styles.
 *
 * @since 0.1.0
 *
 * @param array $enqueue_styles Woo styles.
 *
 * @return mixed
 */
function mai_dequeue_woocommerce_styles( $enqueue_styles ) {
	$styles = [
		'general',
	];

	foreach ( $styles as $style ) {
		unset( $enqueue_styles[ "woocommerce-$style" ] );
	}

	return $enqueue_styles;
}

add_filter( 'woocommerce_style_smallscreen_breakpoint', 'mai_woocommerce_breakpoint' );
/**
 * Modifies the WooCommerce breakpoints.
 *
 * @since 0.1.0
 *
 * @return string
 */
function mai_woocommerce_breakpoint() {
	$breakpoint      = 'md';
	$current         = mai_site_layout();
	$sidebar_layouts = [
		'content-sidebar',
		'sidebar-content',
	];

	if ( in_array( $current, $sidebar_layouts, true ) ) {
		$breakpoint = 'lg';
	}

	return mai_get_unit_value( mai_get_breakpoint( $breakpoint ), 'px' );
}

add_filter( 'woocommerce_product_loop_start', 'mai_product_loop_start_columns' );
/**
 * Adds column count as inline custom properties.
 *
 * @since 1/4/21
 *
 * @param array $html The existing loop start HTML.
 *
 * @return string
 */
function mai_product_loop_start_columns( $html ) {
	$count = wc_get_loop_prop( 'columns' );

	if ( ! is_numeric( $count ) ) {
		return $html;
	}

	$dom   = mai_get_dom_document( $html );
	$first = mai_get_dom_first_child( $dom );

	if ( ! $first ) {
		return $html;
	}

	// Get the columns breakpoint array.
	$columns = mai_get_breakpoint_columns(
		[
			'columns' => $count,
		]
	);

	$style  = $first->getAttribute( 'style' );
	$style .= sprintf( '--columns-xs:%s;', $columns['xs'] );
	$style .= sprintf( '--columns-sm:%s;', $columns['sm'] );
	$style .= sprintf( '--columns-md:%s;', $columns['md'] );
	$style .= sprintf( '--columns-lg:%s;', $columns['lg'] );

	$first->setAttribute( 'style', $style );

	return str_replace( '</ul>', '', $dom->saveHTML() );
}

add_filter( 'woocommerce_pagination_args', 'mai_woocommerce_pagination_previous_next_text' );
/**
 * Changes the adjacent entry previous and next link text.
 *
 * @since 1/4/21
 *
 * @param array $args The pagination args.
 *
 * @return array
 */
function mai_woocommerce_pagination_previous_next_text( $args ) {
	$args['prev_text'] = esc_html__( '← Previous', 'mai-engine' );
	$args['next_text'] = esc_html__( 'Next →', 'mai-engine' );

	return $args;
}

/**
 * Filter single product post_class.
 * Make sure it's only run on the main product entry wrap.
 *
 * @since 2.4.3
 *
 * @param array $classes The existing classes.
 *
 * @return array
 */
add_action( 'woocommerce_before_single_product', function() {
	add_filter( 'post_class', 'mai_woocommerce_product_single_class' );
});
add_action( 'woocommerce_before_single_product_summary', function() {
	remove_filter( 'post_class', 'mai_woocommerce_product_single_class' );
});

/**
 * Adds product single class.
 *
 * @since 2.4.3
 *
 * @param array $classes The existing classes.
 *
 * @return array
 */
function mai_woocommerce_product_single_class( $classes ) {
	$classes[] = 'product-single';
	return $classes;
}

/**
 * Trim zeros in price decimals.
 *
 * @since 0.1.0
 *
 * @return bool
 */
add_filter( 'woocommerce_price_trim_zeros', '__return_true', 8 );

add_filter( 'woocommerce_cart_item_remove_link', 'mai_woocommerce_cart_item_remove_icon' );
/**
 * Replaces cart item remove link x with an svg.
 *
 * @since 2.7.0
 *
 * @return string
 */
function mai_woocommerce_cart_item_remove_icon( $link ) {
	$svg = mai_get_svg_icon( 'times', 'light' );
	return str_replace( '&times;', $svg, $link );
}

add_action( 'admin_bar_menu', 'mai_woocommerce_edit_shop_link', 90 );
/**
 * Adds toolbar link to edit the shop page when view the shop archive.
 *
 * @since 2.10.0
 *
 * @param object $wp_admin_bar
 *
 * @return void
 */
function mai_woocommerce_edit_shop_link( $wp_admin_bar ) {
	if ( is_admin() ) {
		return;
	}

	$page_id = get_option( 'woocommerce_shop_page_id' );

	if ( ! $page_id ) {
		return;
	}

	$wp_admin_bar->add_node( [
		'id'    => 'mai-woocommerce-shop-page',
		'title' => '<span class="ab-icon dashicons dashicons-edit" style="margin-top:2px;"></span><span class="ab-label">' . __( 'Edit Page', 'mai-engine' ) . '</span>',
		'href'  => get_edit_post_link( $page_id, false ),
	] );
}

/**
 * Ajax update cart contents total.
 *
 * @since 2.7.0
 *
 * @param array $fragments The existing fragment elements to update.
 *
 * @return array
 */
add_filter( 'woocommerce_add_to_cart_fragments', 'mai_cart_total_fragment' );
function mai_cart_total_fragment( $fragments ) {
	$fragments['mai-cart-total'] = mai_get_cart_total();
	return $fragments;
}
