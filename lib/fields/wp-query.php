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

add_filter( 'acf/load_field/key=mai_grid_block_post_type', 'mai_post_type_field' );
/**
 * Loads post type choices.
 *
 * @since TBD
 *
 * @param array $field The existing field array.
 *
 * @return array
 */
function mai_post_type_field( $field ) {
	$field['choices'] = mai_get_post_type_choices();
	return $field;
}

add_filter( 'acf/load_field/key=mai_grid_block_tax_taxonomy', 'mai_load_taxonomy_field' );
/**
 * Loads taxonomy choices.
 *
 * @since TBD
 *
 * @param array $field The existing field array.
 *
 * @return array
 */
function mai_load_taxonomy_field( $field ) {
	$field['choices'] = mai_get_post_types_taxonomy_choices();
	return $field;
}

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
function mai_get_wp_query_defaults() {
	static $defaults = null;

	if ( ! is_null( $defaults ) ) {
		return $defaults;
	}

	$defaults = [
		'post_type'           => [ 'post' ],
		'query_by'            => '',
		'post__in'            => '',
		'taxonomy'            => '',
		'terms'               => '',
		'current'             => 0,
		'operator'            => 'IN',
		'taxonomies_relation' => 'AND',
		'meta_key'            => '',
		'meta_compare'        => '',
		'meta_value'          => '',
		'meta_keys_relation'  => 'AND',
		'current_children'    => 0,
		'post_parent__in'     => '',
		'posts_per_page'      => 12,
		'offset'              => 0,
		'date_after'          => '',
		'date_before'         => '',
		'orderby'             => 'date',
		'orderby_meta_key'    => '',
		'order'               => 'DESC',
		'post__not_in'        => '',
		'excludes'            => '',
	];

	return $defaults;
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
function mai_get_wp_query_fields() {
	static $fields = null;

	if ( ! is_null( $fields ) ) {
		return $fields;
	}

	$defaults  = mai_get_wp_query_defaults();
	$date_info = mai_get_block_setting_info_link( 'https://help.bizbudding.com/article/176-mai-grid-blocks' );
	$fields    = [
		[
			'key'           => 'mai_grid_block_post_type',
			'name'          => 'post_type',
			'label'         => esc_html__( 'Post Type', 'mai-engine' ),
			'type'          => 'select',
			'default_value' => $defaults['post_type'],
			'choices'       => [], // Added later via load_field.
			'multiple'      => 1,
			'ui'            => 1,
			'ajax'          => 0,
		],
		[
			'key'               => 'mai_grid_block_query_by',
			'name'              => 'query_by',
			'label'             => esc_html__( 'Get Entries By', 'mai-engine' ),
			'type'              => 'select',
			'default_value'     => $defaults['query_by'],
			'choices'           => [
				''         => esc_html__( 'Default Query', 'mai-engine' ),
				'id'       => esc_html__( 'Choice', 'mai-engine' ),
				'tax_meta' => esc_html__( 'Taxonomy/Meta', 'mai-engine' ),
				'parent'   => esc_html__( 'Parent', 'mai-engine' ),
			],
			'conditional_logic' => [
				[
					'field'    => 'mai_grid_block_post_type',
					'operator' => '!=empty',
				],
			],
		],
		[
			'key'               => 'mai_grid_block_post_in',
			'name'              => 'post__in',
			'label'             => esc_html__( 'Choose Entries', 'mai-engine' ),
			'desc'              => esc_html__( 'Show specific entries. Choose all that apply.', 'mai-engine' ),
			'type'              => 'post_object',
			// 'sanitize'       => 'absint',
			'default_value'     => $defaults['post__in'],
			'conditional_logic' => [
				[
					'field'    => 'mai_grid_block_post_type',
					'operator' => '!=empty',
				],
				[
					'field'    => 'mai_grid_block_query_by',
					'operator' => '==',
					'value'    => 'id',
				],
			],
			'multiple'          => 1,
			'return_format'     => 'id',
			'ui'                => 1,
		],
		[
			'key'               => 'mai_grid_block_post_taxonomies',
			'name'              => 'taxonomies',
			'label'             => esc_html__( 'Taxonomies', 'mai-engine' ),
			'type'              => 'repeater',
			'conditional_logic' => [
				[
					'field'    => 'mai_grid_block_post_type',
					'operator' => '!=empty',
				],
				[
					'field'    => 'mai_grid_block_query_by',
					'operator' => '==',
					'value'    => 'tax_meta',
				],
			],
			'collapsed'    => 'mai_grid_block_tax_taxonomy',
			'layout'       => 'block',
			'button_label' => esc_html__( 'Add Condition', 'mai-engine' ),
			'sub_fields'   => [
				[
					'key'           => 'mai_grid_block_tax_taxonomy',
					'name'          => 'taxonomy',
					'label'         => esc_html__( 'Taxonomy', 'mai-engine' ),
					'type'          => 'select',
					'choices'       => [], // Added later via load_field.
					'default_value' => $defaults['taxonomy'],
					'ui'            => 1,
					'ajax'          => 1,
				],
				[
					'key'               => 'mai_grid_block_tax_terms',
					'name'              => 'terms',
					'label'             => esc_html__( 'Terms', 'mai-engine' ),
					'type'              => 'select',
					// 'sanitize'       => 'absint',
					'default_value'     => $defaults['terms'],
					'ui'                => 1,
					'ajax'              => 1,
					'multiple'          => 1,
					'conditional_logic' => [
						[
							'field'    => 'mai_grid_block_tax_taxonomy',
							'operator' => '!=empty',
						],
					],
				],
				[
					'key'               => 'mai_grid_block_tax_terms_current',
					'name'              => 'current',
					'label'             => '',
					'type'              => 'true_false',
					// 'sanitize'       => 'sanitize_bool',
					'default_value'     => $defaults['current'],
					'message'           => sprintf( '%s %s', esc_html__( 'Use current', 'mai-engine' ), mai_get_block_setting_info_link( 'https://docs.bizbudding.com/docs/mai-grid-blocks/#taxonomy-meta' ) ),
					'conditional_logic' => [
						[
							'field'    => 'mai_grid_block_tax_taxonomy',
							'operator' => '!=empty',
						],
					],
				],
				[
					'key'               => 'mai_grid_block_tax_operator',
					'name'              => 'operator',
					'label'             => esc_html__( 'Operator', 'mai-engine' ),
					'type'              => 'select',
					'default_value'     => $defaults['operator'],
					'choices'           => [
						'IN'     => esc_html__( 'In', 'mai-engine' ),
						'NOT IN' => esc_html__( 'Not In', 'mai-engine' ),
					],
					'conditional_logic' => [
						[
							'field'    => 'mai_grid_block_tax_taxonomy',
							'operator' => '!=empty',
						],
					],
				],
			],
		],
		[
			'key'               => 'mai_grid_block_post_taxonomies_relation',
			'name'              => 'taxonomies_relation',
			'label'             => esc_html__( 'Taxonomies Relation', 'mai-engine' ),
			'type'              => 'select',
			'default_value'     => $defaults['taxonomies_relation'],
			'choices'           => [
				'AND' => esc_html__( 'And', 'mai-engine' ),
				'OR'  => esc_html__( 'Or', 'mai-engine' ),
			],
			'conditional_logic' => [
				[
					'field'    => 'mai_grid_block_post_type',
					'operator' => '!=empty',
				],
				[
					'field'    => 'mai_grid_block_query_by',
					'operator' => '==',
					'value'    => 'tax_meta',
				],
				[
					'field'    => 'mai_grid_block_post_taxonomies',
					'operator' => '>',
					'value'    => '1', // More than 1 row.
				],
			],
		],
		[
			'key'               => 'mai_grid_block_post_meta_keys',
			'name'              => 'meta_keys',
			'label'             => esc_html__( 'Meta Keys', 'mai-engine' ),
			'type'              => 'repeater',
			'default_value'     => '',
			'conditional_logic' => [
				[
					'field'    => 'mai_grid_block_post_type',
					'operator' => '!=empty',
				],
				[
					'field'    => 'mai_grid_block_query_by',
					'operator' => '==',
					'value'    => 'tax_meta',
				],
			],
			'collapsed'         => 'mai_grid_block_post_meta_key',
			'layout'            => 'block',
			'button_label'      => esc_html__( 'Add Condition', 'mai-engine' ),
			'sub_fields'        => [
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				[
					'key'           => 'mai_grid_block_post_meta_key',
					'name'          => 'meta_key',
					'label'         => esc_html__( 'Meta Key', 'mai-engine' ),
					'type'          => 'text',
					'default_value' => $defaults['meta_key'],
				],
				[
					'key'               => 'mai_grid_block_post_meta_compare',
					'name'              => 'meta_compare',
					'label'             => esc_html__( 'Compare', 'mai-engine' ),
					'type'              => 'select',
					'default_value'     => $defaults['meta_compare'],
					'choices'           => [
						'='          => __( 'Is equal to', 'mai-engine' ),
						'!='         => __( 'Is not equal to', 'mai-engine' ),
						'>'          => __( 'Is greater than', 'mai-engine' ),
						'>='         => __( 'Is great than or equal to', 'mai-engine' ),
						'<'          => __( 'Is less than', 'mai-engine' ),
						'<='         => __( 'Is less than or equal to', 'mai-engine' ),
						'EXISTS'     => __( 'Exists', 'mai-engine' ),
						'NOT EXISTS' => __( 'Does not exist', 'mai-engine' ),
					],
					'conditional_logic' => [
						[
							'field'    => 'mai_grid_block_post_meta_key',
							'operator' => '!=empty',
						],
					],
				],
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				[
					'key'               => 'mai_grid_block_post_meta_value',
					'name'              => 'meta_value',
					'label'             => esc_html__( 'Meta Value', 'mai-engine' ),
					'type'              => 'text',
					'default_value'     => $defaults['meta_value'],
					'conditional_logic' => [
						[
							'field'    => 'mai_grid_block_post_meta_key',
							'operator' => '!=empty',
						],
						[
							'field'    => 'mai_grid_block_post_meta_compare',
							'operator' => '!=',
							'value'    => 'EXISTS',
						],
						[
							'field'    => 'mai_grid_block_post_meta_compare',
							'operator' => '!=',
							'value'    => 'NOT EXISTS',
						],
					],
				],
			],
		],
		[
			'key'               => 'mai_grid_block_post_meta_keys_relation',
			'name'              => 'meta_keys_relation',
			'label'             => esc_html__( 'Meta Keys Relation', 'mai-engine' ),
			'type'              => 'select',
			'default_value'     => $defaults['meta_keys_relation'],
			'choices'           => [
				'AND' => esc_html__( 'And', 'mai-engine' ),
				'OR'  => esc_html__( 'Or', 'mai-engine' ),
			],
			'conditional_logic' => [
				[
					'field'    => 'mai_grid_block_post_type',
					'operator' => '!=empty',
				],
				[
					'field'    => 'mai_grid_block_query_by',
					'operator' => '==',
					'value'    => 'tax_meta',
				],
				[
					'field'    => 'mai_grid_block_post_meta_keys',
					'operator' => '>',
					'value'    => '1', // More than 1 row.
				],
			],
		],
		[
			'key'               => 'mai_grid_block_post_current_children',
			'name'              => 'current_children',
			'label'             => '',
			'type'              => 'true_false',
			// 'sanitize'       => 'mai_sanitize_bool',
			'default_value'     => $defaults['current_children'],
			'message'           => esc_html__( 'Show children of current entry', 'mai-engine' ),
			'conditional_logic' => [
				[
					'field'    => 'mai_grid_block_post_type',
					'operator' => '!=empty',
				],
				[
					'field'    => 'mai_grid_block_query_by',
					'operator' => '==',
					'value'    => 'parent',
				],
			],
		],
		[
			'key'               => 'mai_grid_block_post_parent_in',
			'name'              => 'post_parent__in',
			'label'             => esc_html__( 'Parent', 'mai-engine' ),
			'type'              => 'post_object',
			// 'sanitize'       => 'absint',
			'default_value'     => $defaults['post_parent__in'],
			'multiple'          => 1, // WP_Query allows multiple parents.
			'return_format'     => 'id',
			'ui'                => 1,
			'conditional_logic' => [
				[
					'field'    => 'mai_grid_block_post_type',
					'operator' => '!=empty',
				],
				[
					'field'    => 'mai_grid_block_query_by',
					'operator' => '==',
					'value'    => 'parent',
				],
				[
					'field'    => 'mai_grid_block_post_current_children',
					'operator' => '!=',
					'value'    => 1,
				],
			],
		],
		[
			'key'               => 'mai_grid_block_posts_per_page',
			'name'              => 'posts_per_page',
			'label'             => esc_html__( 'Number of Entries', 'mai-engine' ),
			'desc'              => esc_html__( 'Use 0 to show all.', 'mai-engine' ),
			'type'              => 'number',
			// 'sanitize'       => 'absint',
			'default_value'     => $defaults['posts_per_page'],
			// 'placeholder'    => 12,
			'min'               => 0,
			'conditional_logic' => [
				[
					'field'    => 'mai_grid_block_post_type',
					'operator' => '!=empty',
				],
				[
					'field'    => 'mai_grid_block_query_by',
					'operator' => '!=',
					'value'    => 'id',
				],
			],
		],
		[
			'key'               => 'mai_grid_block_posts_offset',
			'name'              => 'offset',
			'label'             => esc_html__( 'Offset', 'mai-engine' ),
			'desc'              => esc_html__( 'Skip this number of entries.', 'mai-engine' ),
			'type'              => 'number',
			// 'sanitize'       => 'absint',
			'default_value'     => $defaults['offset'],
			'placeholder'       => 0,
			'min'               => 0,
			'conditional_logic' => [
				[
					'field'    => 'mai_grid_block_post_type',
					'operator' => '!=empty',
				],
				[
					'field'    => 'mai_grid_block_query_by',
					'operator' => '!=',
					'value'    => 'id',
				],
			],
		],
		[
			'key'               => 'mai_grid_block_posts_date_after',
			'name'              => 'date_after',
			'label'             => __( 'After date', 'mai-engine' ),
			'desc'              => sprintf( esc_html__( 'Get posts after a date/time. %s', 'mai-engine' ), $date_info ),
			'type'              => 'text',
			'default_value'     => $defaults['date_after'],
			'placeholder'       => '3 months ago', // Can't translate. I think strtotime() requires English?
			'conditional_logic' => [
				[
					'field'    => 'mai_grid_block_post_type',
					'operator' => '!=empty',
				],
				[
					'field'    => 'mai_grid_block_query_by',
					'operator' => '!=',
					'value'    => 'id',
				],
			],
		],
		[
			'key'               => 'mai_grid_block_posts_date_before',
			'name'              => 'date_before',
			'label'             => __( 'Before date', 'mai-engine' ),
			'desc'              => sprintf( esc_html__( 'Get posts after a date/time. %s', 'mai-engine' ), $date_info ),
			'type'              => 'text',
			'default_value'     => $defaults['date_before'],
			'placeholder'       => '30 days', // Can't translate. I think strtotime() requires English?
			'conditional_logic' => [
				[
					'field'    => 'mai_grid_block_post_type',
					'operator' => '!=empty',
				],
				[
					'field'    => 'mai_grid_block_query_by',
					'operator' => '!=',
					'value'    => 'id',
				],
			],
		],
		[
			'key'               => 'mai_grid_block_posts_orderby',
			'name'              => 'orderby',
			'label'             => esc_html__( 'Order By', 'mai-engine' ),
			'type'              => 'select',
			'default_value'     => $defaults['orderby'],
			'ui'                => 1,
			'ajax'              => 1,
			'choices'           => [
				'title'          => esc_html__( 'Title', 'mai-engine' ),
				'name'           => esc_html__( 'Slug', 'mai-engine' ),
				'date'           => esc_html__( 'Date', 'mai-engine' ),
				'modified'       => esc_html__( 'Modified', 'mai-engine' ),
				'rand'           => esc_html__( 'Random', 'mai-engine' ),
				'comment_count'  => esc_html__( 'Comment Count', 'mai-engine' ),
				'menu_order'     => esc_html__( 'Menu Order', 'mai-engine' ),
				'meta_value_num' => esc_html__( 'Meta Value Number', 'mai-engine' ),
			],
			'conditional_logic' => [
				[
					'field'    => 'mai_grid_block_post_type',
					'operator' => '!=empty',
				],
				[
					'field'    => 'mai_grid_block_query_by',
					'operator' => '!=',
					'value'    => 'id',
				],
			],
		],
		[
			'key'               => 'mai_grid_block_posts_orderby_meta_key',
			'name'              => 'orderby_meta_key',
			'label'             => esc_html__( 'Meta key', 'mai-engine' ),
			'type'              => 'text',
			'default_value'     => $defaults['orderby_meta_key'],
			'conditional_logic' => [
				[
					'field'    => 'mai_grid_block_post_type',
					'operator' => '!=empty',
				],
				[
					'field'    => 'mai_grid_block_posts_orderby',
					'operator' => '==',
					'value'    => 'meta_value_num',
				],
			],
		],
		[
			'key'               => 'mai_grid_block_posts_order',
			'name'              => 'order',
			'label'             => esc_html__( 'Order', 'mai-engine' ),
			'type'              => 'select',
			'default_value'     => $defaults['order'],
			'choices'           => [
				'ASC'  => esc_html__( 'Ascending', 'mai-engine' ),
				'DESC' => esc_html__( 'Descending', 'mai-engine' ),
			],
			'conditional_logic' => [
				[
					'field'    => 'mai_grid_block_post_type',
					'operator' => '!=empty',
				],
				[
					'field'    => 'mai_grid_block_query_by',
					'operator' => '!=',
					'value'    => 'id',
				],
			],
		],
		[
			'key'               => 'mai_grid_block_post_not_in',
			'name'              => 'post__not_in',
			'label'             => esc_html__( 'Exclude Entries', 'mai-engine' ),
			'desc'              => esc_html__( 'Hide specific entries. Choose all that apply.', 'mai-engine' ),
			'type'              => 'post_object',
			'default_value'     => $defaults['post__not_in'],
			'multiple'          => 1,
			'return_format'     => 'id',
			'ui'                => 1,
			// 'sanitize'       => 'absint',
			'conditional_logic' => [
				[
					'field'    => 'mai_grid_block_post_type',
					'operator' => '!=empty',
				],
				[
					'field'    => 'mai_grid_block_query_by',
					'operator' => '!=',
					'value'    => 'id',
				],
			],
		],
		[
			'key'                => 'mai_grid_block_posts_exclude',
			'name'               => 'excludes',
			'label'              => esc_html__( 'Exclude', 'mai-engine' ),
			'type'               => 'checkbox',
			'default_value'      => $defaults['excludes'],
			'choices'            => [
				'exclude_displayed' => esc_html__( 'Exclude displayed', 'mai-engine' ),
				'exclude_current'   => esc_html__( 'Exclude current', 'mai-engine' ),
			],
			'conditional_logic'  => [
				[
					'field'    => 'mai_grid_block_post_type',
					'operator' => '!=empty',
				],
				[
					'field'    => 'mai_grid_block_query_by',
					'operator' => '!=',
					'value'    => 'id',
				],
			],
		],
	];

	return $fields;
}
