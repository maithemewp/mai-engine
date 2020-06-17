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

// Enable shortcodes in archive description.
add_filter( 'genesis_cpt_archive_intro_text_output', 'do_shortcode' );

add_filter( 'excerpt_more', 'mai_read_more_ellipsis' );
add_filter( 'get_the_content_more_link', 'mai_read_more_ellipsis' );
add_filter( 'the_content_more_link', 'mai_read_more_ellipsis' );
/**
 * Filter the excerpt and content "read more" string.
 *
 * @since 0.1.0
 * @since 0.3.11 Modified and added excerpt_more.
 *
 * @uses excerpt_more              When the excerpt is shorter then the full content, this read more link will show.
 * @uses get_the_content_more_link Genesis function to get the more link, if characters are limited.
 * @uses the_content_more_link     Core WP function to get the more link, if characters are limited.
 *
 * @param  string $more "Read more" excerpt string.
 *
 * @return string
 */
function mai_read_more_ellipsis( $more ) {
	return mai_get_ellipsis();
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

add_action( 'genesis_archive_title_descriptions', 'mai_do_blog_description' );
/**
 * Output the static blog page content before the posts.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_do_blog_description() {
	$posts_page = get_option( 'page_for_posts' );

	// Bail if not the blog page.
	if ( ! ( is_home() && $posts_page ) ) {
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

	echo wp_kses_post( $content );
}
