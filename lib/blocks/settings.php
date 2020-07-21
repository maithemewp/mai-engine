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

add_filter( 'render_block', 'mai_add_blocks_settings_classes', 10, 2 );
/**
 * Dynamically adds classes based on our custom attributes.
 *
 * @since 2.2.3
 *
 * @param string $block_content The existing block content.
 * @param array  $block         The button block object.
 *
 * @return string
 */
function mai_add_blocks_settings_classes( $block_content, $block ) {
	if ( ! in_array( $block['blockName'], [ 'core/cover', 'core/group' ] ) ) {
		return $block_content;
	}

	$width  = mai_isset( $block['attrs'], 'contentWidth', '' );
	$top    = mai_isset( $block['attrs'], 'verticalSpacingTop', '' );
	$bottom = mai_isset( $block['attrs'], 'verticalSpacingBottom', '' );
	$left   = mai_isset( $block['attrs'], 'verticalSpacingLeft', '' );
	$right  = mai_isset( $block['attrs'], 'verticalSpacingRight', '' );

	if ( $width || $top || $bottom || $left || $right ) {
		$dom    = mai_get_dom_document( $block_content );
		$xpath  = new \DOMXpath( $dom );
		$name   = str_replace( 'core/', '', $block['blockName'] );
		$query  = sprintf( '//div[starts-with(@class,"wp-block-%s ")]', $name ); // Needs space so it doesn't target inner_container too.
		$blocks = $xpath->query( $query );

		if ( $blocks && isset( $blocks[0] ) ) {

			/**
			 * @var DOMElement $first_block The block inner-container.
			 */
			$first_block = $blocks[0];

			$classes = $first_block->getAttribute( 'class' );

			// Remove classes left from old regex.
			$classes = str_replace( [
				'has-xs-content-width',
				'has-sm-content-width',
				'has-md-content-width',
				'has-lg-content-width',
				'has-xl-content-width',
				'has-xs-padding-top',
				'has-sm-padding-top',
				'has-md-padding-top',
				'has-lg-padding-top',
				'has-xl-padding-top',
				'has-xs-padding-bottom',
				'has-sm-padding-bottom',
				'has-md-padding-bottom',
				'has-lg-padding-bottom',
				'has-xl-padding-bottom',
				'has-xs-padding-left',
				'has-sm-padding-left',
				'has-md-padding-left',
				'has-lg-padding-left',
				'has-xl-padding-left',
				'has-xs-padding-right',
				'has-sm-padding-right',
				'has-md-padding-right',
				'has-lg-padding-right',
				'has-xl-padding-right',
			], '', $classes );

			if ( $width ) {
				$classes .= sprintf( ' has-%s-content-width', $width );
			}

			if ( $top ) {
				$classes .= sprintf( ' has-%s-padding-top', $top );
			}

			if ( $bottom ) {
				$classes .= sprintf( ' has-%s-padding-bottom', $bottom );
			}

			if ( $left ) {
				$classes .= sprintf( ' has-%s-padding-left', $left );
			}

			if ( $right ) {
				$classes .= sprintf( ' has-%s-padding-right', $right );
			}

			$first_block->setAttribute( 'class', $classes );

			$block_content = $dom->saveHTML();
		}
	}

	return $block_content;
}
