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

add_filter( 'genesis_comment_list_args', 'mai_setup_comments_gravatar' );
/**
 * Modify size of the Gravatar in the entry comments.
 *
 * @since 0.1.0
 *
 * @param array $args Genesis comment list arguments.
 *
 * @return mixed
 */
function mai_setup_comments_gravatar( array $args ) {
	$args['avatar_size'] = mai_get_config( 'settings' )['genesis']['avatar_size'];

	return $args;
}

// Remove author 'says' text.
add_filter( 'comment_author_says_text', '__return_empty_string' );

add_filter( 'comment_reply_link', 'mai_comment_reply_button_class' );
/**
 * Add comment reply button classes.
 *
 * @since 0.1.0
 *
 * @param string $link The button html.
 *
 * @return string
 */
function mai_comment_reply_button_class( $link ) {
	$dom   = mai_get_dom_document( $link );
	$links = $dom->getElementsByTagName( 'a' );

	if ( ! $links ) {
		return $link;
	}

	foreach ( $links as $button ) {
		$classes = $button->getAttribute( 'class' );
		$classes = mai_add_classes( [ 'button', 'button-secondary', 'button-small' ], $classes );
		$classes = $button->setAttribute( 'class', $classes );
	}

	return $dom->saveHTML( $dom->documentElement );
}

/**
 * Filters the cancel comment reply link HTML.
 *
 * @since 2.7.0
 *
 * @param string $formatted_link The HTML-formatted cancel comment reply link.
 * @param string $link           Cancel comment reply link URL.
 * @param string $text           Cancel comment reply link text.
 */
add_filter( 'cancel_comment_reply_link', 'mai_comment_reply_link', 10, 3 );
function mai_comment_reply_link( $formatted_link, $link, $text ) {
	if ( ! $formatted_link ) {
		return $formatted_link;
	}

	$dom  = mai_get_dom_document( $formatted_link );
	$link = mai_get_dom_first_child( $dom );

	if ( ! $link ) {
		return $formatted_link;
	}

	$classes = $link->getAttribute( 'class' );
	$classes = mai_add_classes( 'cancel-comment-reply-link', $classes );
	$classes = $link->setAttribute( 'class', $classes );

	return $dom->saveHTML( $dom->documentElement );
}
