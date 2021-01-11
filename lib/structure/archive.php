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

// Remove Genesis Connect for WooCommerce intro text fallback.
add_action( 'genesis_before', function() {
	remove_filter( 'genesis_term_intro_text_output', 'genesiswooc_term_intro_text_output' );
});

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
	$tiny = mai_get_image_width( 'tiny' );

	return $tiny ?: $size;
}

add_filter( 'genesis_attr_taxonomy-archive-description', 'mai_attributes_archive_description' );
add_filter( 'genesis_attr_author-archive-description', 'mai_attributes_archive_description' );
/**
 * Removes possible conflicting class names.
 *
 * @since TBD
 *
 * @param array $attributes Existing attributes for the author description element.
 *
 * @return array
 */
function mai_attributes_archive_description( $attributes ) {
	$attributes['class'] = str_replace( ' taxonomy-description', '' ,$attributes['class'] );
	$attributes['class'] = str_replace( ' author-description', '', $attributes['class'] );

	return $attributes;
}

add_action( 'genesis_before_loop', 'mai_do_archives_description', 18 );
/**
 * Adds single hook for the archives description output.
 *
 * @since TBD
 *
 * @return void
 */
function mai_do_archives_description() {
	if ( ! mai_is_type_archive() ) {
		return;
	}

	do_action( 'mai_archives_description' );
}

add_action( 'mai_archives_description', 'mai_do_blog_description' );
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
	if ( is_paged() ) {
		return;
	}

	$description = apply_filters( 'the_content', get_post( $posts_page )->post_content );
	$description = wp_kses_post( $description );

	// Bail if no description.
	if ( empty( $description ) ) {
		return;
	}

	genesis_markup(
		[
			'open'    => '<div %s>',
			'close'   => '</div>',
			'context' => 'archives-description',
			'content' => mai_get_processed_content( $description ),
			'echo'    => true,
			'atts'    => [
				'class' => 'archives-description blog-description',
			],
		]
	);
}

add_action( 'mai_archives_description', 'mai_do_term_description' );
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
	if ( is_paged() ) {
		return;
	}

	$description = apply_filters( 'mai_term_description', term_description() );
	$description = wp_kses_post( $description );

	if ( ! $description ) {
		return;
	}

	genesis_markup(
		[
			'open'    => '<div %s>',
			'close'   => '</div>',
			'context' => 'archives-description',
			'content' => mai_get_processed_content( $description ),
			'echo'    => true,
			'atts'    => [
				'class' => 'archives-description term-description',
			],
		]
	);
}

add_action( 'mai_archives_description', 'mai_do_author_description' );
/**
 * Add author description before custom taxonomy loop, but after archive title/description.
 * Archive title/description is priority 15 in Genesis.
 *
 * This is the core WP author description, not the Genesis Intro Text.
 * Genesis Intro Text is in page header or before this.
 *
 * @since 2.4.2
 *
 * @return void
 */
function mai_do_author_description() {
	if ( ! is_author() ) {
		return;
	}

	// Bail if not the first page.
	if ( is_paged() ) {
		return;
	}

	$description = '';

	// Bail if author box is enabled.
	if ( get_the_author_meta( 'genesis_author_box_archive', get_query_var( 'author' ) ) ) {
		return;
	}

	$description = get_the_author_meta( 'description' );
	$description = apply_filters( 'mai_author_description', $description );
	$description = wp_kses_post( $description );

	if ( ! $description ) {
		return;
	}

	genesis_markup(
		[
			'open'    => '<div %s>',
			'close'   => '</div>',
			'context' => 'archives-description',
			'content' => mai_get_processed_content( $description ),
			'echo'    => true,
			'atts'    => [
				'class' => 'archives-description author-description',
			],
		]
	);
}

add_action( 'mai_archives_description', 'mai_do_author_archive_author_box' );
/**
 *
 * @since TBD
 *
 * @return void
 */
function mai_do_author_archive_author_box() {
	if ( ! is_author() ) {
		return;
	}

	// Bail if not the first page.
	if ( is_paged() ) {
		return;
	}

	// Bail if author box is not enabled.
	if ( ! get_the_author_meta( 'genesis_author_box_archive', get_query_var( 'author' ) ) ) {
		return;
	}

	global $authordata;
	$authordata = is_object( $authordata ) ? $authordata : get_userdata( get_query_var( 'author' ) );

	echo genesis_get_author_box_by_user( $authordata->ID );
}
