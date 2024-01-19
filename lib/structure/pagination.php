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
 *
 * @param string $content The existing content.
 * @param array  $args    The genesis_markup() element args.
 *
 * @return string
 */
function mai_archive_pagination_wrap( $content, $args ) {
	if ( isset( $args['open'] ) && $args['open'] ) {
		$args    = mai_get_template_args();
		$type    = esc_html( mai_isset( $args, 'posts_nav', 'numeric' ) );
		$classes = sprintf( 'wrap archive-pagination-wrap archive-pagination-%s-wrap', $type );
		$content = sprintf( '<div class="%s">', $classes ) . $content;
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
 * it uses open/close and would be too confusing with DOMDocoment.
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
	$dom        = mai_get_dom_document( $pagination );

	/**
	 * The pagination wrap.
	 *
	 * @var DOMElement $wrap The group block wrap.
	 */
	$wrap = mai_get_dom_first_child( $dom );

	if ( $wrap ) {
		$classes = $wrap->getAttribute( 'class' );
		$wrap->setAttribute( 'class', $classes );

		if ( 'numeric' === $type ) {
			$lis = $wrap->getElementsByTagName( 'li' );

			if ( $lis->length ) {
				foreach ( $lis as $li ) {
					$active = mai_has_string( 'active', $li->getAttribute( 'class' ) );
					$links  = $li->getElementsByTagName( 'a' );

					if ( $links ) {
						foreach ( $links as $link ) {
							$classes     = $link->getAttribute( 'class' );
							$new_classes = [
								'pagination-link',
								'button',
								'button-small',
							];
							if ( ! $active ) {
								$new_classes[] = 'button-secondary';

							}
							$new_classes = apply_filters( 'mai_archive_pagination_link_classes', $new_classes, $active, $type );
							$classes     = mai_add_classes( $new_classes, $classes );
							$link->setAttribute( 'class', $classes );
						}
					}
				}
			}
		} elseif ( 'prev-next' === $type ) {
			$active = false;
			$links  = $wrap->getElementsByTagName( 'a' );

			if ( $links->length ) {
				foreach ( $links as $link ) {
					$classes     = $link->getAttribute( 'class' );
					$new_classes = [
						'pagination-link',
						'button',
						'button-small',
						'button-secondary',
					];
					$new_classes = apply_filters( 'mai_archive_pagination_link_classes', $new_classes, $active, $type );
					$classes     = mai_add_classes( $new_classes, $classes );
					$link->setAttribute( 'class', $classes );
				}
			}
		}

		$pagination = mai_get_dom_html( $dom );
	}

	echo $pagination;
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
	$dom     = mai_get_dom_document( $content );

	/**
	 * The pagination link.
	 *
	 * @var DOMElement $link The group block link.
	 */
	$link = mai_get_dom_first_child( $dom );

	if ( $link ) {
		$classes = $link->getAttribute( 'class' );
		$classes = mai_add_classes( 'adjacent-entry-link', $classes );
		$link->setAttribute( 'class', $classes );
	}

	$content = mai_get_dom_html( $dom );

	return $content;
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
	$image_id   = get_post_thumbnail_id( $post );

	if ( $show_image && $image_id ) {
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

	$image = $show_image && $image ? $image : '';

	return str_replace( '%image', $image, $output );
}
