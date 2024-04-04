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

	// Set up tag processor.
	$tags = new WP_HTML_Tag_Processor( $block_content );

	// Loop through tags.
	while ( $tags->next_tag( [ 'tag_name' => 'div', 'class_name' => 'wp-block-search__inside-wrapper' ] ) ) {
		$style = (string) $tags->get_attribute( 'style' );

		if ( $style ) {
			$style = str_replace( 'width', '--search-max-width', $style );
			$tags->set_attribute( 'style', $style );
		}
	}

	// Save the updated HTML.
	$block_content = $tags->get_updated_html();

	// Set up tag processor.
	$tags = new WP_HTML_Tag_Processor( $block_content );

	// Loop through tags.
	while ( $tags->next_tag( [ 'tag_name' => 'button', 'class_name' => 'wp-block-search__button' ] ) ) {
		$class = $tags->add_class( 'button button-secondary button-small' );
	}

	// Save the updated HTML.
	$block_content = $tags->get_updated_html();

	return $block_content;
}
