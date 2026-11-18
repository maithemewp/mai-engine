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

	$color_value      = '';
	$color_name       = '';
	$background_value = '';
	$background_name  = '';
	$radius           = '';

	// Color.
	if ( isset( $block['attrs']['style']['color']['text'] ) ) {
		$color_value = mai_get_color_value( $block['attrs']['style']['color']['text'] );
	}
	// Legacy.
	elseif ( isset( $block['attrs']['textColor'] ) ) {
		$color_value = mai_get_color_value( $block['attrs']['textColor'] );
		$color_name  = $block['attrs']['textColor'];
	}

	// Background.
	if ( isset( $block['attrs']['style']['color']['background'] ) ) {
		$background_value = mai_get_color_value( $block['attrs']['style']['color']['background'] );
	}
	// Legacy.
	elseif ( isset( $block['attrs']['backgroundColor'] ) ) {
		$background_value = mai_get_color_value( $block['attrs']['backgroundColor'] );
		$background_name  = $block['attrs']['backgroundColor'];
	}

	// Border radius.
	$radius = $block['attrs']['style']['border']['radius'] ?? '';
	$radius = '' !== $radius ? $radius : $block['attrs']['borderRadius'] ?? '';

	// Conditions.
	if ( $color_value || $background_value || $radius || $is_small || $is_large ) {
		$tags   = new WP_HTML_Tag_Processor( $block_content );
		$prefix = ( 'primary' === $type ) ? '--button' : sprintf( '--button-%s', $type );

		while ( $tags->next_tag() ) {
			$tag = $tags->get_tag();

			if ( 'DIV' === $tag ) {
				$style  = $tags->get_attribute( 'style' );
				$styles = explode( ';', is_string( $style ) ? $style : '' );
				$styles = array_map( 'trim', $styles );

				if ( '' !== $color_value ) {
					$styles[] = sprintf( '%s-color:%s', $prefix, mai_get_color_css( $color_value ) );

					if ( $is_outline && mai_is_light_color( $color_value ) ) {
						// For white or light colored outline buttons, change text dark on hover.
						$styles[] = sprintf( '%s-color-hover:%s', $prefix, 'rgba(0,0,0,0.8)' );
					}
				}

				if ( '' !== $background_value ) {
					$styles[] = sprintf( '%s-background:%s', $prefix, mai_get_color_css( $background_value ) );

					if ( ! $is_outline ) {
						$styles[] = sprintf( '%s-background-hover:%s', $prefix, mai_get_color_css( mai_get_color_variant( $background_value, 'dark', 10 ) ) );
					}
				}

				// Radius. If it's an array, WP will automatically add it as inline styles on the <a> tag.
				if ( '' !== $radius && ! is_array( $radius ) ) {
					$styles[] = sprintf( '--button-border-radius:%s', $radius );
				}

				// Cleanup.
				$styles = array_values( array_filter( $styles ) );

				if ( $styles ) {
					$tags->set_attribute( 'style', implode( ';', $styles ) . ';' );
				} else {
					$tags->remove_attribute( 'style' );
				}
			} elseif ( 'A' === $tag ) {
				// Whole class names only, so a class like has-background-blur is left alone.
				$tags->remove_class( 'has-text-color' );
				$tags->remove_class( 'has-background' );

				// Clear color classes.
				if ( $color_name ) {
					$tags->remove_class( sprintf( 'has-%s-color', $color_name ) );
				}

				// Clear background classes.
				if ( $background_name ) {
					$tags->remove_class( sprintf( 'has-%s-background-color', $background_name ) ); // WP 5.9 seems to have added the -color.
					$tags->remove_class( sprintf( 'has-%s-background', $background_name ) );
				}

				if ( $is_small ) {
					$tags->add_class( 'button-small' );
				}

				if ( $is_large ) {
					$tags->add_class( 'button-large' );
				}
			}
		}

		$block_content = $tags->get_updated_html();
	}

	return $block_content;
}
