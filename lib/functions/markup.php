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
 * @since  0.1.0
 *
 * @param array $classes Body classes.
 *
 * @return array
 */
function mai_body_classes( $classes ) {

	// Remove unnecessary page template classes.
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

	// Add simple template name.
	if ( $basename ) {
		$classes[] = 'template-' . $basename;
	}

	// Add boxed container class.
	if ( mai_has_boxed_container() ) {
		$classes[] = 'has-boxed-container';
	}

	// Add transparent header class.
	if ( mai_has_transparent_header() ) {
		$classes[] = 'has-transparent-header';
	}

	// Add sticky header class.
	if ( mai_has_sticky_header() ) {
		$classes[] = 'has-sticky-header';
	}

	// Add block classes.
	if ( mai_has_cover_block() ) {
		$classes[] = 'has-cover-block';
	}

	// Add page header class.
	if ( mai_has_page_header() ) {
		$classes[] = 'has-page-header';
	} else {
		$classes[] = 'no-page-header';
	}

	$header_left  = has_nav_menu( 'header-left' ) || is_active_sidebar( 'header_left' );
	$header_right = has_nav_menu( 'header-right' ) || is_active_sidebar( 'header_right' );

	// Add logo classes.
	if ( ( $header_left && $header_right ) || ( ! $header_right && ! $header_right ) ) {
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

	return $classes;
}

add_filter( 'genesis_attr_site-container', 'mai_back_to_top_anchor' );
/**
 * Description of expected behavior.
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
