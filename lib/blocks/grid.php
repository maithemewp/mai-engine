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
	$fields = mai_get_settings( 'grid-block' );
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
	$fields = mai_get_settings( 'grid-block' );

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
