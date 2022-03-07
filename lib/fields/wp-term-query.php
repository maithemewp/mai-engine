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
function mai_get_wp_term_query_defaults() {
	static $defaults = null;

	if ( ! is_null( $defaults ) ) {
		return $defaults;
	}

	$defaults = [
		'taxonomy'         => [ 'category' ],
		'query_by'         => 'name',
		'include'          => '',
		'current_children' => 0,
		'parent'           => '',
		'number'           => 12,
		'offset'           => 0,
		'orderby'          => 'name',
		'order'            => 'ASC',
		'exclude'          => '',
		'excludes'         => [ 'hide_empty' ],
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
function mai_get_wp_term_query_sanitized( $args ) {
	$array = [
		'taxonomy'         => 'esc_html',
		'query_by'         => 'esc_html',
		'include'          => 'absint',
		'current_children' => 'mai_sanitize_bool',
		'parent'           => 'mai_sanitize_bool',
		'number'           => 'absint',
		'offset'           => 'absint',
		'orderby'          => 'esc_html',
		'order'            => 'esc_html',
		'exclude'          => 'absint',
		'excludes'         => 'esc_html',
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
function mai_get_wp_term_query_fields() {
	static $fields = null;

	if ( ! is_null( $fields ) ) {
		return $fields;
	}

	$defaults = mai_get_wp_term_query_defaults();
	$fields   = [
		[
			'key'           => 'mai_grid_block_taxonomy',
			'name'          => 'taxonomy',
			'label'         => esc_html__( 'Taxonomy', 'mai-engine' ),
			'type'          => 'select',
			// 'sanitize'   => 'esc_html',
			'default_value' => $defaults['taxonomy'],
			'choices'       => mai_get_taxonomy_choices(),
			'multiple'      => 1,
			'ui'            => 1,
			'ajax'          => 0,
		],
		[
			'key'               => 'mai_grid_block_tax_query_by',
			'name'              => 'query_by',
			'label'             => esc_html__( 'Get Entries By', 'mai-engine' ),
			'type'              => 'select',
			// 'sanitize'       => 'esc_html',
			'default_value'     => $defaults['query_by'],
			'choices'           => [
				'name'   => esc_html__( 'Taxonomy', 'mai-engine' ),
				'id'     => esc_html__( 'Choice', 'mai-engine' ),
				'parent' => esc_html__( 'Parent', 'mai-engine' ),
			],
			'conditional_logic' => [
				[
					'field'    => 'mai_grid_block_taxonomy',
					'operator' => '!=empty',
				],
			],
		],
		[
			'key'               => 'mai_grid_block_tax_include',
			'name'              => 'include',
			'label'             => esc_html__( 'Entries', 'mai-engine' ),
			'desc'              => esc_html__( 'Show specific entries. Choose all that apply. If empty, Grid will get entries by date.', 'mai-engine' ),
			'type'              => 'taxonomy',
			// 'sanitize'       => 'absint',
			'default_value'     => $defaults['include'],
			'field_type'        => 'multi_select',
			'add_term'          => 0,
			'save_terms'        => 0,
			'load_terms'        => 0,
			'multiple'          => 1,
			'conditional_logic' => [
				[
					'field'    => 'mai_grid_block_taxonomy',
					'operator' => '!=empty',
				],
				[
					'field'    => 'mai_grid_block_tax_query_by',
					'operator' => '==',
					'value'    => 'id',
				],
			],
		],
		[
			'key'               => 'mai_grid_block_current_children',
			'name'              => 'current_children',
			'label'             => '',
			'type'              => 'true_false',
			// 'sanitize'       => 'mai_sanitize_bool',
			'default_value'     => $defaults['current_children'],
			'message'           => esc_html__( 'Show children of current entry', 'mai-engine' ),
			'conditional_logic' => [
				[
					'field'    => 'mai_grid_block_taxonomy',
					'operator' => '!=empty',
				],
				[
					'field'    => 'mai_grid_block_tax_query_by',
					'operator' => '==',
					'value'    => 'parent',
				],
			],
		],
		[
			'key'               => 'mai_grid_block_tax_parent',
			'name'              => 'parent',
			'label'             => esc_html__( 'Parent', 'mai-engine' ),
			'type'              => 'taxonomy',
			// 'sanitize'       => 'absint',
			'default_value'     => $defaults['parent'],
			'field_type'        => 'select',
			'add_term'          => 0,
			'save_terms'        => 0,
			'load_terms'        => 0,
			'multiple'          => 0, // WP_Term_Query only allows 1 taxonomy.
			'conditional_logic' => [
				[
					'field'    => 'mai_grid_block_taxonomy',
					'operator' => '!=empty',
				],
				[
					'field'    => 'mai_grid_block_tax_query_by',
					'operator' => '==',
					'value'    => 'parent',
				],
				[
					'field'    => 'mai_grid_block_current_children',
					'operator' => '!=',
					'value'    => 1,
				],
			],
		],
		[
			'key'               => 'mai_grid_block_tax_number',
			'name'              => 'number',
			'label'             => esc_html__( 'Number of Entries', 'mai-engine' ),
			'desc'              => esc_html__( 'Use 0 to show all.', 'mai-engine' ),
			'type'              => 'number',
			// 'sanitize'       => 'absint',
			'default_value'     => $defaults['number'],
			'placeholder'       => 12,
			'min'               => 0,
			'conditional_logic' => [
				[
					'field'    => 'mai_grid_block_taxonomy',
					'operator' => '!=empty',
				],
				[
					'field'    => 'mai_grid_block_tax_query_by',
					'operator' => '!=',
					'value'    => 'id',
				],
			],
		],
		[
			'key'               => 'mai_grid_block_tax_offset',
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
					'field'    => 'mai_grid_block_taxonomy',
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
			'key'               => 'mai_grid_block_tax_orderby',
			'name'              => 'orderby',
			'label'             => esc_html__( 'Order By', 'mai-engine' ),
			'type'              => 'select',
			// 'sanitize'       => 'esc_html',
			'default_value'     => $defaults['orderby'],
			'ui'                => 1,
			'ajax'              => 1,
			'choices'           => [
				'name'  => esc_html__( 'Title', 'mai-engine' ),
				'slug'  => esc_html__( 'Slug', 'mai-engine' ),
				'count' => esc_html__( 'Entry Totals', 'mai-engine' ),
				'id'    => esc_html__( 'Term ID', 'mai-engine' ),
			],
			'conditional_logic' => [
				[
					'field'    => 'mai_grid_block_taxonomy',
					'operator' => '!=empty',
				],
				[
					'field'    => 'mai_grid_block_tax_query_by',
					'operator' => '!=',
					'value'    => 'id',
				],
			],
		],
		[
			'key'               => 'mai_grid_block_tax_order',
			'name'              => 'order',
			'label'             => esc_html__( 'Order', 'mai-engine' ),
			'type'              => 'select',
			// 'sanitize'       => 'esc_html',
			'default_value'     => $defaults['order'],
			'choices'           => [
				'ASC'  => esc_html__( 'Ascending', 'mai-engine' ),
				'DESC' => esc_html__( 'Descending', 'mai-engine' ),
			],
			'conditional_logic' => [
				[
					'field'    => 'mai_grid_block_taxonomy',
					'operator' => '!=empty',
				],
				[
					'field'    => 'mai_grid_block_tax_query_by',
					'operator' => '!=',
					'value'    => 'id',
				],
			],
		],
		[
			'key'               => 'mai_grid_block_tax_exclude',
			'name'              => 'exclude',
			'label'             => esc_html__( 'Exclude Entries', 'mai-engine' ),
			'desc'              => esc_html__( 'Hide specific entries. Choose all that apply.', 'mai-engine' ),
			'type'              => 'taxonomy',
			'default_value'     => $defaults['exclude'],
			// 'sanitize'       => 'absint',
			'field_type'        => 'multi_select',
			'add_term'          => 0,
			'save_terms'        => 0,
			'load_terms'        => 0,
			'multiple'          => 1,
			'conditional_logic' => [
				[
					'field'    => 'mai_grid_block_taxonomy',
					'operator' => '!=empty',
				],
				[
					'field'    => 'mai_grid_block_tax_query_by',
					'operator' => '!=',
					'value'    => 'id',
				],
			],
		],
		// TODO: Shoud these be separate fields? We can then have desc text and easier to check when building query.
		[
			'key'                => 'mai_grid_block_tax_excludes',
			'name'               => 'excludes',
			'label'              => esc_html__( 'Exclude', 'mai-engine' ),
			'type'               => 'checkbox',
			// 'sanitize'        => 'esc_html',
			'default_value'      => $defaults['excludes'],
			'choices'            => [
				'hide_empty'        => esc_html__( 'Exclude terms with no posts', 'mai-engine' ),
				'exclude_displayed' => esc_html__( 'Exclude displayed', 'mai-engine' ),
				'exclude_current'   => esc_html__( 'Exclude current', 'mai-engine' ),
			],
			'conditional_logic'  => [
				[
					'field'    => 'mai_grid_block_taxonomy',
					'operator' => '!=empty',
				],
			],
		],
	];

	return $fields;
}
