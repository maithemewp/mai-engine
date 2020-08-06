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

add_filter( 'post_class', 'mai_remove_post_classes', 10, 3 );
/**
 * Remove unnecessary post classes.
 *
 * @since 2.3.0
 *
 * @param string[] $classes A string of post class names.
 * @param string[] $class   An array of additional class names added to the post.
 * @param int      $post_id The post ID.
 *
 * @return array
 */
function mai_remove_post_classes( $classes, $class, $post_id ) {
	return array_diff(
		$classes,
		[
			'post',
			'has-post-thumbnail',
			'category-uncategorized',
			'post-' . $post_id,
			'type-' . get_post_type( $post_id ),
			'status-' . get_post_status( $post_id ),
			'format-' . ( get_post_format( $post_id ) ? get_post_format( $post_id ) : 'standard' ),
		]
	);
}
