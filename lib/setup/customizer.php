<?php


/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param $file
 *
 * @return string|null
 */
function mai_import_demo_data_customizer( $file ) {
	global $wp_customize;

	if ( ! file_exists( $file ) ) {
		return new WP_Error( 'mai-demo-customizer-import', __( 'Error importing settings! Please try again.', 'customizer-export-import' ) );
	}

	if ( ! function_exists( 'wp_handle_upload' ) ) {
		require_once ABSPATH . '/wp-admin/includes/file.php';
	}

	if ( ! class_exists( 'WP_Customize_Setting' ) ) {
		require ABSPATH . '/wp-includes/class-wp-customize-setting.php';
	}

	$error    = false;
	$template = get_template();
	$raw      = file_get_contents( $file );
	$data     = unserialize( $raw );

	if ( 'array' !== gettype( $data ) ) {
		$error = __( 'Error importing settings! Please check that you uploaded a customizer export file.', 'customizer-export-import' );
	}

	if ( ! isset( $data['template'] ) || ! isset( $data['mods'] ) ) {
		$error = __( 'Error importing settings! Please check that you uploaded a customizer export file.', 'customizer-export-import' );
	}

	if ( $data['template'] != $template ) {
		$error = __( 'Error importing settings! The settings you uploaded are not for the current theme.', 'customizer-export-import' );
	}

	if ( $error ) {
		return new WP_Error( 'mai-demo-customizer-import', $error );
	}

	if ( isset( $_REQUEST['cei-import-images'] ) ) {
		$data['mods'] = mai_demo_import_customizer_images( $data['mods'] );
	}

	if ( isset( $data['options'] ) ) {
		foreach ( $data['options'] as $option_key => $option_value ) {
			$option = new Mai_Import_Option( $wp_customize, $option_key, [
				'default'    => '',
				'type'       => 'option',
				'capability' => 'edit_theme_options',
			] );

			$option->import( $option_value );
		}
	}

	if ( function_exists( 'wp_update_custom_css_post' ) && isset( $data['wp_css'] ) && '' !== $data['wp_css'] ) {
		wp_update_custom_css_post( $data['wp_css'] );
	}

	foreach ( $data['mods'] as $key => $val ) {
		set_theme_mod( $key, $val );
	}
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param $mods
 *
 * @return mixed
 */
function mai_demo_import_customizer_images( $mods ) {
	foreach ( $mods as $key => $val ) {
		if ( ! is_string( $val ) || ! preg_match( '/\.(jpg|jpeg|png|gif)/i', $val ) ) {
			continue;
		}

		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
		}

		$data = new stdClass();

		if ( ! empty( $val ) ) {
			preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $val, $matches );
			$file_array             = [];
			$file_array['name']     = basename( $matches[0] );
			$file_array['tmp_name'] = download_url( $val );

			if ( is_wp_error( $file_array['tmp_name'] ) ) {
				return $file_array['tmp_name'];
			}

			$id = media_handle_sideload( $file_array, 0 );

			if ( is_wp_error( $id ) ) {
				unlink( $file_array['tmp_name'] );

				return $id;
			}

			$meta                = wp_get_attachment_metadata( $id );
			$data->attachment_id = $id;
			$data->url           = wp_get_attachment_url( $id );
			$data->thumbnail_url = wp_get_attachment_thumb_url( $id );
			$data->height        = $meta['height'];
			$data->width         = $meta['width'];
		}

		if ( ! is_wp_error( $data ) ) {
			$mods[ $key ] = $data->url;

			if ( isset( $mods[ $key . '_data' ] ) ) {
				$mods[ $key . '_data' ] = $data;
				update_post_meta( $data->attachment_id, '_wp_attachment_is_custom_header', get_stylesheet() );
			}
		}
	}

	return $mods;
}
