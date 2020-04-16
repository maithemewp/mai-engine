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

add_action( 'genesis_before', 'mai_widget_areas' );
/**
 * Display widget areas defined in config.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_widget_areas() {
	$widget_areas = mai_get_config( 'widget-areas' )['add'];

	foreach ( $widget_areas as $widget_area ) {
		$id       = $widget_area['id'];
		$hook     = isset( $widget_area['location'] ) ? $widget_area['location'] : false;
		$priority = isset( $widget_area['priority'] ) ? $widget_area['priority'] : 10;
		$defaults = [
			'before' => sprintf( '<div class="%s has-%s-widgets"><div class="wrap">', $id, mai_get_widget_count( $id ) ),
			'after'  => '</div></div>',
		];
		$args     = isset( $widget_area['args'] ) ? wp_parse_args( $widget_area['args'], $defaults ) : $defaults;

		if ( $hook && ! mai_is_element_hidden( mai_convert_case( $id ) ) ) {
			add_action(
				$hook,
				function () use ( $id, $args ) {
					genesis_widget_area( $id, $args );
				},
				$priority
			);
		}
	}
}
