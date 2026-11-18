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
	$tags = new WP_HTML_Tag_Processor( $link );

	while ( $tags->next_tag( 'a' ) ) {
		$tags->add_class( 'button' );
		$tags->add_class( 'button-secondary' );
		$tags->add_class( 'button-small' );
	}

	return $tags->get_updated_html();
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

	$tags = new WP_HTML_Tag_Processor( $formatted_link );

	if ( $tags->next_tag() ) {
		$tags->add_class( 'cancel-comment-reply-link' );
	}

	return $tags->get_updated_html();
}
