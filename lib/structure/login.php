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

add_action( 'login_head', 'mai_login_logo_css' );
/**
 * Add site logo as login logo.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_login_logo_css() {

	$logo_id = get_theme_mod( 'custom_logo' );

	// Bail if we don't have a custom logo.
	if ( ! $logo_id ) {
		return;
	}

	$widths   = mai_get_option( 'logo-width', [] );
	$width_px = isset( $widths['desktop'] ) && ! empty( $widths['desktop'] ) ? mai_get_unit_value( $widths['desktop'] ) : '180px';

	// Hide the default logo and heading.
	echo "<style>
		.login h1,
		.login h1 a {
			background: none !important;
			position: absolute !important;
			clip: rect(0, 0, 0, 0) !important;
			height: 1px !important;
			width: 1px !important;
			padding: 0 !important;
			margin: 0 !important;
			border: 0 !important;
			overflow: hidden !important;
		}
		.login .mai-login-logo a {
			max-width: {$width_px};
			display: block;
			margin: auto;
		}
		.login .mai-login-logo img {
			display: block !important;
			height: auto !important;
			width: auto !important;
			max-width: 100% !important;
			margin: 0 auto !important;
		}
		.login #login_error,
		.login .message {
			margin-top: 16px !important;
		}
		.login #nav,
		.login #backtoblog {
			text-align: center;
		}
	</style>";

	// Add our own inline logo.
	add_action(
		'login_message',
		function () use ( $logo_id ) {
			// From WP core.
			if ( is_multisite() ) {
				$login_header_url   = network_home_url();
				$login_header_title = get_network()->site_name;
			} else {
				$login_header_url   = __( 'https://wordpress.org/' );
				$login_header_title = __( 'Powered by WordPress' );
			}
			printf(
				'<h2 class="mai-login-logo"><a href="%s" title="%s" tabindex="-1">%s</a></h2>',
				esc_url( apply_filters( 'login_headerurl', $login_header_url ) ),
				esc_attr( apply_filters( 'login_headertitle', $login_header_title ) ),
				wp_get_attachment_image( $logo_id, 'medium' )
			);
		}
	);

}

add_filter( 'login_headerurl', 'mai_login_link' );
/**
 * Change login logo url to home url.
 *
 * @since 0.1.0
 *
 * @return string
 */
function mai_login_link() {
	return home_url();
}
