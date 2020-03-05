<?php

/**
 * The main panel ID.
 */
function mai_customizer_panel_id() {
	return 'maitheme';
}

/**
 * Setup Kirki config.
 *
 * @return  void
 */
add_action( 'init', 'mai_customizer_panel' );
function mai_customizer_panel() {

	if ( ! class_exists( 'Kirki' ) ) {
		return;
	}

	$panel_id = mai_customizer_panel_id();

	/**
	 * Mai Theme.
	 */
	Kirki::add_panel( $panel_id, array(
		'title'       => esc_attr__( '!!!! Mai Theme', 'mai-engine' ),
		'description' => esc_attr__( 'Nice description.', 'mai-engine' ),
		'priority'    => 55,
	) );

	// Kirki::add_section( $panel_id . '_archives', [
	// 	'title'       => esc_attr__( 'Content Archives', 'mai-engine' ),
	// 	// 'description' => esc_attr__( 'My panel description', 'mai-engine' ),
	// 	'panel'       => $panel_id,
	// ] );

	/**
	 * Archive Content.
	 */
	// Kirki::add_panel( $panel_id . '_archives', array(
	// 	'title'       => esc_attr__( 'Content Archives', 'mai-engine' ),
	// 	// 'description' => esc_attr__( 'My panel description', 'mai-engine' ),
	// 	'panel'       => $panel_id,
	// 	'priority'    => 10,
	// ) );


	Kirki::add_section( $panel_id . '_archive', [
		'title'       => esc_attr__( 'Content Archives', 'mai-engine' ),
		// 'description' => esc_attr__( 'My panel description', 'mai-engine' ),
		'panel'       => $panel_id,
		'priority'    => 10,
	] );

	/**
	 * Singular Content.
	 */
	Kirki::add_section( $panel_id . '_single', [
		'title'       => esc_attr__( 'Singular Content', 'mai-engine' ),
		// 'description' => esc_attr__( 'My panel description', 'mai-engine' ),
		'panel'       => $panel_id,
		'priority'    => 10,
	] );


	// https://github.com/aristath/kirki/issues/1263#issuecomment-301236807
	// https://wordpress.org/support/topic/nested-panels/
	// Kirki::add_field( $panel_id, [
	// 	'type'        => 'custom',
	// 	'settings'    => 'test_archive',
	// 	'label'       => esc_html__( 'Temporary field', 'kirki' ),
	// 	'section'     => $panel_id . '_archive',
	// 	'default'     => 'This is only here to make the panels show for now.',
	// 	'priority'    => 10,
	// ] );
	// Kirki::add_field( $panel_id, [
	// 	'type'        => 'custom',
	// 	'settings'    => 'test_single',
	// 	'label'       => esc_html__( 'Temporary field', 'kirki' ),
	// 	'section'     => $panel_id . '_single',
	// 	'default'     => 'This is only here to make the panels show for now.',
	// 	'priority'    => 10,
	// ] );
}

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

	$panel_id = mai_customizer_panel_id();

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
			$label     = $post_type->labels->singular_name;
			$label     = trim( $label . ' ' . esc_attr__( 'Archives', 'mai-engine' ) );
		break;
		case 'taxonomy':
			$taxonomy  = get_taxonomy( $name );
			$label     = $taxonomy->labels->singular_name;
			$label     = trim( '&mdash; ' . $label . ' ' . esc_attr__( 'Archives', 'mai-engine' ) );
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

	// Get data.
	$section_id = sprintf( '%s_archives', $name );
	$panel_id   = mai_customizer_panel_id() . '_archive';
	$settings   = new Mai_Entry_Settings( 'archive' );
	$fields     = $settings->get_fields();
	$prefix     = sprintf( '%s_', $name );

	// Section.
	Kirki::add_section( $section_id, [
		'title' => $label,
		// 'panel' => $panel_id,
		'section' => $panel_id,
	] );

	// Loop through fields.
	foreach( $fields as $field_name => $field ) {

		// Bail if not an archive field.
		if ( ! $field['archive'] ) {
			continue;
		}

		// Add field.
		Kirki::add_field( $config_id, $settings->get_data( $field_name, $field, $section_id, $prefix ) );
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
	$label     = $post_type->labels->singular_name;
	$label     = esc_attr__( 'Single', 'mai-engine' ) . ' ' . $label;

	// Get data.
	$section_id = sprintf( '%s_single', $name );
	$panel_id   = mai_customizer_panel_id() . '_single';
	$settings   = new Mai_Entry_Settings( 'single' );
	$fields     = $settings->get_fields();
	$prefix     = sprintf( '%s_', $name );

	// Section.
	Kirki::add_section( $section_id, [
		'title' => $label,
		'panel' => $panel_id,
	] );

	// Loop through fields.
	foreach( $fields as $field_name => $field ) {

		// Bail if not an single field.
		if ( ! $field['single'] ) {
			continue;
		}

		// Add field.
		Kirki::add_field( $config_id, $settings->get_data( $field_name, $field, $section_id, $prefix ) );
	}

}
