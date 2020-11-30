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
 * @since  0.3.3
 *
 * @param  string $block_content The existing block content.
 * @param  object $block         The button block object.
 *
 * @return string The modified block HTML.
 */
function mai_render_button_block( $block_content, $block ) {
	if ( ! $block_content ) {
		return $block_content;
	}

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

	// Add button class to the button link.
	if ( mai_has_string( 'is-style-secondary', $block_content ) ) {
		$block_content = str_replace( ' is-style-secondary', '', $block_content );
		$block_content = str_replace( 'wp-block-button__link', 'wp-block-button__link button button-secondary', $block_content );

	} elseif ( mai_has_string( 'is-style-link', $block_content ) ) {
		$block_content = str_replace( ' is-style-link', '', $block_content );
		$block_content = str_replace( 'wp-block-button__link', 'wp-block-button__link button button-link', $block_content );

	} elseif ( mai_has_string( 'is-style-outline', $block_content ) ) {
		$block_content = str_replace( ' is-style-outline', '', $block_content );
		$block_content = str_replace( 'wp-block-button__link', 'wp-block-button__link button button-outline', $block_content );
		$colors        = mai_get_default_colors();

		if ( isset( $block['attrs']['textColor'] ) && isset( $colors[ $block['attrs']['textColor'] ] ) ) {
			if ( mai_is_light_color( $colors[ $block['attrs']['textColor'] ] ) ) {
				$block_content = str_replace( 'wp-block-button__link', 'wp-block-button__link has-light-button-text', $block_content );
			}
		}
	} else {
		$block_content = str_replace( 'wp-block-button__link', 'wp-block-button__link button', $block_content );
	}

	$has_small  = isset( $block['attrs']['className'] ) && mai_has_string( 'button-small', $block['attrs']['className'] );
	$has_large  = isset( $block['attrs']['className'] ) && mai_has_string( 'button-large', $block['attrs']['className'] );
	$has_radius = isset( $block['attrs']['borderRadius'] );

	if ( $has_small || $has_large || $has_radius ) {
		$block_content = $has_small ? str_replace( 'button-small', '', $block_content ) : $block_content;
		$block_content = $has_large ? str_replace( 'button-large', '', $block_content ) : $block_content;

		$dom     = mai_get_dom_document( $block_content );
		$buttons = $dom->getElementsByTagName( 'a' );

		if ( $buttons ) {
			foreach ( $buttons as $button ) {
				if ( $has_small || $has_large ) {
					$classes = $button->getAttribute( 'class' );

					if ( $has_small ) {
						$classes .= ' button-small';
					}

					if ( $has_large ) {
						$classes .= ' button-large';
					}

					$button->setAttribute( 'class', $classes );
				}

				if ( $has_radius ) {
					$style = $button->getAttribute( 'style' );
					$style = str_replace( 'border-radius', '--border-radius', $style );

					$button->setAttribute( 'style', $style );
				}
			}
		}

		$block_content = $dom->saveHTML();
	}

	return $block_content;
}
