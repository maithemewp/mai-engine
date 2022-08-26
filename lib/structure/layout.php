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

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

// Remove unused body classes added by Genesis.
remove_filter( 'body_class', 'genesis_header_body_classes' );

add_filter( 'language_attributes', 'mai_admin_bar_showing' );
/**
 * Add class to html element for styling.
 *
 * @since 0.1.0
 *
 * @param string $output Language attributes markup.
 *
 * @return string
 */
function mai_admin_bar_showing( $output ) {
	if ( is_admin_bar_showing() ) {
		$output .= ' class="admin-bar-showing"';
	}

	return $output;
}

add_filter( 'body_class', 'mai_body_classes' );
/**
 * Add additional classes to the body element.
 *
 * @since 0.1.0
 *
 * @param array $classes Body classes.
 *
 * @return array
 */
function mai_body_classes( $classes ) {
	// Remove unnecessary page template classes.
	if ( mai_get_option( 'remove-template-classes', true ) ) {
		$template  = get_page_template_slug();
		$basename  = basename( $template, '.php' );
		$directory = str_replace( [ '/', basename( $template ) ], '', $template );
		$classes   = array_diff(
			$classes,
			[
				'page-template',
				'page-template-' . $basename,
				'page-template-' . $directory,
				'page-template-' . $directory . $basename . '-php',
			]
		);
	}

	// Add boxed container class.
	if ( mai_has_boxed_container() ) {
		$classes[] = 'has-boxed-container';
	}

	// Add before class.
	if ( mai_has_template_part( 'before-header' ) || is_active_sidebar( 'before-header' ) ) {
		$classes[] = 'has-before-header';
	}

	// Add dark class.
	$colors = mai_get_colors();

	if ( mai_has_dark_body() ) {
		$classes[] = 'has-dark-body';
	}

	if ( mai_has_dark_header() ) {
		$classes[] = 'has-dark-header has-dark-mobile-menu';
	}

	$has_page_header      = mai_has_page_header();
	$has_dark_page_header = $has_page_header && ! mai_has_light_page_header();

	// Add transparent header class.
	if ( mai_has_transparent_header() ) {
		$classes[] = 'has-transparent-header';

		if ( $has_dark_page_header || ( ! $has_page_header && mai_has_dark_background_first() ) ) {
			$classes[] = 'has-dark-transparent-header';
		}
	}

	// Add page header classes.
	if ( $has_page_header ) {
		$classes[] = 'has-page-header';
		$classes[] = $has_dark_page_header ? 'has-dark-page-header' : 'has-light-page-header';
	} else {
		$classes[] = 'no-page-header';
	}

	// Add sticky header class.
	if ( mai_has_sticky_header_enabled() && ! mai_is_element_hidden( 'sticky_header' ) ) {
		$classes[] = 'has-sticky-header';
	}

	// Add scroll logo class.
	if ( mai_has_sticky_scroll_logo() ) {
		$classes[] = 'has-scroll-logo';
	}

	// Add or alignfull class.
	if ( mai_has_alignfull_first() ) {
		$classes[] = 'has-alignfull-first';
	}

	$header_left  = has_nav_menu( 'header-left' ) || mai_has_template_part( 'header-left' );
	$header_right = has_nav_menu( 'header-right' ) || mai_has_template_part( 'header-right' );

	// Add logo classes.
	if ( $header_right && ! $header_left ) {
		$classes[] = 'has-logo-left';
	} elseif ( $header_left && ! $header_right ) {
		$classes[] = 'has-logo-right';
	} elseif ( ( $header_left && $header_right ) || ( ! $header_right && ! $header_right ) ) {
		$classes[] = 'has-logo-center';
	}

	// Add single type class.
	if ( mai_is_type_single() ) {
		$classes[] = 'is-single';
	}

	// Add archive type class.
	if ( mai_is_type_archive() ) {
		$classes[] = 'is-archive';
	}

	// Sidebar.
	if ( mai_has_string( 'sidebar', genesis_site_layout() ) ) {
		$classes[] = 'has-sidebar';
	} else {
		$classes[] = 'no-sidebar';
	}

	// Always assume no-js.
	$classes[] = 'no-js';

	return $classes;
}

add_filter( 'genesis_attr_site-container', 'mai_back_to_top_anchor' );
/**
 * Adds #top id to site-container.
 *
 * @since 0.1.0
 *
 * @param array $attr Element attributes.
 *
 * @return array
 */
function mai_back_to_top_anchor( $attr ) {
	$attr['id'] = 'top';

	return $attr;
}

add_filter( 'genesis_attr_content-sidebar-wrap', 'mai_add_dark_background_class' );
add_filter( 'genesis_attr_site-footer',          'mai_add_dark_background_class' );
/**
 *
 * @since TBD
 *
 * @param array $attributes Current attributes.
 *
 * @return array The modified attributes.
 */
function mai_add_dark_background_class( $attributes ) {
	if ( ! mai_has_dark_body() ) {
		return $attributes;
	}

	$attributes['class'] = mai_add_classes( 'has-dark-background', $attributes['class'] );

	return $attributes;
}

add_filter( 'genesis_attr_content', 'mai_add_content_classes' );
/**
 * Add layout classes to the content wrap.
 *
 * @since 2.2.2
 *
 * @param array $attr Element attributes.
 *
 * @return array
 */
function mai_add_content_classes( $attr ) {
	$layout        = 'has-' . genesis_site_layout();
	$attr['class'] = mai_add_classes( $layout, $attr['class'] );

	return $attr;
}
