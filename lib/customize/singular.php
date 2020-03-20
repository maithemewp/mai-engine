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
