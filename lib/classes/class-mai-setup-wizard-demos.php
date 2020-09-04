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
 * Class Mai_Setup_Wizard_Demos
 */
class Mai_Setup_Wizard_Demos extends Mai_Setup_Wizard_Service_Provider {

	/**
	 * All demos.
	 *
	 * @var array
	 */
	public $all_demos = [];

	/**
	 * Adds hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_hooks() {
		add_action( 'init', [ $this, 'add_demos' ], 11 );
	}

	/**
	 * Adds all demos.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_demos() {
		$demos = apply_filters( 'mai_setup_wizard_demos', [] );

		foreach ( $demos as $demo ) {
			$this->add_demo( $demo );
		}
	}

	/**
	 * Adds a single demo.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Demo arguments.
	 *
	 * @return void
	 */
	private function add_demo( $args ) {
		$args['id'] = strtolower( str_replace( ' ', '-', $args['name'] ) );
		$args       = wp_parse_args( $args, $this->get_default_args( $args ) );

		$this->all_demos[] = $args;
	}

	/**
	 * Returns a single demo.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Demo ID.
	 *
	 * @return array
	 */
	public function get_demo( $key = '' ) {
		$id   = $this->get_chosen_demo();
		$demo = [];

		foreach ( $this->all_demos as $demo_args ) {
			if ( $id === $demo_args['id'] ) {
				$demo = $demo_args;
			}
		}

		return $key && isset( $demo[ $key ] ) ? $demo[ $key ] : $demo;
	}

	/**
	 * Returns all demos.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_demos() {
		$demos = [];

		foreach ( $this->all_demos as $demo ) {
			$demos[] = $demo;
		}

		return $demos;
	}

	/**
	 * Returns the id of the first demo in the array.
	 *
	 * @since 2.0.1
	 *
	 * @return string
	 */
	public function get_default_demo() {
		$first = reset( $this->all_demos );

		return $first['id'];
	}

	/**
	 * Returns the chosen demo, falls back to default demo.
	 *
	 * @since 2.0.1
	 *
	 * @return string
	 */
	public function get_chosen_demo() {
		if ( ! $this->all_demos ) {
			return '';
		}

		$options = get_option( $this->slug, [] );
		$active  = mai_get_active_theme();
		$current = isset( $options['theme'] ) ? $options['theme'] : '';

		/**
		 * Make sure the saved value is for the current theme.
		 * Some testing showed an old value stored differently than the active theme.
		 */
		if ( $current && ( $active === $current ) && isset( $options['demo'] ) ) {
			return $options['demo'];
		}

		return $this->get_default_demo();
	}

	/**
	 * Returns default demo arguments.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Custom arguments.
	 *
	 * @return array
	 */
	private function get_default_args( $args ) {
		return apply_filters(
			'mai_setup_wizard_demo_defaults',
			[
				'content'    => false,
				'widgets'    => false,
				'customizer' => false,
				'preview'    => false,
				'plugins'    => [],
				'screenshot' => isset( $args['screenshot'] ) ? $args['screenshot'] : $this->get_screenshot( $args['preview'] ),
			]
		);
	}

	/**
	 * Returns a demo screenshot.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url Screenshot URL.
	 *
	 * @return string
	 */
	private function get_screenshot( $url ) {
		$url       = rawurlencode( $url );
		$params    = [
			'w' => 400,
			'h' => 300,
		];
		$src       = 'https://wordpress.com/mshots/v1/' . $url . '?' . http_build_query( $params, null, '&' );
		$cache_key = md5( $src );
		$data_uri  = get_transient( $cache_key );

		if ( ! $data_uri ) {
			$response = wp_remote_get( $src );

			if ( 200 === wp_remote_retrieve_response_code( $response ) ) {
				$image_data = wp_remote_retrieve_body( $response );

				if ( $image_data && is_string( $image_data ) ) {
					$data_uri = 'data:image/jpeg;base64,' . base64_encode( $image_data );
					$src      = $data_uri;

					set_transient( $cache_key, $data_uri, DAY_IN_SECONDS );
				}
			}
		}

		return esc_attr( $src );
	}
}
