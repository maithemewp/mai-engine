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

add_filter( 'render_block', 'mai_recipe_maker_handle_blocks', 10, 2 );
/**
 * Adds button classes to WP Recipe Maker blocks.
 * Removes inline styles.
 *
 * @since 2.34.0
 *
 * @param string $block_content The existing block content.
 * @param object $block         The button block object.
 *
 * @return string The modified block HTML.
 */
function mai_recipe_maker_handle_blocks( $block_content, $block ) {
	if ( ! isset( $block['blockName'] ) || empty( $block['blockName'] ) || ! str_starts_with( $block['blockName'], 'wp-recipe-maker/' ) ) {
		return $block_content;
	}

	// Convert buttons.
	$block_content = mai_wprm_convert_buttons( $block_content);

	return $block_content;
}

add_filter( 'do_shortcode_tag', 'mai_recipe_maker_handle_shortcodes', 10, 2 );
/**
 * Adds button classes to WP Recipe Maker shortcodes.
 * Removes inline styles.
 *
 * @since 2.32.0
 *
 * @param string $output The output from the shortcode.
 * @param string $tag    The name of the shortcode.
 *
 * @return string The modified output.
 */
function mai_recipe_maker_handle_shortcodes( $output, $tag ) {
	if ( ! $output ) {
		return $output;
	}

	if ( ! str_starts_with( $tag, 'wprm-' ) ) {
		return $output;
	}

	// Convert buttons.
	$output = mai_wprm_convert_buttons( $output );

	return $output;
}

/**
 * Convert WP Recipe Maker buttons to use Mai Engine button classes, and convert svgs to use currentColor.
 *
 * @access private
 *
 * @since 2.34.0
 *
 * @param string $html The existing content.
 *
 * @return string The modified HTML.
 */
function mai_wprm_convert_buttons( $html ) {
	// Set up tag processor.
	$tags = new WP_HTML_Tag_Processor( $html );

	// Loop through all buttons.
	while ( $tags->next_tag( [ 'tag_name' => 'a' ] ) ) {
		// If not a button.
		if ( ! ( $tags->get_attribute( 'class' ) && str_contains( $tags->get_attribute( 'class' ), '-button' ) ) ) {
			continue;
		}

		// Remove styles and force button color. Chic template CSS was overriding this.
		$tags->set_attribute( 'style', 'color:var(--button-color);' );

		// Add new classes.
		$tags->add_class( 'button' );
		$tags->add_class( 'button-secondary' );
		$tags->add_class( 'button-small' );

		// Remove classes.
		$tags->remove_class( 'wprm-block-text-normal' );
		$tags->remove_class( 'wprm-color-accent' );
		$tags->remove_class( 'wprm-recipe-link' );
		$tags->remove_class( 'wprm-recipe-link-inline-button' );
	}

	// Update the html.
	$html = $tags->get_updated_html();

	// Bail if no svgs.
	if ( ! str_contains( $html, '</svg>' ) ) {
		return $html;
	}

	// Set vars.
	$dom   = mai_get_dom_document( $html );
	$svgs  = [];
	$nodes = $dom->getElementsByTagName( 'a' );

	// Loop through nodes.
	foreach ( $nodes as $node ) {
		// Get the class attribute.
		$class = $node->getAttribute( 'class' );

		// Skip if no classes, or classes don't contain 'button'.
		if ( ! ( $class && str_contains( $class, 'button' ) ) ) {
			continue;
		}

		// Get all svgs inside the current node.
		$svgs = $node->getElementsByTagName( 'svg' );

		// Loop through each svgs.
		foreach ( $svgs as $svg ) {
			mai_wprm_dom_convert_svgs( $svg );
		}
	}

	return mai_get_dom_html( $dom );
}

/**
 * Recursively convert svgs to use currentColor.
 *
 * @access private
 *
 * @since 2.34.0
 *
 * @param DOMElement $node
 *
 * @return void
 */
function mai_wprm_dom_convert_svgs( $node ) {
	// Skip if not a DOMElement.
	if ( ! $node instanceof DOMElement ) {
		return;
	}

	// Get fill.
	$fill = $node->getAttribute( 'fill' );

	if ( $fill && 'none' !== $fill ) {
		$node->setAttribute( 'fill', 'currentColor' );
	}

	// Recursively call this function for each child node.
	foreach ( $node->childNodes as $child ) {
		mai_wprm_dom_convert_svgs( $child );
	}
}
