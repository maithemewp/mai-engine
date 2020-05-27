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

add_action( 'init', 'mai_register_button_styles' );
/**
 * Register additional button styles.
 *
 * @since 0.3.0
 *
 * @return void
 */
function mai_register_button_styles() {
	$styles = [
		'secondary',
		'tertiary',
	];

	foreach ( $styles as $style ) {
		register_block_style(
			'core/button',
			[
				'name'  => $style,
				'label' => mai_convert_case( $style, 'title' ),
			]
		);
	}
}

add_filter( 'render_block', 'mai_render_button_block', 10, 2 );
/**
 * Add our button classes to the button link.
 * This allows us to only have CSS on button instead of wp-block-button__link.
 *
 * @since   0.3.3
 *
 * @param   string $block_content The existing block content.
 * @param   object $block         The button block object.
 *
 * @return  string|HTML  The modified block HTML.
 */
function mai_render_button_block( $block_content, $block ) {

	// Bail if not a button block.
	if ( 'core/button' !== $block['blockName'] ) {
		return $block_content;
	}

	// Add button class to the button link.
	if ( mai_has_string( 'is-style-secondary', $block_content ) ) {
		$block_content = str_replace( 'wp-block-button__link', 'wp-block-button__link button button-secondary', $block_content );
	} elseif ( mai_has_string( 'is-style-tertiary', $block_content ) ) {
		$block_content = str_replace( 'wp-block-button__link', 'wp-block-button__link button button-tertiary', $block_content );
	} elseif ( mai_has_string( 'is-style-outline', $block_content ) ) {
		$block_content = str_replace( 'wp-block-button__link', 'wp-block-button__link button button-outline', $block_content );
	} else {
		$block_content = str_replace( 'wp-block-button__link', 'wp-block-button__link button', $block_content );
	}

	return $block_content;
}
