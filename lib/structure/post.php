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
 * @since 2.5.0 Removed type-{post_type} from here. We often need that for styling.
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
			'status-' . get_post_status( $post_id ),
			'format-' . ( get_post_format( $post_id ) ? get_post_format( $post_id ) : 'standard' ),
		]
	);
}

add_filter( 'genesis_attr_entry-meta-before-content', 'mai_entry_meta_before_content_atts' );
/**
 * Adds before content class to entry meta.
 *
 * @since 0.1.0
 *
 * @param array $atts Element attributes.
 *
 * @return array
 */
function mai_entry_meta_before_content_atts( $atts ) {
	$atts['class'] .= ' entry-meta-before-content';

	return $atts;
}

add_filter( 'genesis_attr_entry-meta-after-content', 'mai_entry_meta_after_content_atts' );
/**
 * Adds after content class to entry meta.
 *
 * @since 0.1.0
 *
 * @param array $atts Element attributes.
 *
 * @return array
 */
function mai_entry_meta_after_content_atts( $atts ) {
	$atts['class'] .= ' entry-meta-after-content';

	return $atts;
}
