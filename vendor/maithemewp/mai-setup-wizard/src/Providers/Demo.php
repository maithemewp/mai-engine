<?php

namespace MaiSetupWizard\Providers;

use MaiSetupWizard\AbstractServiceProvider;

class Demo extends AbstractServiceProvider {
	public $demos = [];

	public function add_hooks() {
		\add_action( 'init', [ $this, 'add_demos' ], 11 );
	}

	public function add_demos() {
		$demos = \apply_filters( 'mai_setup_wizard_demos', [] );

		foreach ( $demos as $demo ) {
			$this->add_demo( $demo );
		}
	}

	private function add_demo( $args ) {
		$args['id'] = \strtolower( \str_replace( ' ', '-', $args['name'] ) );
		$args       = \wp_parse_args( $args, $this->get_default_args( $args ) );

		$this->demos[] = $args;
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key
	 *
	 * @return array
	 */
	public function get_demo( $key = '' ) {
		$id   = $this->get_chosen_demo();
		$demo = [];

		foreach ( $this->demos as $demo_args ) {
			if ( $id === $demo_args['id'] ) {
				$demo = $demo_args;
			}
		}

		return $key && isset( $demo[ $key ] ) ? $demo[ $key ] : $demo;
	}

	public function get_demos() {
		$demos = [];

		foreach ( $this->demos as $demo ) {
			$demos[] = $demo;
		}

		return $demos;
	}

	public function get_chosen_demo() {
		$options = \get_option( $this->plugin->slug, [] );
		$first   = \reset( $this->demos );

		return isset( $options['demo'] ) ? $options['demo'] : $first['id'];
	}

	private function get_default_args( $args ) {
		return apply_filters( 'mai_setup_wizard_demo_defaults', [
			'content'    => false,
			'widgets'    => false,
			'customizer' => false,
			'preview'    => false,
			'plugins'    => [],
			'screenshot' => isset( $args['screenshot'] ) ? $args['screenshot'] : $this->get_screenshot( $args['preview'] ),
		] );
	}

	private function get_screenshot( $url ) {
		$url       = \urlencode( $url );
		$params    = [
			'w' => 400,
			'h' => 300,
		];
		$src       = 'https://wordpress.com/mshots/v1/' . $url . '?' . \http_build_query( $params, null, '&' );
		$cache_key = \md5( $src );
		$data_uri  = \get_transient( $cache_key );

		if ( ! $data_uri ) {
			$response = \wp_remote_get( $src );

			if ( 200 === \wp_remote_retrieve_response_code( $response ) ) {
				$image_data = \wp_remote_retrieve_body( $response );
				if ( $image_data && \is_string( $image_data ) ) {
					$src = $data_uri = 'data:image/jpeg;base64,' . \base64_encode( $image_data );
					\set_transient( $cache_key, $data_uri, DAY_IN_SECONDS );
				}
			}
		}

		return \esc_attr( $src );
	}
}
