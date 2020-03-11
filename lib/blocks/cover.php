<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2019 BizBudding
 * @license   GPL-2.0-or-later
 */

add_filter( 'render_block', 'mai_render_cover_block', 10, 2 );
/**
 * Convert cover block to inline image with custom srcset.
 * Changes inline styles to CSS custom properties for use in CSS.
 *
 * @param   string $block_content The existing block content.
 * @param   object $block         The cover block object.
 *
 * @return  string|HTML  The modified block HTML.
 */
function mai_render_cover_block( $block_content, $block ) {

	// Bail if not a cover block.
	if ( 'core/cover' !== $block['blockName'] ) {
		return $block_content;
	}

	// Get the image ID.
	$image_id = isset( $block['attrs']['id'] ) ? $block['attrs']['id'] : false;

	// Bail if no image ID.
	if ( ! $image_id ) {
		return $block_content;
	}

	// Get the image URL.
	$image_url = isset( $block['attrs']['url'] ) ? $block['attrs']['url'] : false;

	// Strip background-image inline CSS.
	if ( $image_url ) {
		$block_content = str_replace( sprintf( 'background-image:url(%s);', $image_url ), '', $block_content );
	}

	// Convert inline style to css properties.
	$block_content = str_replace( 'background-position', '--object-position', $block_content );
	$block_content = mai_add_cover_block_image( $block_content, $image_id );

	return $block_content;
}

/**
 * Add cover block image as inline element,
 * instead of using a background-image inline style.
 * Adds custom srcset to the image element.
 *
 * TODO: Use <figure>?
 *
 * @since 0.1.0
 *
 * @param   string $block_content The existing block content.
 * @param   int    $image_id      The cover block image ID.
 *
 * @return  string|HTML  The modified block HTML.
 */
function mai_add_cover_block_image( $block_content, $image_id ) {

	// Create the new document.
	$dom = new DOMDocument;

	// Modify state.
	$libxml_previous_state = libxml_use_internal_errors( true );

	// Load the content in the document HTML.
	$dom->loadHTML( mb_convert_encoding( $block_content, 'HTML-ENTITIES', "UTF-8" ) );

	// Handle errors.
	libxml_clear_errors();

	// Restore.
	libxml_use_internal_errors( $libxml_previous_state );

	// Get image HTML.
	$image_html = mai_get_cover_image_html( $image_id, [ 'class' => 'wp-cover-block__image' ] );

	// Bail if no image.
	if ( ! $image_html ) {
		return $block_content;
	}

	// Get cover blocks by class. Checks if class contains `wp-block-cover` but not as part of another class, like `wp-block-cover__inner-container`.
	$xpath        = new DOMXPath( $dom );
	$cover_blocks = $xpath->query( "//div[contains(concat(' ', normalize-space(@class), ' '), ' wp-block-cover ')]" );

	// Bail if none.
	if ( ! $cover_blocks->length ) {
		return $block_content;
	}

	// Loop through, though we know it's one.
	foreach ( $cover_blocks as $block ) {

		// Build the HTML node.
		$fragment = $dom->createDocumentFragment();
		$fragment->appendXml( $image_html );

		// Add it to the beginning.
		$block->insertBefore( $fragment, $block->firstChild );
	}

	// Save the new content.
	return $dom->saveHTML();
}
