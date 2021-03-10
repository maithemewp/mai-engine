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
 * @since 2.4.0 Changed can_export to true.
 * @since TBD Changed post type name to avoid conflict with WP 5.7.
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

	$wp_admin_bar->add_node(
		[
			'id'     => 'mai-template-parts',
			'parent' => 'site-name',
			'title'  => __( 'Template Parts', 'mai-engine' ),
			'href'   => admin_url( 'edit.php?post_type=mai_template_part' ),
			'meta'   => [
				'title' => __( 'Edit Template Parts', 'mai-engine' ),
			],
		]
	);

	$wp_admin_bar->add_node(
		[
			'id'     => 'mai-reusable-blocks',
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
		foreach ( $objects as $post ) {
			$posts[ $post->post_status ][ $post->post_name ] = $post->post_content ?: '';
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
	$template_parts = [];
	$ids            = [];
	$objects        = mai_get_template_part_objects();

	if ( $objects ) {
		foreach ( $objects as $post ) {
			$ids[ $post->post_name ] = $post->ID;
		}
	}

	$template_parts = $ids;

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
 * Returns an array of template parts.
 * Slugs must exist in the config.
 *
 * @since 2.6.0
 * @since 2.10.0 Changes `posts_per_page` value from `count( $slugs )` to 500
 *              since WP seems to allow draft posts with the same slug as an existing post.
 * @since TBD Added transient and $use_transient param.
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
		$transient = 'mai_template_parts';

		if ( ! ( $use_transient && $parts = get_transient( $transient ) ) ) {

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
					global $post;
					$parts[] = $post;
				endwhile;
			}

			wp_reset_postdata();

			// Set transient, and expire after 1 hour.
			set_transient( $transient, $parts, 1 * HOUR_IN_SECONDS );
		}

		$posts = $parts;
	}

	$template_parts = $posts;

	return $template_parts;
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
		echo mai_get_processed_content( $template_part );
		echo $after;
	}
}

/**
 * Creates default template parts from config.
 * Skips existing template parts.
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
 * Imports template parts from the demo.
 * Skips existing template parts.
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

	foreach ( $blocks as $index => $block ) {
		if ( 'core/cover' === $block['blockName'] ) {
			$image_id  = $block['attrs']['id'];
			$image_url = $block['attrs']['url'];

			// Skip if already a local image.
			if ( mai_has_string( home_url(), $image_url	) ) {
				continue;
			}

			if ( $image_id && $image_url ) {
				$path_parts = pathinfo( $image_url );
				$existing   = mai_get_existing_attachment_from_filename( $path_parts['filename'] );

				if ( $existing ) {
					$new_id  = $existing;
					$new_url = wp_get_attachment_url( $existing );

					$blocks[ $index ]['attrs']['id']  = $new_id;
					$blocks[ $index ]['attrs']['url'] = $new_url;

					$replace_urls[ $image_url ] = $new_url;

				} else {

					$new_id  = mai_get_new_image_from_url( $image_url, $path_parts['filename'] );

					if ( $new_id  ) {
						$new_url = wp_get_attachment_url( $new_id );

						$blocks[ $index ]['attrs']['id']  = $new_id;
						$blocks[ $index ]['attrs']['url'] = $new_url;

						$replace_urls[ $image_url ] = $new_url;
					}
				}

				$blocks[ $index ] = filter_block_kses( $blocks[ $index ], 'post' );
			}

		} elseif ( 'core/image' === $block['blockName'] ) {
			$image_id   = $block['attrs']['id'];
			$image_size = $block['attrs']['sizeSlug'];
			$image_url  = false;

			if ( $image_id ) {
				$dom = mai_get_dom_document( $blocks[ $index ]['innerHTML'] );
				$img = $dom->getElementsByTagName( 'img' )->item(0);

				if ( $img ) {
					$image_url = $img->getAttribute( 'src' );
				}

				if ( $image_url ) {
					// Skip if already a local image.
					if ( mai_has_string( home_url(), $image_url	) ) {
						continue;
					}

					$path_parts = pathinfo( $image_url );
					$filename   = $path_parts['filename'];
					$filename   = preg_replace( '/(\d+)x(\d+)$/', '', $filename ); // Filename without sizes.
					$existing   = mai_get_existing_attachment_from_filename( $filename );

					if ( $existing ) {
						$new_id  = $existing;
						$new_img = wp_get_attachment_image_src( $existing, $image_size ? $image_size : 'full' );
						$new_url = $new_img[0];

						$blocks[ $index ]['attrs']['id']  = $new_id;

						$replace_urls[ $image_url ] = $new_url;

					} else {

						$new_id  = mai_get_new_image_from_url( $image_url, $path_parts['filename'] );

						if ( $new_id  ) {
							$new_img = wp_get_attachment_image_src( $new_id, $image_size ? $image_size : 'full' );
							$new_url = $new_img[0];

							$blocks[ $index ]['attrs']['id']  = $new_id;

							$replace_urls[ $image_url ] = $new_url;
						}
					}

					$blocks[ $index ] = filter_block_kses( $blocks[ $index ], 'post' );
				}
			}

		} elseif ( 'core/media-text' === $block['blockName'] ) {
			$media_id   = $block['attrs']['mediaId'];
			$media_type = $block['attrs']['mediaType'];

			if ( 'image' !== $media_type ) {
				continue;
			}

			if ( $image_id ) {
				$dom = mai_get_dom_document( $blocks[ $index ]['innerHTML'] );
				$img = $dom->getElementsByTagName( 'img' )->item(0);

				if ( $img ) {
					$image_url = $img->getAttribute( 'src' );
				}

				if ( $image_url ) {
					// Skip if already a local image.
					if ( mai_has_string( home_url(), $image_url	) ) {
						continue;
					}

					$path_parts = pathinfo( $image_url );
					$filename   = $path_parts['filename'];
					$filename   = preg_replace( '/(\d+)x(\d+)$/', '', $filename ); // Filename without sizes.
					$existing   = mai_get_existing_attachment_from_filename( $filename );

					if ( $existing ) {
						$new_id  = $existing;
						$new_img = wp_get_attachment_image_src( $existing, 'large' );
						$new_url = $new_img[0];

						$blocks[ $index ]['attrs']['id']  = $new_id;

						$img->setAttribute( 'src', $new_url );

						$replace_urls[ $image_url ] = $new_url;

					} else {

						$new_id  = mai_get_new_image_from_url( $image_url, $path_parts['filename'] );

						if ( $new_id  ) {
							$new_img = wp_get_attachment_image_src( $existing, 'large' );
							$new_url = $new_img[0];

							$blocks[ $index ]['attrs']['id']  = $new_id;

							$img->setAttribute( 'src', $new_url );

							$replace_urls[ $image_url ] = $new_url;
						}
					}

					$blocks[ $index ]['innerHTML'] = $dom->saveHTML();

					$blocks[ $index ] = filter_block_kses( $blocks[ $index ], 'post' );
				}
			}
		}
	}

	if ( $replace_urls ) {
		$blocks = serialize_blocks( $blocks );

		foreach ( $replace_urls as $old => $new ) {
			$blocks = str_replace( $old, $new, $blocks );
		}

		return $blocks;
	}

	return $content;
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

/**
 * Gets template parts content from the demo.
 * Caches via a transient.
 *
 * Requires Mai Demo Exporter plugin to be active on the demo site.
 *
 * @since 2.6.0
 *
 * @return array
 */
function mai_get_template_parts_from_demo() {
	if ( false === ( $template_parts = get_transient( 'mai_demo_template_parts' ) ) ) {
		$template_parts = [];
		$config         = mai_get_config( 'template-parts' );
		$demos          = apply_filters( 'mai_setup_wizard_demos', [] );

		if ( $demos ) {
			$demo = array_shift( $demos );

			if ( $demo && $demo['preview'] && $demo['preview'] ) {
				$url     = sprintf( '%s/wp-json/wp/v2/template-parts', untrailingslashit( $demo['preview'] ) );
				$request = wp_remote_get( $url );

				if ( ! is_wp_error( $request ) ) {
					$body  = wp_remote_retrieve_body( $request );
					$data  = json_decode( $body );

					if ( $data ) {
						$parts = wp_list_pluck( $data, 'content_raw', 'slug' );

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

		set_transient( 'mai_demo_template_parts', $template_parts, HOUR_IN_SECONDS );
	}

	return $template_parts;
}
