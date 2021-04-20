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

add_action( 'genesis_before', 'mai_amp_structure' );
/**
 * Adds all AMP code here.
 * Avoids multiple mai_is_amp() calls inside other functions.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_amp_structure() {
	if ( ! mai_is_amp() ) {
		return;
	}

	remove_action( 'mai_header_left', 'mai_header_left_menu', 15 );
	remove_action( 'mai_header_right', 'mai_header_right_menu' );
	remove_action( 'genesis_after_header', 'mai_after_header_menu' );

	add_action( 'genesis_header', 'mai_do_amp_menu_toggle' );
	add_action( 'genesis_after_footer', 'mai_do_amp_menu_sidebar' );
}

/**
 * Render AMP menu toggle button.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_do_amp_menu_toggle() {
	$menu = mai_get_amp_menu();
	if ( ! $menu ) {
		return;
	}
	echo '<button role="button" on="tap:amp-menu.toggle" tabindex="0" class="amp-menu-toggle has-xs-padding" style="--button-padding:0.5em;' . mai_get_amp_button_styles() . '">';
		echo mai_get_icon(
			[
				'icon'  => 'bars',
				'style' => 'light',
				'size'  => '2em',
			]
		);
	echo '</button>';
}

/**
 * Renders AMP menu.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_do_amp_menu_sidebar() {
	echo mai_get_amp_menu();
}

/**
 * Returns AMP menu HTML.
 *
 * @since 2.7.0
 *
 * @return string
 */
function mai_get_amp_menu() {
	static $amp_menu = null;

	if ( ! is_null( $amp_menu ) ) {
		return $amp_menu;
	}

	$inner = '';
	$items = '';
	$menus = [
		'header-left',
		'header-right',
		'after-header',
	];
	foreach ( $menus as $menu ) {
		if ( ! has_nav_menu( $menu ) ) {
			continue;
		}

		$menu_items = mai_get_menu_items_by_location( $menu );

		if ( ! $menu_items ) {
			continue;
		}

		$opened = false;

		foreach ( $menu_items as $item ) {

			$classes = array_flip( $item->classes );

			if ( isset( $classes['search'] ) ) {
				continue;
			}

			if ( 0 === (int) $item->menu_item_parent ) {
				if ( $opened ) {
					$inner .= '</ul>';
					$opened = false;
				}

				$inner .= mai_get_amp_menu_item( $item );

			} else {
				if ( ! $opened ) {
					$inner .= '<ul style="font-size: 0.9em;--sub-list-margin:var(--spacing-xs) 0 var(--spacing-xs) var(--spacing-sm);--list-padding:0;">';
					$opened = true;
				}

				$inner .= mai_get_amp_menu_item( $item );
			}
		}
	}

	if ( ! $inner ) {
		return $amp_menu;
	}

	$amp_menu .= '<amp-sidebar id="amp-menu" layout="nodisplay" side="right">';
		$amp_menu .= '<button role="button" aria-label="close sidebar" on="tap:amp-menu.toggle" tabindex="0" class="amp-menu-close" style="--button-padding:var(--spacing-lg);' . mai_get_amp_button_styles() . '">';
			$amp_menu .= mai_get_icon(
				[
					'icon'  => 'times',
					'style' => 'light',
					'size'  => '2em',
				]
			);
		$amp_menu .= '</button>';
		$amp_menu .= '<ul class="amp-menu" style="--list-style-type:none;--list-padding:0 var(--spacing-lg) var(--spacing-lg);--list-item-margin:0 0 var(--spacing-xs);--link-color:var(--color-body);">';
			$amp_menu .= $inner;
		$amp_menu .= '</ul>';
	$amp_menu .= '</amp-sidebar>';

	return $amp_menu;
}

/**
 * Returns menu items by meny location slug.
 *
 * @since 2.7.0
 *
 * @param string $location The menu location slug
 *
 * @return string
 */
function mai_get_amp_menu_item( WP_Post $item ) {
	return sprintf(
		'<li class="amp-menu-item"><a href="%s">%s</a></li>',
		esc_url( $item->url ),
		esc_html( $item->title )
	);
}

/**
 * Returns AMP button inline styles.
 *
 * @since 2.7.0
 *
 * @return string
 */
function mai_get_amp_button_styles() {
	return '--button-color:var(--menu-item-link-color,var(--color-heading));--button-color-hover:var(--button-color);--button-background:transparent;--button-background-hover:transparent;--button-border:0;--button-box-shadow:0;';
}

/**
 * Checks if this web page is an AMP URL.
 *
 * @since 2.11.0
 *
 * @return bool
 */
function mai_is_amp() {
	static $amp = null;

	if ( ! is_null( $amp ) ) {
		return $amp;
	}

	$amp = genesis_is_amp();

	return $amp;
}
