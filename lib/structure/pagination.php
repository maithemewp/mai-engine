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

// Reposition pagination.
remove_action( 'genesis_after_endwhile', 'genesis_posts_nav' );
add_action( 'genesis_after_content_sidebar_wrap', 'genesis_posts_nav' );
remove_action( 'genesis_after_entry', 'genesis_adjacent_entry_nav' );
add_action( 'genesis_after_content_sidebar_wrap', 'genesis_adjacent_entry_nav' );

// Remove alignment classes.
remove_filter( 'genesis_attr_pagination-previous', 'genesis_adjacent_entry_attr_previous_post' );
remove_filter( 'genesis_attr_pagination-next', 'genesis_adjacent_entry_attr_next_post' );

add_filter( 'genesis_markup_open', 'mai_entry_pagination_wrap_open', 10, 2 );
/**
 * Outputs the opening pagination wrap markup.
 *
 * @since 0.1.0
 *
 * @param string $open Opening markup.
 * @param array  $args Markup args.
 *
 * @return string
 */
function mai_entry_pagination_wrap_open( $open, $args ) {
	if ( 'archive-pagination' === $args['context'] ) {
		$open .= '<div class="wrap">';
	}

	return $open;
}

add_filter( 'genesis_markup_close', 'mai_entry_pagination_wrap_close', 10, 2 );
/**
 * Outputs the closing pagination wrap markup.
 *
 * @since 0.1.0
 *
 * @param string $close Closing markup.
 * @param array  $args  Markup args.
 *
 * @return string
 */
function mai_entry_pagination_wrap_close( $close, $args ) {
	if ( 'archive-pagination' === $args['context'] ) {
		$close .= '</div>';
	}

	return $close;
}

add_filter( 'genesis_prev_link_text', 'mai_previous_page_link' );
/**
 * Changes the previous page link text.
 *
 * @since 0.1.0
 *
 * @return string
 */
function mai_previous_page_link() {
	return sprintf( '← Previous', 'mai-engine' );
}

add_filter( 'genesis_next_link_text', 'mai_next_page_link' );
/**
 * Changes the next page link text.
 *
 * @since 0.1.0
 *
 * @return string
 */
function mai_next_page_link() {
	return sprintf( 'Next →', 'mai-engine' );
}

add_filter( 'genesis_markup_pagination-previous_content', 'mai_previous_pagination_text' );
/**
 * Changes the previous link arrow icon.
 *
 * @since 0.1.0
 *
 * @param string $content Previous link text.
 *
 * @return string
 */
function mai_previous_pagination_text( $content ) {
	return str_replace( '&#xAB;', '←', $content );
}

add_filter( 'genesis_markup_pagination-next_content', 'mai_next_pagination_text' );
/**
 * Changes the next link arrow icon.
 *
 * @since 0.1.0
 *
 * @param string $content Next link text.
 *
 * @return string
 */
function mai_next_pagination_text( $content ) {
	return str_replace( '&#xBB;', '→', $content );
}

add_filter( 'previous_post_link', 'mai_adjacent_entry_link_thumbnail', 10, 5 );
add_filter( 'next_post_link', 'mai_adjacent_entry_link_thumbnail', 10, 5 );
/**
 * Add adjacent entry images.
 *
 * The dynamic portion of the hook name, `$adjacent`, refers to the type
 * of adjacency, 'next' or 'previous'.
 *
 * @param   string  $output   The adjacent post link.
 * @param   string  $format   Link anchor format.
 * @param   string  $link     Link permalink format.
 * @param   WP_Post $post     The adjacent post.
 * @param   string  $adjacent Whether the post is previous or next.
 *
 * @return  string|HTML  The post link and image HTML.
 */
function mai_adjacent_entry_link_thumbnail( $output, $format, $link, $post, $adjacent ) {
	$image      = '';
	$show_image = apply_filters( 'mai_show_adjacent_entry_image', true );
	$image_id   = get_post_thumbnail_id( $post );
	if ( $show_image && $image_id ) {
		add_filter( 'wp_calculate_image_srcset_meta', '__return_null' );
		$image = wp_get_attachment_image( $image_id, 'tiny', false, [ 'class' => 'adjacent-entry-image' ] );
		remove_filter( 'wp_calculate_image_srcset_meta', '__return_null' );
	}
	$image = $show_image && $image ? $image : '';

	return str_replace( '%image', $image, $output );
}
