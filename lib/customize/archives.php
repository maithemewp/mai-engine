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

add_action( 'after_setup_theme', 'mai_archive_customizer_settings' );
/**
 * Add archive customizer settings from content types in config.
 *
 * @return  void
 */
function mai_archive_customizer_settings() {
	$config = mai_get_config( 'loop' );
	$types  = isset( $config['archive'] ) ? $config['archive'] : [];

	if ( empty( $types ) ) {
		return;
	}

	Kirki::add_panel(
		mai_get_handle() . '-content-archives',
		[
			'title'       => esc_attr__( 'Content Archives', 'mai-engine' ),
			'description' => '',
			'priority'    => 70,
			'panel'       => mai_get_handle(),
		]
	);

	foreach ( $types as $name ) {
		$post_type = get_post_type_object( $name );

		if ( ! ( $post_type && $post_type->public ) ) {
			continue;
		}

		if ( 'post' !== $post_type->name && ! $post_type->has_archive ) {
			continue;
		}

		mai_add_archive_customizer_settings( $name, 'post_type' );

		$taxonomies = array_intersect( $types, get_object_taxonomies( $name ) );

		if ( $taxonomies ) {
			foreach ( $taxonomies as $taxonomy ) {
				mai_add_archive_customizer_settings( $taxonomy, 'taxonomy' );
			}
		}
	}

	if ( in_array( 'search', $types, true ) ) {
		mai_add_archive_customizer_settings( 'search', 'search' );
	}

	if ( in_array( 'author', $types, true ) ) {
		mai_add_archive_customizer_settings( 'author', 'author' );
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
	$panel_id   = mai_get_handle() . '-content-archives';
	$section_id = mai_get_handle() . '-archive-' . $name;

	switch ( $type ) {
		case 'post_type':
			$post_type = get_post_type_object( $name );
			$label     = 'post' === $name ? esc_html__( 'Default', 'mai-engine' ) : $post_type->labels->name;
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

	$fields = mai_get_config( 'archive-settings' );

	Kirki::add_section(
		$section_id,
		[
			'title' => $label,
			'panel' => $panel_id,
		]
	);

	foreach ( $fields as $field ) {
		Kirki::add_field(
			mai_get_handle(),
			mai_get_kirki_field_data( $field, $section_id, $name )
		);
	}

}
