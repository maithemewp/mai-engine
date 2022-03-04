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

// Mai Post Grid.
add_filter( 'acf/load_field/key=mai_grid_block_show', 'mai_acf_load_show', 10, 1 );
add_filter( 'acf/fields/post_object/query/key=mai_grid_block_post_in', 'mai_acf_get_posts', 10, 1 );
add_filter( 'acf/load_field/key=mai_grid_block_tax_terms', 'mai_acf_load_terms', 10, 1 );
add_filter( 'acf/prepare_field/key=mai_grid_block_tax_terms', 'mai_acf_prepare_terms', 10, 1 );
add_filter( 'acf/fields/post_object/query/key=mai_grid_block_post_not_in', 'mai_acf_get_posts', 10, 1 );
add_filter( 'acf/fields/post_object/query/key=mai_grid_block_post_parent_in', 'mai_acf_get_post_parents', 10, 1 );
add_filter( 'acf/fields/post_object/query/key=mai_grid_block_post_in', 'mai_acf_get_posts_by_id', 12, 3 );
add_filter( 'acf/fields/post_object/query/key=mai_grid_block_post_not_in', 'mai_acf_get_posts_by_id', 12, 3 );
add_filter( 'acf/fields/post_object/query/key=mai_grid_block_post_parent_in','mai_acf_get_posts_by_id', 12, 3 );

// Mai Term Grid.
add_filter( 'acf/fields/taxonomy/query/key=mai_grid_block_tax_include', 'mai_acf_get_terms', 10, 1 );
add_filter( 'acf/fields/taxonomy/query/key=mai_grid_block_tax_exclude', 'mai_acf_get_terms', 10, 1 );
add_filter( 'acf/fields/taxonomy/query/key=mai_grid_block_tax_parent', 'mai_acf_get_term_parents', 10, 1 );

add_filter( 'acf/prepare_field/key=mai_grid_block_column_gap', 'mai_acf_load_gap', 10, 1 );
add_filter( 'acf/prepare_field/key=mai_grid_block_row_gap', 'mai_acf_load_gap', 10, 1 );

add_action( 'acf/init', 'mai_register_grid_blocks' );
/**
 * Register Mai Grid blocks.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_register_grid_blocks() {
	if ( ! function_exists( 'acf_register_block_type' ) ) {
		return;
	}

	$icon = '<svg style="display:block;" width="20" height="20" viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;"><g transform="matrix(1,0,0,1,13,-2)"><g transform="matrix(1,0,0,3,0,-28)"><path d="M9,16.125C9,16.056 8.832,16 8.625,16L3.375,16C3.168,16 3,16.056 3,16.125L3,16.375C3,16.444 3.168,16.5 3.375,16.5L8.625,16.5C8.832,16.5 9,16.444 9,16.375L9,16.125Z"/></g><g transform="matrix(1,0,0,3,0,-31)"><path d="M9,16.125C9,16.056 8.832,16 8.625,16L3.375,16C3.168,16 3,16.056 3,16.125L3,16.375C3,16.444 3.168,16.5 3.375,16.5L8.625,16.5C8.832,16.5 9,16.444 9,16.375L9,16.125Z"/></g><g transform="matrix(1,0,0,3,0,-34)"><path d="M9,16.125C9,16.056 8.832,16 8.625,16L3.375,16C3.168,16 3,16.056 3,16.125L3,16.375C3,16.444 3.168,16.5 3.375,16.5L8.625,16.5C8.832,16.5 9,16.444 9,16.375L9,16.125Z"/></g><g transform="matrix(0.333333,0,0,0.333333,2,5.5)"><path d="M19,3L5,3C3.9,3 3,3.9 3,5L3,19C3,20.1 3.9,21 5,21L19,21C20.1,21 21,20.1 21,19L21,5C21,3.9 20.1,3 19,3ZM6,6L18,6L18,10.5C18,10.5 15.7,10.2 15.5,10.5L11.9,14L9,12C8.7,11.8 8.4,11.8 8.2,12L6,13.5L6,6ZM18,18L6,18L6,15L8.6,13.6L11.6,15.5C11.9,15.7 12.3,15.7 12.5,15.4L16,12L18,12L18,18Z" style="fill-rule:nonzero;"/></g></g><g transform="matrix(1,0,0,1,6,-2)"><g transform="matrix(1,0,0,3,0,-28)"><path d="M9,16.125C9,16.056 8.832,16 8.625,16L3.375,16C3.168,16 3,16.056 3,16.125L3,16.375C3,16.444 3.168,16.5 3.375,16.5L8.625,16.5C8.832,16.5 9,16.444 9,16.375L9,16.125Z"/></g><g transform="matrix(1,0,0,3,0,-31)"><path d="M9,16.125C9,16.056 8.832,16 8.625,16L3.375,16C3.168,16 3,16.056 3,16.125L3,16.375C3,16.444 3.168,16.5 3.375,16.5L8.625,16.5C8.832,16.5 9,16.444 9,16.375L9,16.125Z"/></g><g transform="matrix(1,0,0,3,0,-34)"><path d="M9,16.125C9,16.056 8.832,16 8.625,16L3.375,16C3.168,16 3,16.056 3,16.125L3,16.375C3,16.444 3.168,16.5 3.375,16.5L8.625,16.5C8.832,16.5 9,16.444 9,16.375L9,16.125Z"/></g><g transform="matrix(0.333333,0,0,0.333333,2,5.5)"><path d="M19,3L5,3C3.9,3 3,3.9 3,5L3,19C3,20.1 3.9,21 5,21L19,21C20.1,21 21,20.1 21,19L21,5C21,3.9 20.1,3 19,3ZM6,6L18,6L18,10.5C18,10.5 15.7,10.2 15.5,10.5L11.9,14L9,12C8.7,11.8 8.4,11.8 8.2,12L6,13.5L6,6ZM18,18L6,18L6,15L8.6,13.6L11.6,15.5C11.9,15.7 12.3,15.7 12.5,15.4L16,12L18,12L18,18Z" style="fill-rule:nonzero;"/></g></g><g transform="matrix(1,0,0,1,-1,-2)"><g transform="matrix(1,0,0,3,0,-28)"><path d="M9,16.125C9,16.056 8.832,16 8.625,16L3.375,16C3.168,16 3,16.056 3,16.125L3,16.375C3,16.444 3.168,16.5 3.375,16.5L8.625,16.5C8.832,16.5 9,16.444 9,16.375L9,16.125Z"/></g><g transform="matrix(1,0,0,3,0,-31)"><path d="M9,16.125C9,16.056 8.832,16 8.625,16L3.375,16C3.168,16 3,16.056 3,16.125L3,16.375C3,16.444 3.168,16.5 3.375,16.5L8.625,16.5C8.832,16.5 9,16.444 9,16.375L9,16.125Z"/></g><g transform="matrix(1,0,0,3,0,-34)"><path d="M9,16.125C9,16.056 8.832,16 8.625,16L3.375,16C3.168,16 3,16.056 3,16.125L3,16.375C3,16.444 3.168,16.5 3.375,16.5L8.625,16.5C8.832,16.5 9,16.444 9,16.375L9,16.125Z"/></g><g transform="matrix(0.333333,0,0,0.333333,2,5.5)"><path d="M19,3L5,3C3.9,3 3,3.9 3,5L3,19C3,20.1 3.9,21 5,21L19,21C20.1,21 21,20.1 21,19L21,5C21,3.9 20.1,3 19,3ZM6,6L18,6L18,10.5C18,10.5 15.7,10.2 15.5,10.5L11.9,14L9,12C8.7,11.8 8.4,11.8 8.2,12L6,13.5L6,6ZM18,18L6,18L6,15L8.6,13.6L11.6,15.5C11.9,15.7 12.3,15.7 12.5,15.4L16,12L18,12L18,18Z" style="fill-rule:nonzero;"/></g></g></svg>';

	// Mai Post Grid.
	acf_register_block_type(
		[
			'name'            => 'mai-post-grid',
			'title'           => __( 'Mai Post Grid', 'mai-engine' ),
			'description'     => __( 'Display posts/pages/cpts in various layouts.', 'mai-engine' ),
			'icon'            => $icon,
			'category'        => 'widgets',
			'keywords'        => [ 'grid', 'post', 'page' ],
			'mode'            => 'preview',
			'render_callback' => 'mai_do_post_grid_block',
			'supports'        => [
				'align'  => [ 'wide', 'full' ],
				'ancher' => true,
			],
		]
	);

	// Mai Term Grid.
	acf_register_block_type(
		[
			'name'            => 'mai-term-grid',
			'title'           => __( 'Mai Term Grid', 'mai-engine' ),
			'description'     => __( 'Display categories/tags/terms in various layouts.', 'mai-engine' ),
			'icon'            => $icon,
			'category'        => 'widgets',
			'keywords'        => [ 'grid', 'category', 'term' ],
			'mode'            => 'preview',
			'render_callback' => 'mai_do_term_grid_block',
			'supports'        => [
				'align'  => [ 'wide', 'full' ],
				'ancher' => true,
			],
		]
	);
}

/**
 * Renders a post grid block.
 *
 * @since 0.1.0
 *
 * @param array      $block      Block object.
 * @param string     $content    String of content.
 * @param bool       $is_preview Is preview check.
 * @param int|string $post_id    The post ID this block is saved to.
 *
 * @return void
 */
function mai_do_post_grid_block( $block, $content = '', $is_preview = false, $post_id = 0 ) {
	// TODO: block id?
	mai_do_grid_block( 'post', $block, $content = '', $is_preview, $post_id );
}

/**
 * Renders a term grid block.
 *
 * @since 0.1.0
 *
 * @param array      $block      Block object.
 * @param string     $content    String of content.
 * @param bool       $is_preview Is preview check.
 * @param int|string $post_id    The post ID this block is saved to.
 *
 * @return void
 */
function mai_do_term_grid_block( $block, $content = '', $is_preview = false, $post_id = 0 ) {
	// TODO: block id?
	mai_do_grid_block( 'term', $block, $content = '', $is_preview, $post_id );
}

/**
 * Renders a grid block.
 *
 * @since 0.1.0
 *
 * @param string     $type       Type of grid.
 * @param array      $block      Block object.
 * @param string     $content    Content string.
 * @param bool       $is_preview Is preview check.
 * @param int|string $post_id    The post ID this block is saved to.
 *
 * @return void
 */
function mai_do_grid_block( $type, $block, $content = '', $is_preview = false, $post_id = 0 ) {
	$args          = mai_get_grid_field_values( $type );
	$args['class'] = isset( $args['class'] ) ? $args['class'] : '';

	if ( ! empty( $block['className'] ) ) {
		$args['class'] = mai_add_classes( $block['className'], $args['class'] );
	}

	if ( ! empty( $block['align'] ) ) {
		$args['class'] = mai_add_classes( 'align' . $block['align'], $args['class'] );
	}

	$args['preview'] = $is_preview;

	mai_do_grid( $type, $args );
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
	$fields = mai_get_grid_block_settings();
	$values = [];

	foreach ( $fields as $key => $field ) {
		// Skip tabs.
		if ( 'tab' === $field['type'] ) {
			continue;
		}

		// Skip if not the block we want.
		if ( ! in_array( $type, $field['block'], true ) ) {
			continue;
		}

		$value                    = get_field( $field['name'] );
		$values[ $field['name'] ] = is_null( $value ) ? $fields[ $key ]['default'] : $value;
	}

	return $values;
}

add_action( 'init', 'mai_register_grid_field_groups', 99 );
/**
 * Register field groups for the grid block.
 * This can't be on 'after_setup_theme' or 'acf/init' hook because it's too early,
 * and get_post_types() doesn't get all custom post types.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_register_grid_field_groups() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	// $post_grid = [];
	// $term_grid = [];
	// $user_grid = [];

	// // Get fields.
	// $fields = mai_get_grid_block_settings();

	// foreach ( $fields as $key => $field ) {

	// 	// Post grid.
	// 	if ( in_array( 'post', $field['block'], true ) ) {
	// 		$post_grid[] = mai_get_acf_field_data( $key, $field );
	// 	}
	// 	// Term grid.
	// 	if ( in_array( 'term', $field['block'], true ) ) {
	// 		$term_grid[] = mai_get_acf_field_data( $key, $field );
	// 	}
	// 	// Post grid.
	// 	if ( in_array( 'user', $field['block'], true ) ) {
	// 		$user_grid[] = mai_get_acf_field_data( $key, $field );
	// 	}
	// }

	$fields = [
		// Display.
		'mai_grid_block_display_tab',
		'mai_grid_block_show',
		'mai_grid_block_title_size',
		'mai_grid_block_image_orientation',
		'mai_grid_block_image_size',
		'mai_grid_block_image_position',
		'mai_grid_block_image_alternate',
		'mai_grid_block_image_width',
		'mai_grid_block_header_meta',
		'mai_grid_block_custom_content',
		'mai_grid_block_content_limit',
		'mai_grid_block_more_link_text',
		'mai_grid_block_footer_meta',
		'mai_grid_block_align_text',
		'mai_grid_block_align_text_vertical',
		'mai_grid_block_image_stack',
		'mai_grid_block_boxed',
		'mai_grid_block_border_radius',
		// Layout.
		'mai_grid_block_layout_tab',
		'mai_grid_block_columns',
		'mai_grid_block_columns_responsive',
		'mai_grid_block_columns_md',
		'mai_grid_block_columns_sm',
		'mai_grid_block_columns_xs',
		'mai_grid_block_align_columns',
		'mai_grid_block_align_columns_vertical',
		'mai_grid_block_column_gap',
		'mai_grid_block_row_gap',
		'mai_grid_block_remove_spacing', // TODO: Convert this to margin top/bottom? Check if this is true and set to None, if false auto set to MD?
	];

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
					'clone'   => array_merge(
						$fields,
						[
							// Entries.
							'mai_grid_block_entries_tab',
							'mai_grid_block_post_type',
							'mai_grid_block_query_by',
							'mai_grid_block_post_in',
							'mai_grid_block_post_taxonomies',
							'mai_grid_block_post_taxonomies_relation',
							'mai_grid_block_post_meta_keys',
							'mai_grid_block_post_meta_keys_relation',
							'mai_grid_block_post_current_children',
							'mai_grid_block_post_parent_in',
							'mai_grid_block_posts_per_page',
							'mai_grid_block_posts_offset',
							'mai_grid_block_posts_date_after',
							'mai_grid_block_posts_date_before',
							'mai_grid_block_posts_orderby',
							'mai_grid_block_posts_orderby_meta_key',
							'mai_grid_block_posts_order',
							'mai_grid_block_post_not_in',
							'mai_grid_block_posts_exclude',
							'mai_grid_block_disable_entry_link',
						]
					),
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
					'clone'   => array_merge(
						$fields,
						[
							// Entries.
							'mai_grid_block_taxonomy',
							'mai_grid_block_tax_query_by',
							'mai_grid_block_tax_include',
							'mai_grid_block_current_children',
							'mai_grid_block_tax_parent',
							'mai_grid_block_tax_number',
							'mai_grid_block_tax_offset',
							'mai_grid_block_tax_orderby',
							'mai_grid_block_tax_order',
							'mai_grid_block_tax_exclude',
							'mai_grid_block_tax_excludes',
							'mai_grid_block_disable_entry_link',
						]
					),
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
 * Gets ACF field data and prepares it for field registration.
 *
 * @since 0.1.0
 *
 * @param string $key   Field key.
 * @param array  $field Field data.
 *
 * @return array
 */
function mai_get_acf_field_data( $key, $field ) {

	// Setup data.
	$data = [
		'key'   => $key,
		'name'  => $field['name'],
		'label' => $field['label'],
		'type'  => $field['type'],
	];

	// Maybe add description.
	if ( isset( $field['desc'] ) ) {
		$data['instructions'] = $field['desc'];
	}

	// Additional attributes.
	if ( isset( $field['atts'] ) ) {
		foreach ( $field['atts'] as $field_key => $value ) {

			// Sub fields.
			if ( 'sub_fields' === $field_key ) {
				$data['sub_fields'] = [];
				foreach ( $value as $sub_field_key => $sub_field ) {
					$data['sub_fields'][] = mai_get_acf_field_data( $sub_field_key, $sub_field );
				}
			} else {

				// Standard field data.
				$data[ $field_key ] = $value;
			}
		}
	}

	// Maybe add conditional logic.
	if ( isset( $field['conditions'] ) ) {
		$data['conditional_logic'] = $field['conditions'];
	}

	// Maybe add default.
	if ( isset( $field['default'] ) ) {

		/**
		 * This needs default_value instead of default.
		 *
		 * @link  https://www.advancedcustomfields.com/resources/register-fields-via-php/
		 */
		$data['default_value'] = $field['default'];
	}

	/*
	 * Maybe add choices.
	 * TODO: If sites with a lot of posts cause slow loading,
	 * (if ACF ajax isn't working with this code),
	 * we may need to move to load_field filters,
	 * though this should work as-is.
	 */

	if ( isset( $field['choices'] ) ) {
		if ( is_array( $field['choices'] ) ) {
			$data['choices'] = $field['choices'];

		} elseif ( is_string( $field['choices'] ) && is_callable( $field['choices'] ) && mai_has_string( 'mai_', $field['choices'] ) ) {
			$data['choices'] = call_user_func( $field['choices'] );
		}
	}

	return $data;
}

/**
 * Loads the "Show" field.
 *
 * @since 0.1.0
 *
 * @param array $field Field array.
 *
 * @return mixed
 */
function mai_acf_load_show( $field ) {

	// Get existing values, which are sorted correctly, without infinite loop.
	remove_filter( 'acf/load_field/key=mai_grid_block_show', 'mai_acf_load_show' );

	$defaults = $field['choices'];
	$existing = get_field( 'show' );

	// Make sure only valid choices are used.
	$existing = $existing ? array_intersect_key( array_flip( $existing ), $defaults ) : [];

	add_filter( 'acf/load_field/key=mai_grid_block_show', 'mai_acf_load_show' );

	// If we have existing values, reorder them.
	$field['choices'] = $existing ? array_merge( $existing, $defaults ) : $field['choices'];

	return $field;
}

/**
 * Gets chosen post type for use in other field filters.
 *
 * @since 0.1.0
 *
 * @param array $args Field args.
 *
 * @return mixed
 */
function mai_acf_get_posts( $args ) {
	$args['post_type'] = [];
	$post_types        = mai_get_acf_request( 'post_type' );

	if ( ! $post_types ) {
		return $args;
	}

	foreach ( (array) $post_types as $post_type ) {
		$args['post_type'][] = sanitize_text_field( wp_unslash( $post_type ) );
	}

	return $args;
}

/**
 * Load term choices based on existing saved field value.
 * Ajax loading terms was working, but if a term was already saved
 * it was not loading correctly when editing a post.
 *
 * @link  https://github.com/maithemewp/mai-engine/issues/93
 *
 * @since 0.3.3
 *
 * @param array $field The ACF field array.
 *
 * @return mixed
 */
function mai_acf_prepare_terms( $field ) {
	if ( ! $field['value'] ) {
		return $field;
	}

	$term_id = $field['value'][0];

	if ( ! $term_id ) {
		return $field;
	}

	$term = get_term( $term_id );

	if ( ! $term ) {
		return $field;
	}

	$field['choices'] = mai_get_term_choices_from_taxonomy( $term->taxonomy );

	return $field;
}

/**
 * Get terms from an ajax query.
 * The taxonomy is passed via JS on select2_query_args filter.
 *
 * @since 0.1.0
 *
 * @param array $field The ACF field array.
 *
 * @return mixed
 */
function mai_acf_load_terms( $field ) {
	$taxonomy = mai_get_acf_request( 'taxonomy' );

	if ( ! $taxonomy ) {
		return $field;
	}

	$field['choices'] = mai_get_term_choices_from_taxonomy( $taxonomy );

	return $field;
}

/**
 * Get terms from an ajax query.
 * The taxonomy is passed via JS on select2_query_args filter.
 *
 * @since 0.1.0
 *
 * @param array $args Field args.
 *
 * @return mixed
 */
function mai_acf_get_terms( $args ) {
	$args['taxonomy'] = [];
	$taxonomies       = mai_get_acf_request( 'taxonomy' );

	if ( ! $taxonomies ) {
		return $args;
	}

	foreach ( (array) $taxonomies as $taxonomy ) {
		$args['taxonomy'][] = sanitize_text_field( wp_unslash( $taxonomy ) );
	}

	return $args;
}

/**
 * Get taxonomies from an ajax query.
 * The taxonomy is passed via JS on select2_query_args filter.
 *
 * @since 0.1.0
 *
 * @param array $args Field args.
 *
 * @return mixed
 */
function mai_acf_get_term_parents( $args ) {
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

/**
 * Set the post type args for post_object query in ACF.
 *
 * @since 0.1.0
 *
 * @param array $args Field args.
 *
 * @return mixed
 */
function mai_acf_get_post_parents( $args ) {
	$args['post_type'] = [];
	$post_types        = mai_get_acf_request( 'post_type' );

	if ( ! $post_types ) {
		return $args;
	}

	foreach ( (array) $post_types as $post_type ) {
		$args['post_type'][] = sanitize_text_field( wp_unslash( $post_type ) );
	}

	foreach ( $args['post_type'] as $index => $post_type ) {
		if ( ! is_post_type_hierarchical( $post_type ) ) {
			unset( $args['post_type'][ $index ] );
		}
	}

	return $args;
}

/**
 * Allow searching for post objects by ID.
 *
 * @since 2.15.0
 *
 * @link https://www.powderkegwebdesign.com/fantastic-way-allow-searching-id-advanced-custom-fields-objects/
 *
 * @return array
 */
function mai_acf_get_posts_by_id( $args, $field, $post_id ) {
	$query = ! empty( $args['s'] ) ? $args['s'] : false;

	if ( ! $query ) {
		return $args;
	}

	// Bail if not a numeric query.
 	if ( ! is_numeric( $query ) ) {
		return $args;
	}

	// Set the post ID in the query.
	$args['post__in'] = array( $query );

	// Unset the actual search param.
	unset( $args['s'] );

	return $args;
}

/**
 * Get post type choices for select field.
 * If this is called too early all post types may not be registered.
 *
 * @since 0.1.0
 *
 * @return array
 */
function mai_get_post_type_choices() {
	$choices    = [];
	$post_types = get_post_types( [ 'public' => true ] );

	unset( $post_types['attachment'] );

	$post_types = array_keys( $post_types );
	$post_types = apply_filters( 'mai_grid_post_types', $post_types );

	if ( $post_types ) {
		foreach ( $post_types as $post_type ) {
			$choices[ $post_type ] = get_post_type_object( $post_type )->label;
		}
	}

	return $choices;
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
 * Sets the default gap if existing value is not a valid size.
 * This helps deprecate the old text field values, so the default is set correctly for existing block instances.
 *
 * @since 2.4.0
 *
 * @param array $field The existing field.
 *
 * @return array
 */
function mai_acf_load_gap( $field ) {
	if ( $field['value'] && ! mai_is_valid_size( $field['value'] ) ) {
		$field['value'] = $field['default_value'];
	}

	return $field;
}

/**
 * Gets an ACF request, checking nonce and value.
 *
 * @since 0.1.0
 *
 * @param string $request Request data.
 *
 * @return bool
 */
function mai_get_acf_request( $request ) {
	if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'acf_nonce' ) && isset( $_REQUEST[ $request ] ) && ! empty( $_REQUEST[ $request ] ) ) {
		return $_REQUEST[ $request ];
	}

	return false;
}
