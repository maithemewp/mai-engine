<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2019 BizBudding
 * @license   GPL-2.0-or-later
 */

add_action( 'init', 'mai_archive_customizer_settings' );
/**
 * Add archive customizer settings from content types in config.
 *
 * @return  void
 */
function mai_archive_customizer_settings() {
	$types = mai_get_config( 'archive-settings-content-types' );

	if ( ! $types ) {
		return;
	}

	// Content Archives panel.
	Kirki::add_panel(
		'mai_content_archives',
		[
			'title'       => esc_attr__( 'Content Archives', 'mai-engine' ),
			'description' => '',
			'priority'    => 125,
		]
	);

	foreach ( $types as $name ) {

		// Get the post type object.
		$post_type = get_post_type_object( $name );

		// Skip if no post type or it's not public.
		if ( ! ( $post_type && $post_type->public ) ) {
			continue;
		}

		// Skip if no archive. has_archive is false for core Posts.
		if ( 'post' !== $post_type->name && ! $post_type->has_archive ) {
			continue;
		}

		// Add the settings.
		mai_add_archive_customizer_settings( $name, 'post_type' );

		// Get supported taxonomies.
		$taxonomies = array_intersect( $types, get_object_taxonomies( $name ) );

		// Skip if no taxonomies.
		if ( ! $taxonomies ) {
			continue;
		}

		// Loop through the taxos.
		foreach ( $taxonomies as $taxonomy ) {
			mai_add_archive_customizer_settings( $taxonomy, 'taxonomy' );
		}
	}

	if ( in_array( 'search', $types, true ) ) {
		mai_add_archive_customizer_settings( 'search', 'search' );
	}

	if ( in_array( 'author', $types, true ) ) {
		mai_add_archive_customizer_settings( 'author', 'author' );
	}
}

add_action( 'init', 'mai_single_customizer_settings' );
/**
 * Add single customizer settings from post_types in config.
 *
 * @return  void
 */
function mai_single_customizer_settings() {
	$post_types = mai_get_config( 'single-settings-post-types' );

	if ( ! $post_types ) {
		return;
	}

	// Singular Content panel.
	Kirki::add_panel(
		'mai_singular_content',
		[
			'title'       => esc_attr__( 'Singular Content', 'mai-engine' ),
			'description' => '',
			'priority'    => 130,
		]
	);

	foreach ( $post_types as $post_type ) {

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
 * @param  string $name The registered content type name.
 * @param  string $type The object type. Either 'taxonomy', 'post_type', 'search', 'author'.
 *
 * @todo: Date?
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
	Kirki::add_config(
		$config_id,
		[
			'capability'  => 'edit_theme_options',
			'option_type' => 'option',
			'option_name' => $config_id,
		]
	);

	// Get label.
	switch ( $type ) {
		case 'post_type':
			$post_type = get_post_type_object( $name );
			$label     = $post_type->labels->name;
			break;
		case 'taxonomy':
			$taxonomy = get_taxonomy( $name );
			$label    = $taxonomy->labels->name;
			break;
		case 'search':
			$label = esc_attr__( 'Search Results', 'mai-engine' );
			break;
		case 'author':
			$label = esc_attr__( 'Author Archives', 'mai-engine' );
			break;
		default:
			$label = '';
	}

	// Get fields.
	$fields = mai_get_config( 'archive-settings' );

	// Section.
	Kirki::add_section(
		$config_id,
		[
			'title' => $label,
			'panel' => 'mai_content_archives',
		]
	);

	// Loop through fields.
	foreach ( $fields as $field ) {

		// Add field.
		Kirki::add_field( $config_id, mai_get_kirki_field_data( $field, $config_id, $name ) );
	}
}

/**
 * Add single customizer settings.
 *
 * @param  string $name The registered post type name.
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
	Kirki::add_config(
		$config_id,
		[
			'capability'  => 'edit_theme_options',
			'option_type' => 'option',
			'option_name' => $config_id,
		]
	);

	// Get label.
	$post_type = get_post_type_object( $name );
	$label     = $post_type->labels->name;

	// Get fields.
	$fields = mai_get_config( 'single-settings' );

	// Section.
	Kirki::add_section(
		$config_id,
		[
			'title' => $label,
			'panel' => 'mai_singular_content',
		]
	);

	// Loop through fields.
	foreach ( $fields as $field ) {

		// Add field.
		Kirki::add_field( $config_id, mai_get_kirki_field_data( $field, $config_id, $name ) );
	}
}

/**
 * Setup the field data from config for kirki add_field method.
 *
 * @param  array  $field        The field config data.
 * @param  string $section_id   The Customizer section ID.
 * @param  string $name         The post or content type name.
 *
 * @return array The field data.
 */
function mai_get_kirki_field_data( $field, $section_id, $name = '' ) {

	$data = [
		'type'     => $field['type'],
		'label'    => $field['label'],
		'settings' => $field['name'],
		'section'  => $section_id,
		'priority' => 10,
	];

	// Maybe add description.
	if ( isset( $field['desc'] ) ) {
		$data['description'] = $field['desc'];
	}

	// Maybe add attributes.
	if ( isset( $field['atts'] ) ) {
		foreach ( $field['atts'] as $key => $value ) {
			$data[ $key ] = $value;
		}
	}

	// Maybe add conditional logic.
	if ( isset( $field['conditions'] ) ) {
		$data['active_callback'] = $field['conditions'];
	}

	// Maybe add default.
	if ( isset( $field['default'] ) ) {
		// Force radio buttonsets to strings, for some reason integers don't work with Kirki.
		if ( 'radio-buttonset' === $field['type'] && is_integer( $field['default'] ) ) {
			$field['default'] = (string) $field['default'];
		}
		$data['default'] = $field['default'];
	}

	// Maybe get choices.
	if ( isset( $field['choices'] ) ) {
		if ( is_array( $field['choices'] ) ) {
			$data['choices'] = $field['choices'];
		} elseif ( is_callable( $field['choices'] ) ) {
			$data['choices'] = call_user_func_array( $field['choices'], [ 'name' => $name ] );
		}
	}

	// Maybe add custom sanitize function.
	if ( isset( $field['sanitize'] ) ) {
		$data['sanitize_callback'] = $field['sanitize'];
	}

	return $data;
}
