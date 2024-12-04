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
 * @since 2.17.0
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

	$name = mai_get_color_name( $field['value'] );

	if ( $name ) {
		$field['value'] = $name;
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
 * @since 2.17.0
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

	// If we have values.
	if ( $width || $top || $bottom || $left || $right ) {
		// Get class for tag processor.
		switch ( $block['blockName'] ) {
			case 'core/cover':
				$args = [ 'class_name' => 'wp-block-cover' ];
			break;
			case 'core/group':
				$args = [ 'class_name' => 'wp-block-group' ];
			break;
			default:
				$args = [];
		}

		// Bail if no class.
		if ( ! $args ) {
			return $block_content;
		}

		// Set up tag processor.
		$tags = new WP_HTML_Tag_Processor( $block_content );

		// Loop through tags.
		while ( $tags->next_tag( $args ) ) {
			if ( $width ) {
				$tags->add_class( sprintf( 'has-%s-content-width', $width ) );
			}

			if ( $top ) {
				$tags->add_class( sprintf( 'has-%s-padding-top', $top ) );
			}

			if ( $bottom ) {
				$tags->add_class( sprintf( 'has-%s-padding-bottom', $bottom ) );
			}

			if ( $left ) {
				$tags->add_class( sprintf( 'has-%s-padding-left', $left ) );
			}

			if ( $right ) {
				$tags->add_class( sprintf( 'has-%s-padding-right', $right ) );
			}

			// Only apply to the first instance. Not nested blocks.
			break;
		}

		// Update the content.
		$block_content = $tags->get_updated_html();
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

	$width = mai_isset( $block['attrs'], 'maxWidth', '' );

	if ( $width ) {
		// Get class for tag processor.
		switch ( $block['blockName'] ) {
			case 'core/paragraph':
				$args = [ 'tag_name' => 'p' ];
			break;
			case 'core/heading':
				$args = [ 'class_name' => 'wp-block-heading' ];
			break;
			default:
				$args = [];
		}

		// Bail if no args.
		if ( ! $args ) {
			return $block_content;
		}

		// Set up tag processor.
		$tags = new WP_HTML_Tag_Processor( $block_content );

		// Loop through tags.
		while ( $tags->next_tag( $args ) ) {
			$tags->add_class( sprintf( 'has-%s-max-width', $width ) );

			// Only apply to the first instance. Not nested blocks.
			break;
		}

		// Update the content.
		$block_content = $tags->get_updated_html();
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
		// Get class for tag processor.
		switch ( $block['blockName'] ) {
			case 'core/paragraph':
				$args = [ 'tag_name' => 'p' ];
			break;
			case 'core/heading':
				$args = [ 'class_name' => 'wp-block-heading' ];
			break;
			case 'core/separator':
				$args = [ 'class_name' => 'wp-block-separator' ];
			break;
			default:
				$args = [];
		}

		// Bail if no class.
		if ( ! $args ) {
			return $block_content;
		}

		// Set up tag processor.
		$tags = new WP_HTML_Tag_Processor( $block_content );

		// Loop through tags.
		while ( $tags->next_tag( $args ) ) {
			if ( $top ) {
				$tags->add_class( sprintf( 'has-%s-margin-top', $top ) );
			}

			if ( $bottom ) {
				$tags->add_class( sprintf( 'has-%s-margin-bottom', $bottom ) );
			}

			// Only apply to the first instance. Not nested blocks.
			break;
		}

		// Update the content.
		$block_content = $tags->get_updated_html();
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
		// Get class for tag processor.
		switch ( $block['blockName'] ) {
			case 'core/image':
				$args = [ 'class_name' => 'wp-block-image' ];
			break;
			case 'core/cover':
				$args = [ 'class_name' => 'wp-block-cover' ];
			break;
			case 'core/group':
				$args = [ 'class_name' => 'wp-block-group' ];
			break;
			default:
				$args = [];
		}

		// Bail if no class.
		if ( ! $args ) {
			return $block_content;
		}

		// Set up tag processor.
		$tags = new WP_HTML_Tag_Processor( $block_content );

		// Loop through tags.
		while ( $tags->next_tag( $args ) ) {
			if ( $top ) {
				$tags->add_class( sprintf( 'has-%s-margin-top', $top ) );
			}

			if ( $right ) {
				$tags->add_class( sprintf( 'has-%s-margin-right', $right ) );
			}

			if ( $bottom ) {
				$tags->add_class( sprintf( 'has-%s-margin-bottom', $bottom ) );
			}

			if ( $left ) {
				$tags->add_class( sprintf( 'has-%s-margin-left', $left ) );
			}

			// Only apply to the first instance. Not nested blocks.
			break;
		}

		// Update the content.
		$block_content = $tags->get_updated_html();
	}

	return $block_content;
}
