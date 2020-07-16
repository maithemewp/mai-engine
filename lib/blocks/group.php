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
	if ( 'core/group' !== $block['blockName'] ) {
		return $block_content;
	}

	if ( isset( $block['attrs']['backgroundColor'] ) ) {
		$light_or_dark = mai_is_light_color( $block['attrs']['backgroundColor'] ) ? 'light' : 'dark';
		$block_content = str_replace( ' has-background ', " has-$light_or_dark-background has-background ", $block_content );
	} elseif ( isset( $block['attrs']['customBackgroundColor'] ) ) {
		$light_or_dark = mai_is_light_color( $block['attrs']['customBackgroundColor'] ) ? 'light' : 'dark';
		$block_content = str_replace( ' has-background ', " has-$light_or_dark-background has-background ", $block_content );
	}

	return $block_content;
}
