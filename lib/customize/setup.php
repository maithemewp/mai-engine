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

use Kirki\Util\Helper;

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

// Disable kirki telemetry.
add_filter( 'kirki_telemetry', '__return_false' );

// Skip hidden webfont choices.
add_filter( 'kirki_mai-engine_webfonts_skip_hidden', '__return_false' );

add_filter( 'kirki_path_url', 'mai_kirki_path_url', 10, 2 );
/**
 * Uses engine url for kirki assets.
 *
 * @since 2.21.0
 *
 * @param string $url  The existing url.
 * @param string $path The existing path.
 *
 * @return string
 */
function mai_kirki_path_url( $url, $path ) {
	return str_replace( mai_get_dir(), mai_get_url(), $url );
}

add_action( 'init', 'mai_settings_panel', 8 );
/**
 * Add Kirki theme settings panel.
 *
 * @since 2.21.0
 *
 * @return void
 */
function mai_settings_panel() {
	new \Kirki\Panel(
		mai_get_handle(),
		[
			'priority' => 150,
			'title'    => esc_html__( 'Theme Settings', 'mai-engine' ),
		]
	);
}

add_action( 'wp_head', 'mai_kirki_loading_icon', 101 );
/**
 * Adds the Mai Theme icon logo as the loader.
 * Run safter Kirki, and add !important to override.
 * Until Kikri v4 with a filter for this.
 *
 * @since 2.13.0
 *
 * @return void
 */
function mai_kirki_loading_icon() {
	if ( ! is_customize_preview() ) {
		return;
	}
	printf( '<style>.kirki-customizer-loading-wrapper { background-image: url( "%s" ) !important;background-size:50px; }</style>', mai_get_url() . 'assets/svg/mai-logo-icon.svg' );
}

add_action( 'customize_register', 'mai_handle_existing_customizer_sections' );
/**
 * Move Genesis Customizer sections into our settings panel.
 *
 * @since 0.1.0
 *
 * @param WP_Customize_Manager $wp_customize WP Customize Manager object.
 *
 * @return void
 */
function mai_handle_existing_customizer_sections( $wp_customize ) {
	/**
	 * Bail if not in the customizer.
	 * Ajax and other editor stuff were throwing errors here.
	 */
	if ( ! is_customize_preview() ) {
		return;
	}

	$handle   = mai_get_handle();
	$sections = genesis_get_config( 'customizer-theme-settings' )['genesis']['sections'];

	foreach ( $sections as $id => $data ) {
		if ( $wp_customize && isset( $wp_customize->get_section( $id )->panel ) ) {
			$wp_customize->get_section( $id )->panel = $handle;
		}
	}

	$wp_customize->remove_control( 'header_text' );
	$wp_customize->remove_section( 'genesis_layout' );
	$wp_customize->remove_section( 'genesis_single' );
	$wp_customize->remove_section( 'genesis_archives' );
	$wp_customize->remove_section( 'genesis_footer' );

	$wp_customize->get_setting( 'custom_logo' )->transport = 'refresh';
}
