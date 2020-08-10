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

add_filter( 'render_block', 'mai_render_cover_block', 10, 2 );
/**
 * Convert cover block to inline image with custom srcset.
 * Changes inline styles to CSS custom properties for use in CSS.
 *
 * @since 0.1.0
 *
 * @param  string $block_content The existing block content.
 * @param  object $block         The cover block object.
 *
 * @return string
 */
function mai_render_cover_block( $block_content, $block ) {

	// Bail if not a cover block.
	if ( 'core/cover' !== $block['blockName'] ) {
		return $block_content;
	}

	// Return early if using parallax.
	if ( isset( $block['attrs']['hasParallax'] ) && $block['attrs']['hasParallax'] ) {
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
		$block_content = str_replace( sprintf( 'background-image:url(%s)', $image_url ), '', $block_content ); // Some cover blocks only have one inline style, so no semicolon.
	}

	// Convert inline style to css properties.
	$block_content = str_replace( 'background-position', '--object-position', $block_content );
	$block_content = str_replace( 'style=""', '', $block_content ); // Some scenarios will leave an empty style attribute.
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
 * @param string $block_content The existing block content.
 * @param mixed  $image_id      The cover block image ID or URL.
 *
 * @return string
 */
function mai_add_cover_block_image( $block_content, $image_id ) {
	// Get image HTML.
	$image_html = mai_get_cover_image_html( $image_id, [ 'class' => 'wp-cover-block__image' ] );

	// Bail if no image.
	if ( ! $image_html ) {
		return $block_content;
	}

	$dom = mai_get_dom_document( $block_content );

	/**
	 * The group block container.
	 *
	 * @var DOMElement $first_block The group block container.
	 */
	$first_block = mai_get_dom_first_child( $dom );

	if ( $first_block ) {
		// Build the HTML node.
		$fragment = $dom->createDocumentFragment();
		$fragment->appendXml( $image_html );

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$first_block->insertBefore( $fragment, $first_block->firstChild );

		$block_content = $dom->saveHTML();
	}

	return $block_content;
}
