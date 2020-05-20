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

	$_wp_sidebars_widgets = ! empty( $_wp_sidebars_widgets ) ? $_wp_sidebars_widgets : get_option( 'sidebars_widgets', [] );

	if ( isset( $_wp_sidebars_widgets[ $widget_area_id ] ) ) {
		$widget_count = count( $_wp_sidebars_widgets[ $widget_area_id ] );

	} else {
		$widget_count = 0;
	}

	return $widget_count;
}

/**
 * Get a widget area's default content.
 *
 * @since 0.3.3
 *
 * @param string $location The widget area location id.
 *
 * @return string  The widget area content.
 */
function mai_get_widget_area_default_content( $location ) {
	static $widget_areas = [];
	if ( isset( $widget_areas[ $location ] ) ) {
		return $widget_areas[ $location ];
	}
	$areas = mai_get_config( 'widget-areas' )['add'];
	foreach ( $areas as $area ) {
		$widget_areas[ $area['id'] ] = isset( $area['default'] ) ? $area['default'] : '';
	}
	if ( ! isset( $widget_areas[ $location ] ) ) {
		$widget_areas[ $location ] = '';
	}
	return $widget_areas[ $location ];
}
