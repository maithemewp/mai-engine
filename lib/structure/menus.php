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

add_filter( 'genesis_attr_nav-header-left', 'mai_header_nav_class' );
add_filter( 'genesis_attr_nav-header-right', 'mai_header_nav_class' );
/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @param array $atts Element attributes.
 *
 * @return mixed
 */
function mai_header_nav_class( $atts ) {
	$atts['class'] = 'nav-header ' . $atts['class'];

	return $atts;
}

add_action( 'mai_header_left', 'mai_header_left_menu', 15 );
/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_header_left_menu() {
	genesis_nav_menu(
		[
			'theme_location' => 'header-left',
		]
	);
}

add_action( 'mai_header_right', 'mai_header_right_menu' );
/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_header_right_menu() {
	genesis_nav_menu(
		[
			'theme_location' => 'header-right',
		]
	);
}

add_action( 'genesis_after_header', 'mai_after_header_menu' );
/**
 * Display the After Header menu.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_after_header_menu() {

	if ( is_singular() && mai_is_element_hidden( 'after_header' ) ) {
		return;
	}

	genesis_nav_menu(
		[
			'theme_location' => 'after-header',
		]
	);
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
	genesis_nav_menu(
		[
			'theme_location' => 'footer',
		]
	);
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

add_filter( 'nav_menu_link_attributes', 'mai_nav_link_atts' );
/**
 * Pass nav menu link attributes through attribute parser.
 *
 * Adds nav menu link attributes via the Genesis markup API.
 *
 * @since 0.1.0
 *
 * @param array $atts Element attributes.
 *
 * @return array
 */
function mai_nav_link_atts( $atts ) {
	$atts['class'] = 'menu-item-link';
	$atts['class'] .= $atts['aria-current'] ? ' menu-item-link-current' : '';

	return $atts;
}

add_filter( 'wp_nav_menu_objects', 'mai_first_last_menu_items' );
/**
 * Adds first and last classes to menu items for cleaner styling.
 *
 * @since 0.1.0
 *
 * @param array $items The menu items, sorted by each menu item's menu order.
 *
 * @return array
 */
function mai_first_last_menu_items( $items ) {
	$items[1]->classes[]                 = 'menu-item-first';
	$items[ count( $items ) ]->classes[] = 'menu-item-last';

	return $items;
}

/**
 * Allow shortcodes in nav menu items.
 *
 * @since 0.1.0
 *
 * @return string
 */
add_filter( 'walker_nav_menu_start_el', 'do_shortcode' );

add_filter( 'nav_menu_css_class', 'mai_remove_menu_item_classes', 100 );
add_filter( 'nav_menu_item_id', 'mai_remove_menu_item_classes', 100 );
add_filter( 'page_css_class', 'mai_remove_menu_item_classes', 100 );
/**
 * Remove unnecessary menu item classes.
 *
 * @since 1.0.0
 *
 * @param $classes
 *
 * @return array|string
 */
function mai_remove_menu_item_classes( $classes ) {
	if ( is_array( $classes ) && mai_get_option( 'remove-menu-item-classes', true ) ) {
		$safelist = [
			'menu-item',
			'menu-item-first',
			'menu-item-last',
			'current-menu-item',
		];

		$classes = array_flip( $classes );

		foreach ( $classes as $class => $index ) {
			if ( ! in_array( $class, $safelist, true ) ) {
				unset( $classes[ $class ] );
			}
		}

		$classes = array_flip( $classes );
	}

	return $classes;
}
