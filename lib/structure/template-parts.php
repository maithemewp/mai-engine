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

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

add_action( 'genesis_before', 'mai_render_template_parts' );
/**
 * Display content areas defined in config.
 *
 * @since 2.0.1
 *
 * @return void
 */
function mai_render_template_parts() {
	$template_parts = mai_get_config( 'template-parts' );

	foreach ( $template_parts as $slug => $template_part ) {
		$hook      = isset( $template_part['hook'] ) ? $template_part['hook'] : false;
		$priority  = isset( $template_part['priority'] ) ? $template_part['priority'] : 10;
		$condition = isset( $template_part['condition'] ) ? $template_part['condition'] : '';
		$before    = isset( $template_part['before'] ) ? $template_part['before'] : '';
		$after     = isset( $template_part['after'] ) ? $template_part['after'] : '';

		if ( $hook && ! mai_is_element_hidden( mai_convert_case( $slug ) ) ) {
			add_action( $hook, function() use ( $slug, $before, $after, $condition ) {
				mai_render_template_part( $slug, $before, $after, $condition );
			}, $priority );
		}
	}
}
