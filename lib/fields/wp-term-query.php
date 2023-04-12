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

add_filter( 'acf/load_field/key=mai_grid_block_taxonomy', 'mai_grid_load_taxonomy_field' );
/**
 * Loads taxonomy choices.
 *
 * @since 2.21.0
 * @since 2.25.6 Only run in admin.
 *
 * @param array $field The existing field array.
 *
 * @return array
 */
function mai_grid_load_taxonomy_field( $field ) {
	if ( ! is_admin() ) {
		return $field;
	}

	$field['choices'] = mai_get_taxonomy_choices();

	return $field;
}

add_filter( 'acf/load_field/key=mai_grid_block_tax_include', 'mai_grid_load_include_terms_field' );
add_filter( 'acf/load_field/key=mai_grid_block_tax_exclude', 'mai_grid_load_include_terms_field' );
/**
 * Sets taxonomy based on the block taxonomies field, when the include/exclude fields are initially loaded.
 *
 * @since TBD
 *
 * @param array $field
 *
 * @return array
 */
function mai_grid_load_include_terms_field( $field ) {
	if ( ! is_admin() ) {
		return $field;
	}

	$action = mai_get_acf_request( 'action' );
	$block  = mai_get_acf_request( 'block' );

	if ( ! ( $action && $block && 'acf/ajax/fetch-block' === $action ) ) {
		return $field;
	}

	$block = json_decode( wp_unslash( $block ), true );

	if ( ! isset( $block['name'] ) || 'acf/mai-term-grid' !== $block['name'] ) {
		return $field;
	}

	if ( isset( $block['data']['taxonomy'] ) ) {
		$taxonomies        = (array) $block['data']['taxonomy'];
		$field['taxonomy'] = reset( $taxonomies );
	}

	return $field;
}

add_filter( 'acf/fields/taxonomy/query/key=mai_grid_block_tax_include', 'mai_acf_get_terms', 10, 3 );
add_filter( 'acf/fields/taxonomy/query/key=mai_grid_block_tax_exclude', 'mai_acf_get_terms', 10, 3 );
/**
 * Get terms from an ajax query.
 * The taxonomy is passed via JS on select2_query_args filter.
 *
 * @since 0.1.0
 * @since 2.25.6 Only run in admin.
 * @since TBD Force first taxonomy. See #631.
 *
 * @param array $args Field args.
 *
 * @return mixed
 */
function mai_acf_get_terms( $args, $field, $post_id  ) {
	if ( ! is_admin() ) {
		return $args;
	}

	$args['taxonomy'] = [];
	$taxonomies       = mai_get_acf_request( 'taxonomy' );

	if ( ! $taxonomies ) {
		return $args;
	}

	// Forces first taxonomy, since ACF's Taxonomy field type does not allow multiple taxonomies.
	$taxonomies       = wp_unslash( (array) $taxonomies );
	$args['taxonomy'] = reset( $taxonomies );

	return $args;
}

add_filter( 'acf/fields/taxonomy/query/key=mai_grid_block_tax_parent', 'mai_acf_get_term_parents', 10, 1 );
/**
 * Get taxonomies from an ajax query.
 * The taxonomy is passed via JS on select2_query_args filter.
 *
 * @since 0.1.0
 * @since 2.25.6 Only run in admin.
 *
 * @param array $args Field args.
 *
 * @return mixed
 */
function mai_acf_get_term_parents( $args ) {
	if ( ! is_admin() ) {
		return $args;
	}

	$args['taxonomy'] = [];
	$taxonomies       = mai_get_acf_request( 'taxonomy' );

	if ( ! $taxonomies ) {
		return $args;
	}

	foreach ( (array) $taxonomies as $taxonomy ) {
		$args['taxonomy'][] = sanitize_text_field( wp_unslash( $taxonomy ) );
	}

	foreach ( $args['taxonomy'] as $index => $taxonomy ) {
		if ( ! is_taxonomy_hierarchical( $taxonomy ) ) {
			unset( $args['taxonomy'][ $index ] );
		}

		continue;
	}

	return $args;
}

add_filter( 'acf/fields/taxonomy/query/key=mai_grid_block_tax_include', 'mai_acf_get_terms_by_id', 10, 3 );
add_filter( 'acf/fields/taxonomy/query/key=mai_grid_block_tax_exclude', 'mai_acf_get_terms_by_id', 10, 3 );
add_filter( 'acf/fields/taxonomy/query/key=mai_grid_block_tax_parent',  'mai_acf_get_terms_by_id', 10, 3 );
/**
 * Allow searching for terms by ID.
 *
 * @since 2.22.0
 * @since 2.25.6 Only run in admin.
 *
 * @link https://www.powderkegwebdesign.com/fantastic-way-allow-searching-id-advanced-custom-fields-objects/
 *
 * @return array
 */
function mai_acf_get_terms_by_id( $args, $field, $post_id ) {
	if ( ! is_admin() ) {
		return $args;
	}

	$query = ! empty( $args['search'] ) ? $args['search'] : false;

	if ( ! $query ) {
		return $args;
	}

	// Bail if not a numeric query.
 	if ( ! is_numeric( $query ) ) {
		return $args;
	}

	// Set the term ID in the query.
	$args['include'] = [ $query ];

	// Unset the actual search param.
	unset( $args['search'] );

	return $args;
}

/**
 * Get taxonomy choices for select field
 *
 * @since 0.1.0
 *
 * @return array
 */
function mai_get_taxonomy_choices() {
	$choices = [];

	$taxonomies = get_taxonomies(
		[
			'public' => true,
		],
		'objects',
		'or'
	);

	if ( $taxonomies ) {

		// Remove taxonomies we don't want.
		unset( $taxonomies['post_format'] );
		unset( $taxonomies['product_shipping_class'] );
		unset( $taxonomies['yst_prominent_words'] );

		foreach ( $taxonomies as $name => $taxonomy ) {
			$choices[ $name ] = $taxonomy->label;
		}
	}

	return $choices;
}

/**
 * Get taxonomy choices from the current post type.
 * The post_type is passed via JS on select2_query_args filter.
 *
 * @since 0.1.0
 * @since 2.18.0 Added $fallback.
 *
 * @param bool $fallback Whether to use fallback choices.
 *
 * @return array
 */
function mai_get_post_types_taxonomy_choices( $fallback = true ) {
	$choices = [];

	if ( ! ( is_admin() || is_customize_preview() ) ) {
		return $choices;
	}

	$post_types = mai_get_acf_request( 'post_type' );

	if ( ! $post_types && $fallback ) {
		$taxonomies = get_taxonomies( [], 'objects' );
		$choices    = wp_list_pluck( $taxonomies, 'label', 'name' );

		return $choices;
	}

	return mai_get_taxonomy_choices_from_post_types( $post_types );
}

/**
 * Get taxonomy choices from an array of post types.
 *
 * @since 0.3.3
 *
 * @param array $post_types Array of registered post_type names.
 *
 * @return array
 */
function mai_get_taxonomy_choices_from_post_types( $post_types = [] ) {
	$choices = [];

	if ( ! $post_types ) {
		return $choices;
	}

	foreach ( (array) $post_types as $post_type ) {
		$taxonomies = get_object_taxonomies( sanitize_text_field( wp_unslash( $post_type ) ), 'objects' );

		if ( $taxonomies ) {

			// Remove taxonomies we don't want.
			unset( $taxonomies['post_format'] );
			unset( $taxonomies['product_shipping_class'] );
			unset( $taxonomies['yst_prominent_words'] );

			foreach ( $taxonomies as $name => $taxo ) {
				$choices[ $name ] = $taxo->label;
			}
		}
	}

	return $choices;
}

/**
 * Get term choices from a taxonomy
 *
 * @since 0.3.3
 *
 * @param string $taxonomy A registered taxonomy name.
 *
 * @return array
 */
function mai_get_term_choices_from_taxonomy( $taxonomy = '' ) {
	$choices = [];
	$terms   = get_terms(
		$taxonomy,
		[
			'hide_empty' => false,
		]
	);

	if ( ! $terms ) {
		return $choices;
	}

	foreach ( $terms as $term ) {
		$choices[ $term->term_id ] = $term->name;
	}

	return $choices;
}

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
 * @since 2.21.0
 *
 * @return array
 */
function mai_get_wp_term_query_sanitized( $args ) {
	$array = [
		'taxonomy'         => 'esc_html',
		'query_by'         => 'esc_html',
		'include'          => 'absint',
		'current_children' => 'mai_sanitize_bool',
		'parent'           => 'absint',
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
 * @since 2.21.0
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
			'default_value' => $defaults['taxonomy'],
			'choices'       => '', // Loaded in filter later.
			'multiple'      => 1,
			'ui'            => 1,
			'ajax'          => 0,
		],
		[
			'key'               => 'mai_grid_block_tax_query_by',
			'name'              => 'query_by',
			'label'             => esc_html__( 'Get Entries By', 'mai-engine' ),
			'type'              => 'select',
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
			'default_value'     => $defaults['offset'],
			'placeholder'       => 0,
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
			'key'               => 'mai_grid_block_tax_orderby',
			'name'              => 'orderby',
			'label'             => esc_html__( 'Order By', 'mai-engine' ),
			'type'              => 'select',
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
