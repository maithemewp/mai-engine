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

add_action( 'genesis_before', 'mai_render_template_parts' );
/**
 * Display widget areas defined in config.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_render_template_parts() {
	$template_parts = mai_get_config( 'template-parts' );

	foreach ( $template_parts as $template_part ) {
		$id       = $template_part['id'];
		$hook     = isset( $template_part['location'] ) ? $template_part['location'] : false;
		$priority = isset( $template_part['priority'] ) ? $template_part['priority'] : 10;
		$defaults = [
			'before' => sprintf( '<div class="%s">', $id ),
			'after'  => '</div>',
		];
		$args     = isset( $template_part['args'] ) ? wp_parse_args( $template_part['args'], $defaults ) : $defaults;

		if ( $hook && ! mai_is_element_hidden( mai_convert_case( $id ) ) ) {
			add_action(
				$hook,
				function () use ( $id, $args ) {
					mai_render_template_part( $id, $args );
				},
				$priority
			);
		}
	}
}
