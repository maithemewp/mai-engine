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
 * Gets field defaults.
 * TODO: Move these to config.php?
 *
 * @access private
 *
 * @since 2.21.0
 *
 * @return array
 */
function mai_get_grid_layout_defaults() {
	static $defaults = null;

	if ( ! is_null( $defaults ) ) {
		return $defaults;
	}

	$defaults = [
		'columns'                => 3,
		'columns_responsive'     => 0,
		'columns_md'             => 1,
		'columns_sm'             => 1,
		'columns_xs'             => 1,
		'align_columns'          => 'start',
		'align_columns_vertical' => '',
		'column_gap'             => 'lg',
		'row_gap'                => 'lg',
		'margin_top'             => '',
		'margin_bottom'          => '',
	];

	return $defaults;
}

/**
 * Gets sanitized field values.
 *
 * @access private
 *
 * @since 2.21.0
 *
 * @return array
 */
function mai_get_grid_layout_sanitized( $args ) {
	$array = [
		'columns'                => 'absint',
		'columns_responsive'     => 'mai_sanitize_bool',
		'columns_md'             => 'absint',
		'columns_sm'             => 'absint',
		'columns_xs'             => 'absint',
		'align_columns'          => 'esc_html',
		'align_columns_vertical' => 'esc_html',
		'column_gap'             => 'esc_html',
		'row_gap'                => 'esc_html',
		'margin_top'             => 'sanitize_html_class',
		'margin_bottom'          => 'sanitize_html_class',
		'remove_spacing'         => 'mai_sanitize_bool',
	];

	foreach ( $array as $key => $function ) {
		if ( ! isset( $args[ $key ] ) ) {
			continue;
		}

		$args[ $key ] = mai_sanitize( $args[ $key ], $function );
	}

	return $args;
}

/**
 * Gets fields for acf field group.
 *
 * @access private
 *
 * @since 2.21.0
 *
 * @return array
 */
function mai_get_grid_layout_fields() {
	static $fields = null;

	if ( ! is_null( $fields ) ) {
		return $fields;
	}

	$defaults = mai_get_grid_layout_defaults();
	$fields   = [
		[
			'key'           => 'mai_grid_block_columns',
			'name'          => 'columns',
			'label'         => esc_html__( 'Columns (desktop)', 'mai-engine' ),
			'type'          => 'button_group',
			'default_value' => $defaults['columns'],
			'choices'       => mai_get_columns_choices(),
			'wrapper'       => [
				'class' => 'mai-acf-button-group',
			],
		],
		[
			'key'           => 'mai_grid_block_columns_responsive',
			'name'          => 'columns_responsive',
			'label'         => '',
			'type'          => 'true_false',
			'default_value' => $defaults['columns_responsive'],
			'message'       => esc_html__( 'Custom responsive columns', 'mai-engine' ),
		],
		[
			'key'               => 'mai_grid_block_columns_md',
			'name'              => 'columns_md',
			'label'             => esc_html__( 'Columns (lg tablets)', 'mai-engine' ),
			'type'              => 'button_group',
			'default_value'     => $defaults['columns_md'],
			'choices'           => mai_get_columns_choices(),
			'conditional_logic' => [
				[
					'field'    => 'mai_grid_block_columns_responsive',
					'operator' => '==',
					'value'    => 1,
				],
			],
			'wrapper'           => [
				'class' => 'mai-acf-button-group mai-acf-nested-columns mai-acf-nested-columns-first',
			],
		],
		[
			'key'               => 'mai_grid_block_columns_sm',
			'name'              => 'columns_sm',
			'label'             => esc_html__( 'Columns (sm tablets)', 'mai-engine' ),
			'type'              => 'button_group',
			'default_value'     => $defaults['columns_sm'],
			'choices'           => mai_get_columns_choices(),
			'conditional_logic' => [
				[
					'field'    => 'mai_grid_block_columns_responsive',
					'operator' => '==',
					'value'    => 1,
				],
			],
			'wrapper'           => [
				'class' => 'mai-acf-button-group mai-acf-nested-columns',
			],
		],
		[
			'key'               => 'mai_grid_block_columns_xs',
			'name'              => 'columns_xs',
			'label'             => esc_html__( 'Columns (mobile)', 'mai-engine' ),
			'type'              => 'button_group',
			'default_value'     => $defaults['columns_xs'],
			'choices'           => mai_get_columns_choices(),
			'conditional_logic' => [
				[
					'field'    => 'mai_grid_block_columns_responsive',
					'operator' => '==',
					'value'    => 1,
				],
			],
			'wrapper'           => [
				'class' => 'mai-acf-button-group mai-acf-nested-columns mai-acf-nested-columns-last',
			],
		],
		[
			'key'               => 'mai_grid_block_align_columns',
			'name'              => 'align_columns',
			'label'             => esc_html__( 'Align Columns', 'mai-engine' ),
			'type'              => 'button_group',
			'default_value'     => $defaults['align_columns'],
			'choices'           => [
				'start'  => esc_html__( 'Start', 'mai-engine' ),
				'center' => esc_html__( 'Center', 'mai-engine' ),
				'end'    => esc_html__( 'End', 'mai-engine' ),
			],
			'conditional_logic' => [
				[
					'field'    => 'mai_grid_block_columns',
					'operator' => '!=',
					'value'    => 1,
				],
			],
			'wrapper'           => [
				'class' => 'mai-acf-button-group',
			],
		],
		[
			'key'               => 'mai_grid_block_align_columns_vertical',
			'name'              => 'align_columns_vertical',
			'label'             => esc_html__( 'Align Columns (vertical)', 'mai-engine' ),
			'type'              => 'button_group',
			'default_value'     => $defaults['align_columns_vertical'],
			'choices'           => [
				''       => esc_html__( 'Full', 'mai-engine' ),
				'top'    => esc_html__( 'Top', 'mai-engine' ),
				'middle' => esc_html__( 'Middle', 'mai-engine' ),
				'bottom' => esc_html__( 'Bottom', 'mai-engine' ),
			],
			'conditional_logic' => [
				[
					'field'    => 'mai_grid_block_columns',
					'operator' => '!=',
					'value'    => 1,
				],
			],
			'wrapper'           => [
				'class' => 'mai-acf-button-group',
			],
		],
		[
			'key'           => 'mai_grid_block_column_gap',
			'name'          => 'column_gap',
			'label'         => esc_html__( 'Column Gap', 'mai-engine' ),
			'type'          => 'button_group',
			'default_value' => $defaults['column_gap'],
			'choices'       => [
				// Values mapped to a spacing sizes, labels kept consistent.
				''      => esc_html__( 'None', 'mai-engine' ),
				'md'    => esc_html__( 'XS', 'mai-engine' ),
				'lg'    => esc_html__( 'S', 'mai-engine' ),
				'xl'    => esc_html__( 'M', 'mai-engine' ),
				'xxl'   => esc_html__( 'L', 'mai-engine' ),
				'xxxl'  => esc_html__( 'XL', 'mai-engine' ),
				'xxxxl' => esc_html__( '2XL', 'mai-engine' ),
			],
			'wrapper'       => [
				'class' => 'mai-acf-button-group mai-acf-button-group-small',
			],
		],
		[
			'key'           => 'mai_grid_block_row_gap',
			'name'          => 'row_gap',
			'label'         => esc_html__( 'Row Gap', 'mai-engine' ),
			'type'          => 'button_group',
			'default_value' => $defaults['row_gap'],
			'choices'       => [
				// Values mapped to a spacing sizes, labels kept consistent.
				''      => esc_html__( 'None', 'mai-engine' ),
				'md'    => esc_html__( 'XS', 'mai-engine' ),
				'lg'    => esc_html__( 'S', 'mai-engine' ),
				'xl'    => esc_html__( 'M', 'mai-engine' ),
				'xxl'   => esc_html__( 'L', 'mai-engine' ),
				'xxxl'  => esc_html__( 'XL', 'mai-engine' ),
				'xxxxl' => esc_html__( '2XL', 'mai-engine' ),
			],
			'wrapper'       => [
				'class' => 'mai-acf-button-group mai-acf-button-group-small',
			],
		],
		[
			'key'               => 'mai_grid_block_margin_top',
			'label'             => esc_html__( 'Top Margin', 'mai-engine' ),
			'name'              => 'margin_top',
			'type'              => 'button_group',
			'choices'           => [
				''      => esc_html__( 'None', 'mai-engine' ),
				'md'    => esc_html__( 'XS', 'mai-engine' ),
				'lg'    => esc_html__( 'S', 'mai-engine' ),
				'xl'    => esc_html__( 'M', 'mai-engine' ),
				'xxl'   => esc_html__( 'L', 'mai-engine' ),
				'xxxl'  => esc_html__( 'XL', 'mai-engine' ),
				'xxxxl' => esc_html__( '2XL', 'mai-engine' ),
			],
			'default_value'     => '',
			'wrapper'           => [
				'class'            => 'mai-acf-button-group mai-acf-button-group-small',
			],
		],
		[
			'key'               => 'mai_grid_block_margin_bottom',
			'label'             => esc_html__( 'Bottom Margin', 'mai-engine' ),
			'name'              => 'margin_bottom',
			'type'              => 'button_group',
			'choices'           => [
				''      => esc_html__( 'None', 'mai-engine' ),
				'md'    => esc_html__( 'XS', 'mai-engine' ),
				'lg'    => esc_html__( 'S', 'mai-engine' ),
				'xl'    => esc_html__( 'M', 'mai-engine' ),
				'xxl'   => esc_html__( 'L', 'mai-engine' ),
				'xxxl'  => esc_html__( 'XL', 'mai-engine' ),
				'xxxxl' => esc_html__( '2XL', 'mai-engine' ),
			],
			'default_value'     => '',
			'wrapper'           => [
				'class'            => 'mai-acf-button-group mai-acf-button-group-small',
			],
		],
	];

	return $fields;
}
