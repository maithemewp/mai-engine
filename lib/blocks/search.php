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

add_filter( 'render_block', 'mai_render_search_block', 10, 2 );
/**
 * Add our button classes to the search button.
 *
 * @since  2.6.0
 *
 * @param  string $block_content The existing block content.
 * @param  object $block         The button block object.
 *
 * @return string The modified block HTML.
 */
function mai_render_search_block( $block_content, $block ) {
	if ( ! $block_content ) {
		return $block_content;
	}

	// Bail if not a search block.
	if ( 'core/search' !== $block['blockName'] ) {
		return $block_content;
	}

	$block_content = str_replace( 'wp-block-search__button', 'wp-block-search__button button-secondary', $block_content );

	return $block_content;
}
