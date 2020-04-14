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

add_action( 'edit_form_after_title', 'mai_add_editor_on_posts_page' );
/**
 * Enable the editor on the page for posts.
 * Force an empty space as the post content so block editor (Gutenberg) will show in the admin when editing this page,
 * otherwise default editor will show. This is a bit hacky and I wish Gutenberg didn't do this.
 *
 * @since 0.1.0
 *
 * @param WP_Post $post Post object.
 *
 * @return void
 */
function mai_add_editor_on_posts_page( $post ) {
	if ( get_option( 'page_for_posts' ) !== $post->ID ) {
		return;
	}

	remove_action( 'edit_form_after_title', '_wp_posts_page_notice' );
	add_post_type_support( 'page', 'editor' );

	// Bail the post has content.
	if ( ! empty( $post->post_content ) ) {
		return;
	}

	// Update the post, adding a space as the content.
	$post_id = wp_update_post(
		[
			'ID'           => $post->ID,
			'post_content' => ' ',
		]
	);

	// TODO: Make a page refresh happen so block editor is enabled.
	// if ( $post_id ) {
	// wp_safe_redirect( get_permalink() );
	// exit;
	// }
}
