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

add_action( 'current_screen', 'mai_create_template_parts' );
/**
 * Create default template parts if they don't exist.
 * Only runs on main template part admin list.
 *
 * @since 2.0.0
 *
 * @return void
 */
function mai_create_template_parts( $current_screen ) {
	// Bail if running in setup wizard.
	if ( did_action( 'mai_setup_wizard_before_steps' ) ) {
		return;
	}

	if ( 'edit-wp_template_part' !== $current_screen->id ) {
		return;
	}

	$template_parts = mai_get_config( 'template-parts' );

	foreach ( $template_parts as $template_part ) {
		if ( mai_template_part_exists( $template_part['id'] ) ) {
			continue;
		}

		$args = [
			'post_type'   => 'wp_template_part',
			'post_title'  => mai_convert_case( $template_part['id'], 'title' ),
			'post_name'   => $template_part['id'],
			'post_status' => 'publish',
		];

		wp_insert_post( $args );
	}
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
 * @return mixed
 */
function mai_template_part_post_state( $states, $post ) {
	$template_parts = mai_get_config( 'template-parts' );

	foreach ( $template_parts as $template_part ) {
		if ( $template_part['id'] === $post->post_name && $post->post_content ) {
			$states[] = __( 'Active', 'mai-engine' );
		}
	}

	return $states;
}
