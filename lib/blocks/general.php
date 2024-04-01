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

add_filter( 'acf/register_block_type_args', 'mai_acf_register_block_type_args' );
/**
 * Makes sure all mai blocks are using the v2 API.
 * This insures blocks registered via Mai plugins use the current API.
 *
 * @since 2.25.0
 *
 * @param array $args The block args.
 *
 * @return array
 */
function mai_acf_register_block_type_args( $args ) {
	if ( mai_has_string( 'acf/mai-', $args['name'] ) ) {
		$args['acf_block_version'] = 2;
	}

	return $args;
}

add_filter( 'acf/blocks/wrap_frontend_innerblocks', 'mai_acf_remove_wrap_frontend_innerblocks', 10, 2 );
/**
 * Removes innerblocks wrap from ACF.
 * This allows us to update Mai blocks from other plugins to the v2 API from here.
 *
 * @since 2.25.0
 *
 * @param bool   $wrap Whether to include the wrapping element on the front end.
 * @param string $name The registered block name.
 *
 * @return bool
 */
function mai_acf_remove_wrap_frontend_innerblocks( $wrap, $name ) {
	if ( mai_has_string( 'acf/mai-', $name ) ) {
		return false;
	}

	return $wrap;
}

add_filter( 'render_block_data', 'mai_render_block_data_handle_link_color', 10, 3 );
/**
 * Removes inline styles from blocks.
 *
 * @since 2.32.0
 *
 * @param array         $parsed_block The block being rendered.
 * @param array         $source_block An un-modified copy of $parsed_block, as it appeared in the source content.
 * @param WP_Block|null $parent_block If this is a nested block, a reference to the parent block.
 *
 * @return array
 */
function mai_render_block_data_handle_link_color( $parsed_block, $source_block, $parent_block ) {
	// Remove link colors from inline styles.
	if ( isset( $parsed_block['attrs']['style']['elements']['link']['color'] ) ) {
		unset( $parsed_block['attrs']['style']['elements']['link']['color'] );
	}

	return $parsed_block;
}

add_filter( 'render_block', 'mai_render_block_handle_link_color', 10, 2 );
/**
 * Fixes WP 6.4 conflict with link color class.
 *
 * @since 2.32.0
 *
 * @param string $block_content The existing block content.
 * @param array  $block         The button block object.
 *
 * @return string
 */
function mai_render_block_handle_link_color( $block_content, $block ) {
	if ( ! $block_content ) {
		return $block_content;
	}

	// Find marks with has-link-color and replace with has-links-color.
	$mark = false;
	$tags = new WP_HTML_Tag_Processor( $block_content );

	// Handle mark color.
	while ( $tags->next_tag( [ 'tag_name' => 'mark', 'class_name' => 'has-link-color' ] ) ) {
		// Get array of classes, add new, and flip.
		$class   = explode( ' ', $tags->get_attribute( 'class' ) );
		$class[] = 'has-links-color';
		$class   = array_flip( $class );

		// Remove link color class.
		unset( $class['has-link-color'] );

		$tags->set_attribute( 'class', implode( ' ', array_flip( $class ) ) );
		$mark = true;
		break;
	}

	// If we have a mark, update the content.
	if ( $mark ) {
		$block_content = $tags->get_updated_html();
	}

	// Get color settings.
	$text    = mai_isset( $block['attrs'], 'textColor', false );
	$bg      = mai_isset( $block['attrs'], 'backgroundColor', false );
	$overlay = mai_isset( $block['attrs'], 'overlayColor', false );

	// Bail if no link colors.
	if ( ! in_array( 'link', [ $text, $bg, $overlay ], true ) ) {
		return $block_content;
	}

	// Find first instance of has-link-color and replace with has-links-color.
	$tags = new WP_HTML_Tag_Processor( $block_content );

	// Handle text color.
	if ( 'link' === $text ) {
		while ( $tags->next_tag( [ 'class_name' => 'has-link-color' ] ) ) {
			// Get array of classes, add new, and flip.
			$class   = explode( ' ', $tags->get_attribute( 'class' ) );
			$class[] = 'has-links-color';
			$class   = array_flip( $class );

			// Remove link color class.
			unset( $class['has-link-color'] );

			$tags->set_attribute( 'class', implode( ' ', array_flip( $class ) ) );
			break;
		}
	}

	// Handle background and overlay color.
	if ( 'link' === $bg || 'link' === $overlay ) {
		// Find first instance of has-link-background-color and replace with has-links-background-color.
		$tags = new WP_HTML_Tag_Processor( $block_content );

		while ( $tags->next_tag( [ 'class_name' => 'has-link-background-color' ] ) ) {
			// Get array of classes, add new, and flip.
			$class   = explode( ' ', $tags->get_attribute( 'class' ) );
			$class[] = 'has-links-background-color';
			$class   = array_flip( $class );

			// Remove link background color class.
			unset( $class['has-link-background-color'] );

			$tags->set_attribute( 'class', implode( ' ', array_flip( $class ) ) );
			break;
		}
	}

	return $tags->get_updated_html();
}