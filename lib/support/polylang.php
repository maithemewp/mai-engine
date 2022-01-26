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

add_filter( 'register_post_type_args', 'mai_polylang_create_template_parts', 8, 2 );
/**
 * Allows users to create new template parts.
 *
 * @since 2.19.0
 *
 * @param array  $args      Array of arguments for registering a post type. See the register_post_type() function for accepted arguments.
 * @param string $post_type Post type key.
 *
 * @return array
 */
function mai_polylang_create_template_parts( $args, $post_type ) {
	if ( 'mai_template_part' === $post_type ) {
		$args['capabilities']['create_posts'] = 'edit_posts';
	}

	return $args;
}

add_filter( 'pll_get_post_types', 'mai_polylang_post_types' );
/**
 * Adds content areas as a translateable post type.
 *
 * @uses Polylang
 *
 * @since 2.16.0
 *
 * @param array $post_types The translateable post types.
 *
 * @return array
 */
function mai_polylang_post_types( $post_types ) {
	$post_types['mai_template_part'] = 'mai_template_part';
	return $post_types;
}

add_filter( 'mai_template_part_objects', 'mai_polylang_template_part_objects' );
/**
 * Gets translated content areas.
 *
 * @uses Polylang
 *
 * @since 2.16.0
 *
 * @param array $template_parts The content areas objects.
 *
 * @return array
 */
function mai_polylang_template_part_objects( $template_parts ) {

	if ( ! $template_parts ) {
		return $template_parts;
	}

	foreach ( $template_parts as $slug => $post ) {
		$translated = pll_get_post( $post->ID );

		if ( ! ( $translated && $translated !== $post->ID ) ) {
			continue;
		}

		$template_parts[ $slug ] = get_post( $translated );
	}

	return $template_parts;
}
