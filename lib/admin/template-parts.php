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
