<?php

add_action( 'after_setup_theme', 'mai_redirect_to_setup_wizard', 5 );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_redirect_to_setup_wizard() {
	global $pagenow;

	if ( $pagenow === 'admin.php' && isset( $_GET['page'] ) && sanitize_text_field( $_GET['page'] ) === 'mai-demo-import' ) {
		wp_redirect( admin_url( '/admin.php?page=mai-setup-wizard' ) );
		exit;
	}
}

add_filter( 'mai_setup_wizard_menu', 'mai_setup_wizard_menu', 10, 2 );
/**
 * Description of expected behavior.
 *
 * @since 0.3.0
 *
 * @param array $args
 *
 * @return array
 */
function mai_setup_wizard_menu( $args ) {
	$args['parent_slug'] = 'mai-theme';
	$args['menu_slug']   = 'mai-setup-wizard';
	$args['menu_title']  = __( 'Setup Wizard', 'mai-engine' );

	return $args;
}

add_filter( 'mai_setup_wizard_demos', 'mai_setup_wizard_demos', 15, 1 );
/**
 * Description of expected behavior.
 *
 * @since 0.3.0
 *
 * @param $defaults
 *
 * @return array
 */
function mai_setup_wizard_demos( $defaults ) {
	$theme   = mai_get_active_theme();
	$demos   = mai_get_config( 'demos' );
	$config  = mai_get_config( 'plugins' );
	$plugins = [];

	if ( empty( $demos ) ) {
		return [];
	}

	foreach ( $demos as $demo => $id ) {
		$demo_url = "https://demo.bizbudding.com/{$theme}-{$demo}/wp-content/uploads/sites/{$id}/mai-engine/";

		foreach ( $config as $plugin ) {
			if ( in_array( $demo, $plugin['demos'], true ) ) {
				$plugins[] = $plugin;
			}
		}

		$defaults[] = [
			'name'       => ucwords( $demo ),
			'content'    => $demo_url . 'content.xml',
			'widgets'    => $demo_url . 'widgets.json',
			'customizer' => $demo_url . 'customizer.dat',
			'preview'    => "https://demo.bizbudding.com/{$theme}-{$demo}/",
			'plugins'    => $plugins,
		];
	}

	return $defaults;
}

add_action( 'mai_setup_wizard_before_steps', 'mai_setup_wizard_header_content' );
/**
 * Add wizard logo/icon.
 *
 * @since 0.3.0
 *
 * @return void
 */
function mai_setup_wizard_header_content() {
	printf( '<p class="setup-wizard-logo-wrap"><img class="setup-wizard-logo" src="%sassets/img/wizard-icon.png" alt="Mai Theme logo"></p>', mai_get_url() );
}


add_filter( 'mai_setup_wizard_steps', 'mai_setup_wizard_welcome_step_description' );
/**
 * Add additional description text to the welcome step.
 *
 * @since 0.3.0
 *
 * @return string
 */
function mai_setup_wizard_welcome_step_description( $steps ) {
	$text = __( 'Mai Theme Setup Wizard Page', 'mai-engine' );
	$link = sprintf( '<a target="_blank" rel="noopener nofollow" href="https://bizbudding.com/mai-setup-wizard/">%s</a>', $text );
	$steps['welcome']['description'] .= ' ' . sprintf( '%s %s %s.',
		__( 'To learn more about providing your email and claiming your free goodies, visit the', 'mai-engine' ),
		$link,
		__( 'on BizBudding', 'mai-engine' )
	);
	return $steps;
}

add_action( 'mai_setup_wizard_email_submit', 'mai_setup_wizard_email_option' );
/**
 * Send email to subcribe user.
 *
 * @since 0.3.0
 *
 * @return void
 */
function mai_setup_wizard_email_option( $email_address ) {
	$to          = 'subscribe-af4840f00e125c4e59953f0197daf346@subscription-serv.com';
	$subject     = 'mai setup wizard email optin';
	$message     = $email_address;
	$headers     = [];
	$attachments = [];
	$filter      = function( $email ) use ( $email_address ) {
		return $email_address;
	};
	add_filter( 'wp_mail_from', $filter );
	$sent = wp_mail( $to, $subject, $message, $headers, $attachments );
	remove_filter( 'wp_mail_from', $filter );
}
