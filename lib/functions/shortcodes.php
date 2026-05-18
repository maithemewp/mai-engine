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

	$atts = array_map( 'sanitize_text_field', $atts );

	// If current ID.
	if ( 'current' === $atts['id'] && in_the_loop() ) {
		$atts['id'] = get_the_ID();
	}

	// If current post_type.
	if ( 'current' === $atts['post_type'] && in_the_loop() ) {
		$atts['post_type'] = get_post_type();
	}

	// Bail if empty.
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

add_shortcode( 'mai_terms', 'mai_terms_shortcode' );
/**
 * Displays the terms for a post.
 *
 * @since 2.35.2
 *
 * @param array $atts The shortcode attributes.
 *
 * @return string
 */
function mai_terms_shortcode( $atts ) {
	$atts = shortcode_atts( [
		'taxonomy' => 'category', // Comma separated list of taxonomies.
		'before'   => '',
		'after'    => '',
		'sep'      => ', ',
		'post_id'  => get_the_ID(),
		'link'     => true,
	], $atts, 'mai_terms' );


	// Sanitize.
	$taxonomies       = explode( ',', $atts['taxonomy'] );
	$taxonomies       = array_map( 'sanitize_text_field', $taxonomies );
	$taxonomies       = array_filter( $taxonomies );
	$atts['taxonomy'] = sanitize_text_field( $taxonomies );
	$atts['before']   = wp_kses_post( $atts['before'] );
	$atts['after']    = wp_kses_post( $atts['after'] );
	$atts['sep']      = wp_kses_post( $atts['sep'] ); // Not sanitize_text_field(), which would strip intentional leading/trailing spaces.
	$atts['post_id']  = absint( $atts['post_id'] );
	$atts['link']     = filter_var( $atts['link'], FILTER_VALIDATE_BOOLEAN );

	// Get it started.
	$items = [];

	// Loop through taxonomies.
	foreach ( $taxonomies as $taxonomy ) {
		$terms = get_the_terms( $atts['post_id'], $taxonomy );

		// Bail if no terms.
		if ( ! $terms || is_wp_error( $terms ) ) {
			continue;
		}

		// Loop through terms.
		foreach ( $terms as $term ) {
			$inside = esc_html( $term->name );

			// If we're linking the terms.
			if ( $atts['link'] ) {
				// Get the term link.
				$link = get_term_link( $term );

				// If we have a link, wrap the term in an anchor tag.
				if ( $link ) {
					$inside = sprintf( '<a href="%s" rel="tag">%s</a>', esc_url( $link ), $inside );
				}
			}

			// Build the term html.
			$items[] = sprintf(
				'<span class="mai-term mai-term-%s">%s</span>',
				esc_attr( $taxonomy ),
				$inside
			);
		}
	}

	// Bail if no terms.
	if ( ! $items ) {
		return '';
	}

	// Build the html.
	$html = sprintf( '<div %s>%s%s%s</div>', genesis_attr( 'mai-terms' ), $atts['before'], implode( $atts['sep'], $items ), $atts['after'] );

	return $html;
}

add_shortcode( 'mai_style_guide', 'mai_style_guide_shortcode' );
/**
 * Displays a style guide.
 *
 * @since 2.39.0
 *
 * @return string
 */
function mai_style_guide_shortcode() {
	$html = '<div class="mai-style-guide">';

	// Logo.
	$logo = mai_get_logo();

	if ( $logo ) {
		$html .= '<div class="mai-style-guide-section mai-style-guide-logo has-xxl-margin-bottom">';
		$html .= sprintf( '<h2>%s</h2><hr>', __( 'Logo', 'mai-engine' ) );
		$html .= '<div style="max-width:400px;">';
		$html .= $logo;
		$html .= '</div>';
		$html .= '</div>';
	}

	// Typography.
	$fonts = [
		'heading' => __( 'Heading', 'mai-engine' ),
		'body'    => __( 'Body', 'mai-engine' ),
	];

	// Add alt font if configured.
	$alt_family = mai_get_font_family( 'alt' );

	if ( $alt_family && 'unset' !== $alt_family ) {
		$fonts['alt'] = __( 'Alt', 'mai-engine' );
	}

	$html .= '<div class="mai-style-guide-section mai-style-guide-typography has-xxl-margin-bottom">';
	$html .= sprintf( '<h2>%s</h2><hr>', __( 'Typography', 'mai-engine' ) );

	foreach ( $fonts as $element => $label ) {
		$family = mai_get_font_family( $element );

		$html .= '<div class="mai-style-guide-font" style="margin-bottom:24px;">';
		$html .= sprintf( '<p style="margin-bottom:8px;"><strong>%s:</strong> %s</p>', esc_html( $label ), esc_html( $family ) );
		$html .= sprintf(
			'<p style="font-family:var(--%s-font-family);font-size:1.25em;margin:0;">The quick brown fox jumps over the lazy dog</p>',
			esc_attr( $element )
		);
		$html .= '</div>';
	}

	$html .= '</div>';

	// Headings.
	$html .= '<div class="mai-style-guide-section mai-style-guide-headings has-xxl-margin-bottom">';
	$html .= sprintf( '<h2>%s</h2><hr>', __( 'Headings', 'mai-engine' ) );

	for ( $i = 1; $i <= 6; $i++ ) {
		$html .= sprintf( '<h%1$d>Heading %1$d</h%1$d>', $i );
	}

	$html .= '</div>';

	// Colors.
	$colors   = mai_get_colors();
	$elements = mai_get_color_elements();

	$html .= '<div class="mai-style-guide-section mai-style-guide-colors has-xxl-margin-bottom">';
	$html .= sprintf( '<h2>%s</h2><hr>', __( 'Colors', 'mai-engine' ) );
	$html .= '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:24px;">';

	foreach ( $colors as $slug => $hex ) {
		$is_light = mai_is_light_color( $hex );
		$text     = $is_light ? '#000' : '#fff';
		$name     = isset( $elements[ $slug ] ) ? $elements[ $slug ] : mai_convert_case( $slug, 'title' );

		$html .= sprintf(
			'<div class="mai-style-guide-color"><div style="background:%s;color:%s;aspect-ratio:1;border-radius:var(--border-radius, 4px);display:flex;flex-direction:column;align-items:center;justify-content:center;border:1px solid rgba(0,0,0,.1);"><strong>%s</strong><span>%s</span></div></div>',
			esc_attr( $hex ),
			esc_attr( $text ),
			esc_html( $name ),
			esc_html( $hex )
		);
	}

	$html .= '</div>';
	$html .= '</div>';

	// Buttons.
	$buttons = [
		__( 'Primary', 'mai-engine' )   => 'button',
		__( 'Secondary', 'mai-engine' ) => 'button button-secondary',
		__( 'Outline', 'mai-engine' )   => 'button button-outline',
		__( 'Small', 'mai-engine' )     => 'button button-small',
	];

	$html .= '<div class="mai-style-guide-section mai-style-guide-buttons has-xxl-margin-bottom">';
	$html .= sprintf( '<h2>%s</h2><hr>', __( 'Buttons', 'mai-engine' ) );
	$html .= '<div style="display:flex;flex-wrap:wrap;gap:16px;align-items:center;">';

	foreach ( $buttons as $label => $class ) {
		$html .= sprintf( '<button type="button" class="%s">%s</button>', esc_attr( $class ), esc_html( $label ) );
	}

	$html .= '</div>';
	$html .= '</div>';

	// Lists.
	$html .= '<div class="mai-style-guide-section mai-style-guide-lists has-xxl-margin-bottom">';
	$html .= sprintf( '<h2>%s</h2><hr>', __( 'Lists', 'mai-engine' ) );
	$html .= '<ul>';
	$html .= sprintf( '<li>%s</li>', __( 'Unordered list item one', 'mai-engine' ) );
	$html .= sprintf( '<li>%s', __( 'Unordered list item two', 'mai-engine' ) );
	$html .= '<ul>';
	$html .= sprintf( '<li>%s</li>', __( 'Nested list item one', 'mai-engine' ) );
	$html .= sprintf( '<li>%s</li>', __( 'Nested list item two', 'mai-engine' ) );
	$html .= '</ul>';
	$html .= '</li>';
	$html .= sprintf( '<li>%s</li>', __( 'Unordered list item three', 'mai-engine' ) );
	$html .= '</ul>';
	$html .= '<ol>';
	$html .= sprintf( '<li>%s</li>', __( 'Ordered list item one', 'mai-engine' ) );
	$html .= sprintf( '<li>%s', __( 'Ordered list item two', 'mai-engine' ) );
	$html .= '<ol>';
	$html .= sprintf( '<li>%s</li>', __( 'Nested list item one', 'mai-engine' ) );
	$html .= sprintf( '<li>%s</li>', __( 'Nested list item two', 'mai-engine' ) );
	$html .= '</ol>';
	$html .= '</li>';
	$html .= sprintf( '<li>%s</li>', __( 'Ordered list item three', 'mai-engine' ) );
	$html .= '</ol>';
	$html .= '</div>';

	// Blockquote.
	$html .= '<div class="mai-style-guide-section mai-style-guide-blockquote has-xxl-margin-bottom">';
	$html .= sprintf( '<h2>%s</h2><hr>', __( 'Blockquote', 'mai-engine' ) );
	$html .= '<blockquote><p>The quick brown fox jumps over the lazy dog.</p></blockquote>';
	$html .= '</div>';

	$html .= '</div>';

	return $html;
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
	$output  = mai_get_dom_html( $dom );

	return trim( $output );
}

add_filter( 'do_shortcode_tag', 'mai_gallery_shortcode_tag', 10, 4 );
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
