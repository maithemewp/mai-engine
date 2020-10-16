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

add_filter( 'render_block', 'mai_render_social_links_block', 10, 2 );
/**
 * Convert social xmlns links to https.
 *
 * @since 2.5.1.
 *
 * @param  string $block_content The existing block content.
 * @param  object $block         The cover block object.
 *
 * @return string
 */
function mai_render_social_links_block( $block_content, $block ) {
	// Bail if not a social-link block.
	if ( 'core/social-link' !== $block['blockName'] ) {
		return $block_content;
	}

	// Bail if link is empty.
	if ( ! $block_content ) {
		return $block_content;
	}

	if ( mai_is_https() ) {
		$dom = mai_get_dom_document( $block_content );

		$svgs = $dom->getElementsByTagName( 'svg' );
		$svg  = $svgs->item(0);

		if ( $svg ) {
			$xmlns = $svg->attributes->getNamedItem( 'xmlns' );
			$xmlns = $xmlns->value;

			if ( $xmlns ) {
				$xmlns = str_replace( 'http:', 'https:', $xmlns );
				$svg->setAttribute( 'xmlns', $xmlns );
				$block_content = $dom->saveHTML();
			}
		}
	}

	return $block_content;
}

add_action( 'init', 'mai_register_social_icon_block_styles' );
/**
 * Register social links no background style.
 *
 * @since 0.3.0
 *
 * @return void
 */
function mai_register_social_icon_block_styles() {
	if ( ! function_exists( 'register_block_style' ) ) {
		return;
	}

	register_block_style(
		'core/social-links',
		[
			'name'  => 'no-background',
			'label' => __( 'No Background', 'mai-engine' ),
		]
	);
}
