<?php

/**
 * Add archive customizer settings from content types in config.
 *
 * @return  void
 */
add_action( 'init', 'mai_archive_customizer_settings' );
function mai_archive_customizer_settings() {

	$archives = mai_get_config( 'archive-settings' );

	if ( ! $archives ) {
		return;
	}

	// Content Archives panel.
	Kirki::add_panel( 'mai_content_archives', [
		'title'       => esc_attr__( 'Content Archives', 'mai-engine' ),
		'description' => esc_attr__( '', 'mai-engine' ),
		'priority'    => 125,
	] );

	foreach( $archives as $name ) {

		// Get the post type object.
		$post_type = get_post_type_object( $name );

		// Skip if no post type or it's not public.
		if ( ! ( $post_type && $post_type->public ) ) {
			continue;
		}

		// Skip if no archive. has_archive is false for core Posts.
		if ( 'post' !== $post_type->name  && ! $post_type->has_archive ) {
			continue;

			// Skip if does not have an archive.
			if ( ! $post_type->has_archive ) {
				continue;
			}
		}

		// Add the settings.
		mai_add_archive_customizer_settings( $name, 'post_type' );

		// Get supported taxonomies.
		$taxonomies = array_intersect( $archives, get_object_taxonomies( $name ) );

		// Skip if no taxonomies.
		if ( ! $taxonomies ) {
			continue;
		}

		// Loop through the taxos.
		foreach( $taxonomies as $taxonomy ) {
			mai_add_archive_customizer_settings( $taxonomy, 'taxonomy' );
		}
	}

	if ( in_array( 'search', $archives ) ) {
		mai_add_archive_customizer_settings( 'search', 'search' );
	}

	if ( in_array( 'author', $archives ) ) {
		mai_add_archive_customizer_settings( 'author', 'author' );
	}
}

/**
 * Add single customizer settings from post_types in config.
 *
 * @return  void
 */
add_action( 'init', 'mai_single_customizer_settings' );
function mai_single_customizer_settings() {

	$post_types = mai_get_config( 'single-settings' );

	if ( ! $post_types ) {
		return;
	}

	// Singular Content panel.
	Kirki::add_panel( 'mai_singular_content', [
		'title'       => esc_attr__( 'Singular Content', 'mai-engine' ),
		'description' => esc_attr__( '', 'mai-engine' ),
		'priority'    => 130,
	] );

	foreach( $post_types as $post_type ) {

		// Bail if not a post type.
		if ( ! post_type_exists( $post_type ) ) {
			continue;
		}

		// Add the settings.
		mai_add_single_customizer_settings( $post_type );
	}
}

/**
 * Add archive customizer settings.
 *
 * @param  string  $name  The registered content type name.
 * @param  string  $type  The object type. Either 'taxonomy', 'post_type', 'search', 'author'. TODO: Date?
 */
function mai_add_archive_customizer_settings( $name, $type = 'post_type' ) {

	// Bail if no Kirki.
	if ( ! class_exists( 'Kirki' ) ) {
		return;
	}

	// Bail if no name.
	if ( ! $name ) {
		return;
	}

	$config_id = 'mai_archive_' . $name;

	/**
	 * Kirki Config.
	 */
	Kirki::add_config( $config_id, array(
		'capability'  => 'edit_theme_options',
		'option_type' => 'option',
		'option_name' => $config_id,
	) );

	// Get label.
	switch ( $type ) {
		case 'post_type':
			$post_type = get_post_type_object( $name );
			$label     = $post_type->labels->name;
		break;
		case 'taxonomy':
			$taxonomy  = get_taxonomy( $name );
			$label     = $taxonomy->labels->name;
		break;
		case 'search':
			$label     = esc_attr__( 'Search Results', 'mai-engine' );
		break;
		case 'author':
			$label     = esc_attr__( 'Author Archives', 'mai-engine' );
		break;
		default:
			$label = '';
	}

	// Get fields.
	$settings = new Mai_Entry_Settings( 'archive' );
	$fields   = $settings->get_fields();

	// Section.
	Kirki::add_section( $config_id, [
		'title' => $label,
		'panel' => 'mai_content_archives',
	] );

	// Loop through fields.
	foreach( $fields as $field_name => $field ) {

		// Bail if not an archive field.
		if ( ! $field['archive'] ) {
			continue;
		}

		// TODO: Check post type support. How to handle where it works with grid post_type as well?
		// Skip if post type doesn't support a required feature.
		// if ( 'post_type' === $type && isset( $field['supports'] ) && ! in_array( $field['supports'], $post_type->supports ) ) {
		// 	continue;
		// }

		// Add field.
		Kirki::add_field( $config_id, $settings->get_data( $field_name, $field, $config_id ) );
	}

}

/**
 * Add single customizer settings.
 *
 * @param  string  $name  The registered post type name.
 */
function mai_add_single_customizer_settings( $name ) {

	// Bail if no Kirki.
	if ( ! class_exists( 'Kirki' ) ) {
		return;
	}

	// Bail if no name.
	if ( ! $name ) {
		return;
	}

	$config_id = 'mai_single_' . $name;

	/**
	 * Kirki Config.
	 */
	Kirki::add_config( $config_id, array(
		'capability'  => 'edit_theme_options',
		'option_type' => 'option',
		'option_name' => $config_id,
	) );

	// Get label.
	$post_type = get_post_type_object( $name );
	$label     = $post_type->labels->name;

	// Get fields.
	$settings = new Mai_Entry_Settings( 'single' );
	$fields   = $settings->get_fields();

	// Section.
	Kirki::add_section( $config_id, [
		'title' => $label,
		'panel' => 'mai_singular_content',
	] );

	// Loop through fields.
	foreach( $fields as $field_name => $field ) {

		// Bail if not an single field.
		if ( ! $field['single'] ) {
			continue;
		}

		// TODO: Check post type support. How to handle where it works with grid post_type as well?
		// Skip if post type doesn't support a required feature.
		// if ( 'post_type' === $type && isset( $field['supports'] ) && ! in_array( $field['supports'], $post_type->supports ) ) {
		// 	continue;
		// }

		// Add field.
		Kirki::add_field( $config_id, $settings->get_data( $field_name, $field, $config_id ) );
	}

}
