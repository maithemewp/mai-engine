<?php

namespace MaiSetupWizard\Providers;

use MaiSetupWizard\AbstractServiceProvider;

class Ajax extends AbstractServiceProvider {

	public function add_hooks() {
		\add_action( 'wp_ajax_mai_setup_wizard_welcome', [ $this, 'step_welcome' ] );
		\add_action( 'wp_ajax_mai_setup_wizard_demo', [ $this, 'step_demo' ] );
		\add_action( 'wp_ajax_mai_setup_wizard_plugins', [ $this, 'step_plugins' ] );
		\add_action( 'wp_ajax_mai_setup_wizard_content', [ $this, 'step_content' ] );
		\add_action( 'wp_ajax_mai_setup_wizard_done', [ $this, 'step_done' ] );
	}

	private function get_field() {
		return isset( $_POST['field'] ) ? $this->sanitize_field( $_POST['field'] ) : [];
	}

	private function sanitize_field( $field ) {
		foreach ( $field as $attr => $value ) {
			$field[ $attr ] = \sanitize_text_field( $value );
		}

		if ( isset( $field['type'] ) && 'checkbox' === $field['type'] ) {
			if ( ! isset( $field['checked'] ) || ! $field['checked'] ) {
				$field = [];
			}
		}

		return $field;
	}

	public function step_welcome() {
		$field         = $this->get_field();
		$email_address = isset( $field['value'] ) ? \sanitize_email( $field['value'] ) : false;

		if ( ! $email_address ) {
			\wp_send_json_error( __( 'Email address can not be empty.', 'mai-setup-wizard' ) );
		}

		if ( ! \is_email( $email_address ) ) {
			\wp_send_json_error( __( 'Please enter a valid email address.', 'mai-setup-wizard' ) );
		}

		$email = \apply_filters( 'mai_setup_wizard_email', [
			'to'          => \apply_filters( 'mai_setup_wizard_email_address', 'seothemeswp@gmail.com' ),
			'subject'     => $this->plugin->name,
			'message'     => $email_address,
			'headers'     => [ 'Content-Type: text/html; charset=UTF-8' ],
			'attachments' => [],
			'send'        => false,
		] );

		if ( $email['send'] ) {
			\wp_mail( ...\array_values( $email ) );

			\wp_send_json_success( __( 'Email address sent.', 'mai-setup-wizard' ) );
		}

		\wp_send_json_success( __( 'Email entered but not sent.', 'mai-setup-wizard' ) );
	}

	public function step_demo() {
		$field = $this->get_field();
		$demo  = isset( $field['value'] ) ? $field['value'] : false;

		if ( $demo ) {
			$options         = \get_option( $this->plugin->slug, [] );
			$options['demo'] = $demo;

			\update_option( $this->plugin->slug, $options );
			\wp_send_json_success( $demo . __( ' selected.', 'mai-setup-wizard' ) );

		} else {
			\wp_send_json_error( __( 'No demo selected.', 'mai-setup-wizard' ) );
		}
	}

	public function step_plugins() {
		$field = $this->get_field();
		$slug  = isset( $field['value'] ) ? $field['value'] : false;

		if ( ! $slug ) {
			\wp_send_json_success( __( 'No plugins selected.', 'mai-setup-wizard' ) );
		}

		\set_time_limit( 120 );

		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		if ( ! \array_key_exists( $slug, \get_plugins() ) ) {
			$plugin = \plugins_api(
				'plugin_information',
				[
					'slug' => \strtok( $slug, '/' ),
				]
			);

			if ( \is_wp_error( $plugin ) ) {
				\wp_send_json_error( __( 'Error object from API communication for plugin ', 'mai-setup-wizard' ) . $slug );
			}

			$upgrader  = new \Plugin_Upgrader( new \WP_Upgrader_Skin() );
			$installed = $upgrader->install( $plugin->download_link );

			if ( \is_wp_error( $installed ) ) {
				\wp_send_json_error( __( 'Error installing ', 'mai-setup-wizard' ) . $slug );
			}
		}

		\activate_plugin( $slug, false, false, true );

		$message = __( 'Installed ', 'mai-setup-wizard' ) . $slug;

		\wp_send_json_success( $message );
	}

	public function step_content() {
		$field        = $this->get_field();
		$content_type = isset( $field['value'] ) ? $field['value'] : false;

		if ( ! $content_type ) {
			\wp_send_json_error( __( 'No field value.', 'mai-setup-wizard' ) );
		}

		\set_time_limit( 120 );

		$this->importer->import( $field['value'] );
	}
}
