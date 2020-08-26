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

add_filter( 'render_block', 'mai_render_button_block', 10, 2 );
/**
 * Add our button classes to the button link.
 *
 * This allows us to only have CSS on button instead of wp-block-button__link.
 * Remove default button wrapper class so we don't have to compete with core styles.
 *
 * @since 2.4.2 Removed wrapper div.
 * @since 0.3.3
 *
 * @param string $block_content The existing block content.
 * @param object $block         The button block object.
 *
 * @return string
 */
function mai_render_button_block( $block_content, $block ) {

	// Bail if not a button block.
	if ( 'core/button' !== $block['blockName'] ) {
		return $block_content;
	}

	// Wrap additional lines in span for styling.
	if ( mai_has_string( '<br>', $block_content ) ) {
		$block_content = str_replace( 'wp-block-button__link', 'wp-block-button__link button button-large', $block_content );
		$block_content = str_replace( '<br>', '<br><span class="wp-block-button__line">', $block_content );
		$block_content = str_replace( '</a>', '</span></a>', $block_content );
	}

	$dom     = mai_get_dom_document( $block_content );
	$buttons = $dom->getElementsByTagName( 'a' );

	if ( $buttons ) {

		/**
		 * @var $button DOMElement
		 */
		foreach ( $buttons as $button ) {
			$fragment   = $dom->createDocumentFragment();
			$div        = $button->parentNode;
			$classes    = explode( ' ', $div->getAttribute( 'class' ) . ' ' . $button->getAttribute( 'class' ) );
			$colors     = mai_get_default_colors();
			$text_color = isset( $block['attrs']['textColor'] ) ? $block['attrs']['textColor'] : null;

			foreach ( $classes as $index => $class ) {
				$classes[ $index ] = str_replace( 'is-style-', 'button-', $class );

				// Remove unused classes.
				$classes = array_flip( $classes );
				unset( $classes['wp-block-button'] );
				unset( $classes['wp-block-button__link'] );
				unset( $classes['no-border-radius'] );
				$classes = array_flip( $classes );
			}

			// Add light text color class if set.
			if ( $text_color && isset( $colors[ $text_color ] ) ) {
				if ( mai_is_light_color( $colors[ $text_color ] ) ) {
					$classes[] = 'has-light-button-text';
				}
			}

			$button->setAttribute( 'class', 'button ' . implode( ' ', $classes ) );

			// Remove wrapper div.
			while ( $div->childNodes->length > 0 ) {
				$fragment->appendChild( $div->childNodes->item( 0 ) );
			}

			$div->parentNode->replaceChild( $fragment, $div );
		}
	}

	return $dom->saveHTML();
}
