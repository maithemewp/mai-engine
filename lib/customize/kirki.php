<?php

// Disable kirki telemetry.
add_filter( 'kirki_telemetry', '__return_false' );

add_action( 'genesis_setup', 'mai_kirki_filters' );
/**
 * Add miscellaneous Kirki filters after setup.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_kirki_filters() {
	add_filter( 'kirki/dynamic_css/method', '__return_true' );

	add_filter( 'kirki_gutenberg_' . mai_get_handle() . '_dynamic_css', function () {
		return home_url( '?action=kirki-styles' );
	} );

	if ( ! is_customize_preview() ) {
		add_filter( 'kirki_output_inline_styles', '__return_false' );
	}
}

add_action( 'genesis_setup', 'mai_add_kirki_config' );
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

	\Kirki::add_config( $handle, [
		'capability'        => 'edit_theme_options',
		'option_type'       => 'option',
		'option_name'       => $handle,
		'gutenberg_support' => true,
		'disable_output'    => false,
	] );
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
	return wp_parse_args( [
		'disable_loader' => true,
	], $config );
}

add_filter( 'kirki/config', 'mai_kirki_url', 100 );
/**
 * Manually set the Kirki URL.
 *
 * @since 1.0.0
 *
 * @param array $config The configuration array.
 *
 * @return array
 */
function mai_kirki_url( $config ) {
	$config['url_path'] = mai_get_url() . 'vendor/aristath/kirki';

	return $config;
}
