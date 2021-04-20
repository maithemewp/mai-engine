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

add_filter( 'render_block', 'mai_render_group_block', 10, 2 );
/**
 * Customizes group block HTML output.
 *
 * @since 2.0.1
 *
 * @param string $block_content The existing block content.
 * @param array  $block         The button block object.
 *
 * @return string
 */
function mai_render_group_block( $block_content, $block ) {
	if ( ! $block_content ) {
		return $block_content;
	}

	if ( 'core/group' !== $block['blockName'] ) {
		return $block_content;
	}

	$align     = mai_isset( $block['attrs'], 'contentAlign', false );
	$bg        = mai_isset( $block['attrs'], 'backgroundColor', false );
	$custom_bg = mai_isset( $block['attrs'], 'customBackgroundColor', false );

	if ( ! ( $align || $bg || $custom_bg ) ) {
		return $block_content;
	}

	$light_or_dark = false;

	if ( $bg ) {
		$light_or_dark = mai_is_light_color( $bg ) ? 'light' : 'dark';
	} elseif ( $custom_bg ) {
		$light_or_dark = mai_is_light_color( $custom_bg ) ? 'light' : 'dark';
	}

	if ( $align || $light_or_dark ) {
		$dom = mai_get_dom_document( $block_content );

		/**
		 * The group block container.
		 *
		 * @var DOMElement $first_block The group block container.
		 */
		$first_block = mai_get_dom_first_child( $dom );

		if ( $first_block ) {

			if ( $align ) {
				$style = $first_block->getAttribute( 'style' );
				$style = sprintf( '--group-block-justify-content:%s;', mai_get_flex_align( $align ) ) . $style;
				$first_block->setAttribute( 'style', $style );
			}

			$classes = $first_block->getAttribute( 'class' );
			$classes = mai_add_classes( sprintf( 'has-%s-background', $light_or_dark ), $classes );
			$first_block->setAttribute( 'class', $classes );

			$block_content = $dom->saveHTML();
		}
	}

	return $block_content;
}
