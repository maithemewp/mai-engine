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

add_filter( 'genesis_attr_footer-widgets', 'mai_footer_widgets_columns' );
/**
 * Get the columns at different breakpoints.
 *
 * @since 0.1.0
 *
 * @param array $attributes Column args.
 *
 * @return array
 */
function mai_footer_widgets_columns( $attributes ) {
	$default = get_theme_support( 'genesis-footer-widgets' )[0];
	$count   = absint( mai_get_option( 'footer-widgets-widget-areas', $default ) );
	$columns = [ 'lg' => $count ];

	switch ( $count ) {
		case 6:
			$columns['md'] = 4;
			$columns['sm'] = 3;
			$columns['xs'] = 2;
			break;
		case 5:
			$columns['md'] = 3;
			$columns['sm'] = 2;
			$columns['xs'] = 2;
			break;
		case 4:
			$columns['md'] = 4;
			$columns['sm'] = 2;
			$columns['xs'] = 1;
			break;
		case 3:
			$columns['md'] = 3;
			$columns['sm'] = 1;
			$columns['xs'] = 1;
			break;
		case 2:
			$columns['md'] = 2;
			$columns['sm'] = 2;
			$columns['xs'] = 1;
			break;
		case 1:
			$columns['md'] = 1;
			$columns['sm'] = 1;
			$columns['xs'] = 1;
			break;
		case 0: // Auto.
			$columns['md'] = 0;
			$columns['sm'] = 0;
			$columns['xs'] = 0;
			break;
		default:
			$columns['md'] = $count;
			$columns['sm'] = $count;
			$columns['xs'] = $count;
	}

	$attributes['style'] = isset( $attributes['style'] ) ? $attributes['style'] : '';
	$attributes['style'] .= "--columns-xs:{$columns['xs']};";
	$attributes['style'] .= "--columns-sm:{$columns['sm']};";
	$attributes['style'] .= "--columns-md:{$columns['md']};";
	$attributes['style'] .= "--columns-lg:{$columns['lg']};";

	return $attributes;
}

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
