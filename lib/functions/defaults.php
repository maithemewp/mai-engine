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

add_action( 'after_switch_theme', 'mai_default_theme_settings' );
/**
 * Set default theme settings on theme activation.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_default_theme_settings() {
	$defaults = mai_get_config( 'settings' )['genesis'];

	genesis_update_settings( $defaults );

	update_option( 'posts_per_page', absint( mai_get_config( 'settings' )['content-archives']['post']['posts_per_page'] ) );
}

add_filter( 'simple_social_default_styles', 'mai_default_social_styles' );
/**
 * Set Simple Social Icon defaults.
 *
 * @since 0.1.0
 *
 * @param array $defaults Social style defaults.
 *
 * @return array
 */
function mai_default_social_styles( $defaults ) {
	$args           = mai_get_config( 'settings' )['simple-social-icons'];
	$colors         = array_keys( mai_get_colors() );
	$color_settings = [
		'border_color',
		'border_color_hover',
		'icon_color',
		'icon_color_hover',
		'background_color',
		'background_color_hover',
	];

	// Allow config to set colors by name.
	foreach ( $args as $key => $value ) {
		if ( in_array( $key, $color_settings, true ) && in_array( $value, $colors, true ) ) {
			$args[ $key ] = mai_get_color( $value );
		}
	}

	return wp_parse_args( $args, $defaults );
}

add_filter( 'icon_widget_defaults', 'mai_icon_widget_defaults' );
/**
 * Change Icon Widget plugin default settings.
 *
 * @since 0.1.0
 *
 * @param array $defaults Icon widget defaults.
 *
 * @return array
 */
function mai_icon_widget_defaults( $defaults ) {
	$defaults['color']   = mai_get_color( 'primary' );
	$defaults['weight']  = '400';
	$defaults['size']    = '3x';
	$defaults['align']   = 'center';
	$defaults['padding'] = 20;

	return $defaults;
}

add_filter( 'syntax_highlighting_code_block_style', 'mai_set_syntax_color' );
/**
 * Set syntax color for code block.
 *
 * @since 0.1.0
 *
 * @return string
 */
function mai_set_syntax_color() {
	return 'github';
}
