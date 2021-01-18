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

add_shortcode( 'mai_icon', 'mai_icon_shortcode' );
/**
 * Dipslays an icon.
 *
 * @since 0.3.0
 *
 * @param array $atts The shortcode attributes.
 *
 * @return string
 */
function mai_icon_shortcode( $atts ) {
	return mai_get_icon( $atts );
}

add_shortcode( 'mai_search_form', 'mai_search_form_shortcode' );
/**
 * Displays a search form.
 *
 * @since 0.3.0
 *
 * @return string
 */
function mai_search_form_shortcode() {
	return get_search_form( false );
}

add_shortcode( 'mai_back_to_top', 'mai_back_to_top_shortcode' );
/**
 * Displays a back to top link.
 *
 * @since 0.3.0
 *
 * @param array $atts Shortcode attributes.
 *
 * @return string
 */
function mai_back_to_top_shortcode( $atts = [] ) {
	$atts = shortcode_atts(
		[
			'link'  => '#top',
			'title' => __( 'Return to top of page', 'mai-engine' ),
			'text'  => __( 'Back to top', 'mai-engine' ),
			'class' => 'alignright',
		],
		$atts,
		'mai_back_to_top'
	);

	$atts = [
		'link'  => esc_url( $atts['link'] ),
		'title' => esc_html( $atts['title'] ),
		'text'  => esc_html( $atts['text'] ),
		'class' => sanitize_html_class( $atts['class'] ),
	];

	$class = 'mai-back-to-top';
	if ( $atts['class'] ) {
		$class .= ' ' . $atts['class'];
	}

	return sprintf(
		'<a href="%s" title="%s" class="%s">%s</a>',
		$atts['link'],
		$atts['title'],
		trim( $class ),
		$atts['text']
	);
}

add_shortcode( 'mai_content', 'mai_content_shortcode' );
/**
 * Displays a reusable block via a shortcode.
 * 'id' can be a slug or ID.
 *
 * Examples:
 * [mai_content id="123"]
 * [mai_content id="my-reusable-block"]
 *
 * @since 0.3.0
 *
 * @param array $atts Shortcode attributes.
 *
 * @return string
 */
function mai_content_shortcode( $atts = [] ) {
	$atts = shortcode_atts(
		[
			'id' => '',
		],
		$atts,
		'mai_content'
	);

	if ( empty( $atts['id'] ) ) {
		return;
	}

	return mai_get_post_content( $atts['id'] );
}

add_shortcode( 'mai_menu', 'mai_menu_shortcode' );
/**
 * Displays a menu.
 *
 * @since 0.3.3
 *
 * @param array $atts The shortcode atts.
 *
 * @return string
 */
function mai_menu_shortcode( $atts ) {
	$atts = shortcode_atts(
		[
			'id'      => '',       // The menu ID, slug, name.
			'class'   => '',       // HTML classes.
			'align'   => 'center', // Accepts left, center, or right.
			'display' => '',       // Accepts list.
		],
		$atts,
		'mai_menu'
	);

	if ( ! $atts['id'] ) {
		return;
	}

	return mai_get_menu( $atts['id'], $atts );
}

add_shortcode( 'mai_avatar', 'mai_avatar_shortcode' );
/**
 * Displays an author avatar.
 *
 * @since 2.7.0
 *
 * @return string
 */
function mai_avatar_shortcode( $atts ) {
	$atts = shortcode_atts(
		mai_get_avatar_default_args(),
		$atts,
		'mai_avatar'
	);

	return mai_get_avatar( $atts );
}

add_shortcode( 'mai_cart_total', 'mai_cart_total_shortcode' );
/**
 * Displays the cart total.
 *
 * @uses WooCommerce
 *
 * @since 2.7.0
 *
 * @return string
 */
function mai_cart_total_shortcode() {
	return mai_get_cart_total();
}

add_shortcode( 'mai_price', 'mai_price_shortcode' );
/**
 * Displays the WooCommerce product price.
 *
 * @uses WooCommerce
 *
 * @since 1/4/21
 *
 * @return string
 */
function mai_price_shortcode() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	$atts = shortcode_atts(
		[
			'id' => null,
		],
		$atts,
		'mai_price'
	);

	if ( ! $atts[ 'id' ] ) {
		$atts['id'] = get_the_ID();
	}

	$product = wc_get_product( $atts['id'] );

	if ( ! $product ) {
		return;
	}

	return $product->get_price_html();
}

add_filter( 'genesis_post_terms_shortcode', 'mai_post_terms_shortcode_classes', 10, 3 );
/**
 * Adds taxonomy name as class to entry-terms wrap.
 *
 * @since TBD
 *
 * @param string $output The rendered HTML.
 * @param array $terms   The term link HTML.
 * @param array $atts    The shortcode attributes.
 *
 * @return string
 */
function mai_post_terms_shortcode_classes( $output, $terms, $atts ) {
	if ( ! $output ) {
		return $output;
	}

	if ( ! ( isset( $atts['taxonomy'] ) || $atts['taxonomy'] ) ) {
		return $output;
	}

	$dom     = mai_get_dom_document( $output );
	$first   = mai_get_dom_first_child( $dom );
	$classes = $first->getAttribute( 'class' );
	$classes = mai_add_classes( sprintf( 'entry-terms-%s', sanitize_html_class( $atts['taxonomy'] ) ), $classes );
	$first->setAttribute( 'class', $classes );
	$output  = trim( $dom->saveHTML() );

	return $output;
}

/**
 * Add inline custom properties to native/classic WP galleries.
 *
 * @since   TBD
 *
 * @param   string        $output  Shortcode output.
 * @param   string        $tag     Shortcode name.
 * @param   array|string  $attr    Shortcode attributes array or empty string.
 * @param   array         $m       Regular expression match array.
 *
 * @return  string  The gallery HTML.
 */
add_filter( 'do_shortcode_tag', 'mai_gallery_shortcode_tag', 10, 4 );
function mai_gallery_shortcode_tag( $output, $tag, $atts, $m ) {
	if ( 'gallery' !== $tag ) {
		return $output;
	}

	// Bail if not a default gallery. This fixes compatibility with Jetpack galleries.
	if ( isset( $atts['type'] ) && ! in_array( $atts['type'], [ 'default', 'thumbnails' ] ) ) {
		return $output;
	}

	// Make sure we have a columns value. Would not be set if 3 as per WP core.
	$atts = wp_parse_args( $atts,
		[
			'columns' => 3,
		]
	);

	$dom   = mai_get_dom_document( $output );
	$first = mai_get_dom_first_child( $dom );

	if ( ! $first ) {
		return $output;
	}

	$style   = $first->getAttribute( 'style' );
	$columns = mai_get_breakpoint_columns(
		[
			'columns' => $atts['columns'],
		]
	);
	$columns = array_reverse( $columns, true ); // Mobile first.

	foreach ( $columns as $break => $column ) {
		$style .= sprintf( '--gallery-columns-%s:%s;', $break, $column );
	}

	$first->setAttribute( 'style', $style );

	$output = $dom->saveHTML();

	return $output;
}
