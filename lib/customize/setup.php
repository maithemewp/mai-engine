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

// Skip hidden webfont choices.
add_filter( "kirki_mai-engine_webfonts_skip_hidden", '__return_false' );

add_action( 'after_setup_theme', 'mai_add_kirki_config' );
/**
 * Add Kirki config.
 *
 * @since  0.1.0
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

add_filter( 'kirki/config', 'mai_kirki_config' );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param $config
 *
 * @return mixed
 */
function mai_kirki_config( $config ) {
	$config['disable_loader'] = true;
	$config['url_path']       = mai_get_url() . 'vendor/aristath/kirki';

	return $config;
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
