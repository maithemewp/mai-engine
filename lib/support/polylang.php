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

add_filter( 'pll_get_post_types', 'mai_polylang_post_types' );
/**
 * Adds template parts as a translateable post type.
 *
 * @uses Polylang
 *
 * @since TBD
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
 * Gets translated template parts.
 *
 * @uses Polylang
 *
 * @since TBD
 *
 * @param array $template_parts The template parts objects.
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
