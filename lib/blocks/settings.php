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

add_filter( 'acf/prepare_field/key=mai_column_background', 'mai_prepare_legacy_color_field' );
add_filter( 'acf/prepare_field/key=mai_divider_color', 'mai_prepare_legacy_color_field' );
add_filter( 'acf/prepare_field/key=mai_icon_color', 'mai_prepare_legacy_color_field' );
add_filter( 'acf/prepare_field/key=mai_icon_background', 'mai_prepare_legacy_color_field' );
/**
 * Changes value to 'custom' if existing value is a hex value.
 * This is for existing instances of the block prior to TDB.
 *
 * @since TBD
 *
 * @return array
 */
function mai_prepare_legacy_color_field( $field ) {
	if ( ! $field['value'] ) {
		return $field;
	}

	if ( ! mai_has_string( '#', $field['value'] ) ) {
		return $field;
	}

	$key            = $field['key'];
	$original       = $field['value'];
	$field['value'] = 'custom';

	add_filter( "acf/prepare_field/key={$key}_custom", function( $field ) use ( $original ) {
		$field['value'] = $original;
		return $field;
	});

	return $field;
}

add_filter( 'acf/format_value/key=mai_column_background', 'mai_format_acf_color_value', 10, 3 );
add_filter( 'acf/format_value/key=mai_divider_color', 'mai_format_acf_color_value', 10, 3 );
add_filter( 'acf/format_value/key=mai_icon_color', 'mai_format_acf_color_value', 10, 3 );
add_filter( 'acf/format_value/key=mai_icon_background', 'mai_format_acf_color_value', 10, 3 );
/**
 * Returns custom color value if set to do so.
 *
 * @since TBD
 *
 * @return string
 */
function mai_format_acf_color_value( $value, $post_id, $field ) {
	if ( $value && 'custom' === $value ) {
		$value = get_field( sprintf( '%s_custom', $field['name'] ) );
	}

	return $value;
}

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
	if ( ! $block_content ) {
		return $block_content;
	}

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
 * @since 2.5.0
 *
 * @param string $block_content The existing block content.
 * @param array  $block         The button block object.
 *
 * @return string
 */
function mai_do_block_max_width_settings( $block_content, $block ) {
	if ( ! $block_content ) {
		return $block_content;
	}

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
 * @since 2.5.0
 *
 * @param string $block_content The existing block content.
 * @param array  $block         The button block object.
 *
 * @return string
 */
function mai_do_block_spacing_settings( $block_content, $block ) {
	if ( ! $block_content ) {
		return $block_content;
	}

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

add_filter( 'render_block', 'mai_do_block_margin_settings', 10, 2 );
/**
 * Dynamically adds classes based on our custom attributes.
 *
 * @since 2.5.0
 *
 * @param string $block_content The existing block content.
 * @param array  $block         The button block object.
 *
 * @return string
 */
function mai_do_block_margin_settings( $block_content, $block ) {
	if ( ! $block_content ) {
		return $block_content;
	}

	if ( is_admin() ) {
		return $block_content;
	}

	if ( ! in_array( $block['blockName'], [ 'core/image', 'core/cover', 'core/group' ], true ) ) {
		return $block_content;
	}

	$top    = mai_isset( $block['attrs'], 'marginTop', '' );
	$right  = mai_isset( $block['attrs'], 'marginRight', '' );
	$bottom = mai_isset( $block['attrs'], 'marginBottom', '' );
	$left   = mai_isset( $block['attrs'], 'marginLeft', '' );

	if ( $top || $right || $bottom || $left ) {
		$dom = mai_get_dom_document( $block_content );

		/**
		 * The block container.
		 *
		 * @var DOMElement $first_block The block container.
		 */
		$first_block = mai_get_dom_first_child( $dom );

		if ( $first_block ) {
			$classes = $first_block->getAttribute( 'class' );
			$overlap = false;

			if ( mai_has_string( '-', $top ) ) {
				$overlap = true;
			} elseif ( mai_has_string( '-', $right ) ) {
				$overlap = true;
			} elseif ( mai_has_string( '-', $bottom ) ) {
				$overlap = true;
			} elseif ( mai_has_string( '-', $left ) ) {
				$overlap = true;
			}

			if ( $overlap ) {
				$classes = mai_add_classes( 'has-overlap', $classes );
			}

			if ( $top ) {
				$classes = mai_add_classes( sprintf( 'has-%s-margin-top', $top ), $classes );
			}

			if ( $right ) {
				$classes = mai_add_classes( sprintf( 'has-%s-margin-right', $right ), $classes );
			}

			if ( $bottom ) {
				$classes = mai_add_classes( sprintf( 'has-%s-margin-bottom', $bottom ), $classes );
			}

			if ( $left ) {
				$classes = mai_add_classes( sprintf( 'has-%s-margin-left', $left ), $classes );
			}

			$first_block->setAttribute( 'class', $classes );

			$block_content = $dom->saveHTML();
		}
	}

	return $block_content;
}
