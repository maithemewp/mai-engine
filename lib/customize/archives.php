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
	$types = mai_get_config( 'loop-archive' );

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
