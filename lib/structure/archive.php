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
 * @return int
 */
function mai_author_box_gravatar( $size ) {
	$image_sizes = mai_get_available_image_sizes();

	return isset( $image_sizes['tiny']['width'] ) ? $image_sizes['tiny']['width'] : $size;
}

add_action( 'genesis_archive_title_descriptions', 'mai_do_blog_description' );
/**
 * Output the static blog page content before the posts.
 *
 * @since 0.1.0
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

add_action( 'genesis_before_loop', 'mai_do_term_description', 18 );
/**
 * Add term description before custom taxonomy loop, but after archive title/description.
 * Archive title/description is priority 15 in Genesis.
 *
 * This is the core WP term description, not the Genesis Intro Text.
 * Genesis Intro Text is in page header or before this.
 *
 * @since 2.4.2
 *
 * @return void
 */
function mai_do_term_description() {
	if ( ! ( is_category() || is_tag() || is_tax() ) ) {
		return;
	}

	// Bail if not the first page.
	if ( 0 !== absint( get_query_var( 'paged' ) ) ) {
		return;
	}

	// Bail if a Woo taxo. Description is already output by Woo. Can't use is_product_taxonomy() because it incluces custom taxos on produts.
	if ( class_exists( 'WooCommerce' ) && is_tax( [ 'product_cat', 'product_tag' ] ) ) {
		return;
	}

	$description = apply_filters( 'mai_term_description', term_description() );

	if ( ! $description ) {
		return;
	}

	printf( '<div class="term-description">%s</div>', mai_get_processed_content( $description ) );
}
