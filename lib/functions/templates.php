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

add_action( 'init', 'mai_register_template_part_cpt' );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_register_template_part_cpt() {
	$labels = [
		'name'                  => __( 'Template Parts', 'mai-engine' ),
		'singular_name'         => __( 'Template Part', 'mai-engine' ),
		'menu_name'             => _x( 'Template Parts', 'Admin Menu text', 'mai-engine' ),
		'add_new'               => _x( 'Add New', 'Template Part', 'mai-engine' ),
		'add_new_item'          => __( 'Add New Template Part', 'mai-engine' ),
		'new_item'              => __( 'New Template Part', 'mai-engine' ),
		'edit_item'             => __( 'Edit Template Part', 'mai-engine' ),
		'view_item'             => __( 'View Template Part', 'mai-engine' ),
		'all_items'             => __( 'Template Parts', 'mai-engine' ),
		'search_items'          => __( 'Search Template Parts', 'mai-engine' ),
		'parent_item_colon'     => __( 'Parent Template Part:', 'mai-engine' ),
		'not_found'             => __( 'No template parts found.', 'mai-engine' ),
		'not_found_in_trash'    => __( 'No template parts found in Trash.', 'mai-engine' ),
		'archives'              => __( 'Template part archives', 'mai-engine' ),
		'insert_into_item'      => __( 'Insert in to template part', 'mai-engine' ),
		'uploaded_to_this_item' => __( 'Uploaded to this template part', 'mai-engine' ),
		'filter_items_list'     => __( 'Filter template parts list', 'mai-engine' ),
		'items_list_navigation' => __( 'Template parts list navigation', 'mai-engine' ),
		'items_list'            => __( 'Template parts list', 'mai-engine' ),
	];

	$args = [
		'labels'             => $labels,
		'description'        => __( 'Template parts to include in your theme.', 'mai-engine' ),
		'public'             => false,
		'publicly_queryable' => true,
		'has_archive'        => false,
		'show_ui'            => true,
		'show_in_menu'       => 'themes.php',
		'show_in_admin_bar'  => false,
		'show_in_rest'       => true,
		'rest_base'          => 'mai-engine',
		'capability_type'    => [ 'wp_template_part', 'wp_template_parts' ],
		'map_meta_cap'       => true,
		'supports'           => [
			'title',
			'editor',
			'thumbnail',
			'amp',
			'revisions',
		],
	];

	register_post_type( 'wp_template_part', $args );
}

/**
 * Renders the template part with the given slug.
 *
 * @since 0.1.0
 *
 * @global WP_Post $post   Current WordPress post object.
 *
 * @param string   $slug   Template part slug.
 * @param array    $args   {
 *                         Optional. Additional rendering arguments.
 *
 * @type string    $before Additional markup to render before the template part. Default empty string.
 * @type string    $after  Additional markup to render after the template part. Default empty string.
 * }
 */
function mai_render_template_part( $slug, array $args = [] ) {
	global $post;

	$id = mai_get_template_part_by_slug( $slug );

	if ( empty( $id ) ) {
		return;
	}

	$args = wp_parse_args(
		$args,
		[
			'before' => '',
			'after'  => '',
		]
	);

	// Save original post to restore it later.
	$orig_post = $post;

	// Set up template part and render its content.
	$post = get_post( $id );
	setup_postdata( $post );

	echo $args['before']; // phpcs:ignore WordPress.Security.EscapeOutput
	the_content();
	echo $args['after']; // phpcs:ignore WordPress.Security.EscapeOutput

	// Restore original post.
	$post = $orig_post;
	setup_postdata( $post );
}

/**
 * Checks whether the template part with the given slug exists.
 *
 * @since 0.1.0
 *
 * @param string $slug Template part slug.
 *
 * @return bool True if the template part exists, false otherwise.
 */
function mai_template_part_exists( $slug ) {
	$id = mai_get_template_part_by_slug( $slug );

	return ! empty( $id );
}

/**
 * Gets a template part ID by its slug.
 *
 * @since 0.1.0
 *
 * @param string $slug Template part slug.
 *
 * @return int Template part ID, or 0 if not found.
 */
function mai_get_template_part_by_slug( $slug ) {
	$posts = get_posts(
		[
			'fields'                 => 'ids',
			'posts_per_page'         => 1,
			'post_type'              => 'wp_template_part',
			'post_status'            => 'publish',
			'name'                   => $slug,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		]
	);

	if ( empty( $posts ) ) {
		return 0;
	}

	return (int) array_shift( $posts );
}
