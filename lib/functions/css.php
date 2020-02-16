<?php


add_action( 'wp_ajax_mai_generate_css_variables', 'mai_generate_css_variables' );
add_action( 'wp_ajax_nopriv_mai_generate_css_variables', 'mai_generate_css_variables' );
/**
 * Load CSS custom properties.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_generate_css_variables() {
	$nonce = $_REQUEST['wpnonce'];

	if ( ! wp_verify_nonce( $nonce, 'mai-css-variables-nonce' ) ) {
		die( esc_html__( 'Invalid nonce.', 'mai-engine' ) );

	} else {
		header( 'Content-type: text/css; charset: UTF-8' );

		$css  = '';
		$vars = mai_get_css_variables();

		foreach ( $vars as $name => $value ) {
			$css .= '--' . $name . ':' . $value . ';';
		}

		printf( ':root{%s}', $css );
	}

	exit;
}
