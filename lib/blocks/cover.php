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

	$align         = mai_isset( $block['attrs'], 'align', false );
	$content_align = mai_isset( $block['attrs'], 'contentAlign', false );
	$opacity       = mai_isset( $block['attrs'], 'dimRatio', false );
	$image_id      = mai_isset( $block['attrs'], 'id', false );
	$image_url     = mai_isset( $block['attrs'], 'url', false );
	$parallax      = mai_isset( $block['attrs'], 'hasParallax', false );
	$repeated      = mai_isset( $block['attrs'], 'isRepeated', false );

	$image_id = apply_filters( 'mai_cover_block_image_id', $image_id, $block );

	if ( ! ( $content_align || $opacity || ( $image_id && $image_url ) ) ) {
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

		if ( $content_align ) {
			$style = sprintf( '--cover-block-justify-content:%s;', mai_get_flex_align( $content_align ) ) . $style;
		}

		if ( $opacity ) {
			/**
			 * The dom xpath.
			 *
			 * @var DOMXPath $xpath
			 */
			$xpath    = new DOMXPath( $dom );
			$overlays = $xpath->query( '//span[contains(concat(" ", @class, " "), " wp-block-cover__gradient-background ")]', $first_block );

			if ( $overlays->length ) {
				foreach ( $overlays as $overlay ) {
					$setclass = false;
					$opacity  = (int) round( $opacity, -1, PHP_ROUND_HALF_UP ); // Round to 10.
					$dim      = 'has-background-dim';
					$amount   = sprintf( 'has-background-dim-%s', $opacity );
					$classes  = $overlay->getAttribute( 'class' );
					$array    = explode( ' ', $classes );

					/**
					 * Older instances of Cover block were not using the opacity setting for some reason.
					 * This happened in WP 5.9 and/or engine 2.19.0.
					 * This is a fix so the front end doesn't break.
					 * Idk what changed or why this broke, but it's super frustrating
					 * to have to do this.
					 */

					if ( ! in_array( $dim, $array ) ) {
						$classes  = mai_add_classes( $dim, $classes );
						$setclass = true;
					}

					if ( ! in_array( $amount, $array ) ) {
						$classes = mai_add_classes( $amount, $classes );
						$setclass = true;
					}

					if ( $setclass ) {
						$overlay->setAttribute( 'class', $classes );
					}
				}
			}
		}

		if ( $image_id ) {
			/**
			 * The dom xpath.
			 *
			 * @var DOMXPath $xpath
			 */
			$xpath      = new DOMXPath( $dom );
			$images     = $xpath->query( '//img[contains(concat(" ", @class, " "), " wp-block-cover__image-background ")]', $first_block );
			$image_size = mai_get_cover_image_size();

			if ( $images->length ) {
				foreach ( $images as $image ) {
					$new_url = wp_get_attachment_image_url( $image_id, $image_size ); // New url, using our image size.

					// Replace src.
					$src = $image->getAttribute( 'src' );
					$src = str_replace( $image_url, $new_url, $src );
					$image->setAttribute( 'src', $src );

					// Replace srcset.
					$srcset = $image->getAttribute( 'srcset' );

					// Not sure why, but this doesn't always show srcet and breaks. See #515.
					if ( $srcset ) {
						$srcset = str_replace( $image_url, $new_url, $srcset );
						$image->setAttribute( 'srcset', $srcset );
					}

					// Make sure sizes is full width now that src is showing the smallest size.
					if ( in_array( $align, [ 'full', 'wide' ] ) ) {
						$image->setAttribute( 'sizes', '100vw' );
					}
					// Full width on mobile, half width other.
					else {
						$image->setAttribute( 'sizes', '(max-width: 600px) 100vw, 500vw' );
					}

					// Convert inline style to custom property.
					$image_style = $image->getAttribute( 'style' );
					$image_style = str_replace( 'object-position:', '--object-position:', $image_style );
					$image->setAttribute( 'style', $image_style );
				}
			}
			// No inline image. This is a fallback for existing blocks < WP 5.7.
			elseif ( ! ( $parallax || $repeated ) && mai_has_string( $image_url, $block_content ) ) {
				// Disable background-image inline CSS.
				$before = sprintf( 'background-image:url(%s)', $image_url );
				$style  = str_replace( $before . ':', '', $style ); // With semicolon.
				$style  = str_replace( $before, '', $style );       // Some cover blocks only had one inline style, so no semicolon.

				// Build new image.
				$image_html = wp_get_attachment_image( $image_id, $image_size, false, [ 'class' => 'wp-block-cover__image-background' ] );

				if ( $image_html ) {
					// Build the HTML node.
					$fragment = $dom->createDocumentFragment();
					$fragment->appendXml( $image_html );

					// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$first_block->insertBefore( $fragment, $first_block->firstChild );
				}
			}

			// Responsive background image custom properties.
			if ( $parallax || $repeated ) {
				$sizes     = [
					'lg' => wp_get_attachment_image_url( $image_id, $image_size ),
					'md' => wp_get_attachment_image_url( $image_id, $image_size ),
					'sm' => wp_get_attachment_image_url( $image_id, 'large' ),
				];

				foreach ( $sizes as $size => $url ) {
					$style = sprintf( '--background-image-%s:url(%s);', $size, $url ) . $style;
				}
			}
		}

		if ( $style ) {
			$first_block->setAttribute( 'style', $style );
		} else {
			$first_block->removeAttribute( 'style' );
		}

		$block_content = $dom->saveHTML( $dom->documentElement );
	}

	return $block_content;
}
