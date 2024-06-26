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

// Remove Genesis Connect for WooCommerce intro text fallback.
add_action( 'genesis_before', function() {
	remove_filter( 'genesis_term_intro_text_output', 'genesiswooc_term_intro_text_output' );
});

add_action( 'wp_head', 'mai_add_taxonomy_opengraph_image' );
/**
 * Adds term featured image as Open Graph meta tags.
 *
 * @since 2.25.0
 *
 * @return void
 */
function mai_add_taxonomy_opengraph_image() {
	// Bail if not a term archive.
	if ( ! ( is_category() || is_tag() || is_tax() ) ) {
		return;
	}

	$term = get_queried_object();

	if ( ! $term ) {
		return;
	}

	$image_id  = mai_get_term_image_id( $term->term_id );
	$image     = $image_id ? wp_get_attachment_image_src( $image_id, 'landscape-lg' ) : '';

	if ( ! $image ) {
		return;
	}

	$url    = esc_url( $image[0] );
	$width  = absint( $image[1] );
	$height = absint( $image[2] );
	$info   = pathinfo( $url );
	$ext    = esc_attr( $info['extension'] );
	$ext    = 'jpg' === $ext ? 'jpeg' : $ext;

	printf( '<meta property="og:image" content="%s" class="mai-meta-tag">', $url );
	printf( '<meta property="og:image:width" content="%s" class="mai-meta-tag">', $width );
	printf( '<meta property="og:image:height" content="%s" class="mai-meta-tag">', $height );
	printf( '<meta property="og:image:type" content="image/%s" class="mai-meta-tag">', $ext );
}

add_action( 'genesis_before', 'mai_maybe_hide_archive_elements' );
/**
 * Hides term archive elements if Hide Elements has them hidden.
 *
 * @since 2.25.5
 *
 * @return void
 */
function mai_maybe_hide_archive_elements() {
	if ( ! ( is_category() || is_tag() || is_tax() ) ) {
		return;
	}

	if ( mai_is_element_hidden( 'entry_title' ) ) {
		remove_action( 'genesis_archive_title_descriptions', 'genesis_do_archive_headings_headline', 10, 3 );
	}

	if ( mai_is_element_hidden( 'entry_excerpt' ) ) {
		remove_action( 'genesis_archive_title_descriptions', 'genesis_do_archive_headings_open', 5 );
		remove_action( 'genesis_archive_title_descriptions', 'genesis_do_archive_headings_intro_text', 12 );
		remove_action( 'genesis_archive_title_descriptions', 'genesis_do_archive_headings_close', 15 );
	}
}

add_action( 'genesis_before', 'mai_maybe_hide_blog_page_title' );
/**
 * Hides blog page title if static blog page setting is checked.
 *
 * @since 2.18.0
 *
 * @return void
 */
function mai_maybe_hide_blog_page_title() {
	if ( is_singular() && ! is_home() ) {
		return;
	}

	if ( ! mai_is_element_hidden( 'entry_title' ) ) {
		return;
	}

	remove_action( 'genesis_before_loop', 'genesis_do_posts_page_heading' );
}

/**
 * Enable shortcodes in archive description.
 *
 * @since 2.0.0
 *
 * @return string
 */
add_filter( 'genesis_cpt_archive_intro_text_output', 'do_shortcode' );

add_filter( 'excerpt_more',              'mai_read_more_ellipsis' );
add_filter( 'get_the_content_more_link', 'mai_read_more_ellipsis' );
add_filter( 'the_content_more_link',     'mai_read_more_ellipsis' );
/**
 * Filter the excerpt and content "read more" string.
 *
 * @since 0.1.0
 * @since 0.3.11 Modified and added excerpt_more.
 * @since 2.32.0 Bail if this is the more link inside the latest posts block #639.
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
	global $block_core_latest_posts_excerpt_length;

	if ( $block_core_latest_posts_excerpt_length ) {
		return $more;
	}

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
add_filter( 'genesis_attr_author-archive-description',   'mai_attributes_archive_description' );
/**
 * Removes possible conflicting class names.
 *
 * @since 2.10.0
 *
 * @param array $attributes Existing attributes for the author description element.
 *
 * @return array
 */
function mai_attributes_archive_description( $attributes ) {
	$attributes['class'] = str_replace( ' taxonomy-description', '', $attributes['class'] );
	$attributes['class'] = str_replace( ' author-description', '', $attributes['class'] );

	return $attributes;
}

add_action( 'genesis_before_loop', 'mai_do_archives_description', 18 );
/**
 * Adds single hook for the archives description output.
 *
 * @since 2.10.0
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
	// Bail if not the blog page.
	if ( ! ( is_home() && 'page' === get_option( 'show_on_front' ) ) ) {
		return;
	}

	$posts_page = get_option( 'page_for_posts' );

	if ( ! $posts_page ) {
		return;
	}

	// If not the first page.
	if ( is_paged() ) {
		return;
	}

	$description = get_post( $posts_page )->post_content;
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

	// Bail if hidden.
	if ( mai_is_element_hidden( 'entry_excerpt' ) ) {
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
	$author_id   = get_query_var( 'author' );

	// Bail if author box is enabled.
	if ( get_the_author_meta( 'genesis_author_box_archive', $author_id ) ) {
		return;
	}

	$description = get_the_author_meta( 'description', $author_id );
	$description = apply_filters( 'mai_author_description', $description, $author_id );
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
 * @since 2.10.0
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

	// Get the author ID.
	$author_id = get_query_var( 'author' );

	// Bail if author box is not enabled.
	if ( ! get_the_author_meta( 'genesis_author_box_archive', $author_id ) ) {
		return;
	}

	echo mai_get_processed_content( genesis_get_author_box_by_user( $author_id ) );
}
