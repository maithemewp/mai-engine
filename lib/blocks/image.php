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

add_filter( 'render_block', 'mai_render_image_block', 10, 2 );
/**
 * Add lazy loading to image blocks.
 *
 * @since 1.0.0
 *
 * @param string $block_content Block HTML markup.
 * @param array  $block         Block data.
 *
 * @return string
 */
function mai_render_image_block( $block_content, $block ) {
	if ( ! $block_content ) {
		return $block_content;
	}

	if ( 'core/image' !== $block['blockName'] ) {
		return $block_content;
	}

	if ( apply_filters( 'mai_lazy_load_image_block', true, $block ) ) {
		$block_content = str_replace( '<img ', '<img loading="lazy" ', $block_content );
	}

	return $block_content;
}
