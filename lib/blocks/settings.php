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

add_filter( 'render_block', 'mai_do_cover_group_block_settings', 10, 2 );
/**
 * Dynamically adds classes based on our custom attributes.
 *
 * @since 2.3.0
 *
 * @param string $block_content The existing block content.
 * @param array  $block         The button block object.
 *
 * @return string
 */
function mai_do_cover_group_block_settings( $block_content, $block ) {
	if ( is_admin() ) {
		return $block_content;
	}

	if ( ! in_array( $block['blockName'], [ 'core/cover', 'core/group' ], true ) ) {
		return $block_content;
	}

	$width  = mai_isset( $block['attrs'], 'contentWidth', '' );
	$top    = mai_isset( $block['attrs'], 'verticalSpacingTop', '' );
	$bottom = mai_isset( $block['attrs'], 'verticalSpacingBottom', '' );
	$left   = mai_isset( $block['attrs'], 'verticalSpacingLeft', '' );
	$right  = mai_isset( $block['attrs'], 'verticalSpacingRight', '' );

	if ( $width || $top || $bottom || $left || $right ) {
		$dom = mai_get_dom_document( $block_content );

		/**
		 * The block container.
		 *
		 * @var DOMElement $first_block The block container.
		 */
		$first_block = mai_get_dom_first_child( $dom );

		if ( $first_block ) {

			$classes = $first_block->getAttribute( 'class' );

			// Remove classes left from old regex.
			$classes = str_replace(
				[
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
				],
				'',
				$classes
			);

			if ( $width ) {
				$classes = mai_add_classes( sprintf( 'has-%s-content-width', $width ), $classes );
			}

			if ( $top ) {
				$classes = mai_add_classes( sprintf( 'has-%s-padding-top', $top ), $classes );
			}

			if ( $bottom ) {
				$classes = mai_add_classes( sprintf( 'has-%s-padding-bottom', $bottom ), $classes );
			}

			if ( $left ) {
				$classes = mai_add_classes( sprintf( 'has-%s-padding-left', $left ), $classes );
			}

			if ( $right ) {
				$classes = mai_add_classes( sprintf( 'has-%s-padding-right', $right ), $classes );
			}

			$first_block->setAttribute( 'class', $classes );

			$block_content = $dom->saveHTML();
		}
	}

	return $block_content;
}

add_filter( 'render_block', 'mai_do_block_max_width_settings', 10, 2 );
/**
 * Dynamically adds classes based on our custom attributes.
 *
 * @since TBD
 *
 * @param string $block_content The existing block content.
 * @param array  $block         The button block object.
 *
 * @return string
 */
function mai_do_block_max_width_settings( $block_content, $block ) {
	if ( is_admin() ) {
		return $block_content;
	}

	if ( ! in_array( $block['blockName'], [ 'core/paragraph', 'core/heading' ], true ) ) {
		return $block_content;
	}

	$width  = mai_isset( $block['attrs'], 'maxWidth', '' );

	if ( $width ) {
		$dom = mai_get_dom_document( $block_content );

		/**
		 * The block container.
		 *
		 * @var DOMElement $first_block The block container.
		 */
		$first_block = mai_get_dom_first_child( $dom );

		if ( $first_block ) {
			$classes = mai_add_classes( sprintf( 'has-%s-max-width', $width ), $first_block->getAttribute( 'class' ) );

			$first_block->setAttribute( 'class', $classes );

			$block_content = $dom->saveHTML();
		}
	}

	return $block_content;
}

add_filter( 'render_block', 'mai_do_block_spacing_settings', 10, 2 );
/**
 * Dynamically adds classes based on our custom attributes.
 *
 * @since TBD
 *
 * @param string $block_content The existing block content.
 * @param array  $block         The button block object.
 *
 * @return string
 */
function mai_do_block_spacing_settings( $block_content, $block ) {
	if ( is_admin() ) {
		return $block_content;
	}

	if ( ! in_array( $block['blockName'], [ 'core/paragraph', 'core/heading', 'core/separator' ], true ) ) {
		return $block_content;
	}

	$top    = mai_isset( $block['attrs'], 'spacingTop', '' );
	$bottom = mai_isset( $block['attrs'], 'spacingBottom', '' );

	if ( $top || $bottom ) {
		$dom = mai_get_dom_document( $block_content );

		/**
		 * The block container.
		 *
		 * @var DOMElement $first_block The block container.
		 */
		$first_block = mai_get_dom_first_child( $dom );

		if ( $first_block ) {
			$classes = $first_block->getAttribute( 'class' );

			if ( $top ) {
				$classes = mai_add_classes( sprintf( 'has-%s-margin-top', $top ), $classes );
			}

			if ( $bottom ) {
				$classes = mai_add_classes( sprintf( 'has-%s-margin-bottom', $bottom ), $classes );
			}

			$first_block->setAttribute( 'class', $classes );

			$block_content = $dom->saveHTML();
		}
	}

	return $block_content;
}
