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

add_filter( 'login_body_class', 'mai_login_body_class' );
/**
 * Adds login body class if dark background.
 *
 * @since 2.14.0
 *
 * @param array $classes The existing classes.
 *
 * @return array
 */
function mai_login_body_class( $classes ) {
	$colors = mai_get_colors();

	if ( mai_is_light_color( $colors['header'] ) ) {
		return $classes;
	}

	$classes[] = 'has-dark-background';

	return $classes;
}

add_action( 'login_head', 'mai_login_css', 99 );
/**
 * Adds inline theme styles.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_login_css() {
	if ( class_exists( 'Kirki_Modules_CSS' ) ) {
		$css = Kirki_Modules_CSS::get_instance();
		$css->print_styles_inline();
	}

	$logo_id           = get_theme_mod( 'custom_logo' );
	$header_background = mai_get_color_value( 'header' );
	?>
	<style>
		body.login {
			--system-font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif;
			--body-font-family: var(--system-font-family);
			--heading-font-family: var(--system-font-family);
			color: var(--site-header-color, var(--color-body));
			background: <?php echo $header_background; ?>;
		}
		body.login.has-dark-background {
			color: var(--color-white);
		}
		body.login.has-dark-background a:not(form a),
		body.login.has-dark-background .mai-login-logo a,
		body.login.has-dark-background #nav a,
		body.login.has-dark-background #backtoblog a {
			color: var(--color-white);
		}
		body.login #login {
			margin: 16vh auto 36px !important;
			padding: 1vw 0 0;
		}
		.login .message,
		.login .success,
		.login #login_error {
			color: var(--color-body);
		}
		.login .message a,
		.login .success a,
		.login #login_error a {
			color: var(--color-link);
		}
		body.login.wp-core-ui .button {
			font-size: 13px;
			font-family: var(--system-font-family);
			color: var(--button-color);
			background: var(--button-background, var(--color-primary));
			padding: 10px 16px;
			border: var(--button-border, 0);
			border-radius: var(--button-border-radius, var(--border-radius));
			--button-transform: none;
			--button-transform-hover: none;

		}
		body.login.wp-core-ui .button:hover,
		body.login.wp-core-ui .button:focus {
			color: var(--button-color-hover, var(--button-color));
			background: var(--button-background-hover, var(--color-primary-dark, var(--button-background, var(--color-primary))));
			border: var(--button-border-hover, var(--button-border, 0));
			box-shadow: var(--button-box-shadow-hover, var(--button-box-shadow, none));
		}
		body.login.wp-core-ui .button:not(.button-primary),
		body.login.wp-core-ui .button-secondary {
			--button-color: var(--button-secondary-color);
			--button-color-hover: var(--button-secondary-color-hover, var(--button-secondary-color));
			--button-background: var(--button-secondary-background, var(--color-secondary));
			--button-background-hover: var(--button-secondary-background-hover, var(--color-secondary-dark, var(--color-secondary)));
		}
		body.login form {
			padding: var(--spacing-lg);
			color: var(--color-body);
			background: var(--color-white);
			border: var(--border);
			border-radius: var(--border-radius);
			box-shadow: var(--shadow);
		}
		body.login.has-dark-background form {
			border: 0;
		}
		body.login input[type="text"],
		body.login input[type="password"],
		body.login input[type="color"],
		body.login input[type="date"],
		body.login input[type="datetime"],
		body.login input[type="datetime-local"],
		body.login input[type="email"],
		body.login input[type="month"],
		body.login input[type="number"],
		body.login input[type="search"],
		body.login input[type="tel"],
		body.login input[type="time"],
		body.login input[type="url"],
		body.login input[type="week"],
		body.login textarea {
			padding: 16px;
			background: var(--input-background-color, var(--color-white));
		}
		body.login input[type="text"],
		body.login input[type="password"],
		body.login input[type="color"],
		body.login input[type="date"],
		body.login input[type="datetime"],
		body.login input[type="datetime-local"],
		body.login input[type="email"],
		body.login input[type="month"],
		body.login input[type="number"],
		body.login input[type="search"],
		body.login input[type="tel"],
		body.login input[type="time"],
		body.login input[type="url"],
		body.login input[type="week"],
		body.login select,
		body.login textarea {
			line-height: var(--input-line-height, 1);
			font-size: 18px;
			color: var(--input-color, var(--color-body));
			border: var(--input-border, 1px solid rgba(0,0,0,.1));
			border-radius: var(--input-border-radius, var(--border-radius));
		}
		body.login input[type="color"]:focus,
		body.login input[type="date"]:focus,
		body.login input[type="datetime-local"]:focus,
		body.login input[type="datetime"]:focus,
		body.login input[type="email"]:focus,
		body.login input[type="month"]:focus,
		body.login input[type="number"]:focus,
		body.login input[type="password"]:focus,
		body.login input[type="search"]:focus,
		body.login input[type="tel"]:focus,
		body.login input[type="text"]:focus,
		body.login input[type="time"]:focus,
		body.login input[type="url"]:focus,
		body.login input[type="week"]:focus,
		body.login inputinput:not([type]):focus,
		body.login inputoptgroup:focus,
		body.login inputselect:focus,
		body.login inputtextarea:focus {
			border-color: var(--input-border-color-focus, var(--color-link));
			box-shadow: none;
			outline: none;
		}
		body.login.wp-core-ui .button.wp-hide-pw,
		body.login.wp-core-ui .button.wp-hide-pw:hover,
		body.login.wp-core-ui .button.wp-hide-pw:focus,
		body.login.wp-core-ui .button.wp-hide-pw:active {
			--button-color: var(--color-primary);
			--button-color-hover: var(--color-primary-dark);
			--button-background: transparent;
			--button-background-hover: transparent;
			--button-border: 0;
			--button-border-hover: 0;
			--button-box-shadow: none;
			--button-box-shadow-hover: 0;
			--button-transform-hover: 0;
			top: 8px;
			padding: 0;
		}
		body.login #login form p.forgetmenot,
		body.login #login form p.submit {
			max-width: 50%;
		}
		body.login #login form p.forgetmenot {
			float: left;
			display: flex;
			align-items: center;
			margin-top: 18px;
		}
		body.login #login form p.forgetmenot #rememberme {
			margin-right: 8px;
		}
		body.login #login form p.submit {
			float: right;
			margin-top: 8px;
		}
		body.login #login form p.forgetmenot,
		body.login #login form p.submit .button {
			line-height: 1.625;
		}
		body.login #login form p.submit.reset-pass-submit {
			float: none;
			display: flex;
			flex-flow: row nowrap;
			justify-content: space-between;
			max-width: 100%;
		}
		body.login #login form p.submit.reset-pass-submit button,
		body.login #login form p.submit.reset-pass-submit input {
			padding: 6px 10px;
		}
		body.login #login form p.submit.reset-pass-submit input {
			margin-left: 6px;
		}
		<?php
		$width_px = '100%';
		$logo_id  = get_theme_mod( 'custom_logo' );

		if ( $logo_id ) {
			$widths   = mai_get_option( 'logo-width', [] );
			$width_px = isset( $widths['desktop'] ) && ! empty( $widths['desktop'] ) ? mai_get_unit_value( $widths['desktop'] ) : '180px';
		}
		?>
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
		.login .mai-login-logo {
			text-align: center;
		}
		.login .mai-login-logo a {
			display: block;
			max-width: <?php echo $width_px; ?>;
			margin: auto;
			color: var(--color-heading);
			text-decoration: none;
		}
		.login .mai-login-logo a:hover,
		.login .mai-login-logo a:focus {
			color: var(--color-link);
		}
		.login .mai-login-logo img {
			display: block !important;
			height: auto !important;
			width: 100% !important;
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
		#language-switcher {
			display: flex;
			align-items: center;
		}
		#language-switcher > select,
		#language-switcher > .button {
			min-height: 40px;
			font-size: 14px;
		}
		#language-switcher > .button {
			padding: 4px 12px;
		}
		@media screen and (max-width: 782px) {
			body.login input[type="radio"],
			body.login input[type="checkbox"] {
				height: 1.5625rem;
				width: 1.5625rem;
			}
		}
	</style>
	<?php
	// Add our own site title or inline logo.
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

			$html = $logo_id ? wp_get_attachment_image( $logo_id, 'medium' ) : get_bloginfo( 'name' );

			printf(
				'<h2 class="mai-login-logo"><a href="%s" title="%s" tabindex="-1">%s</a></h2>',
				esc_url( apply_filters( 'login_headerurl', $login_header_url ) ),
				esc_attr( apply_filters( 'login_headertitle', $login_header_title ) ),
				$html
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
