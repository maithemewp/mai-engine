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

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @return string
 */
function mai_is_in_dev_mode() {
	return genesis_is_in_dev_mode() || defined( 'WP_DEBUG' ) && WP_DEBUG;
}

/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @param string $needle   String to check for.
 * @param string $haystack String to check in.
 *
 * @return string
 */
function mai_has_string( $needle, $haystack ) {
	return false !== strpos( $haystack, $needle );
}

/**
 * Check if were on any type of singular page.
 *
 * @since 0.1.0
 *
 * @return bool
 */
function mai_is_type_single() {
	static $is_type_single = null;
	if ( ! is_null( $is_type_single ) ) {
		return $is_type_single;
	}
	$is_type_single = is_front_page() || is_single() || is_page() || is_404() || is_attachment() || is_singular();
	return $is_type_single;
}

/**
 * Check if were on any type of archive page.
 *
 * @since 0.1.0
 *
 * @return bool
 */
function mai_is_type_archive() {
	static $is_type_archive = null;
	if ( ! is_null( $is_type_archive ) ) {
		return $is_type_archive;
	}
	$is_type_archive = is_home() || is_post_type_archive() || is_category() || is_tag() || is_tax() || is_author() || is_date() || is_year() || is_month() || is_day() || is_time() || is_archive() || is_search();
	return $is_type_archive;
}

/**
 * Checks if given sidebar contains a certain widget.
 *
 * @since  0.1.0
 *
 * @uses   $sidebars_widgets
 *
 * @param string $sidebar Name of sidebar, e.g `primary`.
 * @param string $widget  Widget ID to check, e.g `custom_html`.
 *
 * @return bool
 */
function mai_sidebar_has_widget( $sidebar, $widget ) {
	static $sidebar_has_widget = null;
	if ( ! is_null( $sidebar_has_widget ) ) {
		return $sidebar_has_widget;
	}

	$sidebar_has_widget = false;

	global $sidebars_widgets;

	if ( isset( $sidebars_widgets[ $sidebar ][0] ) && strpos( $sidebars_widgets[ $sidebar ][0], $widget ) !== false && is_active_sidebar( $sidebar ) ) {
		$sidebar_has_widget = true;
	}

	return $sidebar_has_widget;
}

/**
 * Checks if first block is cover.
 *
 * @since 0.1.0
 *
 * @return bool
 */
function mai_has_cover_block() {

	static $has_cover_block = null;
	if ( ! is_null( $has_cover_block ) ) {
		return $has_cover_block;
	}

	$has_cover_block = false;

	if ( ! mai_is_type_single() || ! has_blocks() ) {
		return $has_cover_block;
	}

	$post_object = get_post( get_the_ID() );
	$blocks      = (array) parse_blocks( $post_object->post_content );

	$type  = isset( $blocks[0]['blockName'] ) ?: '';
	$align = isset( $blocks[0]['attrs']['align'] ) ?: '';

	if ( 'core/cover' === $type || 'full' === $align ) {
		$has_cover_block = true;
	}

	return $has_cover_block;
}

/**
 * Checks if current page has the page header enabled.
 *
 * @since 0.1.0
 *
 * @return bool
 */
function mai_has_page_header() {
	static $has_page_header = null;
	if ( ! is_null( $has_page_header ) ) {
		return $has_page_header;
	}
	$has_page_header = in_array( 'has-page-header', get_body_class(), true );
	return $has_page_header;
}

/**
 * Checks if current page has a sidebar.
 *
 * @since 0.1.0
 *
 * @return bool
 */
function mai_has_sidebar() {
	static $has_sidebar = null;
	if ( ! is_null( $has_sidebar ) ) {
		return $has_sidebar;
	}
	$has_sidebar = in_array( mai_site_layout(), [ 'content-sidebar', 'sidebar-content' ] );
	return $has_sidebar;
}

/**
 * Checks if the Page Header is active.
 *
 * @since 0.1.0
 *
 * @return bool
 */
function mai_is_page_header_active() {
	static $page_header_active = null;
	if ( ! is_null( $page_header_active ) ) {
		return $page_header_active;
	}

	$page_header_active = false;
	$post_type          = get_post_type();

	if ( mai_is_type_archive() && post_type_supports( $post_type, 'page-header-archive' ) ) {
		$page_header_active = true;
	}

	if ( mai_is_type_single() && post_type_supports( $post_type, 'page-header-single' ) ) {
		$page_header_active = true;
	}

	if ( ! $post_type && class_exists( 'WooCommerce' ) && is_shop() && post_type_supports( 'product', 'page-header-archive' ) ) {
		$page_header_active = true;
	}

	return $page_header_active;
}

/**
 * Quick and dirty way to mostly minify CSS.
 *
 * @since  0.1.0
 *
 * @author Gary Jones
 *
 * @param string $css CSS to minify.
 *
 * @return string
 */
function mai_minify_css( $css ) {
	$css = preg_replace( '/\s+/', ' ', $css );
	$css = preg_replace( '/(\s+)(\/\*(.*?)\*\/)(\s+)/', '$2', $css );
	$css = preg_replace( '~/\*(?![\!|\*])(.*?)\*/~', '', $css );
	$css = preg_replace( '/;(?=\s*})/', '', $css );
	$css = preg_replace( '/(,|:|;|\{|}|\*\/|>) /', '$1', $css );
	$css = preg_replace( '/ (,|;|\{|}|\(|\)|>)/', '$1', $css );
	$css = preg_replace( '/(:| )0\.([0-9]+)(%|em|ex|px|in|cm|mm|pt|pc)/i', '${1}.${2}${3}', $css );
	$css = preg_replace( '/(:| )(\.?)0(%|em|ex|px|in|cm|mm|pt|pc)/i', '${1}0', $css );
	$css = preg_replace( '/0 0 0 0/', '0', $css );
	$css = preg_replace( '/#([a-f0-9])\\1([a-f0-9])\\2([a-f0-9])\\3/i', '#\1\2\3', $css );

	return trim( $css );
}

/**
 * Sanitize a value. Checks for null/array.
 *
 * @param   string $value      The value to sanitize.
 * @param   string $function   The function to use for escaping.
 * @param   bool   $allow_null Wether to return or escape if the value is.
 *
 * @return  mixed
 */
function mai_sanitize( $value, $function = 'esc_html', $allow_null = false ) {

	// Return null if allowing null.
	if ( is_null( $value ) && $allow_null ) {
		return $value;
	}

	// If array, escape and return it.
	if ( is_array( $value ) ) {
		$escaped = [];
		foreach ( $value as $index => $item ) {
			if ( is_array( $item ) ) {
				$escaped[ $index ] = mai_sanitize( $item, $function );
			} else {
				$item              = trim( $item );
				$escaped[ $index ] = $function( $item );
			}
		}

		return $escaped;
	}

	// Return single value.
	$value   = trim( $value );
	$escaped = $function( $value );

	return $escaped;
}

/**
 * Sanitize a value to boolean.
 * Taken from rest_sanitize_boolean()
 * but seemed risky to use that directly.
 *
 * @since  0.1.0
 *
 * @param  string $value String to sanitize.
 *
 * @return bool
 */
function mai_sanitize_bool( $value ) {
	// String values are translated to `true`; make sure 'false' is false.
	if ( is_string( $value ) ) {
		$value = strtolower( $value );
		if ( in_array( $value, [ 'false', '0' ], true ) ) {
			$value = false;
		}
	}

	// Everything else will map nicely to boolean.
	return (bool) $value;
}
