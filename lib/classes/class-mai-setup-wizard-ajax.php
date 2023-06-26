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

/**
 * Class Mai_Setup_Wizard_Ajax
 */
class Mai_Setup_Wizard_Ajax extends Mai_Setup_Wizard_Service_Provider {

	/**
	 * Adds hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_hooks() {
		add_action( 'wp_ajax_mai_setup_wizard_welcome', [ $this, 'step_welcome' ] );
		add_action( 'wp_ajax_mai_setup_wizard_demo',    [ $this, 'step_demo' ] );
		add_action( 'wp_ajax_mai_setup_wizard_plugins', [ $this, 'step_plugins' ] );
		add_action( 'wp_ajax_mai_setup_wizard_content', [ $this, 'step_content' ] );
		add_action( 'wp_ajax_mai_setup_wizard_done',    [ $this, 'step_done' ] );
	}

	/**
	 * Returns the  sanitized field value passed from ajax.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function get_field() {
		return isset( $_POST['field'] ) ? $this->sanitize_field( $_POST['field'] ) : [];
	}

	/**
	 * Sanitizes passed field.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $field Unsanitized field value.
	 *
	 * @return array
	 */
	private function sanitize_field( $field ) {
		foreach ( $field as $attr => $value ) {
			$field[ $attr ] = sanitize_text_field( $value );
		}

		if ( isset( $field['type'] ) && 'checkbox' === $field['type'] ) {
			if ( ! isset( $field['checked'] ) || ! $field['checked'] ) {
				$field = [];
			}
		}

		return $field;
	}

	/**
	 * Welcome step ajax.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function step_welcome() {
		check_ajax_referer( $this->slug, 'nonce' );

		$field         = $this->get_field();
		$email_address = isset( $field['value'] ) ? sanitize_email( $field['value'] ) : false;

		if ( ! $email_address ) {
			wp_send_json_error( __( 'Email address can not be empty.', 'mai-engine' ) );
		}

		if ( ! is_email( $email_address ) ) {
			wp_send_json_error( __( 'Please enter a valid email address.', 'mai-engine' ) );
		}

		do_action( 'mai_setup_wizard_email_submit', $email_address );

		$email = apply_filters(
			'mai_setup_wizard_email',
			[
				'to'          => apply_filters( 'mai_setup_wizard_email_address', 'seothemeswp@gmail.com' ),
				'subject'     => $this->name,
				'message'     => $email_address,
				'headers'     => [ 'Content-Type: text/html; charset=UTF-8' ],
				'attachments' => [],
				'send'        => false,
			],
			$email_address
		);

		if ( $email['send'] ) {
			wp_mail( ...array_values( $email ) );

			wp_send_json_success( __( 'Email address sent.', 'mai-engine' ) );
		}

		wp_send_json_success( __( 'Email entered but not sent.', 'mai-engine' ) );
	}

	/**
	 * Demo step ajax.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function step_demo() {
		check_ajax_referer( $this->slug, 'nonce' );

		$field = $this->get_field();
		$demo  = isset( $field['value'] ) ? $field['value'] : $this->demos->get_default_demo();

		if ( $demo ) {
			$options          = get_option( $this->slug, [] );
			$options['demo']  = $demo;
			$options['theme'] = mai_get_active_theme();

			update_option( $this->slug, $options );

			wp_send_json_success( $demo . __( ' selected.', 'mai-engine' ) );

		} else {
			wp_send_json_error( __( 'No demo selected.', 'mai-engine' ) );
		}
	}

	/**
	 * Plugin step ajax.
	 *
	 * @since 1.0.0
	 * @since 2.13.0 Switched to WP_Dependency_Installer for plugin install/activation.
	 *
	 * @return void
	 */
	public function step_plugins() {
		check_ajax_referer( $this->slug, 'nonce' );

		if ( ! class_exists( 'WP_Dependency_Installer' ) ) {
			wp_send_json_success( __( 'WP Dependency Installer is missing.', 'mai-engine' ) );
		}

		$field = $this->get_field();
		$slug  = isset( $field['value'] ) ? $field['value'] : false;

		if ( ! $slug ) {
			wp_send_json_success( __( 'No plugins selected.', 'mai-engine' ) );
		}

		do_action( 'mai_setup_wizard_before_plugins_ajax', $slug, $field );

		set_time_limit( apply_filters( 'mai_setup_wizard_time_limit', 300 ) );

		if ( ! is_plugin_active( $slug ) ) {
			$plugins = [];
			$config  = mai_get_config_plugins();

			foreach ( $config as $plugin ) {
				if ( $slug !== $plugin['slug'] ) {
					continue;
				}
				$plugin['required'] = true; // Forces installation.
				$plugins[]          = $plugin;
			}

			if ( $plugins ) {
				// $wpdi = WP_Dependency_Installer::instance();
				// $wpdi->register( $plugins );
				// $wpdi->admin_init();
				WP_Dependency_Installer::instance()->register( $plugins )->admin_init();
			} else {
				wp_send_json_error( __( 'Error installing ', 'mai-engine' ) . $slug );
			}
		}

		$message = __( 'Installed ', 'mai-engine' ) . $slug;

		wp_send_json_success( $message );
	}

	/**
	 * Content step ajax.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function step_content() {
		check_ajax_referer( $this->slug, 'nonce' );

		$field        = $this->get_field();
		$content_type = isset( $field['value'] ) ? $field['value'] : false;

		if ( ! $content_type ) {
			wp_send_json_error( __( 'No field value.', 'mai-engine' ) );
		}

		set_time_limit( apply_filters( 'mai_setup_wizard_time_limit', 300 ) );

		$options          = get_option( $this->slug, [] );
		$options['demo']  = $this->demos->get_chosen_demo();
		$options['theme'] = mai_get_active_theme();

		update_option( $this->slug, $options );

		$this->import->import( $field['value'] );
	}
}
