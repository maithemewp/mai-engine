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

add_filter( 'genesis_attr_nav-header-left', 'mai_add_header_nav_attributes' );
add_filter( 'genesis_attr_nav-header-right', 'mai_add_header_nav_attributes' );
/**
 * Adds nav-header left and right classes.
 *
 * @since 2.1.1
 *
 * @param array $atts Element attributes.
 *
 * @return array
 */
function mai_add_header_nav_attributes( $atts ) {
	$atts['id']       = $atts['class'];
	$atts['class']    = 'nav-header ' . $atts['class'];
	$atts['itemtype'] = 'https://schema.org/SiteNavigationElement';

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

add_filter( 'genesis_attr_nav-after-header', 'mai_add_after_header_nav_id' );
/**
 * Adds ID to after header nav for skip link anchor.
 *
 * @since 2.1.1
 *
 * @param array $atts Element attributes.
 *
 * @return array
 */
function mai_add_after_header_nav_id( $atts ) {
	$atts['id'] = $atts['class'];

	return $atts;
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

add_filter( 'nav_menu_css_class', 'mai_nav_menu_buttons', 10, 4 );
/**
 * Moves menu item button classes to the actual menu item links.
 *
 * @since 2.4.3
 *
 * @param string[] $classes Array of the CSS classes that are applied to the menu item's `<li>` element.
 * @param WP_Post  $item    The current menu item.
 * @param stdClass $args    An object of wp_nav_menu() arguments.
 * @param int      $depth   Depth of menu item. Used for padding.
 *
 * @return array
 */
function mai_nav_menu_buttons( $classes, $item, $args, $depth ) {
	$buttons = array_intersect( $classes, [
		'button',
		'button-secondary',
		'button-outline',
		'button-link',
		'button-white',
		'button-small',
		'button-large',
	] );

	if ( ! $buttons ) {
		return $classes;
	}

	$id = $item->ID;

	/**
	 * Adds button classes to menu item link.
	 * @param array $atts {
	 *     The HTML attributes applied to the menu item's `<a>` element, empty strings are ignored.
	 *
	 *     @type string $title        Title attribute.
	 *     @type string $target       Target attribute.
	 *     @type string $rel          The rel attribute.
	 *     @type string $href         The href attribute.
	 *     @type string $aria_current The aria-current attribute.
	 * }
	 * @param WP_Post  $item  The current menu item.
	 * @param stdClass $args  An object of wp_nav_menu() arguments.
	 * @param int      $depth Depth of menu item. Used for padding.
	 *
	 * @return array
	 */
	add_filter( 'nav_menu_link_attributes', function( $atts, $item, $args, $depth ) use ( $id, $buttons ) {
		if ( $id !== $item->ID ) {
			return $atts;
		}
		$atts['class'] = mai_add_classes( $buttons, $atts['class'] );
		return $atts;
	}, 10, 4 );

	// Remove button classes from menu item.
	$classes = array_diff( $classes, $buttons );

	return $classes;
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
	$atts['class']  = 'menu-item-link';
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
			if ( ! mai_has_string( 'menu-item-', $class ) || in_array( $class, $keepers, true ) ) {
				continue;
			}

			unset( $attribute[ $index ] );
		}
	} elseif ( is_string( $attribute ) ) {
		$attribute = mai_has_string( 'menu-item-', $attribute ) ? '' : $attribute;
	}

	return $attribute;
}

add_filter( 'genesis_skip_links_output', 'mai_add_nav_skip_links' );
/**
 * Adds navigation menu skip links.
 *
 * @since 2.1.1
 *
 * @param array $links Skip links.
 *
 * @return array
 */
function mai_add_nav_skip_links( $links ) {
	if ( has_nav_menu( 'header-left' ) ) {
		$links['nav-header-left'] = __( 'Skip to header left navigation', 'mai-engine' );
	}

	if ( has_nav_menu( 'header-right' ) ) {
		$links['nav-header-right'] = __( 'Skip to header right navigation', 'mai-engine' );
	}

	if ( has_nav_menu( 'after-header' ) ) {
		$links['nav-after-header'] = __( 'Skip to after header navigation', 'mai-engine' );
	}

	return $links;
}
