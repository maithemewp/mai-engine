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

// Disable Genesis Title Toggle setting.
add_filter( 'genesis_title_toggle_enabled', '__return_false' );

// Disables the post edit link.
add_filter( 'edit_post_link', '__return_empty_string' );

// Remove author 'says' text.
add_filter( 'comment_author_says_text', '__return_empty_string' );

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
	$link = str_replace( 'comment-reply-link', 'comment-reply-link button button-secondary', $link );

	return $link;
}
