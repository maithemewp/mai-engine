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

add_action( 'init', 'mai_register_template_part_cpt' );
/**
 * Register template part post type.
 *
 * @since 2.0.0
 * @since 2.4.0 Changed can_export to true.
 * @since 2.11.0 Changed post type name to avoid conflict with WP 5.7.
 *
 * @return void
 */
function mai_register_template_part_cpt() {
	$labels = [
		'name'                  => __( 'Content Areas', 'mai-engine' ),
		'singular_name'         => __( 'Content Area', 'mai-engine' ),
		'menu_name'             => _x( 'Content Areas', 'Admin Menu text', 'mai-engine' ),
		'add_new'               => _x( 'Add New', 'Content Area', 'mai-engine' ),
		'add_new_item'          => __( 'Add New Content Area', 'mai-engine' ),
		'new_item'              => __( 'New Content Area', 'mai-engine' ),
		'edit_item'             => __( 'Edit Content Area', 'mai-engine' ),
		'view_item'             => __( 'View Content Area', 'mai-engine' ),
		'all_items'             => __( 'Content Areas', 'mai-engine' ),
		'search_items'          => __( 'Search Content Areas', 'mai-engine' ),
		'parent_item_colon'     => __( 'Parent Content Area:', 'mai-engine' ),
		'not_found'             => __( 'No content areas found.', 'mai-engine' ),
		'not_found_in_trash'    => __( 'No content areas found in Trash.', 'mai-engine' ),
		'archives'              => __( 'Template part archives', 'mai-engine' ),
		'insert_into_item'      => __( 'Insert in to template part', 'mai-engine' ),
		'uploaded_to_this_item' => __( 'Uploaded to this template part', 'mai-engine' ),
		'filter_items_list'     => __( 'Filter content areas list', 'mai-engine' ),
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
		'show_in_menu'      => current_user_can( 'edit_theme_options' ) ? 'themes.php' : false,
		'show_in_nav_menus' => false,
		'show_in_admin_bar' => false,
		'show_in_rest'      => true,
		'rest_base'         => 'mai-template-parts',
		'map_meta_cap'      => true,
		'can_export'        => true,
		'capabilities'      => [
			'create_posts' => 'do_not_allow',
		],
		'supports'          => [
			'title',
			'slug',
			'editor',
			'revisions',
			'custom-fields',
			'page-attributes',
		],
	];

	$db_version = mai_get_option( 'db-version', false );

	// We need the old post type during upgrade/migration.
	if ( $db_version && version_compare( $db_version, '2.11.0', '<' ) && ! post_type_exists( 'wp_template_part' ) ) {
		register_post_type( 'wp_template_part', $args );
		register_meta( 'post', 'theme',
			[
				'object_subtype' => 'wp_template_part',
				'type'           => 'string',
				'description'    => __( 'The theme that provided the template part, if any.', 'mai-engine' ),
				'single'         => true,
				'show_in_rest'   => true,
			]
		);
	}

	register_post_type( 'mai_template_part', $args );
	// TODO: Can we use this to check which theme set the template part instead of making backups?
	register_meta( 'post', 'theme',
		[
			'object_subtype' => 'mai_template_part',
			'type'           => 'string',
			'description'    => __( 'The theme that provided the template part, if any.', 'mai-engine' ),
			'single'         => true,
			'show_in_rest'   => true,
		]
	);
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

	if ( current_user_can( 'manage_options' ) ) {

		$wp_admin_bar->add_node(
			[
				'id'     => 'mai-template-parts',
				'parent' => 'site-name',
				'title'  => __( 'Content Areas', 'mai-engine' ),
				'href'   => admin_url( 'edit.php?post_type=mai_template_part' ),
				'meta'   => [
					'title' => __( 'Edit Content Areas', 'mai-engine' ),
				],
			]
		);
	}

	if ( current_user_can( 'edit_posts' ) ) {

		$wp_admin_bar->add_node(
			[
				'id'     => 'mai-reusable-blocks',
				'parent' => 'site-name',
				'title'  => __( 'Synced Patterns', 'mai-engine' ),
				'href'   => admin_url( 'edit.php?post_type=wp_block' ),
				'meta'   => [
					'title' => __( 'Edit Patterns', 'mai-engine' ),
				],
			]
		);
	}
}

/**
 * Returns an array of all template part content, keyed by slug.
 *
 * @since 2.0.1
 * @since 2.2.2 Now returns an array of template part content content keyed by slug instead of an array of WP_Post
 * @since 2.4.0 Removed the is_admin() check since this was finally solved
 *        https://github.com/maithemewp/mai-engine/issues/251. Changed return values to check and use post status.
 *        objects.
 * @since 2.4.2 Simplify function, remove use of wp_make_content_images_responsive.
 *
 * @return array
 */
function mai_get_template_parts() {
	static $template_parts = null;

	if ( ! is_null( $template_parts ) ) {
		return $template_parts;
	}

	$template_parts = [];
	$posts          = [];
	$objects        = mai_get_template_part_objects();

	if ( $objects ) {
		foreach ( $objects as $slug => $post ) {
			$posts[ $post->post_status ][ $slug ] = $post->post_content ?: '';
		}
	}

	if ( ! empty( $posts ) ) {
		if ( is_admin() ) {
			foreach ( $posts as $status => $parts ) {
				$template_parts = array_merge( $template_parts, $parts );
			}

		} else {
			$template_parts = isset( $posts['publish'] ) ? $posts['publish'] : [];

			if ( current_user_can( 'edit_posts' ) ) {
				$private        = isset( $posts['private'] ) ? $posts['private'] : [];
				$template_parts = array_merge( $template_parts, $private );
			}
		}
	}

	$template_parts = apply_filters( 'mai_template_parts', $template_parts );

	return $template_parts;
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
 * Returns an array of existing template part IDs.
 *
 * @since 2.6.0
 *
 * @return array
 */
function mai_get_template_part_ids() {
	static $ids = null;

	if ( is_array( $ids ) ) {
		return $ids;
	}

	$ids     = [];
	$objects = mai_get_template_part_objects();

	if ( $objects ) {
		foreach ( $objects as $slug => $post ) {
			$ids[ $slug ] = $post->ID;
		}
	}

	return $ids;
}

/**
 * Returns a template part ID from slug.
 *
 * @since 2.6.0
 *
 * @param string $slug The template part slug.
 *
 * @return array
 */
function mai_get_template_part_id( $slug ) {
	$template_parts = mai_get_template_part_ids();
	return isset( $template_parts[ $slug ] ) ? $template_parts[ $slug ] : 0;
}

/**
 * Returns an array of content areas.
 * Slugs must exist in the config.
 *
 * @since 2.6.0
 * @since 2.10.0 Changes `posts_per_page` value from `count( $slugs )` to 500
 *              since WP seems to allow draft posts with the same slug as an existing post.
 * @since 2.11.0 Added transient and $use_transient param.
 *
 * @param bool $use_transient Whether to use cache for fetching.
 *
 * @return array
 */
function mai_get_template_part_objects( $use_transient = true ) {
	static $template_parts = null;

	if ( ! is_null( $template_parts ) ) {
		return $template_parts;
	}

	$slugs = array_keys( mai_get_config( 'template-parts' ) );
	$posts = [];

	if ( ! empty( $slugs ) ) {
		if ( ! ( $use_transient && $parts = mai_cache( 'template-parts' )->get( 'objects' ) ) ) {

			$parts = [];
			$query = new WP_Query(
				[
					'post_type'              => 'mai_template_part',
					'post_status'            => 'any',
					'post_name__in'          => $slugs,
					'posts_per_page'         => 500,
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
					'suppress_filters'       => false, // https://github.com/10up/Engineering-Best-Practices/issues/116
				]
			);

			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) : $query->the_post();
					$post                      = get_post( get_the_ID() );
					$parts[ $post->post_name ] = $post;
				endwhile;
			}

			wp_reset_postdata();

			// Cache for 1 hour.
			mai_cache( 'template-parts' )->set( 'objects', $parts, HOUR_IN_SECONDS );
		}

		$posts = $parts;
	}

	$template_parts = $posts;
	$template_parts = apply_filters( 'mai_template_part_objects', $template_parts );

	return $template_parts;
}

/**
 * Checks whether the template part exists and has content.
 *
 * @since 2.0.1
 * @since 2.25.5 Check condition.
 *
 * @param string $slug Template part slug.
 *
 * @return bool
 */
function mai_has_template_part( $slug ) {
	if ( ! mai_template_part_exists( $slug ) ) {
		return false;
	}

	if ( ! mai_get_template_part( $slug ) ) {
		return false;
	}

	$parts = mai_get_config( 'template-parts' );

	if ( isset( $parts[ $slug ]['condition'] ) && is_callable( $parts[ $slug ]['condition'] ) && ! $parts[ $slug ]['condition']() ) {
		return false;
	}

	return true;
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
	if ( ! mai_has_template_part( $slug ) ) {
		return;
	}

	do_action( "mai_before_{$slug}_template_part" );
	echo $before;
	do_action( "mai_before_{$slug}_template_part_content" );
	echo mai_get_processed_content( mai_get_template_part( $slug ) );
	do_action( "mai_after_{$slug}_template_part_content" );
	echo $after;
	do_action( "mai_after_{$slug}_template_part" );
}

/**
 * Creates default content areas from config.
 * Skips existing content areas.
 *
 * @access private
 *
 * @since 2.6.0
 *
 * @return array
 */
function mai_create_template_parts() {
	$created = [];

	foreach ( mai_get_config( 'template-parts' ) as $slug => $template_part ) {
		if ( mai_template_part_exists( $slug ) ) {
			continue;
		}

		$post_id = wp_insert_post(
			[
				'post_type'    => 'mai_template_part',
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_title'   => mai_convert_case( $slug, 'title' ),
				'post_content' => mai_isset( $template_part, 'default', '' ),
				'menu_order'   => mai_isset( $template_part, 'menu_order', 0 ),
			]
		);

		if ( is_wp_error( $post_id ) ) {
			continue;
		}

		$created[ $post_id ] = $slug;
	}

	return $created;
}

/**
 * Imports content areas from the demo.
 * Skips existing content areas.
 *
 * @access private
 *
 * @since 2.6.0
 *
 * @param false|string $force If 'always', forces the import by trashing an existing template part.
 *                            If 'empty', forces the import by trashing an existing template that has no content.
 *
 * @return string
 */
function mai_import_template_parts( $force = false ) {
	$create   = [];
	$imported = [];
	$config   = mai_get_config( 'template-parts' );

	foreach ( $config as $slug => $template_part ) {
		$result = mai_import_template_part( $slug, $force );

		if ( $result['id'] ) {
			$imported[ $result['id']] = $slug;
		}
	}

	return $imported;
}

/**
 * Imports a template part from the demo.
 *
 * @access private
 *
 * @since 2.6.0
 *
 * @param string       $slug  The template part slug to import.
 * @param false|string $force If 'always', forces the import by trashing an existing template part.
 *                            If 'empty', forces the import by trashing an existing template that has no content.
 *
 * @return array
 */
function mai_import_template_part( $slug, $force = false ) {
	$template_parts = mai_get_template_parts_from_demo();

	if ( ! ( $template_parts && isset( $template_parts[ $slug ] ) && $template_parts[ $slug ] ) ) {
		return [
			'id'      => false,
			'message' => __( 'Sorry, no template part available for this theme.', 'mai-engine' ),
		];
	}

	if ( mai_template_part_exists( $slug ) ) {

		if ( $force ) {
			$id = mai_get_template_part_id( $slug );

			if ( $id ) {
				switch ( $force ) {
					case 'always':
						wp_trash_post( $id );
					break;
					case 'empty':
						if ( mai_has_template_part( $slug ) ) {
							return [
								'id'      => false,
								'message' => __( 'Sorry, this template part is already in use.', 'mai-engine' ),
							];
						}
						wp_trash_post( $id );
					break;
				}
			}

		} else {

			return [
				'id'      => false,
				'message' => sprintf( '%s "%s" %s', __( 'Sorry, ', 'mai-engine' ), mai_convert_case( $slug, 'title' ), __( 'template part already exists', 'mai-engine' ) ),
			];
		}
	}

	$config  = mai_get_config( 'template-parts' );
	$post_id = wp_insert_post(
		[
			'post_type'    => 'mai_template_part',
			'post_name'    => $slug,
			'post_status'  => 'publish',
			'post_title'   => mai_convert_case( $slug, 'title' ),
			'post_content' => mai_localize_blocks( $template_parts[ $slug ] ),
			'menu_order'   => isset( $config[ $slug ]['menu_order'] ) ? $config[ $slug ]['menu_order'] : 0,
		]
	);

	if ( is_wp_error( $post_id ) ) {
		return [
			'id'      => false,
			'message' => $post_id->get_error_message(),
		];
	}

	return [
		'id'      => $post_id,
		'message' => '',
	];
}

/**
 * Imports images locally to the site.
 *
 * @access private
 *
 * @since 2.6.0
 *
 * @param string $content The existing content.
 *
 * @return string
 */
function mai_localize_blocks( $content ) {
	$blocks = parse_blocks( $content );

	if ( ! $blocks ) {
		return $content;
	}

	$replace_urls = [];
	$replace_ids  = [];

	foreach ( $blocks as $index => $block ) {
		$blocks[ $index ] = mai_localize_block( $block, $replace_urls, $replace_ids );
	}

	if ( $replace_urls ) {
		$blocks = serialize_blocks( $blocks );

		foreach ( $replace_urls as $old => $new ) {
			$blocks = str_replace( $old, $new, $blocks );
		}

		// One pass for every image ID class, so a class rewritten to a new ID that is also another image's old ID is not rewritten again.
		$blocks = preg_replace_callback(
			'/\bwp-image-(\d+)\b/',
			fn( $m ) => isset( $replace_ids[ $m[1] ] ) ? 'wp-image-' . $replace_ids[ $m[1] ] : $m[0],
			$blocks
		);

		return $blocks;
	}

	return $content;
}

/**
 * Localizes the images of one block and its inner blocks.
 *
 * Inner blocks are visited first, so a block that is left alone still has its children localized.
 * URL and ID replacements are collected for the caller to apply once to the serialized content.
 *
 * @access private
 *
 * @since 2.42.0
 *
 * @param array $block        The parsed block.
 * @param array $replace_urls Old image URLs mapped to new ones, added to by reference.
 * @param array $replace_ids  Old attachment IDs mapped to new ones, added to by reference.
 *
 * @return array The block.
 */
function mai_localize_block( array $block, array &$replace_urls, array &$replace_ids ): array {
	foreach ( $block['innerBlocks'] as $index => $inner_block ) {
		$block['innerBlocks'][ $index ] = mai_localize_block( $inner_block, $replace_urls, $replace_ids );
	}

	if ( 'core/cover' === $block['blockName'] ) {
		$image_id  = $block['attrs']['id'] ?? 0;
		$image_url = $block['attrs']['url'] ?? '';

		// Skip if already a local image.
		if ( mai_has_string( home_url(), $image_url ) ) {
			return $block;
		}

		if ( $image_id && $image_url ) {
			$path_parts = pathinfo( $image_url );
			$new_id     = mai_get_local_image_id( (int) $image_id, $image_url, $path_parts['filename'], $path_parts['filename'], $replace_ids );

			if ( $new_id ) {
				$new_url = wp_get_attachment_url( $new_id );

				$block['attrs']['id']  = $new_id;
				$block['attrs']['url'] = $new_url;

				$replace_urls[ $image_url ] = $new_url;
				$replace_ids[ $image_id ]   = $new_id;
			}

			$block = filter_block_kses( $block, 'post' );
		}

	} elseif ( 'core/image' === $block['blockName'] ) {
		$image_id   = $block['attrs']['id'] ?? 0;
		$image_size = $block['attrs']['sizeSlug'] ?? '';
		$image_url  = false;

		if ( $image_id ) {
			$tags = new WP_HTML_Tag_Processor( $block['innerHTML'] );

			if ( $tags->next_tag( 'img' ) ) {
				$src       = $tags->get_attribute( 'src' );
				$image_url = is_string( $src ) ? $src : false;
			}

			if ( $image_url ) {
				// Skip if already a local image.
				if ( mai_has_string( home_url(), $image_url ) ) {
					return $block;
				}

				$path_parts = pathinfo( $image_url );
				$filename   = $path_parts['filename'];
				$filename   = preg_replace( '/(\d+)x(\d+)$/', '', $filename ); // Filename without sizes.
				$new_id     = mai_get_local_image_id( (int) $image_id, $image_url, $filename, $path_parts['filename'], $replace_ids );
				$new_img    = $new_id ? wp_get_attachment_image_src( $new_id, $image_size ? $image_size : 'full' ) : false;

				// A match that isn't an image has no src, so leave the block pointing at the demo image.
				if ( $new_img ) {
					$block['attrs']['id'] = $new_id;

					$replace_urls[ $image_url ] = $new_img[0];
					$replace_ids[ $image_id ]   = $new_id;
				}

				$block = filter_block_kses( $block, 'post' );
			}
		}

	} elseif ( 'core/media-text' === $block['blockName'] ) {
		$media_id   = $block['attrs']['mediaId'] ?? 0;
		$media_type = $block['attrs']['mediaType'] ?? '';
		$image_url  = false;

		if ( 'image' !== $media_type ) {
			return $block;
		}

		if ( $media_id ) {
			$tags = new WP_HTML_Tag_Processor( $block['innerHTML'] );

			if ( $tags->next_tag( 'img' ) ) {
				$src       = $tags->get_attribute( 'src' );
				$image_url = is_string( $src ) ? $src : false;
			}

			if ( $image_url ) {
				// Skip if already a local image.
				if ( mai_has_string( home_url(), $image_url ) ) {
					return $block;
				}

				$path_parts = pathinfo( $image_url );
				$filename   = $path_parts['filename'];
				$filename   = preg_replace( '/(\d+)x(\d+)$/', '', $filename ); // Filename without sizes.
				$new_id     = mai_get_local_image_id( (int) $media_id, $image_url, $filename, $path_parts['filename'], $replace_ids );
				$new_img    = $new_id ? wp_get_attachment_image_src( $new_id, 'large' ) : false;

				// The new src reaches the output through $replace_urls, since serialize_blocks() reads innerContent, not innerHTML.
				if ( $new_img ) {
					$block['attrs']['mediaId'] = $new_id;

					$replace_urls[ $image_url ] = $new_img[0];
					$replace_ids[ $media_id ]   = $new_id;
				}

				$block = filter_block_kses( $block, 'post' );
			}
		}
	}

	return $block;
}

/**
 * Gets the local attachment ID for a demo image.
 *
 * Reuses the ID already found for the same demo ID in this run, so a repeated demo image is one attachment and
 * never downloaded twice. Otherwise tries the upload path, then the filename slug, then downloads the image.
 *
 * @access private
 *
 * @since 2.42.0
 *
 * @param int    $demo_id     The demo attachment ID.
 * @param string $image_url   The demo image URL.
 * @param string $slug        The filename to match an existing attachment slug, without sizes.
 * @param string $download    The filename to give a downloaded image.
 * @param array  $replace_ids Old attachment IDs already mapped to new ones in this run.
 *
 * @return int The attachment ID, or 0 if there is none.
 */
function mai_get_local_image_id( int $demo_id, string $image_url, string $slug, string $download, array $replace_ids ): int {
	$new_id = (int) ( $replace_ids[ $demo_id ] ?? 0 );
	$new_id = $new_id ? $new_id : mai_get_attachment_id_from_demo_url( $image_url );
	$new_id = $new_id ? $new_id : (int) mai_get_existing_attachment_from_filename( $slug );
	$new_id = $new_id ? $new_id : (int) mai_get_new_image_from_url( $image_url, $download );

	return $new_id;
}

/**
 * Gets existing attachment from attachment filename.
 *
 * @access private
 *
 * @since 2.6.0
 *
 * @param string $filename The image filename/slug.
 *
 * @return int|false
 */
function mai_get_existing_attachment_from_filename( $filename ) {
	$existing = get_posts(
		[
			'post_type'              => 'attachment',
			'post_status'            => 'any',
			'name'                   => $filename,
			'posts_per_page'         => 1,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'suppress_filters'       => false,
		]
	);

	if ( $existing ) {
		return $existing[0]->ID;
	}

	return false;
}

/**
 * Gets the local attachment ID for a demo image URL, matched by its upload file path.
 *
 * Demo images share the same relative upload path as the local copies, so the path after
 * `/uploads/` is looked up in `_wp_attached_file`. A leading multisite `sites/<id>/` is dropped.
 * Sized URLs fall back to the original file, then to its `-scaled` version.
 *
 * @access private
 *
 * @since 2.42.0
 *
 * @param string $url The demo image URL.
 *
 * @return int The attachment ID, or 0 if none matches.
 */
function mai_get_attachment_id_from_demo_url( string $url ): int {
	$position = strrpos( $url, '/uploads/' );

	if ( false === $position ) {
		return 0;
	}

	$path = substr( $url, $position + strlen( '/uploads/' ) );
	$path = (string) preg_replace( '~^sites/\d+/~', '', $path );

	if ( '' === $path ) {
		return 0;
	}

	$sized      = (string) preg_replace( '~-\d+x\d+(\.[^./]+)?$~', '$1', $path );
	$candidates = [ $path, $sized ];

	// A big original is stored as `-scaled`, and its sized copies are named from that, so the suffix is missing from the size-stripped path.
	$candidates[] = (string) preg_replace( '~(\.[^./]+)?$~', '-scaled$1', $sized, 1 );
	$base_url     = trailingslashit( wp_get_upload_dir()['baseurl'] );

	foreach ( array_unique( $candidates ) as $candidate ) {
		$id = attachment_url_to_postid( $base_url . $candidate );

		if ( $id ) {
			return $id;
		}
	}

	return 0;
}

/**
 * Imports an image from an external URL.
 *
 * @access private
 *
 * @since 2.6.0
 *
 * @param string $image_url The image url.
 * @param string $filename  The image filename/slug.
 *
 * @return int|false
 */
function mai_get_new_image_from_url( $image_url, $filename = '' ) {
	if ( ! function_exists( 'media_sideload_image' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/media.php' );
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
	}

	// Create attachment.
	$new_id = media_sideload_image( $image_url, 0, $filename, 'id' );

	if ( $new_id && ! is_wp_error( $new_id ) ) {
		return $new_id;
	}

	return false;
}

add_action( 'after_switch_theme', 'mai_flush_demo_template_parts' );
/**
 * Flush the demo template-parts cache on theme switch.
 *
 * Demos are theme-dependent (registered via the mai_setup_wizard_demos filter),
 * so switching themes should refresh the cached demo content.
 *
 * @since 2.40.0
 *
 * @return void
 */
function mai_flush_demo_template_parts() {
	mai_cache( 'demo' )->flush();
}

/**
 * Gets content areas content from the demo.
 * Caches the result via mai_cache.
 *
 * Requires Mai Demo Exporter plugin to be active on the demo site.
 *
 * @since 2.6.0
 *
 * @return array
 */
function mai_get_template_parts_from_demo() {
	static $template_parts = null;

	if ( ! is_null( $template_parts ) ) {
		return $template_parts;
	}

	if ( false === ( $template_parts = mai_cache( 'demo' )->get( 'objects' ) ) ) {
		$template_parts = [];
		$config         = mai_get_config( 'template-parts' );
		$demos          = apply_filters( 'mai_setup_wizard_demos', [] );

		if ( $demos ) {
			$demo = array_shift( $demos );

			if ( $demo && $demo['preview'] && $demo['preview'] ) {
				$url     = sprintf( '%s/wp-json/wp/v2/mai-template-parts', untrailingslashit( $demo['preview'] ) );
				$request = wp_remote_get( $url );

				if ( ! is_wp_error( $request ) ) {
					$body = wp_remote_retrieve_body( $request );
					$data = json_decode( $body );

					if ( $data && is_array( $data ) ) {
						$parts = [];

						/**
						 * Can't use `wp_list_pluck()` because the key/property may not exist
						 * since changing the rest route in TDB
						 *
						 * @link https://core.trac.wordpress.org/ticket/34172
						 */
						foreach ( $data as $item ) {
							if ( ! ( is_object( $item ) && property_exists( $item, 'content_raw' ) && property_exists( $item, 'slug' ) ) ) {
								continue;
							}

							$parts[ $item->slug ] = $item->content_raw;
						}

						if ( $parts ) {
							foreach ( $config as $slug => $args ) {
								if ( ! isset( $parts[ $slug ] ) ) {
									continue;
								}

								$template_parts[ $slug ] = $parts[ $slug ];
							}
						}
					}
				}
			}
		}

		mai_cache( 'demo' )->set( 'objects', $template_parts, HOUR_IN_SECONDS );
	}

	$template_parts = (array) $template_parts;

	return $template_parts;
}
