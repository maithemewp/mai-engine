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

// Enable shortcodes in archive description.
add_filter( 'genesis_cpt_archive_intro_text_output', 'do_shortcode' );

add_filter( 'get_the_content_more_link', 'mai_read_more_link' );
add_filter( 'the_content_more_link', 'mai_read_more_link' );
/**
 * Modify the content limit read more link
 *
 * @since 0.1.0
 *
 * @param string $more_link_text Default more link text.
 *
 * @return string
 */
function mai_read_more_link( $more_link_text ) {
	return str_replace( [ '[', ']', '...' ], '', $more_link_text );
}

add_filter( 'genesis_author_box_gravatar_size', 'mai_author_box_gravatar' );
/**
 * Modifies size of the Gravatar in the author box.
 *
 * @since 0.1.0
 *
 * @param int $size Original icon size.
 *
 * @return int Modified icon size.
 */
function mai_author_box_gravatar( $size ) {
	$image_sizes = mai_get_available_image_sizes();
	return isset( $image_sizes['tiny']['width'] ) ? $image_sizes['tiny']['width'] : 80;
}

/**
 * Output the static blog page content before the posts.
 *
 * @return  void
 */
add_action( 'genesis_archive_title_descriptions', 'mai_do_blog_description' );
function mai_do_blog_description() {
	// Bail if not the blog page.
	if ( ! ( is_home() && $posts_page = get_option( 'page_for_posts' ) ) ) {
		return;
	}

	// If not the first page.
	if ( 0 !== absint( get_query_var( 'paged' ) ) ) {
		return;
	}

	$content = apply_filters( 'the_content', get_post( $posts_page )->post_content );

	// Bail if no content.
	if ( empty( $content ) ) {
		return;
	}

	echo $content;
}
