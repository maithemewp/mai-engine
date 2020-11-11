<?php

add_action( 'current_screen', 'mai_maybe_create_template_parts' );
/**
 * Creates default template parts if they don't exist.
 * Only runs on main template part admin list.
 *
 * @since 2.0.0
 * @since 2.4.0 Removed unnecesarry wp_doing_ajax() call since solving https://github.com/maithemewp/mai-engine/issues/251.
 * @since 2.6.0 Imports template parts from the demo via REST API when action link is clicked.
 *
 * @param WP_Screen $current_screen Current WP_Screen object.
 *
 * @return void
 */
function mai_maybe_create_template_parts( $current_screen ) {
	if ( ( 'wp_template_part' !== $current_screen->post_type ) && ( 'edit-wp_template_part' !== $current_screen->id ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	$is_import = false;
	$redirect  = admin_url( 'edit.php?post_type=wp_template_part' );
	$message   = '';

	if ( isset( $_REQUEST['mai_import_nonce'] )
		&& wp_verify_nonce( filter_var( $_REQUEST['mai_import_nonce'], FILTER_SANITIZE_STRING ), 'mai_import_nonce' )
		&& ( 'mai_import' === filter_input( INPUT_GET, 'mai_action', FILTER_SANITIZE_STRING ) )
		&& ( 'wp_template_part' === filter_input( INPUT_GET, 'post_type', FILTER_SANITIZE_STRING ) )
		&& ( $slug = filter_input( INPUT_GET, 'mai_slug', FILTER_SANITIZE_STRING ) )
		&& $slug ) {

		$is_import = true;
	}

	if ( $is_import ) {
		$result = mai_import_template_part( $slug, true );

		if ( ! $result['id'] ) {
			if ( $result['message'] ) {
				$redirect = add_query_arg( 'mai_type', 'error', $redirect );
				$message  = $result['message'];
			}
		} else {
			$message = sprintf( '"%s" %s', mai_convert_case( $slug, 'title' ), __( 'template part imported successfully!', 'mai-engine' ) );
		}

	} else {

		$template_parts = mai_create_template_parts();

		if ( ! $template_parts ) {
			return;
		}

		$count = count( $template_parts );

		if ( 1 === $count ) {
			$message = sprintf( '%s %s', $count, __( 'default template part automatically created.', 'mai-engine' ) );
		} else {
			$message = sprintf( '%s %s', $count, __( 'default template parts automatically created.', 'mai-engine' ) );
		}
	}

	$redirect = add_query_arg( 'mai_notice', urlencode( esc_html( $message ) ), $redirect );

	wp_safe_redirect( $redirect );
	exit;
}

add_filter( 'post_row_actions', 'mai_template_parts_import_row_action', 10, 2 );
/**
 * Adds row action to import a template part from the demo if it exists.
 *
 * @since 2.6.0
 *
 * @param array   $actions The existing options.
 * @param WP_Post $post    The current post.
 *
 * @return array
 */
function mai_template_parts_import_row_action( $actions, $post ) {
	if ( 'wp_template_part' !== $post->post_type ) {
		return $actions;
	}

	if ( ! current_user_can( 'delete_post', $post->ID ) ) {
		return;
	}

	$template_parts = mai_get_template_parts_from_demo();

	if ( ! $template_parts ) {
		return $actions;
	}

	if ( ! ( isset( $template_parts[ $post->post_name ] ) && $template_parts[ $post->post_name ] ) ) {
		return $actions;
	}

	static $script = false;

	$admin_url  = admin_url( 'edit.php?post_type=wp_template_part' );
	$import_url = add_query_arg(
		[
			'mai_action' => 'mai_import',
			'mai_slug'   => $post->post_name,
		],
		$admin_url,
	);

	$html = sprintf( '<a href="%s" onclick="return maiImportConfirmation()">%s</a>',
		wp_nonce_url( $import_url, 'mai_import_nonce', 'mai_import_nonce' ),
		__( 'Import From Demo', 'mai-engine' )
	);

	if ( ! $script ) {
		$notice = __( 'Warning! Importing will move the existing template part to the trash.', 'mai-engine' );
		$script = '<script type="text/javascript">
			function maiImportConfirmation() {
				if ( ! window.confirm( "' . esc_html( $notice ) . '" ) ) {
					return false;
				}
			}
		</script>';
		$html .= $script;
	}

	$trash = '';
	if ( isset( $actions['trash'] ) ) {
		$trash = $actions['trash'];
		unset( $actions['trash'] );
	}

	$actions['mai_import'] = $html;

	if ( $trash ) {
		$actions['trash'] = $trash;
	}

	return $actions;
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
 * @return array
 */
function mai_template_part_post_state( $states, $post ) {
	if ( 'wp_template_part' !== $post->post_type ) {
		return $states;
	}

	$template_parts = mai_get_config( 'template-parts' );

	foreach ( $template_parts as $slug => $template_part ) {
		if ( $slug === $post->post_name && 'publish' === $post->post_status && $post->post_content ) {
			$states[] = __( 'Active', 'mai-engine' );
		}
	}

	return $states;
}

add_filter( 'manage_wp_template_part_posts_columns', 'mai_template_part_add_slug_column' );
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
function mai_template_part_add_slug_column( $column_array ) {
	$new_column = [
		'slug' => __( 'Slug', 'mai-engine' ),
	];

	$offset = count( $column_array ) > 1 ? count( $column_array ) - 1 : count( $column_array );

	return array_slice( $column_array, 0, $offset, true ) + $new_column + array_slice( $column_array, $offset, null, true );
}

add_action( 'manage_posts_custom_column', 'mai_template_part_add_slug', 10, 2 );
/**
 * Populate template part slug column with actual slug.
 *
 * @since 2.0.0
 *
 * @param string $column_name The name of the column to display.
 * @param int    $post_id     The current post ID.
 *
 * @return void
 */
function mai_template_part_add_slug( $column_name, $post_id ) {
	if ( 'slug' === $column_name ) {
		echo get_post_field( 'post_name', $post_id );
	}
}

add_action( 'pre_get_posts', 'mai_template_parts_order' );
/**
 * Reorder template part admin list.
 *
 * @since 2.0.0
 *
 * @param WP_Query $query Current WordPress query object.
 *
 * @return void
 */
function mai_template_parts_order( $query ) {
	if ( ! is_admin() ) {
		return;
	}

	if ( ! $query->is_main_query() ) {
		return;
	}

	$screen = get_current_screen();

	if ( ! $screen || ( 'edit-wp_template_part' !== $screen->id ) ) {
		return;
	}

	$query->set( 'orderby', 'menu_order' );
	$query->set( 'order', 'ASC' );
}

add_action( 'current_screen', 'mai_widgets_template_parts_admin_notice' );
/**
 * Adds admin notice for template parts to widgets screen.
 *
 * @since 2.6.0
 *
 * @return void
 */
function mai_widgets_template_parts_admin_notice( $screen ) {
	if ( 'widgets' !== $screen->id ) {
		return;
	}

	add_action( 'admin_notices', function() {
		printf(
			'<div class="notice notice-warning is-dismissible"><p>%s <a href="%s">%s</a>.</p></div>',
			__( 'Mai Theme uses "Template Parts" (block-based widget areas).', 'mai-engine' ),
			admin_url( 'edit.php?post_type=wp_template_part' ),
			__( 'Edit template parts now', 'mai-engine' ),
		);
	});
}

add_action( 'load-edit.php', 'mai_template_parts_admin_notice' );
/**
 * Adds admin notice to template parts.
 *
 * @since 2.6.0
 *
 * @return void
 */
function mai_template_parts_admin_notice() {
	$screen = get_current_screen();

	if ( 'wp_template_part' !== $screen->post_type ) {
		return;
	}

	add_action( 'admin_notices', function() {
		printf(
			'<div class="notice notice-success is-dismissible"><p>%s <a target="_blank" href="https://docs.bizbudding.com/docs/template-parts/">%s</a>.</p></div>',
			__( 'View documentation for', 'mai-engine' ),
			__( 'Template Parts', 'mai-engine' ),
		);
	});
}

add_action( 'after_switch_theme', 'mai_default_theme_template_parts' );
/**
 * Sets demo template parts on theme switch.
 *
 * @since 2.6.0
 *
 * @return void
 */
function mai_default_theme_template_parts() {

	delete_transient( 'mai_demo_template_parts' );

	$notices = [];

	// Import existing template parts. Skips existing template parts with content.
	$template_parts = mai_import_template_parts( 'empty' );

	if ( $template_parts ) {
		$count = count( $template_parts );

		if ( 1 === $count ) {
			$notices[] = printf( '%s %s', $count, __( 'default template part imported successfully.', 'mai-engine' ) );
		} else {
			$notices[] = printf( '%s %s', $count, __( 'default template parts imported successfully.', 'mai-engine' ) );
		}

	}

	// Create default template parts.
	$template_parts = mai_create_template_parts();

	if ( $template_parts ) {
		$count = count( $template_parts );

		if ( 1 === $count ) {
			$notices[] = printf( '%s %s', $count, __( 'default template part automatically created.', 'mai-engine' ) );
		} else {
			$notices[] = printf( '%s %s', $count, __( 'default template parts automatically created.', 'mai-engine' ) );
		}
	}

	if ( $notices ) {
		// Adds admin notice(s). May not display if redirected to setup wizard.
		add_action( 'admin_notices', function() use ( $notices ) {
			foreach ( $notices as $notice ) {
				printf( '<div class="notice notice-success">%s</div>', esc_html( $notice ) );
			}
		});
	}
}
