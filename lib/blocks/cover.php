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
 * Convert non fixed background cover block to inline image with custom srcset.
 * Convert fixed background to responsive image sizes.
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

	$image_id  = mai_isset( $block['attrs'], 'id', false );
	$image_url = mai_isset( $block['attrs'], 'url', false );

	if ( ! ( $image_id && $image_url ) ) {
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

		$style = $first_block->getAttribute( 'style' );

		// Strip background-image inline CSS.
		$style = str_replace( sprintf( 'background-image:url(%s);', $image_url ), '', $style ); // With semicolon.
		$style = str_replace( sprintf( 'background-image:url(%s)', $image_url ) , '', $style ); // Some cover blocks only have one inline style, so no semicolon.

		if ( mai_isset( $block['attrs'], 'hasParallax', false ) ) {

			$sizes = [
				'lg' => wp_get_attachment_image_url( $image_id, 'cover' ),
				'md' => wp_get_attachment_image_url( $image_id, 'landscape-lg' ),
				'sm' => wp_get_attachment_image_url( $image_id, 'landscape-md' ),
			];

			foreach ( $sizes as $size => $url ) {
				$style = sprintf( '--background-image-%s:url(%s);', $size, $url ) . $style;
			}

		} else {

			// Convert inline style to css properties.
			$style = str_replace( 'background-position', '--object-position', $style );
			$style = str_replace( 'style=""', '', $style ); // Some scenarios will leave an empty style attribute.

			// Check alignment.
			$align = mai_isset( $block['attrs'], 'align', '' );
			$full  = $align && in_array( $align, [ 'full', 'wide' ] );

			// Get image HTML.
			$image_html = mai_get_cover_image_html( $image_id,
				[
					'class' => 'wp-cover-block__image',
				],
				$full ? '100vw' : mai_get_breakpoint( 'xl' )
			);

			if ( $image_html ) {

				// Build the HTML node.
				$fragment = $dom->createDocumentFragment();
				$fragment->appendXml( $image_html );

				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$first_block->insertBefore( $fragment, $first_block->firstChild );
			}
		}

		$first_block->setAttribute( 'style', $style );

		$block_content = $dom->saveHTML();
	}

	return $block_content;
}
