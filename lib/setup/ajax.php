<?php

add_action( 'wp_ajax_mai_setup_wizard', 'mai_setup_wizard_ajax' );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_setup_wizard_ajax() {
	$response = [];
	$option   = get_option( 'mai-setup-wizard', [] );
	$data     = [
		'current_step'  => '',
		'email_address' => '',
		'site_style'    => '',
	];

	foreach ( $data as $key => $value ) {
		if ( isset( $_POST[ $key ] ) ) {
			$data[ $key ] = sanitize_text_field( $_POST[ $key ] );
		}
	}

	$function = "mai_{$data['current_step']}_step_ajax";
	$response = $function( $data, $option, $response );

	wp_send_json( $response );
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param $data
 * @param $option
 * @param $response
 *
 * @return array
 */
function mai_welcome_step_ajax( $data, $option, $response ) {
	if ( $data['email_address'] && is_email( $data['email_address'] ) ) {
		$option['email_address'] = $data['email_address'];

		if ( apply_filters( 'mai_send_email', false ) ) {
			wp_mail(
				'lee@bizbudding.com',
				'Mai Setup Wizard',
				json_encode( $option ),
				[ 'Content-Type: text/html; charset=UTF-8' ]
			);
		}

	} else {
		$option['email_address'] = false;
		$response['error']       = 'Please enter an email address';
	}

	update_option( 'mai-setup-wizard', $option );

	return $response;
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param $data
 * @param $option
 * @param $response
 *
 * @return array
 */
function mai_style_step_ajax( $data, $option, $response ) {
	if ( $data['site_style'] ) {
		$option['site_style']    = $data['site_style'];
		$response['plugin_list'] = mai_get_demo_plugin_list_items( $option['site_style'] );

	} else {
		$response['error'] = __( 'Please select a site style', 'mai-engine' );
	}

	update_option( 'mai-setup-wizard', $option );

	return $response;
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param $data
 * @param $option
 * @param $response
 *
 * @return array
 */
function mai_plugins_step_ajax( $data, $option, $response ) {
	$config  = mai_get_config( 'required-plugins' );
	$plugins = [];

	if ( isset( $_POST['plugins'] ) ) {
		foreach ( $config as $plugin ) {
			if ( in_array( $plugin['slug'], $_POST['plugins'], true ) ) {
				$plugins[] = $plugin;
			}
		}

		foreach ( $plugins as $step => $plugin ) {
			genesis_onboarding_install_dependencies( $plugins, $step );
		}
	}

	$option['plugins'] = $plugins;

	update_option( 'mai-setup-wizard', $option );

	return $response;
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param $data
 * @param $option
 * @param $response
 *
 * @return array
 */
function mai_content_step_ajax( $data, $option, $response ) {
	$content = [];
	$theme   = mai_get_active_theme();

	do_action( 'mai_before_demo_import' );

	if ( isset( $_POST['content'] ) && isset( $option['site_style'] ) && $option['site_style'] ) {
		$demo_data = mai_fetch_demo_data( $theme, $option['site_style'] );

		if ( isset( $demo_data['error'] ) ) {
			$response['error'] = $demo_data['error'];

		} else {
			foreach ( $_POST['content'] as $type ) {
				$content[] = sanitize_text_field( $type );
				$types     = mai_get_demo_data_types();
				$handle    = mai_get_handle();
				$file      = WP_CONTENT_DIR . "/{$handle}/{$type}.{$types[$type]}";
				$function  = "mai_import_demo_data_$type";

				$function( $file );

				$option['content_imported'][] = $type;
			}
		}
	}

	$option['content'] = $content;

	update_option( 'mai-setup-wizard', $option );

	do_action( 'mai_after_demo_import' );

	return $response;
}
