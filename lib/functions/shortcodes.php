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
 * Render the icon shortcode.
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
 * Adds search form shortcode.
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
 * Add [mai_back_to_top] shortcode.
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
 * Display a reusable block via a shortcode.
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
 * Display a menu.
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
			'id'      => '',       // The menu ID, slug, name, or object.
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
