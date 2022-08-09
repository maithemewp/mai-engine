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

/**
 * Renders a post grid.
 *
 * @since 0.1.0
 *
 * @param array $args Grid args.
 *
 * @return void
 */
function mai_do_post_grid( $args ) {
	mai_do_grid( 'post', $args );
}

/**
 * Renders a term grid.
 *
 * @since 0.1.0
 *
 * @param array $args Grid args.
 *
 * @return void
 */
function mai_do_term_grid( $args ) {
	mai_do_grid( 'term', $args );
}

/**
 * Renders a user grid.
 *
 * @since 0.1.0
 *
 * @param array $args Grid args.
 *
 * @return void
 */
function mai_do_user_grid( $args ) {
	mai_do_grid( 'user', $args );
}

/**
 * Renders a grid.
 *
 * @since 0.1.0
 *
 * @param string $type Grid type.
 * @param array  $args Grid args.
 *
 * @return void
 */
function mai_do_grid( $type, $args = [] ) {
	$args = array_merge( [ 'type' => $type ], $args );
	$grid = new Mai_Grid( $args );
	$grid->render();
}

/**
 * Get the text align value from a setting value.
 *
 * @since 0.1.0
 *
 * @param string $alignment Text alignment.
 *
 * @return string
 */
function mai_get_align_text( $alignment ) {
	switch ( $alignment ) {
		case 'start':
		case 'left':
			$value = 'start';
		break;
		case 'top':
			$value = 'flex-start';
			break;
		case 'center':
		case 'middle':
			$value = 'center';
		break;
		case 'end':
		case 'right':
			$value = 'end';
		break;
		case 'bottom':
			$value = 'flex-end';
		break;
		default:
			$value = 'unset';
	}

	return $value;
}

/**
 * Get the flexbox property value from a setting value.
 *
 * @since 0.1.0
 *
 * @param string $value Gets flex align rule.
 *
 * @return string
 */
function mai_get_flex_align( $value ) {
	switch ( $value ) {
		case 'start':
		case 'left':
		case 'top':
			$return = 'flex-start';
		break;
		case 'center':
		case 'middle':
			$return = 'center';
		break;
		case 'end':
		case 'right':
		case 'bottom':
			$return = 'flex-end';
		break;
		case 'between':
			$return = 'space-between';
		break;
		default:
			$return = 'initial'; // Needs initial for nested columns.
	}

	return $return;
}

/**
 * Get columns choices for settings.
 *
 * @since 0.1.0
 *
 * @return array
 */
function mai_get_columns_choices() {
	$choices = [];

	if ( ! ( is_admin() || is_customize_preview() ) ) {
		return $choices;
	}

	return [
		'1' => esc_html__( '1', 'mai-engine' ),
		'2' => esc_html__( '2', 'mai-engine' ),
		'3' => esc_html__( '3', 'mai-engine' ),
		'4' => esc_html__( '4', 'mai-engine' ),
		'5' => esc_html__( '5', 'mai-engine' ),
		'6' => esc_html__( '6', 'mai-engine' ),
		'0' => esc_html__( 'Fit', 'mai-engine' ),
	];
}

/**
 * Gets field keys to be used by clone fields.
 *
 * @access private
 *
 * @since 2.21.0
 *
 * @return array
 */
function mai_get_grid_field_keys() {
	static $keys = null;

	if ( ! is_null( $keys ) ) {
		return $keys;
	}

	$names = [];
	$data  = [
		'display'       => mai_get_grid_display_fields(),
		'layout'        => mai_get_grid_layout_fields(),
		'wp_query'      => mai_get_wp_query_fields(),
		'wp_term_query' => mai_get_wp_term_query_fields(),
	];

	foreach ( $data as $name => $values ) {
		foreach ( $values as $field ) {
			$names[ $name ][] = $field['key'];
			// $sub_fields       = isset( $field['sub_fields'] ) ? $field['sub_fields'] : [];

			// if ( $sub_fields ) {
			// 	foreach ( $sub_fields as $sub_field ) {
			// 		$names[ $name ][] = $sub_field['key'];
			// 	}
			// }
		}
	}

	$display         = array_merge( [ 'mai_grid_block_display_tab' ], array_diff( $names['display'], [ 'mai_grid_block_disable_entry_link' ] ) );
	$layout          = array_merge( [ 'mai_grid_block_layout_tab' ], $names['layout'] );
	$wp_query        = array_merge( [ 'mai_grid_block_entries_tab' ], $names['wp_query'] );
	$wp_term_query   = array_merge( [ 'mai_grid_block_entries_tab' ], $names['wp_term_query'] );
	$keys            = [
		'post' => array_merge( $display, $layout, $wp_query, [ 'mai_grid_block_disable_entry_link' ] ),
		'term' => array_merge( $display, $layout, $wp_term_query, [ 'mai_grid_block_disable_entry_link' ] ),
	];

	return $keys;
}
