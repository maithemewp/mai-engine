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
	if ( ! $block_content ) {
		return $block_content;
	}

	// Bail if not a cover block.
	if ( 'core/cover' !== $block['blockName'] ) {
		return $block_content;
	}

	$align    = mai_isset( $block['attrs'], 'contentAlign', false );
	$image_id = mai_isset( $block['attrs'], 'id', false );
	// $parallax = mai_isset( $block['attrs'], 'hasParallax', false );
	// $repeated = mai_isset( $block['attrs'], 'isRepeated', false );

	if ( ! ( $align || $image_id ) ) {
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

		if ( $align ) {
			$style .= sprintf( '--cover-block-justify-content:%s;', mai_get_flex_align( $align ) );
		}

		if ( $image_id ) {
			// Strip background-image inline CSS.
			$style = str_replace( sprintf( 'background-image:', '--background-image:' ), '', $style ); // With semicolon.

			/**
			 * The dom xpath.
			 *
			 * @var DOMXPath $xpath
			 */
			$xpath  = new DOMXPath( $dom );
			$images = $xpath->query( '//img[contains(concat(" ", @class, " "), " wp-block-cover__image-background ")]', $first_block );

			// Convert inline style to custom property.
			if ( $images ) {
				foreach ( $images as $image ) {
					$image_style = $image->getAttribute( 'style' );
					$image_style = str_replace( 'object-position:', '--object-position:', $image_style );
					$image->setAttribute( 'style', $image_style );
				}
			}

			// Responsive background image custom properties.
			if ( mai_isset( $block['attrs'], 'hasParallax', false ) ) {
				$available = mai_get_available_image_sizes();
				$sizes     = [
					'lg' => wp_get_attachment_image_url( $image_id, isset( $available['2048x2048'] ) ? '2048x2048' : 'large' ),
					'md' => wp_get_attachment_image_url( $image_id, isset( $available['1536x1536'] ) ? '1536x1536' : 'large' ),
					'sm' => wp_get_attachment_image_url( $image_id, 'large' ),
				];

				foreach ( $sizes as $size => $url ) {
					$style = sprintf( '--background-image-%s:url(%s);', $size, $url ) . $style;
				}
			}
		}

		$first_block->setAttribute( 'style', $style );

		$block_content = $dom->saveHTML();
	}

	return $block_content;
}
