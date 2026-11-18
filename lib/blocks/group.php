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
		$tags = new WP_HTML_Tag_Processor( $block_content );

		// Without a tag there is nothing to change, so the input comes back untouched.
		if ( $tags->next_tag() ) {
			if ( $align ) {
				$style = $tags->get_attribute( 'style' );
				$style = is_string( $style ) ? $style : '';
				$tags->set_attribute( 'style', sprintf( '--group-block-justify-content:%s;', mai_get_flex_align( $align ) ) . $style );
			}

			if ( $light_or_dark ) {
				$tags->add_class( sprintf( 'has-%s-background', $light_or_dark ) );
			}

			$block_content = $tags->get_updated_html();
		}
	}

	return $block_content;
}
