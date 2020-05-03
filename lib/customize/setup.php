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

// Disable kirki telemetry.
add_filter( 'kirki_telemetry', '__return_false' );

add_action( 'after_setup_theme', 'mai_kirki_filters' );
/**
 * Add miscellaneous Kirki filters after setup.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_kirki_filters() {
	$handle = mai_get_handle();

	add_filter( "kirki_{$handle}_styles", 'mai_remove_kirki_default_output', 10, 1 );
	add_filter( "kirki_${handle}_webfonts_skip_hidden", '__return_false' );
	add_filter( 'kirki/dynamic_css/method', '__return_true' );

	add_filter(
		"kirki_gutenberg_${handle}_dynamic_css",
		function () {
			return home_url( '?action=kirki-styles' );
		}
	);

	if ( ! is_customize_preview() ) {
		add_filter( 'kirki_output_inline_styles', '__return_false' );
	}
}

/**
 * Removes custom properties CSS output when they are the same as the defaults.
 * Skips defaults set in the child theme.
 *
 * @since 1.0.0
 *
 * @param array $css Kirki CSS output.
 *
 * @return array
 */
function mai_remove_kirki_default_output( $css ) {
	if ( ! isset( $css['global'][':root'] ) ) {
		return $css;
	}

	$custom_theme = mai_get_custom_theme_variables();

	foreach ( $css['global'][':root'] as $property => $value ) {
		$name = str_replace( '--color-', '', $property );

		if ( isset( $custom_theme['colors'][ $name ] ) ) {
			continue;
		}

		if ( mai_get_color( $name ) === $value ) {
			unset( $css['global'][':root'][ $property ] );
		}
	}

	return $css;
}

add_action( 'after_setup_theme', 'mai_add_kirki_config' );
/**
 * Add Kirki config.
 *
 * @since  1.0.0
 *
 * @link   https://aristath.github.io/kirki/docs/getting-started/config.html
 *
 * @return void
 */
function mai_add_kirki_config() {
	$handle = mai_get_handle();

	\Kirki::add_config(
		$handle,
		[
			'capability'        => 'edit_theme_options',
			'option_type'       => 'option',
			'option_name'       => $handle,
			'gutenberg_support' => true,
			'disable_output'    => false,
		]
	);

	\Kirki::add_panel(
		$handle,
		[
			'priority' => 150,
			'title'    => esc_html__( 'Theme Settings', 'mai-engine' ),
		]
	);
}

add_filter( 'kirki_config', 'mai_disable_kirki_loader' );
/**
 * Remove Kirki loader icon.
 *
 * @param array $config The configuration array.
 *
 * @return array
 */
function mai_disable_kirki_loader( $config ) {
	return wp_parse_args(
		[
			'disable_loader' => true,
		],
		$config
	);
}

add_filter( 'kirki/config', 'mai_kirki_url', 100 );
/**
 * Manually set the Kirki URL.
 *
 * @since 0.1.0
 *
 * @param array $config The configuration array.
 *
 * @return array
 */
function mai_kirki_url( $config ) {
	$config['url_path'] = mai_get_url() . 'vendor/aristath/kirki';

	return $config;
}

add_action( 'after_setup_theme', 'mai_register_customizer_api' );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_register_customizer_api() {
	$customizer_api = mai_get_instance( Mai_Customizer_API::class );
	$customizer_api->add_panels();
}

add_action( 'customize_register', 'mai_reposition_genesis_customizer_sections' );
/**
 * Move Genesis Customizer sections into our settings panel.
 *
 * @since 1.0.0
 *
 * @param WP_Customize_Manager $wp_customize WP Customize Manager object.
 *
 * @return void
 */
function mai_reposition_genesis_customizer_sections( $wp_customize ) {
	$handle   = mai_get_handle();
	$sections = genesis_get_config( 'customizer-theme-settings' )['genesis']['sections'];

	foreach ( $sections as $id => $data ) {
		$wp_customize->get_section( $id )->panel = $handle;
	}

	$wp_customize->remove_section( 'genesis_single' );
	$wp_customize->remove_section( 'genesis_archives' );
	$wp_customize->remove_section( 'genesis_footer' );
}

add_action( 'customize_register', 'mai_customize_register_posts_per_page', 99 );
/**
 * Adds Posts Per Page option to Customizer > Theme Settings > Content Archives > Default.
 * Saves/manages WP core option.
 *
 * @since 0.1.0
 *
 * @param WP_Customize_Manager $wp_customize WP_Customize_Manager instance.
 *
 * @return void
 */
function mai_customize_register_posts_per_page( $wp_customize ) {
	$wp_customize->add_setting(
		'posts_per_page',
		[
			'default'           => get_option( 'posts_per_page' ),
			'type'              => 'option',
			'sanitize_callback' => 'absint',
		]
	);
	$wp_customize->add_control(
		'posts_per_page',
		[
			'label'    => __( 'Posts Per Page', 'mai-engine' ),
			'section'  => 'mai-engine-content-archives-post',
			'settings' => 'posts_per_page',
			'type'     => 'text',
			'priority' => 99,
		]
	);
}
