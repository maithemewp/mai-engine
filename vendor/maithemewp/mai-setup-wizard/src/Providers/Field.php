<?php

namespace MaiSetupWizard\Providers;

use MaiSetupWizard\AbstractServiceProvider;

class Field extends AbstractServiceProvider {
	public $fields = [];

	public function add_hooks() {
		\add_action( 'init', [ $this, 'add_fields' ], 12 );
	}

	private function get_defaults( $id ) {
		return \apply_filters( 'mai_setup_wizard_field_defaults', [
			'element'    => 'input',
			'content'    => '',
			'label'      => false,
			'img'        => false,
			'order'      => 10,
			'attributes' => [
				'id'      => $id,
				'name'    => $id,
				'type'    => 'text',
				'checked' => false,
			],
		] );
	}

	public function add_fields() {
		foreach ( $this->get_default_fields() as $step => $fields ) {
			if ( empty( $fields ) ) {
				continue;
			}

			foreach ( $fields as $field ) {
				$defaults            = $this->get_defaults( $field['id'] );
				$attributes          = isset( $field['attributes'] ) ? \wp_parse_args( $field['attributes'], $defaults['attributes'] ) : $defaults['attributes'];
				$field               = \wp_parse_args( $field, $defaults );
				$field['attributes'] = $attributes;

				$this->fields[ $step ][ $field['id'] ] = $field;
			}
		}
	}

	public function get_fields( $step ) {
		return isset( $this->fields[ $step ] ) ? $this->fields[ $step ] : [];
	}

	private function get_default_fields() {
		return \apply_filters( 'mai_setup_wizard_fields', [
			'welcome' => $this->get_welcome_fields(),
			'demo'    => $this->get_demo_fields(),
			'plugins' => $this->get_plugins_fields(),
			'content' => $this->get_content_fields(),
			'done'    => $this->get_done_fields(),
		] );
	}

	private function get_welcome_fields() {
		return [
			[
				'id'         => 'email',
				'element'    => 'input',
				'attributes' => [
					'type'        => 'email',
					'placeholder' => __( 'Email address', 'mai-setup-wizard' ),
				],
			],
		];
	}

	private function get_demo_fields() {
		$fields = [];
		$demos  = $this->demo->get_demos();
		$chosen = $this->demo->get_chosen_demo();

		/**
		 * @var Demo $demo
		 */
		foreach ( $demos as $demo ) {
			$fields[] = [
				'id'         => $demo['id'],
				'label'      => \sprintf(
					'<h4>%s</h4>&nbsp;<a href="%s" target="_blank" class="button">%s</a>',
					$demo['name'],
					$demo['preview'],
					__( 'Preview', 'mai-setup-wizard' )
				),
				'img'        => [
					'src'   => $demo['screenshot'],
					'width' => 200,
				],
				'attributes' => [
					'value'   => $demo['id'],
					'name'    => 'demo',
					'type'    => 'radio',
					'checked' => $chosen === $demo['id'] ? 'checked' : false,
				],
			];
		}

		return $fields;
	}

	private function get_plugins_fields() {
		$fields            = [];
		$demos             = $this->demo->get_demos();
		$installed_plugins = \get_plugins();
		$active_plugins    = \get_option( 'active_plugins' );

		foreach ( $demos as $demo ) {
			foreach ( $demo['plugins'] as $plugin ) {
				$id        = \strtolower( \str_replace( ' ', '-', $plugin['name'] ) );
				$installed = \array_key_exists( $plugin['slug'], $installed_plugins );
				$active    = \in_array( $plugin['slug'], $active_plugins, true );
				$label     = ( $active ? __( ' (Active)', 'mai-setup-wizard' ) : ( $installed ? __( ' (Installed but not active)', 'mai-setup-wizard' ) : '' ) );

				if ( \array_key_exists( $id, $fields ) ) {
					$data_attr[] = $demo['id'];
				}

				$fields[] = [
					'id'         => $demo['id'] . '-' . $id,
					'label'      => \sprintf(
						'%s&nbsp;<strong>%s</strong>&nbsp;<a href="%s" target="_blank">%s</a>',
						$plugin['name'],
						'',
						$plugin['uri'],
						__( ' View details', 'mai-setup-wizard' )
					),
					'attributes' => [
						'value'     => $plugin['slug'],
						'name'      => 'plugins',
						'type'      => 'checkbox',
						'checked'   => true,
						'data-demo' => $demo['id'],
					],
				];
			}
		}

		return $fields;
	}

	private function get_content_fields() {
		$fields        = [];
		$demos         = $this->demo->get_demos();
		$content_types = [ 'content', 'widgets', 'customizer' ];

		foreach ( $demos as $demo ) {
			foreach ( $content_types as $content_type ) {
				$fields[] = [
					'id'         => $demo['id'] . '-' . $content_type,
					'label'      => \ucwords( $content_type ) . '<span class="progress"> &nbsp; <span>0</span>%</span>',
					'element'    => 'input',
					'attributes' => [
						'value'     => $content_type,
						'name'      => $content_type,
						'type'      => 'checkbox',
						'checked'   => true,
						'data-demo' => $demo['id'],
					],
				];
			}
		}

		return $fields;
	}

	private function get_done_fields() {
		return [
			[
				'id'         => 'view',
				'element'    => 'a',
				'content'    => __( 'View your site', 'mai-setup-wizard' ),
				'attributes' => [
					'class' => 'button button-primary button-hero',
					'href' => \home_url(),
				],
			],
		];
	}

	public function render( $field ) {
		$skip = [ 'checked', 'disabled', 'required' ];
		$html = '<' . \esc_html( $field['element'] );

		foreach ( $field['attributes'] as $attribute => $value ) {
			if ( ! \in_array( $attribute, $skip, true ) ) {
				$html .= ' ' . \esc_html( $attribute ) . '="' . \esc_attr( $value ) . '"';
			} elseif ( $value ) {
				$html .= ' ' . \esc_html( $attribute );
			}
		}

		$html .= '>' . $field['content'];

		if ( 'input' !== $field['element'] ) {
			$html .= '</' . $field['element'] . '>';
		}

		if ( ! empty( $field['img'] ) ) {
			$html .= '<img';

			foreach ( $field['img'] as $attribute => $value ) {
				$html .= ' ' . \esc_html( $attribute ) . '="' . \esc_attr( $value ) . '"';
			}

			$html .= '>';
		}

		if ( $field['label'] ) {
			$html = \sprintf(
				'<label for="%s">%s<span class="label">%s</span></label>',
				\esc_attr( $field['id'] ),
				$html,
				\wp_kses_post( $field['label'] )
			);
		}

		return \apply_filters( "mai_setup_wizard_render_{$field['id']}_field", $html );
	}
}
