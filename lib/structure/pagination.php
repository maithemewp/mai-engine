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
	if ( 'archive-pagination' === $args['context'] || 'adjacent-entry-pagination' === $args['context'] ) {
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
	if ( 'archive-pagination' === $args['context'] || 'adjacent-entry-pagination' === $args['context'] ) {
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
