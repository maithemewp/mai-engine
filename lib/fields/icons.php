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
 * Gets fields for acf field group.
 *
 * @access private
 *
 * @since TBD
 *
 * @return array
 */
function mai_get_icons_fields() {
	static $fields = null;

	if ( ! is_null( $fields ) ) {
		return $fields;
	}

	$fields = [
		[
			'key'           => 'mai_icon_style',
			'name'          => 'style',
			'label'         => esc_html__( 'Style', 'mai-engine' ),
			'type'          => 'button_group',
			'default_value' => 'light',
			'choices'       => [
				'light'   => esc_html__( 'Light', 'mai-engine' ),
				'regular' => esc_html__( 'Regular', 'mai-engine' ),
				'solid'   => esc_html__( 'Solid', 'mai-engine' ),
				'brands'  => esc_html__( 'Brands', 'mai-engine' ),
			],
			'wrapper' => [
				'class' => 'mai-acf-button-group mai-acf-button-group-small',
			],
		],
		[
			'key'               => 'mai_icon_choices',
			'name'              => 'icon',
			'label'             => esc_html__( 'Icon', 'mai-engine' ) . sprintf( ' (%s <a href="https://fontawesome.com/v5/search/">Font Awesome</a>)', __( 'full search via', 'mai-engine' ) ),
			'type'              => 'select',
			'default_value'     => 'heart',
			'allow_null'        => 1, // These fields are cloned in Mai Notices and other blocks so we need to allow null.
			'multiple'          => 0,
			'ui'                => 1,
			'ajax'              => 1,
			'conditional_logic' => [
				[
					'field'    => 'mai_icon_style',
					'operator' => '!=',
					'value'    => 'brands',
				],
			],
			'wrapper'           => [
				'class' => 'mai-icon-select',
			],
		],
		[
			'key'               => 'mai_icon_brand_choices',
			'name'              => 'icon_brand',
			'label'             => esc_html__( 'Icon (Brands)', 'mai-engine' ),
			'type'              => 'select',
			'default_value'     => 'wordpress-simple',
			'allow_null'        => 1, // These fields are cloned in Mai Notices, Mai Lists, etc. so we need to allow null.
			'multiple'          => 0,
			'ui'                => 1,
			'ajax'              => 1,
			'conditional_logic' => [
				[
					'field'    => 'mai_icon_style',
					'operator' => '==',
					'value'    => 'brands',
				],
			],
			'wrapper'           => [
				'class' => 'mai-icon-select',
			],
		],

		/***********
		 * Columns *
		 ***********/

		[
			'key'           => 'mai_columns',
			'label'         => 'Columns',
			'name'          => 'columns',
			'type'          => 'button_group',
			'default_value' => 3,
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
			'key'     => 'mai_columns_responsive',
			'name'    => 'columns_responsive',
			'type'    => 'true_false',
			'message' => __( 'Custom responsive columns', 'mai-engine' ),
		],
		[
			'key'               => 'mai_columns_md',
			'label'             => __( 'Columns (lg tablets)', 'mai-engine' ),
			'name'              => 'columns_md',
			'type'              => 'button_group',
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
			'label'             => __( 'Columns (md tablets)', 'mai-engine' ),
			'name'              => 'columns_sm',
			'type'              => 'button_group',
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
			'label'             => __( 'Columns (mobile)', 'mai-engine' ),
			'name'              => 'columns_xs',
			'type'              => 'button_group',
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
			'label'             => __( 'Align Columns', 'mai-engine' ),
			'name'              => 'align_columns',
			'type'              => 'button_group',
			'default_value'     => 'center',
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
						'value'    => '1',
					],
				],
			],
		],
		[
			'key'               => 'mai_align_columns_vertical',
			'label'             => __( 'Align Columns (vertical)', 'mai-engine' ),
			'name'              => 'align_columns_vertical',
			'type'              => 'button_group',
			'choices'           => [
				'full'   => __( 'Full', 'mai-engine' ),
				'top'    => __( 'Top', 'mai-engine' ),
				'middle' => __( 'Middle', 'mai-engine' ),
				'bottom' => __( 'Bottom', 'mai-engine' ),
			],
			'wrapper'           => [
				'class' => 'mai-acf-button-group mai-acf-button-group-small',
			],
			'conditional_logic' => [
				[
					[
						'field'    => 'mai_columns',
						'operator' => '!=',
						'value'    => '1',
					],
				],
			],
		],
		[
			'key'           => 'mai_column_gap',
			'label'         => __( 'Column Gap', 'mai-engine' ),
			'name'          => 'column_gap',
			'type'          => 'button_group',
			'default_value' => 'md',
			'choices'       => [
				''      => __( 'None', 'mai-engine' ),
				'md'    => __( 'XS', 'mai-engine' ),
				'lg'    => __( 'S', 'mai-engine' ),
				'xl'    => __( 'M', 'mai-engine' ),
				'xxl'   => __( 'L', 'mai-engine' ),
				'xxxl'  => __( 'XL', 'mai-engine' ),
				'xxxxl' => __( '2XL', 'mai-engine' ),
			],
			'wrapper'       => [
				'class' => 'mai-acf-button-group mai-acf-button-group-small',
			],
		],
		[
			'key'           => 'mai_row_gap',
			'label'         => __( 'Row Gap', 'mai-engine' ),
			'name'          => 'row_gap',
			'type'          => 'button_group',
			'default_value' => 'md',
			'choices'       => [
				''      => __( 'None', 'mai-engine' ),
				'md'    => __( 'XS', 'mai-engine' ),
				'lg'    => __( 'S', 'mai-engine' ),
				'xl'    => __( 'M', 'mai-engine' ),
				'xxl'   => __( 'L', 'mai-engine' ),
				'xxxl'  => __( 'XL', 'mai-engine' ),
				'xxxxl' => __( '2XL', 'mai-engine' ),
			],
			'wrapper'       => [
				'class' => 'mai-acf-button-group mai-acf-button-group-small',
			],
		],
		[
			'key'           => 'mai_margin_top',
			'label'         => __( 'Top Margin', 'mai-engine' ),
			'name'          => 'margin_top',
			'type'          => 'button_group',
			'default_value' => '',
			'choices'       => [
				''      => __( 'None', 'mai-engine' ),
				'md'    => __( 'XS', 'mai-engine' ),
				'lg'    => __( 'S', 'mai-engine' ),
				'xl'    => __( 'M', 'mai-engine' ),
				'xxl'   => __( 'L', 'mai-engine' ),
				'xxxl'  => __( 'XL', 'mai-engine' ),
				'xxxxl' => __( '2XL', 'mai-engine' ),
			],
			'wrapper' => [
				'class' => 'mai-acf-button-group mai-acf-button-group-small',
			],
		],
		[
			'key'           => 'mai_margin_bottom',
			'label'         => __( 'Bottom Margin', 'mai-engine' ),
			'name'          => 'margin_bottom',
			'type'          => 'button_group',
			'default_value' => '',
			'choices'       => [
				''      => __( 'None', 'mai-engine' ),
				'md'    => __( 'XS', 'mai-engine' ),
				'lg'    => __( 'S', 'mai-engine' ),
				'xl'    => __( 'M', 'mai-engine' ),
				'xxl'   => __( 'L', 'mai-engine' ),
				'xxxl'  => __( 'XL', 'mai-engine' ),
				'xxxxl' => __( '2XL', 'mai-engine' ),
			],
			'wrapper' => [
				'class' => 'mai-acf-button-group mai-acf-button-group-small',
			],
		],
	];

	return $fields;
}
