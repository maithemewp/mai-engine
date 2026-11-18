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

add_filter( 'genesis_pre_get_option_posts_nav', 'mai_genesis_posts_nav' );
/**
 * Uses mai-engine config/setting for posts_nav.
 *
 * @since 2.9.1
 *
 * @param string $value The existing option value.
 *
 * @return string
 */
function mai_genesis_posts_nav( $value ) {
	$args = mai_get_template_args();
	return mai_isset( $args, 'posts_nav', 'numeric' );
}

// Remove alignment classes.
remove_filter( 'genesis_attr_pagination-previous', 'genesis_adjacent_entry_attr_previous_post' );
remove_filter( 'genesis_attr_pagination-next', 'genesis_adjacent_entry_attr_next_post' );

add_filter( 'genesis_markup_archive-pagination_content', 'mai_archive_pagination_wrap', 10, 2 );
/**
 * Adds wrap to archive pagination.
 *
 * @since 0.2.0
 * @since 2.42.0 Close the wrap when open and close arrive in one call.
 *
 * @param string $content The existing content.
 * @param array  $args    The genesis_markup() element args.
 *
 * @return string
 */
function mai_archive_pagination_wrap( $content, $args ) {
	if ( isset( $args['open'] ) && $args['open'] ) {
		$template_args = mai_get_template_args();
		$type          = esc_html( mai_isset( $template_args, 'posts_nav', 'numeric' ) );
		$classes       = sprintf( 'wrap archive-pagination-wrap archive-pagination-%s-wrap', $type );
		$content       = sprintf( '<div class="%s">', $classes ) . $content;
	}

	if ( isset( $args['close'] ) && $args['close'] ) {
		$content .= '</div>';
	}

	return $content;
}

add_filter( 'genesis_attr_archive-pagination', 'mai_archive_pagination_type' );
/**
 * Adds archive pagination type class.
 *
 * @since 2.9.1
 *
 * @return array
 */
function mai_archive_pagination_type( $attributes ) {
	$args                 = mai_get_template_args();
	$type                 = esc_html( mai_isset( $args, 'posts_nav', 'numeric' ) );
	$attributes['class'] .= sprintf( ' archive-pagination-%s', $type );

	return $attributes;
}

add_action( 'genesis_after_endwhile', 'mai_posts_nav', 9 );
/**
 * Add classes to pagination elements.
 * We can't use `genesis_markup_archive-pagination_content` filter because
 * it uses open/close, so the whole nav is buffered and walked instead.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_posts_nav() {
	remove_action( 'genesis_after_endwhile', 'genesis_posts_nav' );

	ob_start();
	genesis_posts_nav();
	$pagination = ob_get_clean();

	if ( ! $pagination ) {
		return;
	}

	$args       = mai_get_template_args();
	$type       = esc_html( mai_isset( $args, 'posts_nav', 'numeric' ) );
	$pagination = str_replace( ' alignleft', '', $pagination );
	$pagination = str_replace( ' alignright', '', $pagination );
	echo mai_get_posts_nav_with_classes( $pagination, $type );
}

/**
 * Adds the button classes to the links in archive pagination markup.
 * Split out of mai_posts_nav() so it can run without Genesis.
 *
 * @since 2.42.0
 *
 * @access private
 *
 * @param string $pagination The pagination markup from Genesis.
 * @param string $type       The pagination type, numeric or prev-next.
 *
 * @return string
 */
function mai_get_posts_nav_with_classes( $pagination, $type ) {
	if ( ! in_array( $type, [ 'numeric', 'prev-next' ], true ) ) {
		return $pagination;
	}

	$tags      = new WP_HTML_Tag_Processor( $pagination );
	$numeric   = 'numeric' === $type;
	$in_li     = false;
	$li_active = false;

	// Closers are visited so the numeric walk knows when it leaves an li.
	while ( $tags->next_tag( [ 'tag_closers' => 'visit' ] ) ) {
		$name = $tags->get_tag();

		if ( 'LI' === $name ) {
			if ( $tags->is_tag_closer() ) {
				$in_li     = false;
				$li_active = false;
			} else {
				$class     = $tags->get_attribute( 'class' );
				$in_li     = true;
				$li_active = is_string( $class ) && mai_has_string( 'active', $class );
			}

			continue;
		}

		if ( 'A' !== $name || $tags->is_tag_closer() ) {
			continue;
		}

		// Genesis keeps every numeric link inside an li. Anything outside one is not ours.
		if ( $numeric && ! $in_li ) {
			continue;
		}

		$active  = $numeric ? $li_active : false;
		$classes = [ 'pagination-link', 'button', 'button-small' ];

		if ( ! $active ) {
			$classes[] = 'button-secondary';
		}

		$classes  = apply_filters( 'mai_archive_pagination_link_classes', $classes, $active, $type );
		$existing = $tags->get_attribute( 'class' );
		$tags->set_attribute( 'class', mai_add_classes( $classes, is_string( $existing ) ? $existing : '' ) );
	}

	return $tags->get_updated_html();
}

add_filter( 'genesis_markup_pagination-previous_content', 'mai_adjacent_entry', 10, 2 );
add_filter( 'genesis_markup_pagination-next_content', 'mai_adjacent_entry', 10, 2 );
/**
 * Adds classes to adjacent entry nav elements.
 *
 * @since 2.9.1
 *
 * @param string $content Content being passed through Markup API.
 * @param array  $args  Array with markup arguments.
 *
 * @return string
 */
function mai_adjacent_entry( $content, $args ) {
	if ( ! $content ) {
		return $content;
	}

	if ( ! is_singular() ) {
		return $content;
	}

	$content = str_replace( 'adjacent-post-link', 'adjacent-entry-link-inner', $content );
	$tags    = new WP_HTML_Tag_Processor( $content );

	if ( $tags->next_tag() ) {
		$tags->add_class( 'adjacent-entry-link' );
	}

	return $tags->get_updated_html();
}

add_filter( 'genesis_prev_link_text', 'mai_previous_page_link', 8 );
/**
 * Changes the previous page link text.
 *
 * @since 0.1.0
 *
 * @return string
 */
function mai_previous_page_link() {
	return esc_html__( 'Previous', 'mai-engine' );
}

add_filter( 'genesis_next_link_text', 'mai_next_page_link', 8 );
/**
 * Changes the next page link text.
 *
 * @since 0.1.0
 *
 * @return string
 */
function mai_next_page_link() {
	return esc_html__( 'Next', 'mai-engine' );
}

add_filter( 'previous_post_link', 'mai_adjacent_entry_link_thumbnail', 10, 5 );
add_filter( 'next_post_link', 'mai_adjacent_entry_link_thumbnail', 10, 5 );
/**
 * Add adjacent entry images.
 *
 * The dynamic portion of the hook name, `$adjacent`, refers to the type
 * of adjacency, 'next' or 'previous'.
 *
 * @since 0.1.0
 *
 * @param string $output   The adjacent post link.
 * @param string $format   Link anchor format.
 * @param string $link     Link permalink format.
 * @param WP_Post $post    The adjacent post.
 * @param string $adjacent Whether the post is previous or next.
 *
 * @return string|HTML  The post link and image HTML.
 */
function mai_adjacent_entry_link_thumbnail( $output, $format, $link, $post, $adjacent ) {
	$image      = '';
	$show_image = apply_filters( 'mai_show_adjacent_entry_image', true );

	if ( ! $show_image ) {
		return str_replace( '%image', $image, $output );
	}

	// Queried only once the filter has allowed it. Adjacent posts are in no cache, so this is
	// an uncached meta lookup on every singular request.
	$image_id = get_post_thumbnail_id( $post );

	if ( $image_id ) {
		add_filter( 'wp_calculate_image_srcset_meta', '__return_null' );
		$image = wp_get_attachment_image(
			$image_id,
			'tiny',
			false,
			[
				'class'   => 'adjacent-entry-image',
				'loading' => 'lazy',
			]
		);
		remove_filter( 'wp_calculate_image_srcset_meta', '__return_null' );
	}

	return str_replace( '%image', $image, $output );
}
