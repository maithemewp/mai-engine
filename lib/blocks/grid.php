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

	// Mai Post Grid.
	acf_register_block_type(
		[
			'name'            => 'mai-post-grid',
			'title'           => __( 'Mai Post Grid', 'mai-engine' ),
			'description'     => __( 'Display posts/pages/cpts in various layouts.', 'mai-engine' ),
			'icon'            => 'grid-view',
			'category'        => 'widgets',
			'keywords'        => [ 'grid', 'post', 'page' ],
			'mode'            => 'preview',
			'render_callback' => 'mai_do_post_grid_block',
			'supports'        => [
				'align'  => [ 'wide' ],
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
			'icon'            => 'grid-view',
			'category'        => 'widgets',
			'keywords'        => [ 'grid', 'category', 'term' ],
			'mode'            => 'preview',
			'render_callback' => 'mai_do_term_grid_block',
			'supports'        => [
				'align'  => [ 'wide' ],
				'ancher' => true,
			],
		]
	);
}

/**
 * Description of expected behavior.
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
	mai_do_grid_block( 'post', $block, $content = '', $is_preview = false, $post_id = 0 );
}

/**
 * Description of expected behavior.
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
	mai_do_grid_block( 'term', $block, $content = '', $is_preview = false, $post_id = 0 );
}

/**
 * Description of expected behavior.
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

	mai_do_grid( $type, $args );
}

/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @param string $type Field type.
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

add_action( 'init', 'mai_register_grid_field_groups', 20 );
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

	$post_grid = [];
	$term_grid = [];
	$user_grid = [];

	// Get fields.
	$fields = mai_get_grid_block_settings();

	foreach ( $fields as $key => $field ) {

		// Post grid.
		if ( in_array( 'post', $field['block'], true ) ) {
			$post_grid[] = mai_get_acf_field_data( $key, $field );
		}
		// Term grid.
		if ( in_array( 'term', $field['block'], true ) ) {
			$term_grid[] = mai_get_acf_field_data( $key, $field );
		}
		// Post grid.
		if ( in_array( 'user', $field['block'], true ) ) {
			$user_grid[] = mai_get_acf_field_data( $key, $field );
		}
	}

	acf_add_local_field_group(
		[
			'key'      => 'group_5de9b54440a2p',
			'title'    => __( 'Mai Post Grid', 'mai-engine' ),
			'fields'   => $post_grid,
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
			'key'      => 'group_5de9b54440a2t',
			'title'    => __( 'Mai Term Grid', 'mai-engine' ),
			'fields'   => $term_grid,
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
 * Description of expected behavior.
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

	// Maybe add choices.
	// TODO: If sites with a lot of posts cause slow loading,
	// (if ACF ajax isn't working with this code),
	// we may need to move to load_field filters,
	// though this should work as-is.
	if ( isset( $field['choices'] ) ) {
		if ( is_array( $field['choices'] ) ) {
			$data['choices'] = $field['choices'];
		} elseif ( is_string( $field['choices'] ) && is_callable( $field['choices'] ) && mai_has_string( 'mai_', $field['choices'] ) ) {
			$data['choices'] = call_user_func( $field['choices'] );
		}
	}

	return $data;
}

/*****************
 * Mai Post Grid *
 */
// Show 'show'.
add_filter( 'acf/load_field/key=field_5e441d93d6236', 'mai_acf_load_show', 10, 1 );
// Posts 'post__in'.
add_filter( 'acf/fields/post_object/query/key=field_5df1053632cbc', 'mai_acf_get_posts', 10, 1 );
// Terms 'terms' sub field.
add_filter( 'acf/load_field/key=field_5df139a216272', 'mai_acf_load_terms', 10, 1 );
add_filter( 'acf/prepare_field/key=field_5df139a216272', 'mai_acf_prepare_terms', 10, 1 );
// Parent 'post_parent__in'.
add_filter( 'acf/fields/post_object/query/key=field_5df1053632ce4', 'mai_acf_get_post_parents', 10, 1 );
// Exclude Entries 'post__not_in'.
add_filter( 'acf/fields/post_object/query/key=field_5e349237e1c01', 'mai_acf_get_posts', 10, 1 );
/*****************
 * Mai Term Grid *
 */
// Include Entries 'include'.
add_filter( 'acf/fields/taxonomy/query/key=field_5df10647743cb', 'mai_acf_get_terms', 10, 1 );
// Exclude Entries 'exclude'.
add_filter( 'acf/fields/taxonomy/query/key=field_5e459348f2d12', 'mai_acf_get_terms', 10, 1 );
// Parent 'parent'.
add_filter( 'acf/fields/taxonomy/query/key=field_5df1054743df5', 'mai_acf_get_term_parents', 10, 1 );
/*****************
 * Mai User Grid *
 *****************/
// TODO: Will we need/have these? Maybe rely on select field.

/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @param array $field Field array.
 *
 * @return mixed
 */
function mai_acf_load_show( $field ) {
	// Get existing values, which are sorted correctly, without infinite loop.
	remove_filter( 'acf/load_field/key=field_5e441d93d6236', 'mai_acf_load_show' );
	$existing = get_field( 'show' );
	$defaults = $field['choices'];
	add_filter( 'acf/load_field/key=field_5e441d93d6236', 'mai_acf_load_show' );
	// If we have existing values, reorder them.
	$field['choices'] = $existing ? array_merge( array_flip( $existing ), $defaults ) : $field['choices'];

	return $field;
}

/**
 * Description of expected behavior.
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
 * @link https://github.com/maithemewp/mai-engine/issues/93
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
	foreach( $args['taxonomy'] as $index => $taxonomy ) {
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
 * Get post type choices for select field.
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
 *
 * @return array
 */
function mai_get_post_types_taxonomy_choices() {
	$choices = [];
	if ( ! ( is_admin() || is_customize_preview() ) ) {
		return $choices;
	}
	$post_types = mai_get_acf_request( 'post_type' );
	if ( ! $post_types ) {
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
	$terms = get_terms( $taxonomy, array(
		'hide_empty' => false,
	) );
	if ( ! $terms ) {
		return $choices;
	}
	foreach ( $terms as $term ) {
		$choices[ $term->term_id ] = $term->name;
	}
	return $choices;
}

/**
 * Description of expected behavior.
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


function mai_get_grid_block_settings() {

	return [
		'field_5bd51cac98282' => [
			'name'    => 'display_tab',
			'label'   => esc_html__( 'Display', 'mai-engine' ),
			'block'   => [ 'post', 'term', 'user' ],
			'type'    => 'tab',
			'default' => '',
		],
		'field_5e441d93d6236' => [
			'name'     => 'show',
			'label'    => esc_html__( 'Show', 'mai-engine' ),
			'desc'     => esc_html__( 'Show/hide and re-order elements.', 'mai-engine' ),
			'block'    => [ 'post', 'term', 'user' ],
			'type'     => 'checkbox',
			'sanitize' => 'esc_html',
			'default'  => [ 'image', 'title' ],
			'choices'  => 'mai_get_grid_show_choices',
			'atts'     => [
				'wrapper' => [
					'width' => '',
					'class' => 'mai-sortable',
					'id'    => '',
				],
			],
		],
		'field_5e4d4efe99279' => [
			'name'       => 'image_orientation',
			'label'      => esc_html__( 'Image Orientation', 'mai-engine' ),
			'block'      => [ 'post', 'term', 'user' ],
			'type'       => 'select',
			'sanitize'   => 'esc_html',
			'default'    => 'landscape',
			'choices'    => 'mai_get_image_orientation_choices',
			'conditions' => [
				[
					'field'    => 'field_5e441d93d6236', // Show.
					'operator' => '==',
					'value'    => 'image',
				],
			],
			'atts'       => [
				'wrapper' => [
					'width' => '',
					'class' => 'mai-grid-show-conditional',
					'id'    => '',
				],
			],
		],
		'field_5bd50e580d1e9' => [
			'name'       => 'image_size',
			'label'      => esc_html__( 'Image Size', 'mai-engine' ),
			'block'      => [ 'post', 'term', 'user' ],
			'type'       => 'select',
			'sanitize'   => 'esc_html',
			'default'    => 'landscape-md',
			'choices'    => 'mai_get_image_size_choices',
			'conditions' => [
				[
					'field'    => 'field_5e441d93d6236', // Show.
					'operator' => '==',
					'value'    => 'image',
				],
				[
					'field'    => 'field_5e4d4efe99279', // Image_orientation.
					'operator' => '==',
					'value'    => 'custom',
				],
			],
			'atts'       => [
				'wrapper' => [
					'width' => '',
					'class' => 'mai-grid-show-conditional',
					'id'    => '',
				],
			],
		],
		'field_5e2f3adf82130' => [
			'name'       => 'image_position',
			'label'      => esc_html__( 'Image Position', 'mai-engine' ),
			'block'      => [ 'post', 'term', 'user' ],
			'type'       => 'select',
			'sanitize'   => 'esc_html',
			'default'    => 'full',
			'choices'    => [
				'full'         => esc_html__( 'Full', 'mai-engine' ),
				'center'       => esc_html__( 'Center', 'mai-engine' ),
				'left-top'     => esc_html__( 'Left Top', 'mai-engine' ),
				'left-middle'  => esc_html__( 'Left Middle', 'mai-engine' ),
				'left-full'    => esc_html__( 'Left Full', 'mai-engine' ),
				'right-top'    => esc_html__( 'Right Top', 'mai-engine' ),
				'right-middle' => esc_html__( 'Right Middle', 'mai-engine' ),
				'right-full'   => esc_html__( 'Right Full', 'mai-engine' ),
				'background'   => esc_html__( 'Background', 'mai-engine' ),
			],
			'conditions' => [
				[
					'field'    => 'field_5e441d93d6236', // Show.
					'operator' => '==',
					'value'    => 'image',
				],
			],
			'atts'       => [
				'wrapper' => [
					'width' => '',
					'class' => 'mai-grid-show-conditional',
					'id'    => '',
				],
			],
		],
		'field_5e2f3beg93241' => [
			'name'       => 'image_width',
			'label'      => esc_html__( 'Image Width', 'mai-engine' ),
			'block'      => [ 'post', 'term', 'user' ],
			'type'       => 'button_group',
			'sanitize'   => 'esc_html',
			'default'    => 'third',
			'choices'    => [
				'fourth' => esc_html__( 'One Fourth', 'mai-engine' ),
				'third'  => esc_html__( 'One Third', 'mai-engine' ),
				'half'   => esc_html__( 'One Half', 'mai-engine' ),
			],
			'conditions' => [
				[
					[
						'field'    => 'field_5e441d93d6236', // Show.
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'field_5e4d4efe99279', // Image Orientation.
						'operator' => '!=',
						'value'    => 'custom',
					],
					[
						'field'    => 'field_5e2f3adf82130', // Image Position.
						'operator' => '==',
						'value'    => 'left-top',
					],
				],
				[
					[
						'field'    => 'field_5e441d93d6236', // Show.
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'field_5e4d4efe99279', // Image Orientation.
						'operator' => '!=',
						'value'    => 'custom',
					],
					[
						'field'    => 'field_5e2f3adf82130', // Image Position.
						'operator' => '==',
						'value'    => 'left-middle',
					],
				],
				[
					[
						'field'    => 'field_5e441d93d6236', // Show.
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'field_5e4d4efe99279', // Image Orientation.
						'operator' => '!=',
						'value'    => 'custom',
					],
					[
						'field'    => 'field_5e2f3adf82130', // Image Position.
						'operator' => '==',
						'value'    => 'left-full',
					],
				],
				[
					[
						'field'    => 'field_5e441d93d6236', // Show.
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'field_5e4d4efe99279', // Image Orientation.
						'operator' => '!=',
						'value'    => 'custom',
					],
					[
						'field'    => 'field_5e2f3adf82130', // Image Position.
						'operator' => '==',
						'value'    => 'right-top',
					],
				],
				[
					[
						'field'    => 'field_5e441d93d6236', // Show.
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'field_5e4d4efe99279', // Image Orientation.
						'operator' => '!=',
						'value'    => 'custom',
					],
					[
						'field'    => 'field_5e2f3adf82130', // Image Position.
						'operator' => '==',
						'value'    => 'right-middle',
					],
				],
				[
					[
						'field'    => 'field_5e441d93d6236', // Show.
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'field_5e4d4efe99279', // Image Orientation.
						'operator' => '!=',
						'value'    => 'custom',
					],
					[
						'field'    => 'field_5e2f3adf82130', // Image Position.
						'operator' => '==',
						'value'    => 'right-full',
					],
				],
			],
		],
		'field_5e2b563a7c6cf' => [
			'name'       => 'header_meta',
			'label'      => esc_html__( 'Header Meta', 'mai-engine' ),
			'block'      => [ 'post', 'term', 'user' ],
			'type'       => 'text',
			'sanitize'   => 'wp_kses_post',
			// TODO: this should be different, or empty depending on the post type?
			'default'    => '[post_date] [post_author_posts_link before="by "]',
			'conditions' => [
				[
					'field'    => 'field_5e441d93d6236', // Show.
					'operator' => '==',
					'value'    => 'header_meta',
				],
			],
			'atts'       => [
				'wrapper' => [
					'width' => '',
					'class' => 'mai-grid-show-conditional',
					'id'    => '',
				],
			],
		],
		'field_5bd51ac107244' => [
			'name'       => 'content_limit',
			'label'      => esc_html__( 'Content Limit', 'mai-engine' ),
			'desc'       => esc_html__( 'Limit the number of characters shown for the content or excerpt. Use 0 for no limit.', 'mai-engine' ),
			'block'      => [ 'post', 'term', 'user' ],
			'type'       => 'text',
			'sanitize'   => 'absint',
			'default'    => 0,
			'conditions' => [
				[
					[
						'field'    => 'field_5e441d93d6236', // Show.
						'operator' => '==',
						'value'    => 'excerpt',
					],
				],
				[
					[
						'field'    => 'field_5e441d93d6236', // Show.
						'operator' => '==',
						'value'    => 'content',
					],
				],
			],
			'atts'       => [
				'wrapper' => [
					'width' => '',
					'class' => 'mai-grid-show-conditional',
					'id'    => '',
				],
			],
		],
		'field_5c85465018395' => [
			'name'       => 'more_link_text',
			'label'      => esc_html__( 'More Link Text', 'mai-engine' ),
			'block'      => [ 'post', 'term', 'user' ],
			'type'       => 'text',
			'sanitize'   => 'esc_attr', // We may want to add icons/spans and HTML in here.
			'default'    => '',
			'conditions' => [
				[
					'field'    => 'field_5e441d93d6236', // Show.
					'operator' => '==',
					'value'    => 'more_link',
				],
			],
			'atts'       => [
				'placeholder' => mai_get_read_more_text(),
				'wrapper'     => [
					'width' => '',
					'class' => 'mai-grid-show-conditional',
					'id'    => '',
				],
			],
		],
		'field_5e2b567e7c6d0' => [
			'name'       => 'footer_meta',
			'label'      => esc_html__( 'Footer Meta', 'mai-engine' ),
			'block'      => [ 'post', 'term', 'user' ],
			'type'       => 'text',
			'sanitize'   => 'wp_kses_post',
			// TODO: this should be different, or empty depending on the post type?
			'default'    => '[post_categories]',
			'conditions' => [
				[
					'field'    => 'field_5e441d93d6236', // Show.
					'operator' => '==',
					'value'    => 'footer_meta',
				],
			],
			'atts'       => [
				'wrapper' => [
					'width' => '',
					'class' => 'mai-grid-show-conditional',
					'id'    => '',
				],
			],
		],
		'field_5c853f84eacd6' => [
			'name'     => 'align_text',
			'label'    => esc_html__( 'Align Text', 'mai-engine' ),
			'block'    => [ 'post', 'term', 'user' ],
			'type'     => 'button_group',
			'sanitize' => 'esc_html',
			'default'  => '',
			'choices'  => [
				''       => esc_html__( 'Clear', 'mai-engine' ),
				'start'  => esc_html__( 'Start', 'mai-engine' ),
				'center' => esc_html__( 'Center', 'mai-engine' ),
				'end'    => esc_html__( 'End', 'mai-engine' ),
			],
			'atts'     => [
				'wrapper' => [
					'width' => '',
					'class' => 'mai-grid-button-group mai-grid-button-group-clear',
					'id'    => '',
				],
			],
		],
		'field_5e2f519edc912' => [
			'name'       => 'align_text_vertical',
			'label'      => esc_html__( 'Align Text (vertical)', 'mai-engine' ),
			'block'      => [ 'post', 'term', 'user' ],
			'type'       => 'button_group',
			'sanitize'   => 'esc_html',
			'default'    => '',
			'choices'    => [
				''       => esc_html__( 'Clear', 'mai-engine' ),
				'top'    => esc_html__( 'Top', 'mai-engine' ),
				'middle' => esc_html__( 'Middle', 'mai-engine' ),
				'bottom' => esc_html__( 'Bottom', 'mai-engine' ),
			],
			'conditions' => [
				[
					[
						'field'    => 'field_5e441d93d6236', // Show.
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'field_5e2f3adf82130', // Image Position.
						'operator' => '==',
						'value'    => 'left-top',
					],
				],
				[
					[
						'field'    => 'field_5e441d93d6236', // Show.
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'field_5e2f3adf82130', // Image Position.
						'operator' => '==',
						'value'    => 'left-middle',
					],
				],
				[
					[
						'field'    => 'field_5e441d93d6236', // Show.
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'field_5e2f3adf82130', // Image Position.
						'operator' => '==',
						'value'    => 'left-full',
					],
				],
				[
					[
						'field'    => 'field_5e441d93d6236', // Show.
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'field_5e2f3adf82130', // Image Position.
						'operator' => '==',
						'value'    => 'right-top',
					],
				],
				[
					[
						'field'    => 'field_5e441d93d6236', // Show.
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'field_5e2f3adf82130', // Image Position.
						'operator' => '==',
						'value'    => 'right-middle',
					],
				],
				[
					[
						'field'    => 'field_5e441d93d6236', // Show.
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'field_5e2f3adf82130', // Image Position.
						'operator' => '==',
						'value'    => 'right-full',
					],
				],
				[
					[
						'field'    => 'field_5e441d93d6236', // Show.
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'field_5e2f3adf82130', // Image_position.
						'operator' => '==',
						'value'    => 'background',
					],
				],
			],
			'atts'       => [
				'wrapper' => [
					'width' => '',
					'class' => 'mai-grid-button-group mai-grid-button-group-clear',
					'id'    => '',
				],
			],
		],
		'field_5f3b19b193c3d' => [
			'name'       => 'image_stack',
			'label'      => esc_html__( 'Stack Image', 'mai-engine' ),
			'block'      => [ 'post', 'term', 'user' ],
			'type'       => 'true_false',
			'sanitize'   => 'mai_sanitize_bool',
			'default'    => 1, // True.
			'atts'       => [
				'message' => esc_html__( 'Stack image and content on mobile', 'mai-engine' ),
			],
			'conditions' => [
				[
					[
						'field'    => 'field_5e441d93d6236', // Show.
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'field_5e2f3adf82130', // Image Position.
						'operator' => '==',
						'value'    => 'left-top',
					],
				],
				[
					[
						'field'    => 'field_5e441d93d6236', // Show.
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'field_5e2f3adf82130', // Image Position.
						'operator' => '==',
						'value'    => 'left-middle',
					],
				],
				[
					[
						'field'    => 'field_5e441d93d6236', // Show.
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'field_5e2f3adf82130', // Image Position.
						'operator' => '==',
						'value'    => 'left-full',
					],
				],
				[
					[
						'field'    => 'field_5e441d93d6236', // Show.
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'field_5e2f3adf82130', // Image Position.
						'operator' => '==',
						'value'    => 'right-top',
					],
				],
				[
					[
						'field'    => 'field_5e441d93d6236', // Show.
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'field_5e2f3adf82130', // Image Position.
						'operator' => '==',
						'value'    => 'right-middle',
					],
				],
				[
					[
						'field'    => 'field_5e441d93d6236', // Show.
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'field_5e2f3adf82130', // Image Position.
						'operator' => '==',
						'value'    => 'right-full',
					],
				],
			],
		],
		'field_5e2a08a182c2c' => [
			'name'     => 'boxed',
			'label'    => esc_html__( 'Boxed', 'mai-engine' ),
			'block'    => [ 'post', 'term', 'user' ],
			'type'     => 'true_false',
			'sanitize' => 'mai_sanitize_bool',
			'default'  => 1, // True.
			'atts'     => [
				'message' => esc_html__( 'Display boxed styling', 'mai-engine' ),
			],
		],
		/********
		 * Layout
		 */
		'field_5c8549172e6c7' => [
			'name'    => 'layout_tab',
			'label'   => esc_html__( 'Layout', 'mai-engine' ),
			'block'   => [ 'post', 'term', 'user' ],
			'type'    => 'tab',
			'default' => '',
		],
		'field_5c854069d358c' => [
			'name'     => 'columns',
			'label'    => esc_html__( 'Columns (desktop)', 'mai-engine' ),
			'block'    => [ 'post', 'term', 'user' ],
			'type'     => 'button_group',
			'sanitize' => 'absint',
			'default'  => 3,
			'choices'  => 'mai_get_columns_choices',
			'atts'     => [
				'wrapper' => [
					'width' => '',
					'class' => 'mai-grid-button-group',
					'id'    => '',
				],
			],
		],
		'field_5e334124b905d' => [
			'name'     => 'columns_responsive',
			'label'    => '',
			'block'    => [ 'post', 'term', 'user' ],
			'type'     => 'true_false',
			'sanitize' => 'mai_sanitize_bool',
			'default'  => 0,
			'atts'     => [
				'message' => esc_html__( 'Custom responsive columns', 'mai-engine' ),
			],
		],
		'field_5e3305dff9d8b' => [
			'name'       => 'columns_md',
			'label'      => esc_html__( 'Columns (lg tablets)', 'mai-engine' ),
			'block'      => [ 'post', 'term', 'user' ],
			'type'       => 'button_group',
			'sanitize'   => 'absint',
			'default'    => 1,
			'choices'    => 'mai_get_columns_choices',
			'conditions' => [
				[
					'field'    => 'field_5e334124b905d', // Columns_responsive.
					'operator' => '==',
					'value'    => 1,
				],
			],
			'atts'       => [
				'wrapper' => [
					'width' => '',
					'class' => 'mai-grid-button-group mai-grid-nested-columns mai-grid-nested-columns-first',
					'id'    => '',
				],
			],
		],
		'field_5e3305f1f9d8c' => [
			'name'       => 'columns_sm',
			'label'      => esc_html__( 'Columns (sm tablets)', 'mai-engine' ),
			'block'      => [ 'post', 'term', 'user' ],
			'type'       => 'button_group',
			'sanitize'   => 'absint',
			'default'    => 1,
			'choices'    => 'mai_get_columns_choices',
			'conditions' => [
				[
					'field'    => 'field_5e334124b905d', // Columns_responsive.
					'operator' => '==',
					'value'    => 1,
				],
			],
			'atts'       => [
				'wrapper' => [
					'width' => '',
					'class' => 'mai-grid-button-group mai-grid-nested-columns',
					'id'    => '',
				],
			],
		],
		'field_5e332a5f7fe08' => [
			'name'       => 'columns_xs',
			'label'      => esc_html__( 'Columns (mobile)', 'mai-engine' ),
			'block'      => [ 'post', 'term', 'user' ],
			'type'       => 'button_group',
			'sanitize'   => 'absint',
			'default'    => 1,
			'choices'    => 'mai_get_columns_choices',
			'conditions' => [
				[
					'field'    => 'field_5e334124b905d', // Columns_responsive.
					'operator' => '==',
					'value'    => 1,
				],
			],
			'atts'       => [
				'wrapper' => [
					'width' => '',
					'class' => 'mai-grid-button-group mai-grid-nested-columns mai-grid-nested-columns-last',
					'id'    => '',
				],
			],
		],
		'field_5c853e6672972' => [
			'name'       => 'align_columns',
			'label'      => esc_html__( 'Align Columns', 'mai-engine' ),
			'block'      => [ 'post', 'term', 'user' ],
			'type'       => 'button_group',
			'sanitize'   => 'esc_html',
			'default'    => '',
			'choices'    => [
				''       => esc_html__( 'Clear', 'mai-engine' ),
				'start'  => esc_html__( 'Start', 'mai-engine' ),
				'center' => esc_html__( 'Center', 'mai-engine' ),
				'end'    => esc_html__( 'End', 'mai-engine' ),
			],
			'conditions' => [
				[
					'field'    => 'field_5c854069d358c', // Columns.
					'operator' => '!=',
					'value'    => 1,
				],
			],
			'atts'       => [
				'wrapper' => [
					'width' => '',
					'class' => 'mai-grid-button-group mai-grid-button-group-clear',
					'id'    => '',
				],
			],
		],
		'field_5e31d5f0e2867' => [
			'name'       => 'align_columns_vertical',
			'label'      => esc_html__( 'Align Columns (vertical)', 'mai-engine' ),
			'block'      => [ 'post', 'term', 'user' ],
			'type'       => 'button_group',
			'sanitize'   => 'esc_html',
			'default'    => '',
			'choices'    => [
				''       => esc_html__( 'Clear', 'mai-engine' ),
				'top'    => esc_html__( 'Top', 'mai-engine' ),
				'middle' => esc_html__( 'Middle', 'mai-engine' ),
				'bottom' => esc_html__( 'Bottom', 'mai-engine' ),
			],
			'conditions' => [
				[
					'field'    => 'field_5c854069d358c', // Columns.
					'operator' => '!=',
					'value'    => 1,
				],
			],
			'atts'       => [
				'wrapper' => [
					'width' => '',
					'class' => 'mai-grid-button-group mai-grid-button-group-clear',
					'id'    => '',
				],
			],
		],
		'field_5c8542d6a67c5' => [
			'name'     => 'column_gap',
			'label'    => esc_html__( 'Column Gap', 'mai-engine' ),
			'block'    => [ 'post', 'term', 'user' ],
			'type'     => 'text',
			'sanitize' => 'esc_html',
			'default'  => '24px',
		],
		'field_5e29f1785bcb6' => [
			'name'     => 'row_gap',
			'label'    => esc_html__( 'Row Gap', 'mai-engine' ),
			'block'    => [ 'post', 'term', 'user' ],
			'type'     => 'text',
			'sanitize' => 'esc_html',
			'default'  => '24px',
		],
		/***********
		 * Entries *
		 */
		'field_5df13446c49cf' => [
			'name'    => 'entries_tab',
			'label'   => esc_html__( 'Entries', 'mai-engine' ),
			'block'   => [ 'post', 'term', 'user' ],
			'type'    => 'tab',
			'default' => '',
		],
		/*********
		 * Posts *
		 */
		'field_5df1053632ca2' => [
			'name'     => 'post_type',
			'label'    => esc_html__( 'Post Type', 'mai-engine' ),
			'block'    => [ 'post' ],
			'type'     => 'select',
			'sanitize' => 'esc_html',
			'default'  => [ 'post' ],
			'choices'  => 'mai_get_post_type_choices',
			'atts'     => [
				'multiple' => 1,
				'ui'       => 1,
				'ajax'     => 0,
			],
		],
		'field_5df1053632cad' => [
			'name'       => 'query_by',
			'label'      => esc_html__( 'Get Entries By', 'mai-engine' ),
			'block'      => [ 'post' ],
			'type'       => 'select',
			'sanitize'   => 'esc_html',
			'default'    => '',
			'choices'    => [
				''         => esc_html__( 'Default Query', 'mai-engine' ),
				'id'       => esc_html__( 'Choice', 'mai-engine' ),
				'tax_meta' => esc_html__( 'Taxonomy/Meta', 'mai-engine' ),
				'parent'   => esc_html__( 'Parent', 'mai-engine' ),
			],
			'conditions' => [
				[
					'field'    => 'field_5df1053632ca2', // Post_type.
					'operator' => '!=empty',
				],
			],
		],
		'field_5df1053632cbc' => [
			'name'       => 'post__in',
			'label'      => esc_html__( 'Choose Entries', 'mai-engine' ),
			'desc'       => esc_html__( 'Show specific entries. Choose all that apply.', 'mai-engine' ),
			'block'      => [ 'post' ],
			'type'       => 'post_object',
			'sanitize'   => 'absint',
			'default'    => '',
			'conditions' => [
				[
					'field'    => 'field_5df1053632ca2', // Post_type.
					'operator' => '!=empty',
				],
				[
					'field'    => 'field_5df1053632cad', // Query_by.
					'operator' => '==',
					'value'    => 'id',
				],
			],
			'atts'       => [
				'multiple'      => 1,
				'return_format' => 'id',
				'ui'            => 1,
			],
		],
		'field_5df1397316270' => [
			'name'       => 'taxonomies',
			'label'      => esc_html__( 'Taxonomies', 'mai-engine' ),
			'block'      => [ 'post' ],
			'type'       => 'repeater',
			'default'    => '',
			'conditions' => [
				[
					'field'    => 'field_5df1053632ca2', // Post_type.
					'operator' => '!=empty',
				],
				[
					'field'    => 'field_5df1053632cad', // Query_by.
					'operator' => '==',
					'value'    => 'tax_meta',
				],
			],
			'atts'       => [
				'collapsed'    => 'field_5df1398916271',
				'layout'       => 'block',
				'button_label' => esc_html__( 'Add Condition', 'mai-engine' ),
				'sub_fields'   => [
					'field_5df1398916271' => [
						'name'     => 'taxonomy',
						'label'    => esc_html__( 'Taxonomy', 'mai-engine' ),
						'block'    => [ 'post' ],
						'type'     => 'select',
						'sanitize' => 'esc_html',
						'default'  => '',
						'choices'  => 'mai_get_post_types_taxonomy_choices',
						'atts'     => [
							'ui'   => 1,
							'ajax' => 1,
						],
					],
					'field_5df139a216272' => [
						'name'       => 'terms',
						'label'      => esc_html__( 'Terms', 'mai-engine' ),
						'block'      => [ 'post' ],
						'type'       => 'select',
						'sanitize'   => 'absint',
						'default'    => [],
						'conditions' => [
							[
								'field'    => 'field_5df1398916271', // Taxonomy.
								'operator' => '!=empty',
							],
						],
						'atts'       => [
							'ui'       => 1,
							'ajax'     => 1,
							'multiple' => 1,
						],
					],
					'field_5df18f2305c2c' => [
						'name'       => 'operator',
						'label'      => esc_html__( 'Operator', 'mai-engine' ),
						'block'      => [ 'post' ],
						'type'       => 'select',
						'sanitize'   => 'esc_html',
						'default'    => 'IN',
						'choices'    => [
							'IN'     => esc_html__( 'In', 'mai-engine' ),
							'NOT IN' => esc_html__( 'Not In', 'mai-engine' ),
						],
						'conditions' => [
							[
								'field'    => 'field_5df1398916271', // Taxonomy.
								'operator' => '!=empty',
							],
						],
					],
				],
			],
		],
		'field_5df139281626f' => [
			'name'       => 'taxonomies_relation',
			'label'      => esc_html__( 'Taxonomies Relation', 'mai-engine' ),
			'block'      => [ 'post' ],
			'type'       => 'select',
			'sanitize'   => 'esc_html',
			'default'    => 'AND',
			'choices'    => [
				'AND' => esc_html__( 'And', 'mai-engine' ),
				'OR'  => esc_html__( 'Or', 'mai-engine' ),
			],
			'conditions' => [
				[
					'field'    => 'field_5df1053632ca2', // Post_type.
					'operator' => '!=empty',
				],
				[
					'field'    => 'field_5df1053632cad', // Query_by.
					'operator' => '==',
					'value'    => 'tax_meta',
				],
				[
					'field'    => 'field_5df1397316270', // Taxonomies.
					'operator' => '>',
					'value'    => '1', // More than 1 row.
				],
			],
		],
		'field_5df2053632dg5' => [
			'name'       => 'meta_keys',
			'label'      => esc_html__( 'Meta Keys', 'mai-engine' ),
			'block'      => [ 'post' ],
			'type'       => 'repeater',
			'default'    => '',
			'conditions' => [
				[
					'field'    => 'field_5df1053632ca2', // Post_type.
					'operator' => '!=empty',
				],
				[
					'field'    => 'field_5df1053632cad', // Query_by.
					'operator' => '==',
					'value'    => 'tax_meta',
				],
			],
			'atts'       => [
				'collapsed'    => 'field_5df3398916382',
				'layout'       => 'block',
				'button_label' => esc_html__( 'Add Condition', 'mai-engine' ),
				'sub_fields'   => [
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'field_5df3398916382' => [
						'name'     => 'meta_key',
						'label'    => esc_html__( 'Meta Key', 'mai-engine' ),
						'block'    => [ 'post' ],
						'type'     => 'text',
						'sanitize' => 'esc_html',
						'default'  => '',
					],
					'field_5df29f2315d3d' => [
						'name'       => 'meta_compare',
						'label'      => esc_html__( 'Compare', 'mai-engine' ),
						'block'      => [ 'post' ],
						'type'       => 'select',
						'sanitize'   => 'esc_html',
						'default'    => '',
						'choices'    => [
							'='          => __( 'Is equal to', 'mai-engine' ),
							'!='         => __( 'Is not equal to', 'mai-engine' ),
							'>'          => __( 'Is greater than', 'mai-engine' ),
							'>='         => __( 'Is great than or equal to', 'mai-engine' ),
							'<'          => __( 'Is less than', 'mai-engine' ),
							'<='         => __( 'Is less than or equal to', 'mai-engine' ),
							'EXISTS'     => __( 'Exists', 'mai-engine' ),
							'NOT EXISTS' => __( 'Does not exist', 'mai-engine' ),
						],
						'conditions' => [
							[
								'field'    => 'field_5df3398916382', // Meta_key.
								'operator' => '!=empty',
							],
						],
					],
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					'field_5df239a217383' => [
						'name'       => 'meta_value',
						'label'      => esc_html__( 'Meta Value', 'mai-engine' ),
						'block'      => [ 'post' ],
						'type'       => 'text',
						'sanitize'   => 'esc_html',
						'default'    => '',
						'conditions' => [
							[
								'field'    => 'field_5df3398916382', // Meta_key.
								'operator' => '!=empty',
							],
							[
								'field'    => 'field_5df29f2315d3d', // Meta_compare.
								'operator' => '!=',
								'value'    => 'EXISTS',
							],
						],
					],
				],
			],
		],
		'field_5df239282737g' => [
			'name'       => 'meta_keys_relation',
			'label'      => esc_html__( 'Meta Keys Relation', 'mai-engine' ),
			'block'      => [ 'post' ],
			'type'       => 'select',
			'sanitize'   => 'esc_html',
			'default'    => 'AND',
			'choices'    => [
				'AND' => esc_html__( 'And', 'mai-engine' ),
				'OR'  => esc_html__( 'Or', 'mai-engine' ),
			],
			'conditions' => [
				[
					'field'    => 'field_5df1053632ca2', // Post_type.
					'operator' => '!=empty',
				],
				[
					'field'    => 'field_5df1053632cad', // Query_by.
					'operator' => '==',
					'value'    => 'tax_meta',
				],
				[
					'field'    => 'field_5df2053632dg5', // Meta_keys.
					'operator' => '>',
					'value'    => '1', // More than 1 row.
				],
			],
		],
		'field_5df1053744df7' => [
			'name'       => 'current_children',
			'label'      => '',
			'block'      => [ 'post' ],
			'type'       => 'true_false',
			'sanitize'   => 'mai_sanitize_bool',
			'default'    => 0, // False.
			'conditions' => [
				[
					'field'    => 'field_5df1053632ca2', // Post_type.
					'operator' => '!=empty',
				],
				[
					'field'    => 'field_5df1053632cad', // Query_by.
					'operator' => '==',
					'value'    => 'parent',
				],
			],
			'atts'       => [
				'message' => esc_html__( 'Show children of current entry', 'mai-engine' ),
			],
		],
		'field_5df1053632ce4' => [
			'name'       => 'post_parent__in',
			'label'      => esc_html__( 'Parent', 'mai-engine' ),
			'block'      => [ 'post' ],
			'type'       => 'post_object',
			'sanitize'   => 'absint',
			'default'    => '',
			'conditions' => [
				[
					'field'    => 'field_5df1053632ca2', // Post_type.
					'operator' => '!=empty',
				],
				[
					'field'    => 'field_5df1053632cad', // Query_by.
					'operator' => '==',
					'value'    => 'parent',
				],
				[
					'field'    => 'field_5df1053744df7', // Current_children.
					'operator' => '!=',
					'value'    => 1,
				],
			],
			'atts'       => [
				'multiple'      => 1, // WP_Query allows multiple parents.
				'return_format' => 'id',
				'ui'            => 1,
			],
		],
		'field_5df1053632ca8' => [
			'name'       => 'posts_per_page',
			'label'      => esc_html__( 'Number of Entries', 'mai-engine' ),
			'desc'       => esc_html__( 'Use 0 to show all.', 'mai-engine' ),
			'block'      => [ 'post' ],
			'type'       => 'number',
			'sanitize'   => 'absint',
			'default'    => 12,
			'conditions' => [
				[
					'field'    => 'field_5df1053632ca2', // Post_type.
					'operator' => '!=empty',
				],
				[
					'field'    => 'field_5df1053632cad', // Query_by.
					'operator' => '!=',
					'value'    => 'id',
				],
			],
			'atts'       => [
				'placeholder' => 12,
				'min'         => 0,
			],
		],
		'field_5df1bf01ea1de' => [
			'name'       => 'offset',
			'label'      => esc_html__( 'Offset', 'mai-engine' ),
			'desc'       => esc_html__( 'Skip this number of entries.', 'mai-engine' ),
			'block'      => [ 'post' ],
			'type'       => 'number',
			'sanitize'   => 'absint',
			'default'    => 0,
			'conditions' => [
				[
					'field'    => 'field_5df1053632ca2', // Post_type.
					'operator' => '!=empty',
				],
				[
					'field'    => 'field_5df1053632cad', // Query_by.
					'operator' => '!=',
					'value'    => 'id',
				],
			],
			'atts'       => [
				'placeholder' => 0,
				'min'         => 0,
			],
		],
		'field_5df1053632cec' => [
			'name'       => 'orderby',
			'label'      => esc_html__( 'Order By', 'mai-engine' ),
			'block'      => [ 'post' ],
			'type'       => 'select',
			'sanitize'   => 'esc_html',
			'default'    => 'date',
			'choices'    => [
				'title'          => esc_html__( 'Title', 'mai-engine' ),
				'name'           => esc_html__( 'Slug', 'mai-engine' ),
				'date'           => esc_html__( 'Date', 'mai-engine' ),
				'modified'       => esc_html__( 'Modified', 'mai-engine' ),
				'rand'           => esc_html__( 'Random', 'mai-engine' ),
				'comment_count'  => esc_html__( 'Comment Count', 'mai-engine' ),
				'menu_order'     => esc_html__( 'Menu Order', 'mai-engine' ),
				'meta_value_num' => esc_html__( 'Meta Value Number', 'mai-engine' ),
			],
			'conditions' => [
				[
					'field'    => 'field_5df1053632ca2', // Post_type.
					'operator' => '!=empty',
				],
				[
					'field'    => 'field_5df1053632cad', // Query_by.
					'operator' => '!=',
					'value'    => 'id',
				],
			],
			'atts'       => [
				'ui'   => 1,
				'ajax' => 1,
			],
		],
		'field_5df1053632cf4' => [
			'name'       => 'orderby_meta_key',
			'label'      => esc_html__( 'Meta key', 'mai-engine' ),
			'block'      => [ 'post' ],
			'type'       => 'text',
			'sanitize'   => 'esc_html',
			'default'    => '',
			'conditions' => [
				[
					'field'    => 'field_5df1053632ca2', // Post_type.
					'operator' => '!=empty',
				],
				[
					'field'    => 'field_5df1053632cec', // Orderby.
					'operator' => '==',
					'value'    => 'meta_value_num',
				],
			],
		],
		'field_5df1053632cfb' => [
			'name'       => 'order',
			'label'      => esc_html__( 'Order', 'mai-engine' ),
			'block'      => [ 'post' ],
			'type'       => 'select',
			'sanitize'   => 'esc_html',
			'default'    => 'DESC',
			'choices'    => [
				'ASC'  => esc_html__( 'Ascending', 'mai-engine' ),
				'DESC' => esc_html__( 'Descending', 'mai-engine' ),
			],
			'conditions' => [
				[
					'field'    => 'field_5df1053632ca2', // Post_type.
					'operator' => '!=empty',
				],
				[
					'field'    => 'field_5df1053632cad', // Query_by.
					'operator' => '!=',
					'value'    => 'id',
				],
			],
		],
		'field_5e349237e1c01' => [
			'name'       => 'post__not_in',
			'label'      => esc_html__( 'Exclude Entries', 'mai-engine' ),
			'desc'       => esc_html__( 'Hide specific entries. Choose all that apply.', 'mai-engine' ),
			'block'      => [ 'post' ],
			'type'       => 'post_object',
			'sanitize'   => 'absint',
			'default'    => '',
			'conditions' => [
				[
					'field'    => 'field_5df1053632ca2', // Post_type.
					'operator' => '!=empty',
				],
				[
					'field'    => 'field_5df1053632cad', // Query_by.
					'operator' => '!=',
					'value'    => 'id',
				],
			],
			'atts'       => [
				'multiple'      => 1,
				'return_format' => 'id',
				'ui'            => 1,
			],
		],
		'field_5df1053632d03' => [
			'name'       => 'excludes',
			'label'      => esc_html__( 'Exclude', 'mai-engine' ),
			'block'      => [ 'post' ],
			'type'       => 'checkbox',
			'sanitize'   => 'esc_html',
			'default'    => '',
			'choices'    => [
				'exclude_displayed' => esc_html__( 'Exclude displayed', 'mai-engine' ),
				'exclude_current'   => esc_html__( 'Exclude current', 'mai-engine' ),
			],
			'conditions' => [
				[
					'field'    => 'field_5df1053632ca2', // Post_type.
					'operator' => '!=empty',
				],
			],
		],
		/*********
		 * Terms *
		 */
		'field_5df2063632ca2' => [
			'name'     => 'taxonomy',
			'label'    => esc_html__( 'Taxonomy', 'mai-engine' ),
			'block'    => [ 'term' ],
			'type'     => 'select',
			'sanitize' => 'esc_html',
			'default'  => [ 'category' ],
			'choices'  => 'mai_get_taxonomy_choices',
			'atts'     => [
				'multiple' => 1,
				'ui'       => 1,
				'ajax'     => 0,
			],
		],
		'field_5df1054642cad' => [
			'name'       => 'query_by',
			'label'      => esc_html__( 'Get Entries By', 'mai-engine' ),
			'block'      => [ 'term' ],
			'type'       => 'select',
			'sanitize'   => 'esc_html',
			'default'    => 'date',
			'choices'    => [
				'name'   => esc_html__( 'Taxonomy', 'mai-engine' ),
				'id'     => esc_html__( 'Choice', 'mai-engine' ),
				'parent' => esc_html__( 'Parent', 'mai-engine' ),
			],
			'conditions' => [
				[
					'field'    => 'field_5df2063632ca2', // Taxonomy.
					'operator' => '!=empty',
				],
			],
		],
		'field_5df2065733db9' => [
			'name'       => 'number',
			'label'      => esc_html__( 'Number of Entries', 'mai-engine' ),
			'desc'       => esc_html__( 'Use 0 to show all.', 'mai-engine' ),
			'block'      => [ 'term' ],
			'type'       => 'number',
			'sanitize'   => 'absint',
			'default'    => 12,
			'conditions' => [
				[
					'field'    => 'field_5df2063632ca2', // Taxonomy.
					'operator' => '!=empty',
				],
				[
					'field'    => 'field_5df1054642cad', // Query_by.
					'operator' => '!=',
					'value'    => 'id',
				],
			],
			'atts'       => [
				'placeholder' => 12,
				'min'         => 0,
			],
		],
		'field_5df10647743cb' => [
			'name'       => 'include',
			'label'      => esc_html__( 'Entries', 'mai-engine' ),
			'desc'       => esc_html__( 'Show specific entries. Choose all that apply. If empty, Grid will get entries by date.', 'mai-engine' ),
			'block'      => [ 'term' ],
			'type'       => 'taxonomy',
			'sanitize'   => 'absint',
			'default'    => '',
			'conditions' => [
				[
					'field'    => 'field_5df2063632ca2', // Taxonomy.
					'operator' => '!=empty',
				],
				[
					'field'    => 'field_5df1054642cad', // Query_by.
					'operator' => '==',
					'value'    => 'id',
				],
			],
			'atts'       => [
				'field_type' => 'multi_select',
				'add_term'   => 0,
				'save_terms' => 0,
				'load_terms' => 0,
				'multiple'   => 1,
			],
		],
		'field_5df1053854eg6' => [
			'name'       => 'current_children',
			'label'      => '',
			'block'      => [ 'term' ],
			'type'       => 'true_false',
			'sanitize'   => 'mai_sanitize_bool',
			'default'    => 0, // False.
			'conditions' => [
				[
					'field'    => 'field_5df2063632ca2', // Taxonomy.
					'operator' => '!=empty',
				],
				[
					'field'    => 'field_5df1054642cad', // Query_by.
					'operator' => '==',
					'value'    => 'parent',
				],
			],
			'atts'       => [
				'message' => esc_html__( 'Show children of current entry', 'mai-engine' ),
			],
		],
		'field_5df1054743df5' => [
			'name'       => 'parent',
			'label'      => esc_html__( 'Parent', 'mai-engine' ),
			'block'      => [ 'term' ],
			'type'       => 'taxonomy',
			'sanitize'   => 'absint',
			'default'    => '',
			'conditions' => [
				[
					'field'    => 'field_5df2063632ca2', // Taxonomy.
					'operator' => '!=empty',
				],
				[
					'field'    => 'field_5df1054642cad', // Query_by.
					'operator' => '==',
					'value'    => 'parent',
				],
				[
					'field'    => 'field_5df1053854eg6', // Current_children.
					'operator' => '!=',
					'value'    => 1,
				],
			],
			'atts'       => [
				'field_type' => 'select',
				'add_term'   => 0,
				'save_terms' => 0,
				'load_terms' => 0,
				'multiple'   => 0, // WP_Term_Query only allows 1.
			],
		],
		'field_5df2cg12fb2ef' => [
			'name'       => 'offset',
			'label'      => esc_html__( 'Offset', 'mai-engine' ),
			'desc'       => esc_html__( 'Skip this number of entries.', 'mai-engine' ),
			'block'      => [ 'term' ],
			'type'       => 'number',
			'sanitize'   => 'absint',
			'default'    => 0,
			'conditions' => [
				[
					'field'    => 'field_5df2063632ca2', // Taxonomy.
					'operator' => '!=empty',
				],
				[
					'field'    => 'field_5df1053632cad', // Query_by.
					'operator' => '!=',
					'value'    => 'id',
				],
			],
			'atts'       => [
				'placeholder' => 0,
				'min'         => 0,
			],
		],
		'field_5dg2164743dfd' => [
			'name'       => 'orderby',
			'label'      => esc_html__( 'Order By', 'mai-engine' ),
			'block'      => [ 'term' ],
			'type'       => 'select',
			'sanitize'   => 'esc_html',
			'default'    => 'date',
			'choices'    => [
				'name'  => esc_html__( 'Title', 'mai-engine' ),
				'slug'  => esc_html__( 'Slug', 'mai-engine' ),
				'count' => esc_html__( 'Entry Totals', 'mai-engine' ),
				'id'    => esc_html__( 'Term ID', 'mai-engine' ),
			],
			'conditions' => [
				[
					'field'    => 'field_5df2063632ca2', // Taxonomy.
					'operator' => '!=empty',
				],
				[
					'field'    => 'field_5df1054642cad', // Query_by.
					'operator' => '!=',
					'value'    => 'id',
				],
			],
			'atts'       => [
				'ui'   => 1,
				'ajax' => 1,
			],
		],
		'field_5df2164743dgc' => [
			'name'       => 'order',
			'label'      => esc_html__( 'Order', 'mai-engine' ),
			'block'      => [ 'term' ],
			'type'       => 'select',
			'sanitize'   => 'esc_html',
			'default'    => '',
			'choices'    => [
				'ASC'  => esc_html__( 'Ascending', 'mai-engine' ),
				'DESC' => esc_html__( 'Descending', 'mai-engine' ),
			],
			'conditions' => [
				[
					'field'    => 'field_5df2063632ca2', // Taxonomy.
					'operator' => '!=empty',
				],
				[
					'field'    => 'field_5df1054642cad', // Query_by.
					'operator' => '!=',
					'value'    => 'id',
				],
			],
		],
		'field_5e459348f2d12' => [
			'name'       => 'exclude',
			'label'      => esc_html__( 'Exclude Entries', 'mai-engine' ),
			'desc'       => esc_html__( 'Hide specific entries. Choose all that apply.', 'mai-engine' ),
			'block'      => [ 'term' ],
			'type'       => 'taxonomy',
			'sanitize'   => 'absint',
			'default'    => '',
			'conditions' => [
				[
					'field'    => 'field_5df2063632ca2', // Taxonomy.
					'operator' => '!=empty',
				],
				[
					'field'    => 'field_5df1054642cad', // Query_by.
					'operator' => '!=',
					'value'    => 'id',
				],
			],
			'atts'       => [
				'field_type' => 'multi_select',
				'add_term'   => 0,
				'save_terms' => 0,
				'load_terms' => 0,
				'multiple'   => 1,
			],
		],
		// TODO: Shoud these be separate fields? We can then have desc text and easier to check when building query.
		'field_5df21757632e1' => [
			'name'       => 'excludes',
			'label'      => esc_html__( 'Exclude', 'mai-engine' ),
			'block'      => [ 'term' ],
			'type'       => 'checkbox',
			'sanitize'   => 'esc_html',
			'default'    => [
				'hide_empty',
			],
			'choices'    => [
				'hide_empty'        => esc_html__( 'Exclude terms with no posts', 'mai-engine' ),
				'exclude_displayed' => esc_html__( 'Exclude displayed', 'mai-engine' ),
				'exclude_current'   => esc_html__( 'Exclude current', 'mai-engine' ),
			],
			'conditions' => [
				[
					'field'    => 'field_5df2063632ca2', // Taxonomy.
					'operator' => '!=empty',
				],
			],
		],
	];
}
