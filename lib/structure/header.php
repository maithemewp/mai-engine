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

add_action( 'mai_setup', 'mai_site_header_options' );
/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_site_header_options() {
	if ( get_theme_mod( 'transparent_header', 0 ) ) {
		add_theme_support( 'transparent-header' );
	}

	if ( get_theme_mod( 'sticky_header', 0 ) ) {
		add_theme_support( 'sticky-header' );
	}
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

add_filter( 'get_custom_logo', 'mai_custom_logo_size' );
/**
 * Add max-width style to custom logo.
 *
 * @since 0.1.0
 *
 * @param string $html Custom logo HTML output.
 *
 * @return string
 */
function mai_custom_logo_size( $html ) {
	$width  = get_theme_support( 'custom-logo' )[0]['width'];
	$height = get_theme_support( 'custom-logo' )[0]['height'];

	return str_replace( '<img ', '<img style="max-width:' . $width . 'px;max-height:' . $height . 'px"', $html );
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
