<?php

add_action( 'admin_post_mai_import_template_part_action', 'mai_import_template_part_action' );
/**
 * Listener for importing a specific template part.
 *
 * @since 2.10.0
 *
 * @return void
 */
function mai_import_template_part_action() {
	$referrer = check_admin_referer( 'mai_import_template_part_action', 'mai_import_template_part_nonce' );
	$nonce    = filter_input( INPUT_GET, 'mai_import_template_part_nonce', FILTER_SANITIZE_STRING );
	$action   = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_STRING );
	$slug     = filter_input( INPUT_GET, 'slug', FILTER_SANITIZE_STRING );

	if ( current_user_can( 'edit_theme_options' ) && $referrer && $nonce && $action && $slug && wp_verify_nonce( $nonce, $action ) ) {

		$redirect = admin_url( 'edit.php?post_type=mai_template_part' );
		$result   = mai_import_template_part( $slug, true );

		if ( ! $result['id'] ) {
			if ( $result['message'] ) {
				$redirect = add_query_arg( 'mai_type', 'error', $redirect );
				$message  = $result['message'];
			}
		} else {
			$message = sprintf( '"%s" %s', mai_convert_case( $slug, 'title' ), __( 'template part imported successfully!', 'mai-engine' ) );
		}

		if ( $message ) {
			$redirect = add_query_arg( 'mai_notice', urlencode( esc_html( $message ) ), $redirect );
		}

		wp_safe_redirect( $redirect );
		exit;

	} else {
		wp_die(
			__( 'Template Parts failed to generate.', 'mai-engine' ),
			__( 'Error', 'mai-engine' ), array(
				'link_url'  => admin_url( 'edit.php?post_type=mai_template_part' ),
				'link_text' => __( 'Go back.', 'mai-engine' ),
		) );
	}
}

add_action( 'admin_post_mai_generate_template_parts_action', 'mai_generate_template_parts_action' );
/**
 * Listener for generating default template parts.
 *
 * @since 2.10.0
 *
 * @return void
 */
function mai_generate_template_parts_action() {
	$referrer = check_admin_referer( 'mai_generate_template_parts_action', 'mai_generate_template_parts_nonce' );
	$nonce    = filter_input( INPUT_GET, 'mai_generate_template_parts_nonce', FILTER_SANITIZE_STRING );
	$action   = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_STRING );

	if ( current_user_can( 'edit_theme_options' ) && $referrer && $nonce && $action && wp_verify_nonce( $nonce, $action ) ) {
		$redirect       = admin_url( 'edit.php?post_type=mai_template_part' );
		$template_parts = mai_create_template_parts();
		$count          = count( $template_parts );

		switch ( $count ) {
			case 0:
				$message = __( 'Sorry, no template parts are available.', 'mai-engine' );
			break;
			case 1:
				$message = sprintf( '%s %s', $count, __( 'default template parts successfully created.', 'mai-engine' ) );
			break;
			default:
				$message = sprintf( '%s %s', $count, __( 'default template parts successfully created.', 'mai-engine' ) );
		}


		if ( $message ) {
			$redirect = add_query_arg( 'mai_notice', urlencode( esc_html( $message ) ), $redirect );
		}

		wp_safe_redirect( $redirect );
		exit;

	} else {
		wp_die(
			__( 'Template Parts failed to generate.', 'mai-engine' ),
			__( 'Error', 'mai-engine' ), array(
				'link_url'  => admin_url( 'edit.php?post_type=mai_template_part' ),
				'link_text' => __( 'Go back.', 'mai-engine' ),
		) );
	}
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

	if ( 'mai_template_part' !== $screen->post_type ) {
		return;
	}

	add_action( 'admin_notices', function() {
		printf(
			'<div class="notice notice-success is-dismissible"><p>%s <a target="_blank" href="https://docs.bizbudding.com/docs/template-parts/">%s</a>.</p></div>',
			__( 'View documentation for', 'mai-engine' ),
			__( 'Template Parts', 'mai-engine' )
		);
	});

	$config = mai_get_config( 'template-parts' );

	if ( ! $config ) {
		return;
	}

	$slugs     = array_keys( $config );
	$count     = count( $slugs );
	$existing  = mai_get_template_part_objects( false );
	$existing  = wp_list_pluck( $existing, 'post_name' );
	$intersect = count( array_intersect( $slugs, $existing ) );

	// Bail if we have the right amount.
	if ( $count === $intersect ) {
		return;
	}

	$available = ( $count - $intersect );

	// Bail if none available.
	if ( ! $available ) {
		return;
	}

	add_action( 'admin_notices', function() use ( $available ) {

		if ( 1 === $available ) {
			$notice = sprintf( '%s %s', $available, __( 'default Template Part needs to be created.', 'mai-engine' ) );
		} else {
			$notice = sprintf( '%s %s', $available, __( 'default Template Parts need to be created.', 'mai-engine' ) );
		}

		$generate_url = add_query_arg( [ 'action' => 'mai_generate_template_parts_action' ], admin_url( 'admin-post.php' ) );
		$generate_url = wp_nonce_url( $generate_url, 'mai_generate_template_parts_action', 'mai_generate_template_parts_nonce' );
		$button       = sprintf( '<a class="button button-primary" href="%s">%s</a>', $generate_url, __( 'Generate Now', 'mai-engine' ) );

		printf(
			'<div class="notice notice-warning"><p>%s</p><p>%s</p></div>',
			$notice,
			$button
		);
	});
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
	if ( 'mai_template_part' !== $post->post_type ) {
		return $actions;
	}

	if ( ! ( current_user_can( 'edit_theme_options' ) && current_user_can( 'delete_post', $post->ID ) ) ) {
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

	$import_url = add_query_arg(
		[
			'action' => 'mai_import_template_part_action',
			'slug'   => $post->post_name,
		],
		admin_url( 'admin-post.php' )
	);
	$import_url = wp_nonce_url( $import_url, 'mai_import_template_part_action', 'mai_import_template_part_nonce' );
	$html       = sprintf( '<a href="%s" onclick="return maiImportConfirmation()">%s</a>', $import_url, __( 'Import From Demo', 'mai-engine' ) );

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
	if ( 'mai_template_part' !== $post->post_type ) {
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

add_filter( 'manage_mai_template_part_posts_columns', 'mai_template_part_add_slug_column' );
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

	if ( ! $screen || ( 'edit-mai_template_part' !== $screen->id ) ) {
		return;
	}

	$query->set( 'orderby', 'menu_order' );
	$query->set( 'order', 'ASC' );
}

/**
 * Clears the transient on post type save/update.
 *
 * @since TBD
 *
 * @param int $post_id The template part ID.
 *
 * @return void
 */
add_action( 'save_post_mai_template_part', 'mai_save_template_part_delete_transient' );
function mai_save_template_part_delete_transient( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	delete_transient( 'mai_template_parts' );
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
			admin_url( 'edit.php?post_type=mai_template_part' ),
			__( 'Edit template parts now', 'mai-engine' )
		);
	});
}
