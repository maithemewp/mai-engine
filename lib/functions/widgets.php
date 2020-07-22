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

// Enable shortcodes in widgets.
add_filter( 'widget_text', 'do_shortcode' );

/**
 * Count number of widgets in a widget area.
 *
 * @since 0.1.0
 *
 * @param string $widget_area_id The widget area ID.
 *
 * @return int
 */
function mai_get_widget_count( $widget_area_id ) {

	/**
	 * If loading from front page, consult $_wp_sidebars_widgets rather than options
	 * to see if wp_convert_widget_settings() has made manipulations in memory.
	 */
	global $_wp_sidebars_widgets;

	$wp_sidebars_widgets = ! empty( $_wp_sidebars_widgets ) ? $_wp_sidebars_widgets : get_option( 'sidebars_widgets', [] );

	if ( isset( $wp_sidebars_widgets[ $widget_area_id ] ) ) {
		$widget_count = count( $wp_sidebars_widgets[ $widget_area_id ] );

	} else {
		$widget_count = 0;
	}

	return $widget_count;
}

add_action( 'widgets_init', 'mai_register_reusable_block_widget' );
/**
 * Register the widget.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_register_reusable_block_widget() {
	register_widget( 'Mai_Reusable_Block_Widget' );
}
