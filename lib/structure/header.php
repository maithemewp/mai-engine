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

add_filter( 'genesis_attr_title-area', 'mai_title_area_properties' );
/**
 * Add logo width and spacing properties to the header.
 * Inline properties allow us to still use CSS without PHP inline styles.
 *
 * @since 0.1.0
 *
 * @param array $attr The existing attributes.
 *
 * @return array  The modified attributes.
 */
function mai_title_area_properties( $attr ) {
	$values        = get_option( 'mai_header' );
	$values        = $values ?: mai_header_settings_defaults_temp_function();
	$attr['style'] = isset( $attr['style'] ) ? $attr['style'] . ' ' : '';
	$attr['style'] .= sprintf( '--logo-width-large:%s;', mai_get_unit_value( $values['logo_width_large'] ) );
	$attr['style'] .= sprintf( '--logo-top-large:%s;', mai_get_unit_value( $values['logo_spacing_large']['top'] ) );
	$attr['style'] .= sprintf( '--logo-bottom-large:%s;', mai_get_unit_value( $values['logo_spacing_large']['bottom'] ) );
	$attr['style'] .= sprintf( '--logo-width-small:%s;', mai_get_unit_value( $values['logo_width_small'] ) );
	$attr['style'] .= sprintf( '--logo-top-small:%s;', mai_get_unit_value( $values['logo_spacing_small']['top'] ) );
	$attr['style'] .= sprintf( '--logo-bottom-small:%s;', mai_get_unit_value( $values['logo_spacing_small']['bottom'] ) );

	return $attr;
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
