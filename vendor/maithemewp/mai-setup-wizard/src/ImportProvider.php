<?php

namespace MaiSetupWizard;

class ImportProvider extends AbstractServiceProvider {

	public function add_hooks() {
		\add_action( 'mai_setup_wizard_after_import', [ $this, 'after_import' ] );
	}

	private function get_cache_dir() {
		$theme = \mai_get_active_theme();
		$demo  = $this->demo->get_chosen_demo();

		return WP_CONTENT_DIR . "/{$this->plugin->slug}/{$theme}/{$demo}/";
	}

	public function import( $content_type ) {
		$demo_id       = $this->demo->get_chosen_demo();
		$demo          = $this->demo->get_demo( $demo_id );
		$download_file = $demo[ $content_type ];
		$import_file   = $this->get_cache_dir() . \basename( $download_file );

		$this->download_file( $download_file );

		if ( 'content' === $content_type ) {
			$this->import_content( $import_file );
		}

		if ( 'widgets' === $content_type ) {
			$this->import_widgets( $import_file );
		}

		if ( 'customizer' === $content_type ) {
			$this->import_customizer( $import_file );
		}
	}

	private function download_file( $url ) {
		$demo = $this->demo->get_chosen_demo();
		$dir  = $this->get_cache_dir();
		$file = $dir . \basename( $url );

		// Check if file was modified in the last hour.
		if ( \file_exists( $file ) && \time() - \filemtime( $file ) < 3600 ) {
			return;
		}

		include_once ABSPATH . 'wp-admin/includes/file.php';
		\WP_Filesystem();
		global $wp_filesystem;

		if ( ! \is_dir( $dir ) ) {
			\wp_mkdir_p( $dir );
		}

		if ( ! $demo ) {
			\wp_send_json_error( __( 'No demo selected.', 'mai-setup-wizard' ) );
		}

		$response = \wp_remote_get( $url );

		if ( ! $response ) {
			\wp_send_json_error( $url . __( ' could not be retrieved.', 'mai-setup-wizard' ) );
		}

		$body = \wp_remote_retrieve_body( $response );

		if ( ! $body ) {
			\wp_send_json_error( $url . __( ' response body could not be retrived.', 'mai-setup-wizard' ) );
		}

		if ( $body ) {
			$wp_filesystem->put_contents( $file, $body );
		}
	}

	private function import_content( $file ) {
		if ( ! \class_exists( 'WP_Importer' ) ) {
			require_once ABSPATH . '/wp-admin/includes/class-wp-importer.php';
		}

		if ( ! \function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . '/wp-admin/includes/image.php';
		}

		if ( ! \function_exists( 'wp_read_audio_metadata' ) ) {
			require_once ABSPATH . '/wp-admin/includes/media.php';
		}

		$logger   = new \ProteusThemes\WPContentImporter2\WPImporterLogger();
		$importer = new \ProteusThemes\WPContentImporter2\Importer( [
			'fetch_attachments' => true,
		], $logger );

		$importer->import( $file );

		\do_action( 'mai_setup_wizard_after_import' );

		\wp_send_json_success( __( 'Finished importing content.xml file', 'mai-setup-wizard' ) );
	}

	private function import_widgets( $file ) {
		global $wp_registered_sidebars, $wp_registered_widget_controls;

		if ( ! \file_exists( $file ) ) {
			\wp_send_json_error( __( 'Import file could not be found. Please try again.', 'mai-setup-wizard' ) );
		}

		$data = \json_decode( \file_get_contents( $file ) );

		if ( empty( $data ) || ! \is_object( $data ) ) {
			\wp_send_json_error( __( 'Import data could not be read. Please try a different file.', 'mai-setup-wizard' ) );
		}

		$available_widgets   = [];
		$widget_instances    = [];
		$results             = [];
		$widget_message_type = '';
		$widget_message      = '';

		foreach ( $wp_registered_widget_controls as $widget ) {
			if ( ! empty( $widget['id_base'] ) && ! isset( $available_widgets[ $widget['id_base'] ] ) ) {
				$available_widgets[ $widget['id_base'] ]['id_base'] = $widget['id_base'];
				$available_widgets[ $widget['id_base'] ]['name']    = $widget['name'];
			}
		}

		foreach ( $available_widgets as $widget_data ) {
			$widget_instances[ $widget_data['id_base'] ] = \get_option( 'widget_' . $widget_data['id_base'] );
		}

		foreach ( $data as $sidebar_id => $widgets ) {
			if ( 'wp_inactive_widgets' === $sidebar_id ) {
				continue;
			}

			if ( isset( $wp_registered_sidebars[ $sidebar_id ] ) ) {
				$sidebar_available    = true;
				$use_sidebar_id       = $sidebar_id;
				$sidebar_message_type = 'success';
				$sidebar_message      = '';

			} else {
				$sidebar_available    = false;
				$use_sidebar_id       = 'wp_inactive_widgets';
				$sidebar_message_type = 'error';
				$sidebar_message      = \esc_html__( 'Widget area does not exist in theme (using Inactive)', 'mai-setup-wizard' );
			}

			$results[ $sidebar_id ]['name']         = ! empty( $wp_registered_sidebars[ $sidebar_id ]['name'] ) ? $wp_registered_sidebars[ $sidebar_id ]['name'] : $sidebar_id;
			$results[ $sidebar_id ]['message_type'] = $sidebar_message_type;
			$results[ $sidebar_id ]['message']      = $sidebar_message;
			$results[ $sidebar_id ]['widgets']      = [];

			foreach ( $widgets as $widget_instance_id => $widget ) {
				$fail    = false;
				$id_base = \preg_replace( '/-[0-9]+$/', '', $widget_instance_id );
				$widget  = \json_decode( \wp_json_encode( $widget ), true );

				if ( ! $fail && ! isset( $available_widgets[ $id_base ] ) ) {
					$fail                = true;
					$widget_message_type = 'error';
					$widget_message      = \esc_html__( 'Site does not support widget', 'mai-setup-wizard' );
				}

				if ( ! $fail && isset( $widget_instances[ $id_base ] ) ) {
					$sidebars_widgets        = \get_option( 'sidebars_widgets' );
					$sidebar_widgets         = isset( $sidebars_widgets[ $use_sidebar_id ] ) ? $sidebars_widgets[ $use_sidebar_id ] : [];
					$single_widget_instances = ! empty( $widget_instances[ $id_base ] ) ? $widget_instances[ $id_base ] : [];

					foreach ( $single_widget_instances as $check_id => $check_widget ) {
						if ( \in_array( "$id_base-$check_id", $sidebar_widgets, true ) && (array) $widget === $check_widget ) {
							$fail                = true;
							$widget_message_type = 'warning';
							$widget_message      = \esc_html__( 'Widget already exists', 'mai-setup-wizard' );
							break;
						}
					}
				}

				if ( ! $fail ) {
					$single_widget_instances   = \get_option( 'widget_' . $id_base );
					$single_widget_instances   = ! empty( $single_widget_instances ) ? $single_widget_instances : [
						'_multiwidget' => 1,
					];
					$single_widget_instances[] = $widget;

					\end( $single_widget_instances );

					$new_instance_id_number = \key( $single_widget_instances );

					if ( '0' === \strval( $new_instance_id_number ) ) {
						$new_instance_id_number                             = 1;
						$single_widget_instances[ $new_instance_id_number ] = $single_widget_instances[0];
						unset( $single_widget_instances[0] );
					}

					if ( isset( $single_widget_instances['_multiwidget'] ) ) {
						$multiwidget = $single_widget_instances['_multiwidget'];
						unset( $single_widget_instances['_multiwidget'] );
						$single_widget_instances['_multiwidget'] = $multiwidget;
					}

					\update_option( 'widget_' . $id_base, $single_widget_instances );

					$sidebars_widgets = \get_option( 'sidebars_widgets' );

					if ( ! $sidebars_widgets ) {
						$sidebars_widgets = [];
					}

					$new_instance_id = $id_base . '-' . $new_instance_id_number;

					$sidebars_widgets[ $use_sidebar_id ][] = $new_instance_id;

					\update_option( 'sidebars_widgets', $sidebars_widgets );

					if ( $sidebar_available ) {
						$widget_message_type = 'success';
						$widget_message      = \esc_html__( 'Imported', 'mai-setup-wizard' );
					} else {
						$widget_message_type = 'warning';
						$widget_message      = \esc_html__( 'Imported to Inactive', 'mai-setup-wizard' );
					}
				}

				$results[ $sidebar_id ]['widgets'][ $widget_instance_id ]['name']         = isset( $available_widgets[ $id_base ]['name'] ) ? $available_widgets[ $id_base ]['name'] : $id_base;
				$results[ $sidebar_id ]['widgets'][ $widget_instance_id ]['title']        = ! empty( $widget['title'] ) ? $widget['title'] : esc_html__( 'No Title', 'mai-setup-wizard' );
				$results[ $sidebar_id ]['widgets'][ $widget_instance_id ]['message_type'] = $widget_message_type;
				$results[ $sidebar_id ]['widgets'][ $widget_instance_id ]['message']      = $widget_message;
			}
		}

		\do_action( 'mai_setup_wizard_after_import' );

		\wp_send_json_success( __( 'Sucessfully imported widgets.', 'mai-setup-wizard' ) );
	}

	private function import_customizer( $file ) {
		global $wp_customize;

		if ( ! \file_exists( $file ) ) {
			\wp_send_json_error( __( 'Error importing settings! Please try again.', 'mai-setup-wizard' ) );
		}

		if ( ! \function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( ! \class_exists( 'WP_Customize_Setting' ) ) {
			require ABSPATH . 'wp-includes/class-wp-customize-setting.php';
		}

		$template = \get_template();
		$raw      = \file_get_contents( $file );
		$data     = \unserialize( $raw );

		if ( 'array' !== \gettype( $data ) ) {
			\wp_send_json_error( __( 'Error importing settings! Please check that you uploaded a customizer export file.', 'mai-setup-wizard' ) );
		}

		if ( ! isset( $data['template'] ) || ! isset( $data['mods'] ) ) {
			\wp_send_json_error( __( 'Error importing settings! Please check that you uploaded a customizer export file.', 'mai-setup-wizard' ) );
		}

		if ( $data['template'] !== $template ) {
			\wp_send_json_error( __( 'Error importing settings! The settings you uploaded are not for the current theme.', 'mai-setup-wizard' ) );
		}

		if ( isset( $_REQUEST['import-images'] ) ) {
			$data['mods'] = $this->import_customizer_images( $data['mods'] );
		}

		if ( isset( $data['options'] ) ) {
			foreach ( $data['options'] as $option_key => $option_value ) {
				$option = new CustomizeSetting( $wp_customize, $option_key, [
					'default'    => '',
					'type'       => 'option',
					'capability' => 'edit_theme_options',
				] );

				$option->import( $option_value );
			}
		}

		if ( \function_exists( 'wp_update_custom_css_post' ) && isset( $data['wp_css'] ) && '' !== $data['wp_css'] ) {
			\wp_update_custom_css_post( $data['wp_css'] );
		}

		foreach ( $data['mods'] as $key => $val ) {
			\set_theme_mod( $key, $val );
		}

		\do_action( 'mai_setup_wizard_after_import' );

		\wp_send_json_success( __( 'Sucessfully imported customizer.', 'mai-setup-wizard' ) );
	}

	private function import_customizer_images( $mods ) {
		foreach ( $mods as $key => $val ) {
			if ( ! is_string( $val ) || ! \preg_match( '/\.(jpg|jpeg|png|gif)/i', $val ) ) {
				continue;
			}

			if ( ! \function_exists( 'media_handle_sideload' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/media.php' );
				require_once( ABSPATH . 'wp-admin/includes/file.php' );
				require_once( ABSPATH . 'wp-admin/includes/image.php' );
			}

			$data = new \stdClass();

			if ( ! empty( $val ) ) {
				\preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $val, $matches );
				$file_array             = [];
				$file_array['name']     = \basename( $matches[0] );
				$file_array['tmp_name'] = \download_url( $val );

				if ( \is_wp_error( $file_array['tmp_name'] ) ) {
					return $file_array['tmp_name'];
				}

				$id = \media_handle_sideload( $file_array, 0 );

				if ( \is_wp_error( $id ) ) {
					\unlink( $file_array['tmp_name'] );

					return $id;
				}

				$meta                = \wp_get_attachment_metadata( $id );
				$data->attachment_id = $id;
				$data->url           = \wp_get_attachment_url( $id );
				$data->thumbnail_url = \wp_get_attachment_thumb_url( $id );
				$data->height        = $meta['height'];
				$data->width         = $meta['width'];
			}

			if ( ! \is_wp_error( $data ) ) {
				$mods[ $key ] = $data->url;

				if ( isset( $mods[ $key . '_data' ] ) ) {
					$mods[ $key . '_data' ] = $data;
					\update_post_meta( $data->attachment_id, '_wp_attachment_is_custom_header', \get_stylesheet() );
				}
			}
		}

		return $mods;
	}

	function after_import() {

		// Set nav menu locations.
		$menus     = \get_theme_support( 'genesis-menus' )[0];
		$locations = [];

		foreach ( $menus as $id => $name ) {
			$name = 'Footer Menu' === $name ? $name : \str_replace( ' Menu', '', $name );
			$menu = \get_term_by( 'name', $name, 'nav_menu' );

			if ( $menu && isset( $menu->term_id ) ) {
				$locations[ $id ] = $menu->term_id;
			}
		}

		if ( ! empty( $locations ) ) {
			\set_theme_mod( 'nav_menu_locations', $locations );
		}

		// Set static front page.
		\update_option( 'show_on_front', 'page' );

		// Trash default posts and pages.
		$hello_world    = \get_page_by_path( 'hello-world', OBJECT, 'post' );
		$sample_page    = \get_page_by_path( 'sample-page', OBJECT, 'page' );
		$privacy_policy = \get_page_by_path( 'privacy-policy', OBJECT, 'page' );

		if ( $hello_world && isset( $hello_world->ID ) ) {
			\wp_delete_post( $hello_world->ID );
		}

		if ( $sample_page && isset( $sample_page->ID ) ) {
			\wp_delete_post( $sample_page->ID );
		}

		if ( $privacy_policy && isset( $privacy_policy->ID ) ) {
			\wp_delete_post( $privacy_policy->ID );
		}

		// Assign front page and posts page.
		$home = \get_page_by_title( \apply_filters( 'mai_home_page_title', 'Home' ) );
		$blog = \get_page_by_title( \apply_filters( 'mai_blog_page_title', 'Blog' ) );
		$shop = \get_page_by_title( \apply_filters( 'mai_shop_page_title', 'Shop' ) );

		if ( $home ) {
			\update_option( 'page_on_front', $home->ID );
		}

		if ( $blog ) {
			\update_option( 'page_for_posts', $blog->ID );
		}

		if ( $shop ) {
			\update_option( 'woocommerce_shop_page_id', $shop->ID );
		}

		// Update WP Forms settings.
		$wpforms = \get_option( 'wpforms_settings', [] );

		if ( ! isset( $wpforms['disable-css'] ) ) {
			$wpforms['disable-css'] = 2;

			\update_option( 'wpforms_settings', $wpforms );
		}

		/**
		 * WP Rewrite object.
		 *
		 * @var \WP_Rewrite $wp_rewrite WP Rewrite object.
		 */
		global $wp_rewrite;

		// Update permalink structure.
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		$wp_rewrite->flush_rules();
	}
}
