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

add_filter( 'genesis_markup_title-area_close', 'mai_title_area_hook', 10, 1 );
/**
 * Add custom hook after the title area.
 *
 * @since 0.1.0
 *
 * @param string $close_html Closing html markup.
 *
 * @return string
 */
function mai_title_area_hook( $close_html ) {
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
 * @param $atts
 *
 * @return string
 */
function mai_site_title_link( $default ) {
	return str_replace( '<a', '<a class="site-title-link" ', $default );
}

add_action( 'genesis_before_header_wrap', 'mai_before_header_widget' );
/**
 * Displays the before header widget area.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_before_header_widget() {
	genesis_widget_area(
		'before-header',
		[
			'before' => '<div class="before-header"><div class="wrap">',
			'after'  => '</div></div>',
		]
	);
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

add_filter( 'wp_nav_menu_items', 'mai_header_search', 10, 2 );
/**
 * Filter menu items, appending a search form.
 *
 * @since 1.1.0
 *
 * @param string   $menu HTML string of list items.
 * @param stdClass $args Menu arguments.
 *
 * @return string Amended HTML string of list items.
 */
function mai_header_search( $menu, $args ) {
	$settings = get_theme_mod( 'header-search', true );

	if ( $settings && 'primary' === $args->theme_location ) {
		$menu .= get_search_form(
			[
				'echo' => false,
			]
		);
		$menu .= sprintf(
			'<li class="menu-item"><button class="header-search-toggle" onclick="toggle(\'header-search-form\')"><i class="fas fa-search"><span class="screen-reader-text">%s</span></i></button></li>',
			__( 'Toggle header search', 'mai-engine' )
		);
	}

	return $menu;
}

add_filter( 'get_search_form', 'mai_header_search_form' );
/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @param $form
 *
 * @return string
 */
function mai_header_search_form( $form ) {
	if ( ! did_action( 'genesis_after_header' ) ) {
		$form = str_replace( 'class="search-form"', 'class="header-search-form"', $form );
	}

	return $form;
}
