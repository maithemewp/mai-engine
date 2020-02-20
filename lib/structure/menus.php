<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2019 BizBudding
 * @license   GPL-2.0-or-later
 */

add_action( 'genesis_header', 'mai_header_left_menu', 5 );
/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_header_left_menu() {
	genesis_nav_menu( [
		'theme_location' => 'header-left',
	] );
}

add_action( 'mai_after_title_area', 'mai_header_right_menu', 5 );
/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_header_right_menu() {
	genesis_nav_menu( [
		'theme_location' => 'header-right',
	] );
}

add_action( 'mai_after_header_wrap', 'mai_below_header_menu' );
/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_below_header_menu() {
	genesis_nav_menu( [
		'theme_location' => 'below-header',
	] );
}

add_action( 'genesis_footer', 'mai_footer_menu' );
/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_footer_menu() {
	genesis_nav_menu( [
		'theme_location' => 'footer',
	] );
}


add_filter( 'walker_nav_menu_start_el', 'mai_replace_hash_with_void', 999 );
/**
 * Replace # links with JavaScript void.
 *
 * @since 0.1.0
 *
 * @param string $menu_item item HTML.
 *
 * @return string
 */
function mai_replace_hash_with_void( $menu_item ) {
	if ( strpos( $menu_item, 'href="#"' ) !== false ) {
		$menu_item = str_replace( 'href="#"', 'href="javascript:void(0);"', $menu_item );
	}

	return $menu_item;
}

add_filter( 'wp_nav_menu_args', 'mai_menu_depth' );
/**
 * Reduces secondary navigation menu to one level depth.
 *
 * @since 2.2.3
 *
 * @param array $args Original menu options.
 *
 * @return array Menu options with depth set to 1.
 */
function mai_menu_depth( $args ) {
	if ( 'footer' === $args['theme_location'] ) {
		$args['depth'] = 1;
	}

	return $args;
}

add_filter( 'nav_menu_link_attributes', 'mai_nav_link_atts' );
/**
 * Pass nav menu link attributes through attribute parser.
 *
 * Adds nav menu link attributes via the Genesis markup API.
 *
 * @since 0.1.0
 *
 * @param array $atts
 *
 * @return array
 */
function mai_nav_link_atts( $atts ) {
	$atts['class'] = 'menu-item-link';
	$atts['class'] .= $atts['aria-current'] ? ' menu-item-link-current' : '';

	return $atts;
}
