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
	$removed      = mai_get_config( 'widget-areas' )['remove'];

	foreach ( $widget_areas as $widget_area ) {
		$id = $widget_area['id'];

		if ( in_array( $id, $removed, true ) ) {
			continue;
		}

		$hook     = isset( $widget_area['location'] ) ? $widget_area['location'] : false;
		$priority = isset( $widget_area['priority'] ) ? $widget_area['priority'] : 10;
		$defaults = [
			'before'  => sprintf( '<div class="%s"><div class="wrap">', $id ),
			'content' => isset( $widget_area['default'] ) && ! empty( $widget_area['default'] ) ? $widget_area['default'] : '',
			'after'   => '</div></div>',
		];
		$args     = isset( $widget_area['args'] ) ? wp_parse_args( $widget_area['args'], $defaults ) : $defaults;

		if ( $hook && is_active_sidebar( $id ) && ! mai_is_element_hidden( mai_convert_case( $id ) ) ) {
			add_action(
				$hook,
				function () use ( $id, $args ) {
					genesis_widget_area( $id, $args );
					if ( ! is_active_sidebar( $id ) && ! empty( $args['content'] ) ) {
						$content = str_replace( ' ', '&nbsp;', $args['content'] );
						echo $args['before'];
						echo wp_kses_post( do_shortcode( $content ) );
						echo $args['after'];
					}
				},
				$priority
			);
		}
	}
}
