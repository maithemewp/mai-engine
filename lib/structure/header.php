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

add_action( 'genesis_before', 'mai_maybe_hide_site_header' );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_maybe_hide_site_header() {
	if ( ! mai_is_element_hidden( 'site_header' ) ) {
		return;
	}

	remove_action( 'genesis_header', 'genesis_header_markup_open', 5 );
	remove_action( 'genesis_header', 'genesis_do_header' );
	remove_action( 'genesis_header', 'genesis_header_markup_close', 15 );
}

add_filter( 'genesis_markup_title-area_open', 'mai_before_title_area_hook', 10, 1 );
/**
 * Add custom hook after the title area.
 *
 * @since 0.1.0
 *
 * @param string $open_html Closing html markup.
 *
 * @return string
 */
function mai_before_title_area_hook( $open_html ) {
	if ( $open_html ) {
		ob_start();
		do_action( 'mai_before_title_area' );
		$open_html = ob_get_clean() . $open_html;
	}

	return $open_html;
}

add_filter( 'genesis_markup_title-area_close', 'mai_after_title_area_hook', 10, 1 );
/**
 * Add custom hook after the title area.
 *
 * @since 0.1.0
 *
 * @param string $close_html Closing html markup.
 *
 * @return string
 */
function mai_after_title_area_hook( $close_html ) {
	if ( $close_html ) {
		ob_start();
		do_action( 'mai_after_title_area' );
		$close_html = $close_html . ob_get_clean();
	}

	return $close_html;
}

add_filter( 'genesis_markup_site-title_content', 'mai_site_title_link' );
/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @param string $default Default site title link.
 *
 * @return string
 */
function mai_site_title_link( $default ) {
	return str_replace( '<a', '<a class="site-title-link" ', $default );
}

add_action( 'mai_before_title_area', 'mai_header_sections' );
add_action( 'mai_after_title_area', 'mai_header_sections' );
/**
 * Adds header left and right sections.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_header_sections() {
	$location = 'header-' . ( did_action( 'genesis_site_title' ) ? 'right' : 'left' );
	$action   = str_replace( '-', '_', $location );

	if ( ! is_active_sidebar( $action ) && ! has_nav_menu( $location ) ) {
		return;
	}

	genesis_markup(
		[
			'open'    => '<div %s>',
			'context' => $location,
		]
	);

	// phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
	do_action( 'mai_' . $action );

	genesis_markup(
		[
			'close'   => '</div>',
			'context' => $location,
		]
	);
}

add_filter( 'genesis_attr_header-left', 'mai_header_section_class' );
add_filter( 'genesis_attr_header-right', 'mai_header_section_class' );
/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @param array $atts Element attributes.
 *
 * @return mixed
 */
function mai_header_section_class( $atts ) {
	$atts['class'] = 'header-section ' . $atts['class'];

	return $atts;
}
