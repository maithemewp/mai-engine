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

	// Add transparent header class.
	if ( current_theme_supports( 'transparent-header' ) && ( mai_is_hero_section_active() || is_front_page() && is_active_sidebar( 'front-page-1' ) ) ) {
		$classes[] = 'has-transparent-header';
	}

	// Add sticky header class.
	if ( current_theme_supports( 'sticky-header' ) ) {
		$classes[] = 'has-sticky-header';
	}

	// Add single type class.
	if ( mai_is_type_single() ) {
		$classes[] = 'is-single';
	}

	// Add archive type class.
	if ( mai_is_type_archive() ) {
		$classes[] = 'is-archive';
	}

	// Add no hero section class.
	$classes[] = 'no-hero-section';

	// Add front page 1 slider class.
	$classes[] = mai_sidebar_has_widget( 'front-page-1', 'seo_slider' ) ? 'has-home-slider' : '';

	// Add block classes.
	$classes[] = mai_has_cover_block() ? 'has-cover-block' : '';

	return $classes;
}

add_filter( 'genesis_attr_site-container', 'mai_back_to_top_anchor' );
/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @return array
 */
function mai_back_to_top_anchor( $attr ) {
	$attr['id'] = 'top';

	return $attr;
}
