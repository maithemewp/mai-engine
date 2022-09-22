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

add_action( 'acf/init', 'mai_register_columns_block' );
/**
 * Registers the columns blocks.
 *
 * @since 2.10.0
 * @since TBD Converted to block.json via `register_block_type()`.
 *
 * @return void
 */
function mai_register_columns_block() {
	register_block_type( __DIR__ . '/block.json',
		[
			'icon' => '<svg role="img" aria-hidden="true" focusable="false" style="display:block;" width="20" height="20" viewBox="0 0 96 96" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;"><g transform="matrix(0.75,0,0,0.780483,12,10.5366)"><g transform="matrix(1,0,0,0.851775,31,-1.2925)"><g transform="matrix(0.116119,-0.108814,0.238273,0.223283,16.9541,72.8004)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g><g transform="matrix(0.185237,-0.173584,0.238273,0.223283,6.84275,60.9146)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g><g transform="matrix(0.185237,-0.173584,0.238273,0.223283,6.84275,39.5536)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g><g transform="matrix(0.185237,-0.173584,0.238273,0.223283,6.84275,18.5536)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g><g transform="matrix(0.0966534,-0.0905728,0.238273,0.223283,5.13751,-0.987447)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g></g><g transform="matrix(1,0,0,0.851775,-4.26326e-14,-1.2925)"><g transform="matrix(0.116119,-0.108814,0.238273,0.223283,16.9541,72.8004)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g><g transform="matrix(0.185237,-0.173584,0.238273,0.223283,6.84275,60.9146)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g><g transform="matrix(0.185237,-0.173584,0.238273,0.223283,6.84275,39.5536)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g><g transform="matrix(0.185237,-0.173584,0.238273,0.223283,6.84275,18.5536)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g><g transform="matrix(0.0966534,-0.0905728,0.238273,0.223283,5.13751,-0.987447)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g></g><g transform="matrix(1,0,0,0.851775,-31,-1.2925)"><g transform="matrix(0.116119,-0.108814,0.238273,0.223283,16.9541,72.8004)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g><g transform="matrix(0.185237,-0.173584,0.238273,0.223283,6.84275,60.9146)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g><g transform="matrix(0.185237,-0.173584,0.238273,0.223283,6.84275,39.5536)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g><g transform="matrix(0.185237,-0.173584,0.238273,0.223283,6.84275,18.5536)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g><g transform="matrix(0.0966534,-0.0905728,0.238273,0.223283,5.13751,-0.987447)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g></g><g transform="matrix(-0.268797,0,0,0.273288,155.348,7.00041)"><path d="M328.678,18.753L328.678,281.297L243.112,281.297L243.112,18.753M351,-0C351,-0.003 235.671,-0 235.671,-0C225.663,0.022 220.806,3.089 220.79,12.502L220.79,287.548C220.806,293.834 229.385,300.034 235.671,300.05L351,300.05L351,-0Z" style="fill:currentColor;fill-rule:nonzero;"/></g><g transform="matrix(0.291836,0,0,0.273288,-35.4345,7.00041)"><g><path d="M330.441,18.753L330.441,281.297L241.349,281.297L241.349,18.753M351,-0C351,-0.003 220.79,-0 220.79,-0L220.79,300.05L351,300.05L351,-0Z" style="fill:currentColor;fill-rule:nonzero;"/></g></g><g transform="matrix(0.268797,0,0,0.273288,-59.3476,7.00041)"><path d="M328.678,18.753L328.678,281.297L243.112,281.297L243.768,18.753M351,-0C351,-0.003 235.671,-0 235.671,-0C225.663,0.022 220.806,3.089 220.79,12.502L220.79,287.548C220.806,293.834 229.385,300.034 235.671,300.05L351,300.05L351,-0Z" style="fill:currentColor;fill-rule:nonzero;"/></g></g></svg>',
		]
	);
}

/**
 * Callback function to render the block.
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
function mai_do_columns_block( $attributes, $content = '', $is_preview = false, $post_id = 0, $wp_block, $context ) {
	$args['preview']                = $is_preview;
	$args['class']                  = isset( $attributes['className'] ) ? $attributes['className']: '';
	$args['columns']                = get_field( 'columns' );
	$args['columns_responsive']     = get_field( 'columns_responsive' );
	$args['columns_md']             = get_field( 'columns_md' );
	$args['columns_sm']             = get_field( 'columns_sm' );
	$args['columns_xs']             = get_field( 'columns_xs' );
	$args['arrangement']            = get_field( 'arrangement' );
	$args['arrangement_md']         = get_field( 'arrangement_md' );
	$args['arrangement_sm']         = get_field( 'arrangement_sm' );
	$args['arrangement_xs']         = get_field( 'arrangement_xs' );
	$args['column_gap']             = get_field( 'column_gap' );
	$args['row_gap']                = get_field( 'row_gap' );
	$args['align']                  = $attributes['align'];
	$args['align_columns']          = get_field( 'align_columns' );
	$args['align_columns_vertical'] = get_field( 'align_columns_vertical' );
	$args['margin_top']             = get_field( 'margin_top' );
	$args['margin_bottom']          = get_field( 'margin_bottom' );

	$columns = new Mai_Columns( $args );
	$columns->render();
}

add_action( 'acf/render_field/type=button_group', 'mai_render_columns_arrangement_field', 4 );
/**
 * Adds details/summary element to trigger hiding and showing advanced extra settings.
 *
 * @since 2.17.0
 *
 * @return void
 */
function mai_render_columns_arrangement_field( $field ) {
	if ( ! in_array( $field['key'],
		[
			'mai_columns_arrangement_columns',
			'mai_columns_md_arrangement_columns',
			'mai_columns_sm_arrangement_columns',
			'mai_columns_xs_arrangement_columns',
		]
		)) {
		return;
	}

	// Load open if using a hidden value.
	$open = $field['value'] && in_array( $field['value'], [ '1/12', '1/8', '1/6', '1/5', '3/8', '2/5', '3/5', '5/8', '4/5', '5/6', '7/8', '11/12' ] );

	printf( '<details%s><summary><span class="more-text">↓ %s</span><span class="less-text">↑ %s</span> %s</summary></details>',
		$open ? ' open' : '',
		__( 'More', 'mai-engine' ),
		__( 'Less', 'mai-engine' ),
		__( 'options', 'mai-engine' )
	);
}

add_action( 'acf/init', 'mai_register_columns_field_group' );
/**
 * Register Mai Columns block field group.
 *
 * @since 2.10.0
 *
 * @return void
 */
function mai_register_columns_field_group() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	$column_choices = [
		'1/4'   => __( '1/4', 'mai-engine' ),
		'1/3'   => __( '1/3', 'mai-engine' ),
		'1/2'   => __( '1/2', 'mai-engine' ),
		'2/3'   => __( '2/3', 'mai-engine' ),
		'3/4'   => __( '3/4', 'mai-engine' ),
		'1/12'  => __( '1/12', 'mai-engine' ),
		'1/8'   => __( '1/8', 'mai-engine' ),
		'1/6'   => __( '1/6', 'mai-engine' ),
		'1/5'   => __( '1/5', 'mai-engine' ),
		'3/8'   => __( '3/8', 'mai-engine' ),
		'2/5'   => __( '2/5', 'mai-engine' ),
		'3/5'   => __( '3/5', 'mai-engine' ),
		'5/8'   => __( '5/8', 'mai-engine' ),
		'4/5'   => __( '4/5', 'mai-engine' ),
		'5/6'   => __( '5/6', 'mai-engine' ),
		'7/8'   => __( '7/8', 'mai-engine' ),
		'11/12' => __( '11/12', 'mai-engine' ),
		'auto'  => sprintf( '%s </span>%s</span>', __( 'Fit', 'mai-engine' ), __( 'Content', 'mai-engine' ) ),
		'fill'  => sprintf( '%s </span>%s</span>', __( 'Fill', 'mai-engine' ), __( 'Space', 'mai-engine' ) ),
		'full'  => sprintf( '%s </span>%s</span>', __( 'Full', 'mai-engine' ), __( 'Width', 'mai-engine' ) ),
	];

	$arrangement_instructions = __( 'Custom arrangements will repeat in the sequence you set here. Only set one value if you want all columns to be the same.', 'mai-engine' );

	acf_add_local_field_group(
		[
			'key'                 => 'mai_columns_field_group',
			'title'               => __( 'Mai Columns', 'mai-engine' ),
			'fields'              => [
				[
					'key'               => 'mai_columns_columns',
					'label'             => __( 'Columns', 'mai-engine' ),
					'name'              => 'columns',
					'type'              => 'select',
					'choices'           => [
						1                  => '1',
						2                  => '2',
						3                  => '3',
						4                  => '4',
						5                  => '5',
						6                  => '6',
						0                  => __( 'Fit', 'mai-engine' ),
						'custom'           => __( 'Custom arrangement', 'mai-engine' ),
					],
					'default_value'     => 2,
				],
				[
					'key'               => 'mai_columns_arrangement_message',
					'label'             => __( 'Responsive Arrangements', 'mai-engine' ),
					'type'              => 'message',
					'conditional_logic' => [
						[
							[
								'field'          => 'mai_columns_columns',
								'operator'       => '==',
								'value'          => 'custom',
							],
						],
					],
				],
				[
					'key'               => 'mai_columns_arrangement_tab',
					'label'             => __( 'LG', 'mai-engine' ),
					'type'              => 'tab',
					'conditional_logic' => [
						[
							[
								'field'          => 'mai_columns_columns',
								'operator'       => '==',
								'value'          => 'custom',
							],
						],
					],
				],
				[
					'key'               => 'mai_columns_arrangement',
					'label'             => __( 'Arrangement (desktop)', 'mai-engine' ),
					'name'              => 'arrangement',
					'instructions'      => $arrangement_instructions,
					'type'              => 'repeater',
					'conditional_logic' => [
						[
							[
								'field'          => 'mai_columns_columns',
								'operator'       => '==',
								'value'          => 'custom',
							],
						],
					],
					'min'               => 1,
					'max'               => 0,
					'layout'            => 'block',
					'button_label'      => __( 'Add Column', 'mai-engine' ),
					'sub_fields'        => [
						[
							'key'             => 'mai_columns_arrangement_columns',
							'label'           => '',
							'name'            => 'columns',
							'type'            => 'button_group',
							'choices'         => $column_choices,
							'default_value'   => '1/2',
						],
					],
				],
				[
					'key'               => 'mai_columns_arrangement_md_tab',
					'label'             => __( 'MD', 'mai-engine' ),
					'type'              => 'tab',
					'conditional_logic' => [
						[
							[
								'field'          => 'mai_columns_columns',
								'operator'       => '==',
								'value'          => 'custom',
							],
						],
					],
				],
				[
					'key'               => 'mai_columns_md_arrangement',
					'label'             => __( 'Arrangement (lg tablets)', 'mai-engine' ),
					'name'              => 'arrangement_md',
					'instructions'      => $arrangement_instructions,
					'type'              => 'repeater',
					'conditional_logic' => [
						[
							[
								'field'          => 'mai_columns_columns',
								'operator'       => '==',
								'value'          => 'custom',
							],
						],
					],
					'min'               => 1,
					'max'               => 0,
					'layout'            => 'block',
					'button_label'      => __( 'Add Column', 'mai-engine' ),
					'sub_fields'        => [
						[
							'key'             => 'mai_columns_md_arrangement_columns',
							'label'           => '',
							'name'            => 'columns',
							'type'            => 'button_group',
							'choices'         => $column_choices,
							'default_value'   => '1/2',
						],
					],
				],
				[
					'key'               => 'mai_columns_arrangement_sm_tab',
					'label'             => __( 'SM', 'mai-engine' ),
					'type'              => 'tab',
					'conditional_logic' => [
						[
							[
								'field'          => 'mai_columns_columns',
								'operator'       => '==',
								'value'          => 'custom',
							],
						],
					],
				],
				[
					'key'               => 'mai_columns_sm_arrangement',
					'label'             => __( 'Arrangement (sm tablets)', 'mai-engine' ),
					'name'              => 'arrangement_sm',
					'instructions'      => $arrangement_instructions,
					'type'              => 'repeater',
					'conditional_logic' => [
						[
							[
								'field'          => 'mai_columns_columns',
								'operator'       => '==',
								'value'          => 'custom',
							],
						],
					],
					'min'               => 1,
					'max'               => 0,
					'layout'            => 'block',
					'button_label'      => __( 'Add Column', 'mai-engine' ),
					'sub_fields'        => [
						[
							'key'             => 'mai_columns_sm_arrangement_columns',
							'label'           => '',
							'name'            => 'columns',
							'type'            => 'button_group',
							'choices'         => $column_choices,
							'default_value'   => '1/2',
						],
					],
				],
				[
					'key'               => 'mai_columns_arrangement_xs_tab',
					'label'             => __( 'XS', 'mai-engine' ),
					'type'              => 'tab',
					'conditional_logic' => [
						[
							[
								'field'          => 'mai_columns_columns',
								'operator'       => '==',
								'value'          => 'custom',
							],
						],
					],
				],
				[
					'key'               => 'mai_columns_xs_arrangement',
					'label'             => __( 'Arrangement (mobile)', 'mai-engine' ),
					'name'              => 'arrangement_xs',
					'instructions'      => $arrangement_instructions,
					'type'              => 'repeater',
					'conditional_logic' => [
						[
							[
								'field'          => 'mai_columns_columns',
								'operator'       => '==',
								'value'          => 'custom',
							],
						],
					],
					'min'               => 1,
					'max'               => 0,
					'layout'            => 'block',
					'button_label'      => __( 'Add Column', 'mai-engine' ),
					'sub_fields'        => [
						[
							'key'             => 'mai_columns_xs_arrangement_columns',
							'label'           => '',
							'name'            => 'columns',
							'type'            => 'button_group',
							'choices'         => $column_choices,
							'default_value'   => 'full',
						],
					],
				],
				[
					'key'               => 'mai_columns_arrangement_closing_tab',
					'type'              => 'tab',
					'endpoint'          => 1,
					'wrapper'           => [
						'class'            => 'mai-columns-closing-tab',
					],
				],
				[
					'key'               => 'mai_columns_align_columns',
					'label'             => __( 'Align Columns', 'mai-engine' ),
					'name'              => 'align_columns',
					'type'              => 'button_group',
					'choices'           => [
						'start'            => __( 'Start', 'mai-engine' ),
						'center'           => __( 'Center', 'mai-engine' ),
						'end'              => __( 'End', 'mai-engine' ),
						'between'          => __( 'Space', 'mai-engine' ),
					],
					'default_value'     => 'start',
					'wrapper'           => [
						'class'            => 'mai-acf-button-group',
					],
				],
				[
					'key'               => 'mai_columns_align_columns_vertical',
					'label'             => __( 'Align Columns (vertical)', 'mai-engine' ),
					'name'              => 'align_columns_vertical',
					'type'              => 'button_group',
					'choices'           => [
						''                 => __( 'Full', 'mai-engine' ),
						'top'              => __( 'Top', 'mai-engine' ),
						'middle'           => __( 'Middle', 'mai-engine' ),
						'bottom'           => __( 'Bottom', 'mai-engine' ),
					],
					'wrapper'           => [
						'class'            => 'mai-acf-button-group',
					],
				],
				[
					'key'               => 'mai_columns_column_gap',
					'label'             => __( 'Column Gap', 'mai-engine' ),
					'name'              => 'column_gap',
					'type'              => 'button_group',
					'default_value'     => 'xl',
					'choices'           => [
						''                 => __( 'None', 'mai-engine' ),
						'md'               => __( 'XS', 'mai-engine' ),
						'lg'               => __( 'S', 'mai-engine' ),
						'xl'               => __( 'M', 'mai-engine' ),
						'xxl'              => __( 'L', 'mai-engine' ),
						'xxxl'             => __( 'XL', 'mai-engine' ),
						'xxxxl'            => __( '2XL', 'mai-engine' ),
					],
					'wrapper'           => [
						'class'            => 'mai-acf-button-group mai-acf-button-group-small',
					],
				],
				[
					'key'               => 'mai_columns_row_gap',
					'label'             => __( 'Row Gap', 'mai-engine' ),
					'name'              => 'row_gap',
					'type'              => 'button_group',
					'default_value'     => 'xl',
					'choices'           => [
						''                 => __( 'None', 'mai-engine' ),
						'md'               => __( 'XS', 'mai-engine' ),
						'lg'               => __( 'S', 'mai-engine' ),
						'xl'               => __( 'M', 'mai-engine' ),
						'xxl'              => __( 'L', 'mai-engine' ),
						'xxxl'             => __( 'XL', 'mai-engine' ),
						'xxxxl'            => __( '2XL', 'mai-engine' ),
					],
					'wrapper'           => [
						'class'            => 'mai-acf-button-group mai-acf-button-group-small',
					],
				],
				[
					'key'               => 'mai_columns_margin_top',
					'label'             => __( 'Top Margin', 'mai-engine' ),
					'name'              => 'margin_top',
					'type'              => 'button_group',
					'choices'           => [
						''                 => __( 'None', 'mai-engine' ),
						'md'               => __( 'XS', 'mai-engine' ),
						'lg'               => __( 'S', 'mai-engine' ),
						'xl'               => __( 'M', 'mai-engine' ),
						'xxl'              => __( 'L', 'mai-engine' ),
						'xxxl'             => __( 'XL', 'mai-engine' ),
						'xxxxl'            => __( '2XL', 'mai-engine' ),
					],
					'default_value'     => '',
					'wrapper'           => [
						'class'            => 'mai-acf-button-group mai-acf-button-group-small',
					],
				],
				[
					'key'               => 'mai_columns_margin_bottom',
					'label'             => __( 'Bottom Margin', 'mai-engine' ),
					'name'              => 'margin_bottom',
					'type'              => 'button_group',
					'choices'           => [
						''                 => __( 'None', 'mai-engine' ),
						'md'               => __( 'XS', 'mai-engine' ),
						'lg'               => __( 'S', 'mai-engine' ),
						'xl'               => __( 'M', 'mai-engine' ),
						'xxl'              => __( 'L', 'mai-engine' ),
						'xxxl'             => __( 'XL', 'mai-engine' ),
						'xxxxl'            => __( '2XL', 'mai-engine' ),
					],
					'default_value'     => '',
					'wrapper'           => [
						'class'            => 'mai-acf-button-group mai-acf-button-group-small',
					],
				],
			],
			'location'            => [
				[
					[
						'param'            => 'block',
						'operator'         => '==',
						'value'            => 'acf/mai-columns',
					],
				],
			],
		]
	);
}
