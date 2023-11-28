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

add_filter( 'render_block', 'mai_render_block_handle_link_color', 10, 2 );
/**
 * Fixes WP 6.4 conflict with link color class.
 *
 * @since TBD
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

	// Get text color setting.
	$text = mai_isset( $block['attrs'], 'textColor', false );

	if ( 'link' !== $text ) {
		return $block_content;
	}

	// Find first instance of has-link-color and add has-links-color.
	$tags = new WP_HTML_Tag_Processor( $block_content );

	while ( $tags->next_tag( [ 'class_name' => 'has-link-color' ] ) ) {
		$class  = $tags->get_attribute( 'class' );
		$class .= ' has-links-color';
		$tags->set_attribute( 'class', $class );
		break;
	}

	return $tags->get_updated_html();
}