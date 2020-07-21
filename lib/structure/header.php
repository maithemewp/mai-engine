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

/**
 * Always show the site title and description.
 * It's hidden screen-reader-text class if a custom logo is used.
 *
 * @since 0.3.0
 *
 * @return bool
 */
add_filter( 'theme_mod_header_text', '__return_true' );

/**
 * Remove the default Genesis header.
 * This has too much code related to header-right
 * so let's just build our own.
 *
 * @since 0.3.8
 *
 * @return void
 */
remove_action( 'genesis_header', 'genesis_do_header' );

add_action( 'genesis_header', 'mai_do_header' );
/**
 * Display the header content.
 *
 * @since 0.3.8
 *
 * @return void
 */
function mai_do_header() {
	add_filter( 'genesis_attr_nav-menu', 'mai_nav_header_attributes', 10, 3 );
	do_action( 'mai_before_title_area' );

	genesis_markup(
		[
			'open'    => '<div %s>',
			'context' => 'title-area',
		]
	);

	/**
	 * Fires inside the title area, before the site description hook.
	 */
	do_action( 'genesis_site_title' );

	/**
	 * Fires inside the title area, after the site title hook.
	 */
	do_action( 'genesis_site_description' );

	genesis_markup(
		[
			'close'   => '</div>',
			'context' => 'title-area',
		]
	);

	do_action( 'mai_after_title_area' );
	remove_filter( 'genesis_attr_nav-menu', 'mai_nav_header_attributes', 10, 3 );
}

/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @param array  $attributes
 * @param string $context
 * @param array  $params
 *
 * @return mixed
 */
function mai_nav_header_attributes( $attributes, $context, $params ) {
	$attributes['class'] .= ' nav-header';

	return $attributes;
}

add_action( 'genesis_before', 'mai_maybe_hide_site_header' );
/**
 * Hide the site header on specific pages.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_maybe_hide_site_header() {
	if ( ! mai_is_element_hidden( 'site_header' ) ) {
		return;
	}

	remove_action( 'genesis_header', 'genesis_header_markup_open', 5 );
	remove_action( 'genesis_header', 'mai_do_header' );
	remove_action( 'genesis_header', 'genesis_header_markup_close', 15 );
}

add_filter( 'genesis_site_title_wrap', 'mai_remove_site_title_h1' );
/**
 * Removes h1 site title wrap.
 *
 * @since 2.3.0
 *
 * @param string The existing wrap element.
 *
 * @return string
 */
function mai_remove_site_title_h1( $wrap ) {
	return 'p';
}

add_filter( 'genesis_markup_site-title_content', 'mai_site_title_link' );
/**
 * Add site-title-link class.
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

add_action( 'mai_before_title_area', 'mai_do_header_left' );
/**
 * Adds header left section.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_do_header_left() {
	if ( ! has_nav_menu( 'header-left' ) && ! mai_has_template_part( 'header-left' ) && ! is_active_sidebar( 'header-left' ) ) {
		return;
	}

	genesis_markup(
		[
			'open'    => '<div %s>',
			'context' => 'header-left',
			'atts'    => [
				'class' => 'header-section header-left',
			],
		]
	);

	do_action( 'mai_header_left' );

	genesis_markup(
		[
			'close'   => '</div>',
			'context' => 'header-left',
		]
	);
}

add_action( 'mai_after_title_area', 'mai_do_header_right' );
/**
 * Adds header right section.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_do_header_right() {
	if ( ! has_nav_menu( 'header-right' ) && ! mai_has_template_part( 'header-right' ) && ! is_active_sidebar( 'header-right' ) ) {
		return;
	}

	genesis_markup(
		[
			'open'    => '<div %s>',
			'context' => 'header-right',
			'atts'    => [
				'class' => 'header-section header-right',
			],
		]
	);

	do_action( 'mai_header_right' );

	genesis_markup(
		[
			'close'   => '</div>',
			'context' => 'header-right',
		]
	);
}

add_filter( 'genesis_attr_site-title', 'mai_hide_site_title' );
/**
 * Hides site title if using a logo.
 *
 * Adds class for screen readers to site title.
 * This will keep the site title markup but will not have any visual presence on the page.
 *
 * @since   0.3.0
 *
 * @param   array $attributes Current attributes.
 *
 * @return  array  The modified attributes.
 */
function mai_hide_site_title( $attributes ) {
	if ( ! has_custom_logo() ) {
		return $attributes;
	}
	$attributes['class'] .= ' screen-reader-text';

	return $attributes;
}

add_filter( 'genesis_attr_site-description', 'mai_hide_site_description' );
/**
 * Hides site description if using a logo.
 *
 * Adds class for screen readers to site description.
 * This will keep the site description markup but will not have any visual presence on the page.
 *
 * @since  0.3.0
 *
 * @param  array $attributes Current attributes.
 *
 * @return array  The modified attributes.
 */
function mai_hide_site_description( $attributes ) {
	if ( ! has_custom_logo() ) {
		return $attributes;
	}

	$attributes['class'] .= ' screen-reader-text';

	return $attributes;
}
