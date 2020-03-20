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
