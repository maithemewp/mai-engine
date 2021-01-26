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

	$is_style     = isset( $block['attrs']['className'] ) && mai_has_string( 'is-style-', $block['attrs']['className'] );
	$is_secondary = $is_style && mai_has_string( 'is-style-secondary', $block['attrs']['className'] );
	$is_outline   = $is_style && mai_has_string( 'is-style-outline', $block['attrs']['className'] );
	$is_link      = $is_style && mai_has_string( 'is-style-link', $block['attrs']['className'] );
	$is_small     = isset( $block['attrs']['className'] ) && mai_has_string( 'button-small', $block['attrs']['className'] );
	$is_large     = isset( $block['attrs']['className'] ) && mai_has_string( 'button-large', $block['attrs']['className'] );

	// Add button class to the button link.
	if ( $is_secondary ) {
		$type          = 'secondary';
		$block_content = str_replace( ' is-style-secondary', '', $block_content );
		$block_content = str_replace( 'wp-block-button__link', 'wp-block-button__link button button-secondary', $block_content );

	} elseif ( $is_link ) {
		$type          = 'link';
		$block_content = str_replace( ' is-style-link', '', $block_content );
		$block_content = str_replace( 'wp-block-button__link', 'wp-block-button__link button button-link', $block_content );

	} elseif ( $is_outline ) {
		$type          = 'outline';
		$block_content = str_replace( ' is-style-outline', '', $block_content );
		$block_content = str_replace( 'wp-block-button__link', 'wp-block-button__link button button-outline', $block_content );

	} else {
		$type          = 'primary';
		$block_content = str_replace( 'wp-block-button__link', 'wp-block-button__link button', $block_content );
	}

	if ( $is_small ) {
		$block_content = str_replace( 'button-small', '', $block_content );
	}

	if ( $is_large ) {
		$block_content = str_replace( 'button-large', '', $block_content );
	}

	$color           = '';
	$color_name      = '';
	$background      = '';
	$background_name = '';
	$radius          = '';

	if ( isset( $block['attrs']['textColor'] ) ) {
		$color      = mai_get_color_value( $block['attrs']['textColor'] );
		$color_name = $block['attrs']['textColor'];

	} elseif ( isset( $block['attrs']['style']['color']['text'] ) ) {
		$color = mai_get_color_value( $block['attrs']['style']['color']['text'] );
	}

	if ( isset( $block['attrs']['backgroundColor'] ) ) {
		$background      = mai_get_color_value( $block['attrs']['backgroundColor'] );
		$background_name = $block['attrs']['backgroundColor'];

	} elseif ( isset( $block['attrs']['style']['color']['background'] ) ) {
		$background = mai_get_color_value( $block['attrs']['style']['color']['background'] );
	}

	if ( isset( $block['attrs']['borderRadius'] ) ) {
		$radius = mai_get_unit_value( $block['attrs']['borderRadius'] );
	}

	if ( $color || $background || $radius || $is_small || $is_large ) {
		$dom     = mai_get_dom_document( $block_content );
		$wraps   = $dom->getElementsByTagName( 'div' );
		$buttons = $dom->getElementsByTagName( 'a' );

		if ( $wraps ) {
			$prefix = ( 'primary' === $type ) ? '--button' : sprintf( '--button-%s', $type );

			foreach ( $wraps as $wrap ) {
				$style = $wrap->getAttribute( 'style' );
			}

			if ( '' !== $color ) {
				$style .= sprintf( '%s-color:%s;', $prefix, $color );

				if ( $is_outline && mai_is_light_color( $color ) ) {
					// For white or light colored outline buttons, change text dark on hover.
					$style .= sprintf( '%s-color-hover:%s;', $prefix, 'rgba(0,0,0,0.8)' );
				}
			}

			if ( '' !== $background ) {
				$style .= sprintf( '%s-background:%s;', $prefix, $background );

				if ( ! $is_outline ) {
					$style .= sprintf( '%s-background-hover:%s;', $prefix, mai_get_color_variant( $background, 'dark', 10 ) );
				}
			}

			if ( '' !== $radius ) {
				$style .= sprintf( '--button-border-radius:%s;', $radius );
			}

			if ( $style ) {
				$wrap->setAttribute( 'style', trim( $style ) );
			} else {
				$wrap->removeAttribute( 'style' );
			}
		}

		if ( $buttons ) {
			foreach ( $buttons as $button ) {
				$style   = ''; // Clear default inline styles.
				$classes = $button->getAttribute( 'class' );
				$classes = str_replace( 'has-text-color', '', $classes );
				$classes = str_replace( 'has-background', '', $classes );

				if ( $color_name ) {
					$classes = str_replace( sprintf( 'has-%s-color', $color_name ), '', $classes );
				}

				if ( $background_name ) {
					$classes = str_replace( sprintf( 'has-%s-background', $background_name ), '', $classes );
				}

				if ( $is_small ) {
					$classes .= ' button-small';
				}

				if ( $is_large ) {
					$classes .= ' button-large';
				}

				if ( $style ) {
					$button->setAttribute( 'style', trim( $style ) );
				} else {
					$button->removeAttribute( 'style' );
				}

				$button->setAttribute( 'class', trim( $classes ) );
			}
		}

		$block_content = $dom->saveHTML();
	}

	return $block_content;
}
