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

add_action( 'init', 'mai_register_block_areas_cpt' );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_register_block_areas_cpt() {
	$labels = [
		'name'                  => __( 'Block Areas', 'mai-engine' ),
		'singular_name'         => __( 'Block Area', 'mai-engine' ),
		'menu_name'             => _x( 'Block Areas', 'Admin Menu text', 'mai-engine' ),
		'add_new'               => _x( 'Add New', 'Block Area', 'mai-engine' ),
		'add_new_item'          => __( 'Add New Block Area', 'mai-engine' ),
		'new_item'              => __( 'New Block Area', 'mai-engine' ),
		'edit_item'             => __( 'Edit Block Area', 'mai-engine' ),
		'view_item'             => __( 'View Block Area', 'mai-engine' ),
		'all_items'             => __( 'Block Areas', 'mai-engine' ),
		'search_items'          => __( 'Search Block Areas', 'mai-engine' ),
		'parent_item_colon'     => __( 'Parent Block Area:', 'mai-engine' ),
		'not_found'             => __( 'No block areas found.', 'mai-engine' ),
		'not_found_in_trash'    => __( 'No block areas found in Trash.', 'mai-engine' ),
		'archives'              => __( 'Block area archives', 'mai-engine' ),
		'insert_into_item'      => __( 'Insert in to block area', 'mai-engine' ),
		'uploaded_to_this_item' => __( 'Uploaded to this block area', 'mai-engine' ),
		'filter_items_list'     => __( 'Filter block areas list', 'mai-engine' ),
		'items_list_navigation' => __( 'Block areas list navigation', 'mai-engine' ),
		'items_list'            => __( 'Block areas list', 'mai-engine' ),
	];

	$args = [
		'labels'             => $labels,
		'description'        => __( 'Block areas to include in your theme.', 'mai-engine' ),
		'public'             => false,
		'publicly_queryable' => true,
		'has_archive'        => false,
		'show_ui'            => true,
		'show_in_menu'       => 'themes.php',
		'show_in_admin_bar'  => false,
		'show_in_rest'       => true,
		'rest_base'          => 'mai-engine',
		'capability_type'    => [ 'block_area', 'block_areas' ],
		'map_meta_cap'       => true,
		'supports'           => [
			'title',
			'editor',
			'thumbnail',
			'amp',
			'revisions',
		],
	];

	register_post_type( 'block_area', $args );
}

/**
 * Renders the block area with the given slug.
 *
 * @since 0.1.0
 *
 * @global WP_Post $post   Current WordPress post object.
 *
 * @param string   $slug   Block area slug.
 * @param array    $args   {
 *                         Optional. Additional rendering arguments.
 *
 * @type string    $before Additional markup to render before the block area. Default empty string.
 * @type string    $after  Additional markup to render after the block area. Default empty string.
 * }
 */
function mai_render_block_area( $slug, array $args = [] ) {
	global $post;

	$id = mai_get_block_area_by_slug( $slug );

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

	// Set up block area and render its content.
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
 * Checks whether the block area with the given slug exists.
 *
 * @since 0.1.0
 *
 * @param string $slug Block area slug.
 *
 * @return bool True if the block area exists, false otherwise.
 */
function mai_block_area_exists( $slug ) {
	$id = mai_get_block_area_by_slug( $slug );

	return ! empty( $id );
}

/**
 * Gets a block area ID by its slug.
 *
 * @since 0.1.0
 *
 * @param string $slug Block area slug.
 *
 * @return int Block area ID, or 0 if not found.
 */
function mai_get_block_area_by_slug( $slug ) {
	$posts = get_posts(
		[
			'fields'                 => 'ids',
			'posts_per_page'         => 1,
			'post_type'              => 'block_area',
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
