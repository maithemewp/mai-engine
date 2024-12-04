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

add_action( 'acf/init', 'mai_register_column_block' );
/**
 * Registers the columns blocks.
 *
 * @since 2.10.0
 * @since 2.25.0 Converted to block.json via `register_block_type()`.
 *
 * @return void
 */
function mai_register_column_block() {
	register_block_type( __DIR__ . '/block.json',
		[
			'icon' => '<svg role="img" aria-hidden="true" focusable="false" width="20" height="20" viewBox="0 0 96 96" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;"><g transform="matrix(0.75,0,0,0.780483,12,10.5366)"><g transform="matrix(1,0,0,0.851775,-31,-1.2925)"><g transform="matrix(0.116119,-0.108814,0.238273,0.223283,16.9541,72.8004)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g><g transform="matrix(0.185237,-0.173584,0.238273,0.223283,6.84275,60.9146)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g><g transform="matrix(0.185237,-0.173584,0.238273,0.223283,6.84275,39.5536)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g><g transform="matrix(0.185237,-0.173584,0.238273,0.223283,6.84275,18.5536)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g><g transform="matrix(0.0966534,-0.0905728,0.238273,0.223283,5.13751,-0.987447)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g></g><g transform="matrix(-0.268797,0,0,0.273288,155.348,7.00041)"><path d="M328.678,18.753L328.678,281.297L243.112,281.297L243.112,18.753M351,-0C351,-0.003 235.671,-0 235.671,-0C225.663,0.022 220.806,3.089 220.79,12.502L220.79,287.548C220.806,293.834 229.385,300.034 235.671,300.05L351,300.05L351,-0Z" style="fill:currentColor;fill-rule:nonzero;"/></g><g transform="matrix(0.291836,0,0,0.273288,-35.4345,7.00041)"><g><path d="M330.441,18.753L330.441,281.297L241.349,281.297L241.349,18.753M351,-0C351,-0.003 220.79,-0 220.79,-0L220.79,300.05L351,300.05L351,-0Z" style="fill:currentColor;fill-rule:nonzero;"/></g></g><g transform="matrix(0.268797,0,0,0.273288,-59.3476,7.00041)"><path d="M328.678,18.753L328.678,281.297L243.112,281.297L243.768,18.753M351,-0C351,-0.003 235.671,-0 235.671,-0C225.663,0.022 220.806,3.089 220.79,12.502L220.79,287.548C220.806,293.834 229.385,300.034 235.671,300.05L351,300.05L351,-0Z" style="display:block;fill:currentColor;fill-rule:nonzero;"/></g></g></svg>',
		]
	);
}

/**
 * Callback function to render the column block.
 *
 * @since 2.10.0
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
function mai_do_column_block( $attributes, $content, $is_preview, $post_id, $wp_block, $context ) {
	$args  = [
		'preview'               => $is_preview,
		'class'                 => isset( $attributes['className'] ) ? $attributes['className']: '',
		'align_column_vertical' => get_field( 'align_column_vertical' ),
		'spacing'               => get_field( 'spacing' ),
		'background'            => get_field( 'background' ),
		'shadow'                => get_field( 'shadow' ),
		'border'                => get_field( 'border' ),
		'radius'                => get_field( 'radius' ),
		'first_xs'              => get_field( 'first_xs' ),
		'first_sm'              => get_field( 'first_sm' ),
		'first_md'              => get_field( 'first_md' ),
		'fields'                => isset( $context['acf/fields'] ) ? $context['acf/fields'] : [],
	];

	$columns = new Mai_Column( $args );
	$columns->render();
}

add_action( 'acf/init', 'mai_register_column_field_group' );
/**
 * Register Mai Column block field group.
 *
 * @since 2.10.0
 *
 * @return void
 */
function mai_register_column_field_group() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		[
			'key'         => 'mai_column_field_group',
			'title'       => __( 'Mai Column', 'mai-engine' ),
			'fields'      => [
				[
					'key'               => 'mai_column_align_column_vertical',
					'label'             => __( 'Align Content (vertical)', 'mai-engine' ),
					'name'              => 'align_column_vertical',
					'type'              => 'button_group',
					'default_value'     => 'start',
					'choices'           => [
						'start'            => __( 'Top', 'mai-engine' ),
						'middle'           => __( 'Middle', 'mai-engine' ),
						'end'              => __( 'Bottom', 'mai-engine' ),
					],
					'wrapper'           => [
						'class'            => 'mai-acf-button-group',
					],
				],
				[
					'key'       => 'mai_column_spacing',
					'label'     => __( 'Padding', 'mai-engine' ),
					'name'      => 'spacing',
					'type'      => 'select',
					'choices'   => [
						''         => __( 'None', 'mai-engine' ),
						'xs'       => __( 'XS', 'mai-engine' ),
						'sm'       => __( 'SM', 'mai-engine' ),
						'md'       => __( 'MD', 'mai-engine' ),
						'lg'       => __( 'LG', 'mai-engine' ),
						'xl'       => __( 'XL', 'mai-engine' ),
						'xxl'      => __( '2XL', 'mai-engine' ),
						'xxxl'     => __( '3XL', 'mai-engine' ),
					],
				],
				[
					'key'     => 'mai_column_background',
					'label'   => __( 'Background Color', 'mai-engine' ),
					'name'    => 'background',
					'type'    => 'radio',
					'choices' => mai_get_radio_color_choices(),
					'wrapper' => [
						'class' => 'mai-block-colors',
					],
				],
				[
					'key'               => 'mai_column_background_custom',
					'name'              => 'background_custom',
					'type'              => 'color_picker',
					'conditional_logic' => [
						[
							'field'    => 'mai_column_background',
							'operator' => '==',
							'value'    => 'custom',
						],
					],
				],
				[
					'key'               => 'mai_columns_shadow',
					'name'              => 'shadow',
					'label'             => '',
					'message'           => esc_html__( 'Add box shadow', 'mai-engine' ),
					'type'              => 'true_false',
				],
				[
					'key'               => 'mai_columns_border',
					'name'              => 'border',
					'label'             => '',
					'message'           => esc_html__( 'Add border', 'mai-engine' ),
					'type'              => 'true_false',
				],
				[
					'key'               => 'mai_columns_radius',
					'name'              => 'radius',
					'label'             => '',
					'message'           => esc_html__( 'Add border radius', 'mai-engine' ),
					'type'              => 'true_false',
				],
				[
					'key'               => 'mai_columns_first_xs',
					'name'              => 'first_xs',
					'label'             => '',
					'message'           => esc_html__( 'Show first on mobile', 'mai-engine' ),
					'type'              => 'true_false',
				],
				[
					'key'               => 'mai_columns_first_sm',
					'name'              => 'first_sm',
					'label'             => '',
					'message'           => esc_html__( 'Show first on small tablets', 'mai-engine' ),
					'type'              => 'true_false',
				],
				[
					'key'               => 'mai_columns_first_md',
					'name'              => 'first_md',
					'label'             => '',
					'message'           => esc_html__( 'Show first on large tablets', 'mai-engine' ),
					'type'              => 'true_false',
				],
			],
			'location'    => [
				[
					[
						'param'    => 'block',
						'operator' => '==',
						'value'    => 'acf/mai-column',
					],
				],
			],
			'active'      => true,
		]
	);
}
