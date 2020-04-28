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

	if ( is_null( $is_type_single ) ) {
		$is_type_single = is_front_page() || is_single() || is_page() || is_404() || is_attachment() || is_singular();
	}

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

	if ( is_null( $is_type_archive ) ) {
		$is_type_archive = is_home() || is_post_type_archive() || is_category() || is_tag() || is_tax() || is_author() || is_date() || is_year() || is_month() || is_day() || is_time() || is_archive() || is_search();
	}

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
	global $sidebars_widgets;

	if ( isset( $sidebars_widgets[ $sidebar ][0] ) && strpos( $sidebars_widgets[ $sidebar ][0], $widget ) !== false && is_active_sidebar( $sidebar ) ) {
		return true;
	}

	return false;
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

	if ( is_null( $has_cover_block ) ) {
		$has_cover_block = false;

		if ( ! mai_is_type_single() || ! has_blocks() ) {
			return $has_cover_block;
		}

		$post_object = get_post( get_the_ID() );
		$blocks      = (array) parse_blocks( $post_object->post_content );
		$block_name  = isset( $blocks[0]['blockName'] ) ? $blocks[0]['blockName'] : '';
		$align       = isset( $blocks[0]['attrs']['align'] ) ? $blocks[0]['attrs']['align'] : '';

		if ( 'core/cover' === $block_name || 'full' === $align ) {
			$has_cover_block = true;
		}
	}

	return $has_cover_block;
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

	if ( is_null( $has_sidebar ) ) {
		$has_sidebar = in_array( mai_site_layout(), [ 'content-sidebar', 'sidebar-content' ], true );
	}

	return $has_sidebar;
}

/**
 * Checks if site has boxed container.
 *
 * @since 0.1.0
 *
 * @return bool
 */
function mai_has_boxed_container() {
	return mai_get_option( 'boxed-container', current_theme_supports( 'boxed-container' ) );
}

/**
 * Checks if site has sticky header.
 *
 * @since 0.1.0
 *
 * @return bool
 */
function mai_has_sticky_header() {
	return mai_get_option( 'site-header-sticky', current_theme_supports( 'sticky-header' ) );
}

/**
 * Check of site has transparent header.
 *
 * @since 0.1.0
 *
 * @return bool
 */
function mai_has_transparent_header() {
	return mai_get_option( 'site-header-transparent', current_theme_supports( 'transparent-header' ) );
}

/**
 * Checks if the Page Header is active.
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

	$config = mai_get_config( 'page-header' );

	if ( is_string( $config ) && '*' === $config ) {
		$has_page_header = true;
	}

	if ( isset( $config['archive'] ) && '*' === $config['archive'] && mai_is_type_archive() ) {
		$has_page_header = true;
	}

	if ( isset( $config['single'] ) && '*' === $config['single'] && mai_is_type_archive() ) {
		$has_page_header = true;
	}

	if ( mai_is_type_archive() ) {
		$has_page_header = in_array( mai_get_archive_args_name(), mai_get_page_header_types( 'archive' ) );
	}

	if ( mai_is_type_single() ) {
		$has_page_header = in_array( mai_get_singular_args_name(), mai_get_page_header_types( 'single' ) );

		if ( genesis_entry_header_hidden_on_current_page() ) {
			$has_page_header = false;
		}

		if ( mai_is_element_hidden( 'page_header' ) ) {
			$has_page_header = false;
		}
	}

	return $has_page_header;
}

/**
 * Get single content types that have page header enabled.
 *
 * @since 0.1.0
 *
 * @param string $context Archive or single.
 *
 * @return string|array May be * for all or array of types.
 */
function mai_get_page_header_types( $context ) {
	$config   = mai_get_config( 'page-header' );
	$settings = mai_get_option( 'page-header-' . $context );
	$single   = array_merge( array_values( get_post_types( [ 'public' => true ] ) ), [
		'404',
	] );
	$archive  = array_merge( $single, array_values( get_taxonomies( [ 'public' => true ] ) ), [
		'search',
		'author',
		'date',
	] );
	$default  = [
		'archive' => $archive,
		'single'  => $single,
	];

	if ( '*' === $config || isset( $config[ $context ] ) && '*' === $config[ $context ] ) {
		$types = $default[ $context ];
	}

	if ( isset( $config[ $context ] ) && is_array( $config[ $context ] ) ) {
		$types = $config[ $context ];
	}

	return $settings ? $settings : $types;
}

/**
 * Checks if a content type has Page Header support.
 *
 * @since 0.1.0
 *
 * @param Kirki_Control_Base $control The customizer field control (not WP_Customize_Control).
 *
 * @return bool
 */
function mai_has_page_header_support_callback( $control ) {
	$types   = [
		'archive' => 'content-archives',
		'single'  => 'single-content',
	];
	$handle  = mai_get_handle();
	$name    = $control->option_name;
	$context = mai_has_string( 'archive', $name ) ? 'archive' : 'single';
	$type    = str_replace( $handle . '[' . $types[ $context ] . '][', '', $name );
	$type    = str_replace( ']', '', $type );

	return in_array( $type, mai_get_page_header_types( $context ) );
}

/**
 * Check is after header menu is active.
 * Need this as it's own function for customizer fields.
 *
 * @since 0.1.0
 *
 * @return bool
 */
function mai_has_after_header_menu() {
	return has_nav_menu( 'after-header' );
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
 *
 * Taken from rest_sanitize_boolean() but seemed risky to use that directly.
 *
 * String values are translated to `true`; make sure 'false' is false.
 *
 * @since  0.1.0
 *
 * @param  string $value String to sanitize.
 *
 * @return bool
 */
function mai_sanitize_bool( $value ) {
	if ( is_string( $value ) ) {
		$value = strtolower( $value );

		if ( in_array( $value, [ 'false', '0' ], true ) ) {
			$value = false;
		}
	}

	// Everything else will map nicely to boolean.
	return (bool) $value;
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param bool $element Element to check.
 *
 * @return mixed
 */
function mai_is_element_hidden( $element ) {

	// Can't be static, entry-title breaks.
	$elements = get_post_meta( get_the_ID(), 'hide_elements', true );

	return in_array( $element, (array) $elements, true );
}

/**
 * Converts a string to different naming conventions.
 *
 * Camel:    myNameIsBond.
 * Pascal:   MyNameIsBond.
 * Snake:    my_name_is_bond.
 * Ada:      My_Name_Is_Bond.
 * Macro:    MY_NAME_IS_BOND.
 * Kebab:    my-name-is-bond.
 * Train:    My-Name-Is-Bond.
 * Cobol:    MY-NAME-IS-BOND.
 * Lower:    my name is bond.
 * Upper:    MY NAME IS BOND.
 * Title:    My Name Is Bond.
 * Sentence: My name is bond.
 * Dot:      my.name.is.bond.
 *
 * @since  1.0.0
 *
 * @author Lee Anthony https://seothemes.com
 *
 * @param string $string String to convert.
 * @param string $case   Naming convention.
 *
 * @return string
 */
function mai_convert_case( $string, $case = 'snake' ) {
	$delimiters = 'sentence' === $case ? [ ' ', '-', '_' ] : [ ' ', '-', '_', '.' ];
	$lower      = trim( str_replace( $delimiters, $delimiters[0], strtolower( $string ) ), $delimiters[0] );
	$upper      = trim( ucwords( $lower ), $delimiters[0] );
	$pieces     = explode( $delimiters[0], $lower );

	$cases = [
		'camel'    => lcfirst( str_replace( ' ', '', $upper ) ),
		'pascal'   => str_replace( ' ', '', $upper ),
		'snake'    => strtolower( implode( '_', $pieces ) ),
		'ada'      => str_replace( ' ', '_', $upper ),
		'macro'    => strtoupper( implode( '_', $pieces ) ),
		'kebab'    => strtolower( implode( '-', $pieces ) ),
		'train'    => lcfirst( str_replace( ' ', '-', $upper ) ),
		'cobol'    => strtoupper( implode( '-', $pieces ) ),
		'lower'    => strtolower( $string ),
		'upper'    => strtoupper( $string ),
		'title'    => $upper,
		'sentence' => ucfirst( $lower ),
		'dot'      => strtolower( implode( '.', $pieces ) ),
	];

	return $cases[ $case ];
}

/**
 * Add classes to an existing string of classes.
 *
 * @since  0.1.0
 *
 * @param  string|array $new      The classes to add.
 * @param  string       $existing The existing classes.
 *
 * @return string  HTML ready classes.
 */
function mai_add_classes( $new, $existing = '' ) {
	if ( ! empty( $new ) ) {
		$space = ! empty( $existing ) ? ' ' : '';
		$new   = is_array( $new ) ? implode( ' ', $new ) : $new;

		return $existing . $space . $new;
	}

	return $existing;
}
