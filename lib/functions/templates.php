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
 * Register template part post type.
 *
 * @since 2.0.0
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
		'labels'            => $labels,
		'description'       => __( 'Template parts to include in your templates.', 'mai-engine' ),
		'public'            => false,
		'has_archive'       => false,
		'rewrite'           => false,
		'show_ui'           => true,
		'show_in_menu'      => 'themes.php',
		'show_in_nav_menus' => false,
		'show_in_admin_bar' => false,
		'show_in_rest'      => true,
		'rest_base'         => 'template-parts',
		'map_meta_cap'      => true,
		'can_export'        => false,
		'supports'          => [
			'title',
			'slug',
			'editor',
			'revisions',
			'custom-fields',
			'page-attributes',
		],
	];

	// TODO: Can we use this to check which theme set the template part instead of making backups?
	$meta_args = [
		'object_subtype' => 'wp_template_part',
		'type'           => 'string',
		'description'    => __( 'The theme that provided the template part, if any.', 'mai-engine' ),
		'single'         => true,
		'show_in_rest'   => true,
	];

	register_post_type( 'wp_template_part', $args );
	register_meta( 'post', 'theme', $meta_args );
}

add_action( 'admin_bar_menu', 'mai_add_admin_bar_links', 999 );
/**
 * Add links to toolbar.
 *
 * @since 2.1.1
 *
 * @param WP_Admin_Bar $wp_admin_bar Admin bar object.
 *
 * @return void
 */
function mai_add_admin_bar_links( $wp_admin_bar ) {
	if ( is_admin() ) {
		return;
	}

	$wp_admin_bar->add_node( [
		'id'     => 'template-parts',
		'parent' => 'site-name',
		'title'  => __( 'Template Parts', 'mai-engine' ),
		'href'   => admin_url( 'edit.php?post_type=wp_template_part' ),
		'meta'   => [
			'title' => __( 'Edit Template Parts', 'mai-engine' ),
		],
	] );

	$wp_admin_bar->add_node( [
		'id'     => 'reusable-blocks',
		'parent' => 'site-name',
		'title'  => __( 'Reusable Blocks', 'mai-engine' ),
		'href'   => admin_url( 'edit.php?post_type=wp_block' ),
		'meta'   => [
			'title' => __( 'Edit Reusable Blocks', 'mai-engine' ),
		],
	] );
}

/**
 * Returns a static array of all template part data.
 *
 * @since 2.0.1
 *
 * @return array
 */
function mai_get_template_parts() {
	static $template_parts = [];

	if ( empty( $template_parts ) ) {
		$slugs          = [];
		$config         = mai_get_config( 'template-parts' );
		$template_parts = get_posts(
			[
				'numberposts' => -1,
				'post_type'   => 'wp_template_part',
				'post_status' => 'publish',
			]
		);

		foreach ( $config as $template_part ) {
			$slugs[] = $template_part['id'];
		}

		foreach ( $template_parts as $index => $template_part ) {
			if ( ! in_array( $template_part->post_name, $slugs, true ) ) {
				unset( $template_parts[ $index ] );
			}
		}
	}

	return $template_parts;
}

/**
 * Gets a template part ID by its slug.
 *
 * @since 2.0.1
 *
 * @param string $slug Template part slug.
 *
 * @return null|WP_Post
 */
function mai_get_template_part( $slug ) {
	static $template_parts = [];

	if ( ! array_key_exists( $slug, $template_parts ) ) {
		$template_parts[ $slug ] = null;
		$all_template_parts      = mai_get_template_parts();

		/**
		 * @var WP_Post $template_part Post object.
		 */
		foreach ( $all_template_parts as $template_part ) {
			if ( $slug === $template_part->post_name ) {
				$template_parts[ $slug ] = $template_part;
			}
		}
	}

	return $template_parts[ $slug ];
}

/**
 * Checks whether the template part exists and has content.
 *
 * @since 2.0.1
 *
 * @param string $slug Template part slug.
 *
 * @return bool
 */
function mai_has_template_part( $slug ) {
	$template_part = mai_get_template_part( $slug );

	return $template_part && $template_part->post_content;
}

/**
 * Renders the template part with the given slug.
 *
 * No need to check for post_content or post_status, all checks
 * are handled in helper functions.
 *
 * @since 2.0.1
 *
 * @param string $slug   Template part slug.
 * @param string $before Before content markup.
 * @param string $after  After content markup.
 *
 * @return void
 */
function mai_render_template_part( $slug, $before = '', $after = '' ) {
	$template_part = mai_get_template_part( $slug );

	if ( $template_part ) {
		echo $before;
		echo apply_filters( 'the_content', $template_part->post_content );
		echo $after;
	}
}

