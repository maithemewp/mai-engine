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


add_action( 'acf/init', 'mai_register_grid_field_groups' );
/**
 * Register field groups for the grid block.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_register_grid_field_groups() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	$keys = mai_get_grid_field_keys();

	acf_add_local_field_group(
		[
			'key'      => 'mai_post_grid_field_group',
			'title'    => __( 'Mai Post Grid', 'mai-engine' ),
			'fields'   => [
				[
					'key'     => 'mai_post_grid_clone',
					'label'   => __( 'Mai Post Grid', 'mai-engine' ),
					'name'    => 'post_grid_clone',
					'type'    => 'clone',
					'display' => 'group', // 'group' or 'seamless'. 'group' allows direct return of actual field names via get_field( 'style' ).
					'clone'   => $keys['post'],
				],
			],
			'location' => [
				[
					[
						'param'    => 'block',
						'operator' => '==',
						'value'    => 'acf/mai-post-grid',
					],
				],
			],
			'active'   => true,
		]
	);

	acf_add_local_field_group(
		[
			'key'      => 'mai_term_grid_field_group',
			'title'    => __( 'Mai Term Grid', 'mai-engine' ),
			'fields'   => [
				[
					'key'     => 'mai_term_grid_clone',
					'label'   => __( 'Mai Term Grid', 'mai-engine' ),
					'name'    => 'term_grid_clone',
					'type'    => 'clone',
					'display' => 'group', // 'group' or 'seamless'. 'group' allows direct return of actual field names via get_field( 'style' ).
					'clone'   => $keys['term'],
				],
			],
			'location' => [
				[
					[
						'param'    => 'block',
						'operator' => '==',
						'value'    => 'acf/mai-term-grid',
					],
				],
			],
			'active'   => true,
		]
	);
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

	$display         = array_merge( [ 'mai_grid_block_display_tab' ], array_diff( $names['display'], [ 'mai_grid_block_disable_entry_link', 'mai_grid_block_no_results' ] ) );
	$layout          = array_merge( [ 'mai_grid_block_layout_tab' ], $names['layout'] );
	$wp_query        = array_merge( [ 'mai_grid_block_entries_tab' ], $names['wp_query'] );
	$wp_term_query   = array_merge( [ 'mai_grid_block_entries_tab' ], $names['wp_term_query'] );
	$keys            = [
		'post' => array_merge( $display, $layout, $wp_query, [ 'mai_grid_block_disable_entry_link', 'mai_grid_block_no_results' ] ),
		'term' => array_merge( $display, $layout, $wp_term_query, [ 'mai_grid_block_disable_entry_link', 'mai_grid_block_no_results' ] ),
	];

	return $keys;
}

/**
 * Gets the grid values by grid type.
 *
 * @since 0.1.0
 *
 * @param string $type Grid type. post/term/user.
 *
 * @return array
 */
function mai_get_grid_field_values( $type ) {
	$values   = [];
	$defaults = mai_get_grid_display_defaults();
	$defaults = array_merge( $defaults, mai_get_grid_layout_defaults() );

	switch ( $type ) {
		case 'post':
			$defaults = array_merge( $defaults, mai_get_wp_query_defaults() );
		break;
		case 'term':
			$defaults = array_merge( $defaults, mai_get_wp_term_query_defaults() );
		break;
	}

	foreach ( $defaults as $key => $default ) {
		$value          = get_field( $key );
		$values[ $key ] = is_null( $value ) ? $default : $value;
	}

	return $values;
}

/**
 * Renders a post grid block.
 *
 * @since 0.1.0
 *
 * @param array    $attributes The block attributes.
 * @param string   $content The block content.
 * @param bool     $is_preview Whether or not the block is being rendered for editing preview.
 * @param int      $post_id The current post being edited or viewed.
 * @param WP_Block $wp_block The block instance (since WP 5.5).
 * @param array    $context The block context array.
 *
 * @return void
 */
function mai_do_post_grid_block( $attributes, $content, $is_preview, $post_id, $wp_block, $context ) {
	// TODO: block id?
	mai_do_grid_block( 'post', $attributes, $content, $is_preview, $post_id, $wp_block, $context );
}

/**
 * Renders a term grid block.
 *
 * @since 0.1.0
 *
 * @param array    $attributes The block attributes.
 * @param string   $content The block content.
 * @param bool     $is_preview Whether or not the block is being rendered for editing preview.
 * @param int      $post_id The current post being edited or viewed.
 * @param WP_Block $wp_block The block instance (since WP 5.5).
 * @param array    $context The block context array.
 *
 * @return void
 */
function mai_do_term_grid_block( $attributes, $content, $is_preview, $post_id, $wp_block, $context ) {
	// TODO: block id?
	mai_do_grid_block( 'term', $attributes, $content, $is_preview, $post_id, $wp_block, $context );
}

/**
 * Renders a grid block.
 *
 * @since 0.1.0
 *
 * @param array    $attributes The block attributes.
 * @param string   $content The block content.
 * @param bool     $is_preview Whether or not the block is being rendered for editing preview.
 * @param int      $post_id The current post being edited or viewed.
 * @param WP_Block $wp_block The block instance (since WP 5.5).
 * @param array    $context The block context array.
 *
 * @return void
 */
function mai_do_grid_block( $type, $attributes, $content, $is_preview, $post_id, $wp_block, $context ) {
	$args          = mai_get_grid_field_values( $type );
	$args['class'] = isset( $args['class'] ) ? $args['class'] : '';
	// $args['id']    = isset( $args['id'] ) ? $args['id'] : '';

	if ( ! empty( $attributes['className'] ) ) {
		$args['class'] = mai_add_classes( $attributes['className'], $args['class'] );
	}

	if ( ! empty( $attributes['align'] ) ) {
		$args['class'] = mai_add_classes( 'align' . $attributes['align'], $args['class'] );
	}

	$args['preview'] = $is_preview;

	mai_do_grid( $type, $args );
}
