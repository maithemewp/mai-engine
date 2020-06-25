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

/**
 * Class Mai_Setup_Wizard_Importer
 */
class Mai_Setup_Wizard_Importer extends Mai_Setup_Wizard_Service_Provider {

	/**
	 * Description of expected behavior.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	private function get_cache_dir() {
		$theme = mai_get_active_theme();
		$demo  = $this->demo->get_chosen_demo();

		return WP_CONTENT_DIR . "/{$this->plugin->slug}/{$theme}/{$demo}/";
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 1.0.0
	 *
	 * @param $content_type
	 *
	 * @return void
	 */
	public function import( $content_type ) {
		$demo_id       = $this->demo->get_chosen_demo();
		$demo          = $this->demo->get_demo( $demo_id );
		$download_file = $demo[ $content_type ];
		$import_file   = $this->get_cache_dir() . basename( $download_file );

		$this->download_file( $download_file );

		if ( 'content' === $content_type ) {
			$this->import_content( $import_file );
		}

		if ( 'customizer' === $content_type ) {
			$this->import_customizer( $import_file );
		}
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 1.0.0
	 *
	 * @param $url
	 *
	 * @return void
	 */
	private function download_file( $url ) {
		$demo = $this->demo->get_chosen_demo();
		$dir  = $this->get_cache_dir();
		$file = $dir . basename( $url );

		// Check if file was modified in the last hour.
		if ( file_exists( $file ) && time() - filemtime( $file ) < 3600 ) {
			return;
		}

		include_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
		global $wp_filesystem;

		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		if ( ! $demo ) {
			wp_send_json_error( __( 'No demo selected.', 'mai-setup-wizard' ) );
		}

		$response = wp_remote_get( $url );

		if ( ! $response ) {
			wp_send_json_error( $url . __( ' could not be retrieved.', 'mai-setup-wizard' ) );
		}

		$body = wp_remote_retrieve_body( $response );

		if ( ! $body ) {
			wp_send_json_error( $url . __( ' response body could not be retrieved.', 'mai-setup-wizard' ) );
		}

		if ( $body ) {
			$wp_filesystem->put_contents( $file, $body );
		}
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 1.0.0
	 *
	 * @param $file
	 *
	 * @return void
	 */
	private function import_content( $file ) {
		if ( ! class_exists( 'WP_Importer' ) ) {
			require_once ABSPATH . '/wp-admin/includes/class-wp-importer.php';
		}

		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . '/wp-admin/includes/image.php';
		}

		if ( ! function_exists( 'wp_read_audio_metadata' ) ) {
			require_once ABSPATH . '/wp-admin/includes/media.php';
		}

		$logger   = new ProteusThemes\WPContentImporter2\WPImporterLogger();
		$importer = new ProteusThemes\WPContentImporter2\Importer( [
			'fetch_attachments' => true,
		], $logger );

		do_action( 'mai_setup_wizard_before_import', $this->demo->get_chosen_demo() );

		$importer->import( $file );

		do_action( 'mai_setup_wizard_after_import', $this->demo->get_chosen_demo() );

		wp_send_json_success( __( 'Finished importing ', 'mai-setup-wizard' ) . basename( $file ) );
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 1.0.0
	 *
	 * @param $file
	 *
	 * @return void
	 */
	private function import_customizer( $file ) {
		global $wp_customize;

		if ( ! file_exists( $file ) ) {
			wp_send_json_error( __( 'Error importing settings! Please try again.', 'mai-setup-wizard' ) );
		}

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( ! class_exists( 'WP_Customize_Setting' ) ) {
			require ABSPATH . 'wp-includes/class-wp-customize-setting.php';
		}

		$template = get_template();
		$raw      = file_get_contents( $file );
		$data     = unserialize( $raw );

		if ( 'array' !== gettype( $data ) ) {
			wp_send_json_error( __( 'Error importing settings! Please check that you uploaded a customizer export file.', 'mai-setup-wizard' ) );
		}

		if ( ! isset( $data['template'] ) || ! isset( $data['mods'] ) ) {
			wp_send_json_error( __( 'Error importing settings! Please check that you uploaded a customizer export file.', 'mai-setup-wizard' ) );
		}

		if ( $data['template'] !== $template ) {
			wp_send_json_error( __( 'Error importing settings! The settings you uploaded are not for the current theme.', 'mai-setup-wizard' ) );
		}

		if ( isset( $_REQUEST['import-images'] ) ) {
			$data['mods'] = $this->import_customizer_images( $data['mods'] );
		}

		if ( isset( $data['options'] ) ) {
			foreach ( $data['options'] as $option_key => $option_value ) {
				$option = new Mai_Setup_Wizard_Customizer_Setting( $wp_customize, $option_key, [
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

		do_action( 'mai_setup_wizard_after_import' );

		wp_send_json_success( __( 'Sucessfully imported customizer.', 'mai-setup-wizard' ) );
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
	private function import_customizer_images( $mods ) {
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
}
