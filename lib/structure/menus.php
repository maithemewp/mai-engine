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

// Allow shortcodes in nav menu items.
add_filter( 'walker_nav_menu_start_el', 'do_shortcode' );

add_filter( 'genesis_attr_nav-header-left', 'mai_header_nav_class' );
add_filter( 'genesis_attr_nav-header-right', 'mai_header_nav_class' );
/**
 * Adds nav-header left and right classes.
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
 * Displays header left nav menu.
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
 * Displays header right menu.
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
 * @since 2.0.1
 *
 * @param array $items The menu items, sorted by each menu item's menu order.
 *
 * @return array
 */
function mai_first_last_menu_items( $items ) {
	if ( ! empty( $items ) ) {
		$items[ array_keys( $items )[0] ]->classes[] = 'menu-item-first';
		$items[ count( $items ) ]->classes[]         = 'menu-item-last';
	}

	return $items;
}

add_filter( 'nav_menu_item_id', 'mai_remove_menu_item_classes' );
add_filter( 'nav_menu_css_class', 'mai_remove_menu_item_classes' );
add_filter( 'page_css_class', 'mai_remove_menu_item_classes' );
/**
 * Remove unnecessary menu item classes.
 *
 * @since 2.0.0
 *
 * @param array|string $attribute Classes or ID.
 *
 * @return array|string
 */
function mai_remove_menu_item_classes( $attribute ) {
	if ( ! mai_get_option( 'remove-menu-item-classes', true ) ) {
		return $attribute;
	}

	if ( is_array( $attribute ) ) {
		$keepers = [
			'menu-item-first',
			'menu-item-last',
			'menu-item-has-children',
		];

		foreach ( $attribute as $index => $class ) {
			if ( ! mai_has_string( 'menu-item-', $class ) || in_array( $class, $keepers ) ) {
				continue;
			}

			unset( $attribute[ $index ] );
		}

	} elseif ( is_string( $attribute ) ) {
		$attribute = mai_has_string( 'menu-item-', $attribute ) ? '' : $attribute;
	}

	return $attribute;
}
