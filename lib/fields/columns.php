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
 * @since TBD
 *
 * @return array
 */
function mai_get_columns_defaults() {
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
		'align_columns'          => '',
		'align_columns_vertical' => '',
		'column_gap'             => 'xl',
		'row_gap'                => 'xl',
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
 * @since TBD
 *
 * @return array
 */
function mai_get_columns_sanitized( $args ) {
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
 * @since TBD
 *
 * @return array
 */
function mai_get_columns_fields() {
	$defaults = mai_get_columns_defaults();
	return [
		[
			'key'           => 'mai_columns',
			'label'         => 'Columns',
			'name'          => 'columns',
			'type'          => 'button_group',
			'default_value' => $defaults['columns'],
			'choices'       => [
				1 => 1,
				2 => 2,
				3 => 3,
				4 => 4,
				5 => 5,
				6 => 6,
				7 => 7,
				8 => 8,
			],
			'wrapper'       => [
				'class' => 'mai-acf-button-group mai-acf-button-group-small',
			]
		],
		[
			'key'           => 'mai_columns_responsive',
			'name'          => 'columns_responsive',
			'type'          => 'true_false',
			'message'       => esc_html__( 'Custom responsive columns', 'mai-engine' ),
			'default_value' => $defaults['columns_responsive'],
		],
		[
			'key'               => 'mai_columns_md',
			'label'             => esc_html__( 'Columns (lg tablets)', 'mai-engine' ),
			'name'              => 'columns_md',
			'type'              => 'button_group',
			'default_value'     => $defaults['columns_md'],
			'choices'           => [
				1 => 1,
				2 => 2,
				3 => 3,
				4 => 4,
				5 => 5,
				6 => 6,
				7 => 7,
				8 => 8,
			],
			'wrapper'           => [
				'class' => 'mai-acf-button-group mai-acf-button-group-small mai-acf-nested-columns mai-acf-nested-columns-first',
			],
			'conditional_logic' => [
				[
					[
						'field'    => 'mai_columns_responsive',
						'operator' => '==',
						'value'    => 1,
					],
				],
			],
		],
		[
			'key'               => 'mai_columns_sm',
			'label'             => esc_html__( 'Columns (md tablets)', 'mai-engine' ),
			'name'              => 'columns_sm',
			'type'              => 'button_group',
			'default_value'     => $defaults['columns_sm'],
			'choices'           => [
				1 => 1,
				2 => 2,
				3 => 3,
				4 => 4,
				5 => 5,
				6 => 6,
				7 => 7,
				8 => 8,
			],
			'wrapper'           => [
				'class' => 'mai-acf-button-group mai-acf-button-group-small mai-acf-nested-columns',
			],
			'conditional_logic' => [
				[
					[
						'field'    => 'mai_columns_responsive',
						'operator' => '==',
						'value'    => 1,
					],
				],
			],
		],
		[
			'key'               => 'mai_columns_xs',
			'label'             => esc_html__( 'Columns (mobile)', 'mai-engine' ),
			'name'              => 'columns_xs',
			'type'              => 'button_group',
			'default_value'     => $defaults['columns_xs'],
			'choices'           => [
				1 => 1,
				2 => 2,
				3 => 3,
				4 => 4,
				5 => 5,
				6 => 6,
				7 => 7,
				8 => 8,
			],
			'wrapper'           => [
				'class' => 'mai-acf-button-group mai-acf-button-group-small mai-acf-nested-columns mai-acf-nested-columns-last',
			],
			'conditional_logic' => [
				[
					[
						'field'    => 'mai_columns_responsive',
						'operator' => '==',
						'value'    => 1,
					],
				],
			],
		],
		[
			'key'               => 'mai_align_columns',
			'label'             => esc_html__( 'Align Columns', 'mai-engine' ),
			'name'              => 'align_columns',
			'type'              => 'button_group',
			'default_value'     => $defaults['align_columns'],
			'choices'           => [
				'start'  => 'Start',
				'center' => 'Center',
				'end'    => 'End',
			],
			'wrapper'           => [
				'class' => 'mai-acf-button-group mai-acf-button-group-small',
			],
			'conditional_logic' => [
				[
					[
						'field'    => 'mai_columns',
						'operator' => '!=',
						'value'    => 1,
					],
				],
			],
		],
		[
			'key'               => 'mai_align_columns_vertical',
			'label'             => esc_html__( 'Align Columns (vertical)', 'mai-engine' ),
			'name'              => 'align_columns_vertical',
			'type'              => 'button_group',
			'default_value'     => $defaults['align_columns_vertical'],
			'choices'           => [
				'full'   => esc_html__( 'Full', 'mai-engine' ),
				'top'    => esc_html__( 'Top', 'mai-engine' ),
				'middle' => esc_html__( 'Middle', 'mai-engine' ),
				'bottom' => esc_html__( 'Bottom', 'mai-engine' ),
			],
			'wrapper'           => [
				'class' => 'mai-acf-button-group mai-acf-button-group-small',
			],
			'conditional_logic' => [
				[
					[
						'field'    => 'mai_columns',
						'operator' => '!=',
						'value'    => 1,
					],
				],
			],
		],
		[
			'key'           => 'mai_column_gap',
			'label'         => esc_html__( 'Column Gap', 'mai-engine' ),
			'name'          => 'column_gap',
			'type'          => 'button_group',
			'default_value' => $defaults['column_gap'],
			'choices'       => [
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
			'key'           => 'mai_row_gap',
			'label'         => esc_html__( 'Row Gap', 'mai-engine' ),
			'name'          => 'row_gap',
			'type'          => 'button_group',
			'default_value' => $defaults['row_gap'],
			'choices'       => [
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
			'key'           => 'mai_margin_top',
			'label'         => esc_html__( 'Top Margin', 'mai-engine' ),
			'name'          => 'margin_top',
			'type'          => 'button_group',
			'default_value' => $defaults['margin_top'],
			'choices'       => [
				''      => esc_html__( 'None', 'mai-engine' ),
				'md'    => esc_html__( 'XS', 'mai-engine' ),
				'lg'    => esc_html__( 'S', 'mai-engine' ),
				'xl'    => esc_html__( 'M', 'mai-engine' ),
				'xxl'   => esc_html__( 'L', 'mai-engine' ),
				'xxxl'  => esc_html__( 'XL', 'mai-engine' ),
				'xxxxl' => esc_html__( '2XL', 'mai-engine' ),
			],
			'wrapper' => [
				'class' => 'mai-acf-button-group mai-acf-button-group-small',
			],
		],
		[
			'key'           => 'mai_margin_bottom',
			'label'         => esc_html__( 'Bottom Margin', 'mai-engine' ),
			'name'          => 'margin_bottom',
			'type'          => 'button_group',
			'default_value' => $defaults['margin_bottom'],
			'choices'       => [
				''      => esc_html__( 'None', 'mai-engine' ),
				'md'    => esc_html__( 'XS', 'mai-engine' ),
				'lg'    => esc_html__( 'S', 'mai-engine' ),
				'xl'    => esc_html__( 'M', 'mai-engine' ),
				'xxl'   => esc_html__( 'L', 'mai-engine' ),
				'xxxl'  => esc_html__( 'XL', 'mai-engine' ),
				'xxxxl' => esc_html__( '2XL', 'mai-engine' ),
			],
			'wrapper' => [
				'class' => 'mai-acf-button-group mai-acf-button-group-small',
			],
		],
	];
}
