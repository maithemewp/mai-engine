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
		add_action( 'wp_ajax_mai_setup_wizard_demo', [ $this, 'step_demo' ] );
		add_action( 'wp_ajax_mai_setup_wizard_plugins', [ $this, 'step_plugins' ] );
		add_action( 'wp_ajax_mai_setup_wizard_content', [ $this, 'step_content' ] );
		add_action( 'wp_ajax_mai_setup_wizard_done', [ $this, 'step_done' ] );
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
	 *
	 * @return void
	 */
	public function step_plugins() {
		check_ajax_referer( $this->slug, 'nonce' );

		$field = $this->get_field();
		$slug  = isset( $field['value'] ) ? $field['value'] : false;

		if ( ! $slug ) {
			wp_send_json_success( __( 'No plugins selected.', 'mai-engine' ) );
		}

		set_time_limit( apply_filters( 'mai_setup_wizard_time_limit', 300 ) );

		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		if ( ! array_key_exists( $slug, get_plugins() ) ) {
			$plugin = plugins_api(
				'plugin_information',
				[
					'slug' => strtok( $slug, '/' ),
				]
			);

			if ( is_wp_error( $plugin ) ) {
				wp_send_json_error( __( 'Error object from API communication for plugin ', 'mai-engine' ) . $slug );
			}

			$upgrader  = new Plugin_Upgrader( new WP_Upgrader_Skin() );
			$installed = $upgrader->install( $plugin->download_link );

			if ( is_wp_error( $installed ) ) {
				wp_send_json_error( __( 'Error installing ', 'mai-engine' ) . $slug );
			}
		}

		activate_plugin( $slug, false, false, true );

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
