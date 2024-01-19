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
function mai_search_form_shortcode( $atts ) {
	$atts = (array) $atts;
	return mai_get_search_form( $atts );
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
		'class' => esc_html( $atts['class'] ),
	];

	$classes = 'mai-back-to-top';

	if ( $atts['class'] ) {
		$classes = mai_add_classes( $atts['class'], $classes );
	}

	return sprintf(
		'<a href="%s" title="%s" class="%s">%s</a>',
		$atts['link'],
		$atts['title'],
		trim( $classes ),
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
 * [mai_content id="my-page-slug" post_type="page"]
 *
 * @since 0.3.0
 * @since 2.12.0 Added post_type parameter when displaying content by post slug.
 *
 * @param array $atts Shortcode attributes.
 *
 * @return string
 */
function mai_content_shortcode( $atts = [] ) {
	$atts = shortcode_atts(
		[
			'id'        => '',
			'post_type' => 'wp_block',
		],
		$atts,
		'mai_content'
	);

	$atts = array_map( 'esc_html', $atts );

	if ( empty( $atts['id'] ) ) {
		return;
	}

	return mai_get_post_content( $atts['id'], strtolower( $atts['post_type'] ) );
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
	if ( ! ( isset( $atts['id'] ) && $atts['id'] ) ) {
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
	return mai_get_avatar( $atts );
}

add_shortcode( 'mai_date', 'mai_date_shortcode' );
/**
 * Displays post publish and modified date.
 *
 * @since 2.19.0
 *
 * @return string
 */
function mai_date_shortcode( $atts ) {
	return mai_get_date( $atts );
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

add_shortcode( 'mai_rating', 'mai_rating_shortcode' );
/**
 * Displays star rating.
 *
 * @since 2.11.0
 *
 * @return string
 */
function mai_rating_shortcode( $atts ) {
	return mai_get_rating( $atts );
}


add_shortcode( 'mai_price', 'mai_price_shortcode' );
/**
 * Displays the WooCommerce product price.
 *
 * @uses WooCommerce
 *
 * @since 2.9.0
 *
 * @return string
 */
function mai_price_shortcode( $atts ) {
	$woo = class_exists( 'WooCommerce' );
	$edd = class_exists( 'Easy_Digital_Downloads' );

	if ( ! ( $woo || $edd ) ) {
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

	if ( ! $atts['id'] ) {
		return;
	}

	$price     = '';
	$post_type = get_post_type( $atts['id'] );

	if ( $woo && 'product' === $post_type ) {
		$product = wc_get_product( $atts['id'] );

		if ( $product ) {
			$price = $product->get_price_html();
		}

	} elseif ( $edd && 'download' === $post_type ) {
		$price = edd_price( $atts['id'], false, false );
	}

	return $price;
}

add_filter( 'genesis_post_terms_shortcode', 'mai_post_terms_shortcode_classes', 10, 3 );
/**
 * Adds taxonomy name as class to entry-terms wrap.
 *
 * @since 2.10.0
 *
 * @param string $output The rendered HTML.
 * @param array  $terms  The term link HTML.
 * @param array  $atts   The shortcode attributes.
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
	$classes = mai_add_classes( sprintf( 'entry-terms-%s', sanitize_html_class( (string) $atts['taxonomy'] ) ), $classes );
	$first->setAttribute( 'class', $classes );
	$output  = trim( $dom->saveHTML() );

	return $output;
}

/**
 * Add inline custom properties to native/classic WP galleries.
 *
 * @since   2.10.0
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
	if ( ! $output ) {
		return $output;
	}

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

	if ( $style ) {
		$first->setAttribute( 'style', $style );
	} else {
		$first->removeAttribute( 'style' );
	}

	$output = mai_get_dom_html( $dom );

	return $output;
}
