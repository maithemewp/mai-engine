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

add_filter( 'render_block', 'mai_render_paragraph_block', 10, 2 );
/**
 * Remove empty paragraph block markup.
 *
 * For some reason, `' <p></p> ' === $block_content` doesn't work, so
 * instead we have to count the number of characters in the string.
 *
 * @since 2.0.1
 *
 * @param string $block_content Block HTML markup.
 * @param array  $block         Block data.
 *
 * @return string
 */
function mai_render_paragraph_block( $block_content, $block ) {
	if ( 'core/paragraph' === $block['blockName'] && 9 === strlen( $block_content ) ) {
		$block_content = '';
	}

	return $block_content;
}
