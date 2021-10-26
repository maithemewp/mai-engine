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

add_filter( 'mai_template_part_objects', 'mai_wpml_template_part_objects' );
/**
 * Gets translated content areas.
 *
 * @uses WPML
 *
 * @since 2.16.0
 *
 * @param array $template_parts The content areas objects.
 *
 * @return array
 */
function mai_wpml_template_part_objects( $template_parts ) {
	if ( ! $template_parts ) {
		return $template_parts;
	}

	foreach ( $template_parts as $slug => $post ) {
		$translated = apply_filters( 'wpml_object_id', $post->ID, $post->post_type );

		if ( ! ( $translated && $translated !== $post->ID ) ) {
			continue;
		}

		$template_parts[ $slug ] = get_post( $translated );
	}

	return $template_parts;
}
