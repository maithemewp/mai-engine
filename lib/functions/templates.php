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
 * @since 2.3.1 Changed can_export to true.
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
		'can_export'        => true,
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

	$wp_admin_bar->add_node(
		[
			'id'     => 'template-parts',
			'parent' => 'site-name',
			'title'  => __( 'Template Parts', 'mai-engine' ),
			'href'   => admin_url( 'edit.php?post_type=wp_template_part' ),
			'meta'   => [
				'title' => __( 'Edit Template Parts', 'mai-engine' ),
			],
		]
	);

	$wp_admin_bar->add_node(
		[
			'id'     => 'reusable-blocks',
			'parent' => 'site-name',
			'title'  => __( 'Reusable Blocks', 'mai-engine' ),
			'href'   => admin_url( 'edit.php?post_type=wp_block' ),
			'meta'   => [
				'title' => __( 'Edit Reusable Blocks', 'mai-engine' ),
			],
		]
	);
}

/**
 * Returns a static array of all template part content, keyed by slug.
 *
 * @since 2.0.1
 * @since 2.2.2 Now returns an array of template part content content keyed by slug instead of an array of WP_Post objects.
 * @since 2.3.1 Removed the is_admin() check since this was finally solved https://github.com/maithemewp/mai-engine/issues/251.
 *              Changed return values to check and use post status.
 *
 * @return array
 */
function mai_get_template_parts() {
	static $template_parts = null;

	if ( is_null( $template_parts ) ) {
		$template_part = [];
		$config        = mai_get_config( 'template-parts' );
		$slugs         = $config ? wp_list_pluck( $config, 'id' ) : [];

		if ( $slugs ) {

			$posts = get_posts(
				[
					'post_type'              => 'wp_template_part',
					'post_status'            => 'any',
					'post_name__in'          => $slugs,
					'posts_per_page'         => 500, // Force a high number. Without setting this, it uses the WP posts_per_page setting, which could break things.
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				]
			);

			if ( $posts ) {
				foreach ( $posts as $post ) {
					global $wp_embed;

					$content = $post->post_content;

					if ( $content ) {
						$content = $wp_embed->autoembed( $content );              // WP runs priority 8.
						$content = $wp_embed->run_shortcode( $content );          // WP runs priority 8.
						$content = do_blocks( $content );                         // WP runs priority 9.
						$content = wptexturize( $content );                       // WP runs priority 10.
						$content = wpautop( $content );                           // WP runs priority 10.
						$content = shortcode_unautop( $content );                 // WP runs priority 10.
						$content = wp_make_content_images_responsive( $content ); // WP runs priority 10.
						$content = do_shortcode( $content );                      // WP runs priority 11.
						$content = convert_smilies( $content );                   // WP runs priority 20.
					}

					$template_parts[ $post->post_status ][ $post->post_name ] = $content;
				}
			}
		}
	}

	$return = [];

	if ( $template_parts ) {
		if ( is_admin() ) {
			foreach ( $template_parts as $status => $parts ) {
				$return = array_merge( $return, $parts );
			}
		} else {
			$return = isset( $template_parts[ 'publish' ] ) ? $template_parts[ 'publish' ] : [];

			if ( current_user_can( 'manage_options' ) ) {
				$private = isset( $template_parts[ 'private' ] ) ? $template_parts[ 'private' ] : [];
				$return  = array_merge( $return, $private );
			}
		}
	}

	return $return;
}

/**
 * Gets a template part content by its slug.
 *
 * @since 2.0.1
 * @since 2.2.2 Returns the template part content instead of ID.
 *
 * @param string $slug Template part slug.
 *
 * @return string
 */
function mai_get_template_part( $slug ) {
	$template_parts = mai_get_template_parts();
	return isset( $template_parts[ $slug ] ) ? $template_parts[ $slug ] : '';
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
	return mai_template_part_exists( $slug ) && mai_get_template_part( $slug );
}

/**
 * Checks whether the template part exists.
 *
 * @since 2.2.2
 *
 * @param string $slug Template part slug.
 *
 * @return bool
 */
function mai_template_part_exists( $slug ) {
	$template_parts = mai_get_template_parts();
	return isset( $template_parts[ $slug ] );
}

/**
 * Renders the template part with the given slug.
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
		echo $template_part;
		echo $after;
	}
}

