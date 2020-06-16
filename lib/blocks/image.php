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
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param $block_content
 * @param $block
 *
 * @return string
 */
function mai_render_image_block( $block_content, $block ) {

	// Bail if not an image block.
	if ( 'core/image' !== $block['blockName'] ) {
		return $block_content;
	}

	if ( apply_filters( 'mai_lazy_load_image_block', true ) ) {
		$block_content = str_replace( '<img ', '<img loading="lazy" ', $block_content );
	}

	return $block_content;
}
