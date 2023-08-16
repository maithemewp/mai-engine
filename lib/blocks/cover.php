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
 * @since 2.30.0 Converted to `WP_HTML_Tag_Processor`.
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

	// Bail if tag processor is not available, from older WP.
	if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
		return $block_content;
	}

	$align         = mai_isset( $block['attrs'], 'align', false );
	$content_align = mai_isset( $block['attrs'], 'contentAlign', false );
	$opacity       = mai_isset( $block['attrs'], 'dimRatio', false );
	$image_id      = mai_isset( $block['attrs'], 'id', false );
	$image_url     = mai_isset( $block['attrs'], 'url', false );
	$fixed         = mai_isset( $block['attrs'], 'hasParallax', false );
	$repeated      = mai_isset( $block['attrs'], 'isRepeated', false );

	$image_id = apply_filters( 'mai_cover_block_image_id', $image_id, $block );

	if ( ! ( $content_align || $opacity || ( $image_id && $image_url ) ) ) {
		return $block_content;
	}

	// Setup tag processor.
	$tags = new WP_HTML_Tag_Processor( $block_content );

	// Justify content.
	while ( $tags->next_tag( [ 'tag_name' => 'div', 'class_name' => 'wp-block-cover' ] ) ) {
		$style = (string) $tags->get_attribute( 'style' );

		if ( $content_align ) {
			$style .= sprintf( '--cover-block-justify-content:%s;', mai_get_flex_align( $content_align ) );
		}

		if ( $style ) {
			$tags->set_attribute( 'style', $style );
		} else {
			$tags->remove_attribute( 'style' );
		}
	}

	// Update markup.
	$block_content = $tags->get_updated_html();

	// Setup a new tag processor.
	$tags = new WP_HTML_Tag_Processor( $block_content );

	// Images.
	if ( $image_id && ! $repeated ) {
		$image_size = mai_get_cover_image_size();
		$tag_name   = $fixed ? 'div' : 'img';

		while ( $tags->next_tag( [ 'tag_name' => $tag_name, 'class_name' => 'wp-block-cover__image-background' ] ) ) {
			// Fixed.
			if ( $fixed ) {
				$style = '';
				$sizes     = [
					'sm' => wp_get_attachment_image_url( $image_id, $image_size ),
					'md' => wp_get_attachment_image_url( $image_id, 'large' ),
					'lg' => wp_get_attachment_image_url( $image_id, 'cover' ),
				];

				foreach ( $sizes as $size => $url ) {
					$style .= sprintf( '--background-image-%s:url(%s);', $size, $url );
				}

				$existing_style = (string) $tags->get_attribute( 'style' );

				if ( $existing_style ) {
					$image_array = explode( ';', $existing_style );

					foreach ( explode( ';', $existing_style ) as $index => $string ) {
						if ( ! str_starts_with( $string, 'background-image' ) ) {
							continue;
						}

						unset( $image_array[ $index ] );
					}

					$style = $style . implode( ';', $image_array );
				}

				$tags->set_attribute( 'style', $style );
			}
			// Not fixed.
			else {
				// Get image atts.
				$atts  = mai_get_image_src_srcset_sizes( $image_id, $image_size );

				// Replace src.
				$tags->set_attribute( 'src', $atts['src'] );

				// Replace srcset.
				$srcset = (string) $tags->get_attribute( 'srcset' );
				$srcset = $srcset ?: $atts['srcset'];

				// Not sure why, but this doesn't always show srcet and breaks. See #515.
				if ( $srcset ) {
					$tags->set_attribute( 'srcset', $srcset );
				}

				// Make sure sizes is full width now that src is showing the smallest size.
				if ( in_array( $align, [ 'full', 'wide' ] ) ) {
					$tags->set_attribute( 'sizes', '100vw' );
				}
				// Full width on mobile, half width other.
				else {
					$tags->set_attribute( 'sizes', '(max-width: 600px) 100vw, 50vw' );
				}

				// Convert inline style to custom property.
				$style = (string) $tags->get_attribute( 'style' );

				if ( $style ) {
					$style = str_replace( 'object-position:', '--object-position:', $style );
					$tags->set_attribute( 'style', $style );
				} else {
					$tags->remove_attribute( 'style' );
				}
			}
		}

		$block_content = $tags->get_updated_html();
	}

	return $block_content;
}