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
	add_filter( 'genesis_attr_nav-menu', 'mai_nav_header_attributes' );

	mai_do_header_left();

	$elements = mai_get_option( 'site-header-mobile', mai_get_config( 'settings')['site-header-mobile'] );

	if ( $elements ) {

		$filter = function( $defaults ) {
			$defaults['size'] = '1.5em';
			return $defaults;
		};

		add_filter( 'mai_icon_defaults', $filter );

		$sections = [];
		$left     = [];
		$right    = [];
		$flipped  = array_flip( $elements );

		if ( isset( $flipped['title_area'] ) ) {
			$left     = array_slice( $elements, 0, $flipped['title_area'] );
			$right    = array_slice( $elements, $flipped['title_area'] + 1 );
			$elements = array_values( array_diff( $elements, $left, $right ) );
		}

		if ( $left ) {
			genesis_markup(
				[
					'open'    => '<div %s>',
					'context' => 'header-left-mobile',
					'echo'    => true,
					'atts'    => [
						'class' => 'header-section-mobile header-left-mobile'
					]
				]
			);
			foreach( $left as $index => $element ) {
				$function = "mai_do_{$element}";
				if ( function_exists( $function ) ) {
					$function();
				}
			}
			genesis_markup(
				[
					'close'   => '</div>',
					'context' => 'header-left-mobile',
					'echo'    => true,
				]
			);
		}

		if ( $elements ) {
			foreach( $elements as $index => $element ) {
				$function = "mai_do_{$element}";
				if ( function_exists( $function ) ) {
					$function();
				}
			}
		}

		if ( $right ) {
			genesis_markup(
				[
					'open'    => '<div %s>',
					'context' => 'header-right-mobile',
					'echo'    => true,
					'atts'    => [
						'class' => 'header-section-mobile header-right-mobile'
					]
				]
			);
			foreach( $right as $index => $element ) {
				$function = "mai_do_{$element}";
				if ( function_exists( $function ) ) {
					$function();
				}
			}
			genesis_markup(
				[
					'close'   => '</div>',
					'context' => 'header-right-mobile',
					'echo'    => true,
				]
			);
		}

		remove_filter( 'mai_icon_defaults', $filter );
	}

	mai_do_header_right();

	remove_filter( 'genesis_attr_nav-menu', 'mai_nav_header_attributes' );
}

/**
 * Displays title area.
 *
 * @access private
 *
 * @since 2.11.0
 *
 * @return void
 */
function mai_do_title_area() {
	do_action( 'mai_before_title_area' );

	$class    = 'title-area';
	$elements = mai_get_option( 'site-header-mobile', mai_get_config( 'settings')['site-header-mobile'] );

	if ( $elements ) {
		$first = 'title_area' === reset( $elements );
		$last  = 'title_area' === end( $elements );

		if ( ! ( $first && $last ) ) {
			if ( $first ) {
				$class .= ' title-area-first';
			}
			if ( $last ) {
				$class .= ' title-area-last';
			}
		}
	}

	genesis_markup(
		[
			'open'    => '<div %s>',
			'context' => 'title-area',
			'atts'    => [
				'class' => $class,
			]
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
}

/**
 * Displays mobile menu toggle.
 *
 * @access private
 *
 * @since 2.11.0
 *
 * @return void
 */
function mai_do_menu_toggle() {
	$text = sprintf( '<span class="screen-reader-text">%s</span>', __( 'Menu', 'mai-engine' ) );
	$text = apply_filters( 'mai_menu_toggle_text', $text );

	genesis_markup(
		[
			'open'    => '<button %s>',
			'close'   => '</button>',
			'context' => 'menu-toggle',
			'content' => sprintf( '<span class="menu-toggle-icon"></span>%s', $text ),
			'atts'    => [
				'class'         => 'menu-toggle',
				'aria-expanded' => 'false',
				'aria-pressed'  => 'false',
			],
		]
	);
}

/**
 * Displays mobile header search icon and form.
 *
 * @access private
 *
 * @since 2.11.0
 *
 * @return void
 */
function mai_do_header_search() {
	genesis_markup(
		[
			'open'    => '<div %s>',
			'close'   => '</div>',
			'context' => 'header-search',
			'content' => mai_get_search_icon_form( __( 'Header Search', 'mai-engine' ), 24 ),
			'echo'    => true,
			'atts'    => [
				'class' => 'header-search search-icon-form',
			]
		]
	);
}

/**
 * Displays mobile header custom content.
 *
 * @access private
 *
 * @since 2.11.0
 *
 * @return void
 */
function mai_do_header_content() {
	$content = mai_get_option( 'site-header-mobile-content', mai_get_config( 'settings')['site-header-mobile-content'] );
	$content = trim( $content );

	if ( ! $content ) {
		return;
	}

	genesis_markup(
		[
			'open'    => '<div %s>',
			'close'   => '</div>',
			'context' => 'header-content',
			'content' => do_shortcode( $content ),
			'echo'    => true,
		]
	);
}

/**
 * Add nav-header class to menus added via `mai_get_menu()` function, including `[mai_menu]` shortcode.
 *
 * @since 0.1.0
 *
 * @param array $attributes Header attributes.
 *
 * @return mixed
 */
function mai_nav_header_attributes( $attributes ) {
	$attributes['class'] .= ' nav-header';
	$atts['itemtype']     = 'https://schema.org/SiteNavigationElement';

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

add_action( 'genesis_site_title', 'mai_maybe_do_custom_scroll_logo', 0 );
/**
 * Adds filter on custom logo before site title.
 * Removes filter after the logo is added.
 *
 * @since 2.14.0
 *
 * @return void
 */
function mai_maybe_do_custom_scroll_logo() {
	add_filter( 'get_custom_logo', 'mai_custom_scroll_logo', 10, 2 );

	add_action( 'genesis_site_title', function() {
		remove_filter( 'get_custom_logo', 'mai_custom_scroll_logo', 10, 2 );
	}, 99 );
}

/**
 * Adds an image inline in the site title element for the custom scroll logo.
 *
 * @since 2.14.0
 * @since 2.18.0 Check if existing $html. See #519.
 *
 * @param string $html    The existing logo HTML.
 * @param int    $blog_id The current blog ID in multisite.
 *
 * @return string
 */
function mai_custom_scroll_logo( $html, $blog_id ) {
	if ( ! $html ) {
		return $html;
	}

	$logo_url = mai_get_option( 'logo-scroll' );

	if ( ! $logo_url ) {
		return $html;
	}

	$dom   = mai_get_dom_document( $html );
	$first = mai_get_dom_first_child( $dom );

	if ( $first ) {
		$img = $dom->createElement( 'img' );
		$img->setAttribute( 'class', 'custom-scroll-logo' );
		$img->setAttribute( 'src', esc_url( $logo_url ) );
		$img->setAttribute( 'loading', 'lazy' );
		$img->setAttribute( 'data-pin-nopin', 'true' );
		$first->appendChild( $img );
		$html = $dom->saveHTML();
	}

	return $html;
}

add_filter( 'genesis_site_title_wrap', 'mai_remove_site_title_h1' );
/**
 * Removes h1 site title wrap.
 *
 * @since 2.3.0
 *
 * @return string
 */
function mai_remove_site_title_h1() {
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
 * @since 0.3.0
 *
 * @param array $attributes Current attributes.
 *
 * @return array The modified attributes.
 */
function mai_hide_site_title( $attributes ) {
	if ( has_custom_logo() ) {
		$attributes['class'] .= ' screen-reader-text';
	}

	return $attributes;
}

add_filter( 'genesis_attr_site-description', 'mai_hide_site_description' );
/**
 * Hides site description if using a logo.
 *
 * Adds class for screen readers to site description.
 * This will keep the site description markup but will not have any visual presence on the page.
 *
 * @since 2.3.0 Added check for show_tagline option.
 * @since 0.3.0
 *
 * @param array $attributes Current attributes.
 *
 * @return array The modified attributes.
 */
function mai_hide_site_description( $attributes ) {
	$default = mai_get_config( 'settings' )['logo']['show-tagline'];
	$show    = mai_get_option( 'show-tagline', $default );

	if ( has_custom_logo() || ! $show ) {
		$attributes['class'] .= ' screen-reader-text';
	}

	return $attributes;
}
