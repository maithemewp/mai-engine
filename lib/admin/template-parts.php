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

add_action( 'current_screen', 'mai_create_template_parts' );
/**
 * Create default template parts if they don't exist.
 * Only runs on main template part admin list.
 *
 * @since 2.0.0
 *
 * @return void
 */
function mai_create_template_parts( $current_screen ) {
	// Bail if running in setup wizard.
	if ( did_action( 'mai_setup_wizard_before_steps' ) ) {
		return;
	}

	if ( 'edit-wp_template_part' !== $current_screen->id ) {
		return;
	}

	$template_parts = mai_get_config( 'template-parts' );

	foreach ( $template_parts as $template_part ) {
		if ( mai_template_part_exists( $template_part['id'] ) ) {
			continue;
		}

		$args = [
			'post_type'   => 'wp_template_part',
			'post_title'  => mai_convert_case( $template_part['id'], 'title' ),
			'post_name'   => $template_part['id'],
			'post_status' => 'publish',
			'menu_order'  => mai_isset( $template_part['menu_order'], 0 ),
		];

		wp_insert_post( $args );
	}
}

add_filter( 'display_post_states', 'mai_template_part_post_state', 10, 2 );
/**
 * Display active template parts.
 *
 * @since 2.0.0
 *
 * @param array   $states Array of post states.
 * @param WP_Post $post   Post object.
 *
 * @return mixed
 */
function mai_template_part_post_state( $states, $post ) {
	$template_parts = mai_get_config( 'template-parts' );

	foreach ( $template_parts as $template_part ) {
		if ( $template_part['id'] === $post->post_name && $post->post_content ) {
			$states[] = __( 'Active', 'mai-engine' );
		}
	}

	return $states;
}

/**
 * Add slug column to Template Parts.
 * Inserts as second to last item.
 *
 * @since 2.0.0
 *
 * @param array $column_array The existing post type columns.
 *
 * @return array
 */
add_filter( 'manage_wp_template_part_posts_columns', 'mai_template_part_add_slug_column' );
function mai_template_part_add_slug_column( $column_array ) {
	$new_column = [
		'slug' => __( 'Slug', 'mai-engine' ),
	];

	$columns = count( $column_array );
	$offset  = count( $column_array ) > 1 ? count( $column_array ) - 1 : count( $column_array );

	return array_slice( $column_array, 0, $offset, true ) + $new_column + array_slice( $column_array, $offset, null, true );
}

/**
 * Populate template part slug column with actual slug.
 *
 * @since 2.0.0
 *
 * @return void
 */
add_action( 'manage_posts_custom_column', 'mai_template_part_add_slug', 10, 2 );
function mai_template_part_add_slug( $column_name, $post_id ) {
	if ( 'slug' === $column_name ) {
		echo get_post_field( 'post_name', $post_id );
	}
}

/**
 * Reorder template part admin list.
 *
 * @since 2.0.0
 *
 * @return void
 */
add_action( 'pre_get_posts', 'mai_template_parts_order' );
function mai_template_parts_order( $query ) {
	if ( ! is_admin() ) {
		return;
	}

	if ( ! $query->is_main_query() ) {
		return;
	}

	$query->set( 'orderby', 'menu_order' );
	$query->set( 'order', 'ASC' );
}
