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
 * Class Mai_Setup_Wizard_Fields
 */
class Mai_Setup_Wizard_Fields extends Mai_Setup_Wizard_Service_Provider {

	/**
	 * All fields.
	 *
	 * @var array
	 */
	public $all_fields = [];

	/**
	 * Add hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_hooks() {
		add_action( 'init', [ $this, 'add_fields' ], 12 );
	}

	/**
	 * Returns default fields.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id Field ID.
	 *
	 * @return array
	 */
	private function get_defaults( $id ) {
		return apply_filters(
			'mai_setup_wizard_field_defaults',
			[
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
			]
		);
	}

	/**
	 * Adds a field.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_fields() {
		foreach ( $this->get_default_fields() as $step => $fields ) {
			if ( empty( $fields ) ) {
				continue;
			}

			foreach ( $fields as $field ) {
				$defaults            = $this->get_defaults( $field['id'] );
				$attributes          = isset( $field['attributes'] ) ? wp_parse_args( $field['attributes'], $defaults['attributes'] ) : $defaults['attributes'];
				$field               = wp_parse_args( $field, $defaults );
				$field['attributes'] = $attributes;

				$this->all_fields[ $step ][ $field['id'] ] = $field;
			}
		}
	}

	/**
	 * Returns all fields.
	 *
	 * @since 1.0.0
	 *
	 * @param string $step Step ID.
	 *
	 * @return array
	 */
	public function get_fields( $step ) {
		return isset( $this->all_fields[ $step ] ) ? $this->all_fields[ $step ] : [];
	}

	/**
	 * Returns default fields for a step.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function get_default_fields() {
		return apply_filters(
			'mai_setup_wizard_fields',
			[
				'welcome' => $this->get_welcome_fields(),
				'demo'    => $this->get_demo_fields(),
				'plugins' => $this->get_plugins_fields(),
				'content' => $this->get_content_fields(),
				'done'    => $this->get_done_fields(),
			]
		);
	}

	/**
	 * Returns welcome step fields.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function get_welcome_fields() {
		return [
			[
				'id'         => 'email',
				'element'    => 'input',
				'attributes' => [
					'type'        => 'email',
					'placeholder' => __( 'Email address', 'mai-engine' ),
				],
			],
		];
	}

	/**
	 * Returns demo step fields.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function get_demo_fields() {
		$fields = [];
		$demos  = $this->demos->get_demos();
		$chosen = $this->demos->get_chosen_demo();

		/**
		 * Demo objects.
		 *
		 * @var Mai_Setup_Wizard_Demos $demo Demo object.
		 */
		foreach ( $demos as $demo ) {
			$fields[] = [
				'id'         => $demo['id'],
				'label'      => sprintf(
					'<h4>%s</h4>&nbsp;<a href="%s" target="_blank" class="button">%s</a>',
					mai_convert_case( $demo['name'], 'title' ),
					$demo['preview'],
					__( 'Preview', 'mai-engine' )
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

	/**
	 * Returns plugin step fields.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function get_plugins_fields() {
		$fields = [];
		$demos  = $this->demos->get_demos();

		foreach ( $demos as $demo ) {
			foreach ( $demo['plugins'] as $plugin ) {
				$id = strtolower( str_replace( ' ', '-', $plugin['name'] ) );

				if ( array_key_exists( $id, $fields ) ) {
					$data_attr[] = $demo['id'];
				}

				$url = isset( $plugin['url'] ) ? $plugin['url'] : $plugin['uri'];

				$fields[] = [
					'id'         => $demo['id'] . '-' . $id,
					'label'      => sprintf(
						'&nbsp;<strong>%s</strong>&nbsp;<a href="%s" target="_blank">%s</a>',
						$plugin['name'],
						esc_url( $url ),
						__( ' View details', 'mai-engine' )
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

	/**
	 * Returns content step fields.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function get_content_fields() {
		$fields        = [];
		$demos         = $this->demos->get_demos();
		$content_types = [
			'content'    => sprintf(
				'<strong>%s</strong> <ul class="step-description"><li>%s</li><li>%s</li></ul>',
				__( 'Content', 'mai-engine' ),
				__( 'Posts, pages, and menus.' ),
				__( 'Existing posts, pages, and menus will not be deleted.', 'mai-engine' )
			),
			'templates'  => sprintf(
				'<strong>%s</strong> <ul class="step-description"><li>%s</li><li>%s</li><li>%s</li></ul>',
				__( 'Content Areas', 'mai-engine' ),
				__( 'Our block-based replacement for widgets.', 'mai-engine' ),
				__( 'Requires "Content" in order to import images.', 'mai-engine' ),
				__( 'Moves any existing template parts into the trash.', 'mai-engine' )
			),
			'customizer' => sprintf(
				'<strong>%s</strong> <ul class="step-description"><li>%s</li></ul>',
				__( 'Customizer Settings', 'mai-engine' ),
				__( 'Layout, archive/single settings, colors, etc.' )
			),
		];

		foreach ( $demos as $demo ) {
			foreach ( $content_types as $content_type => $name ) {

				$fields[] = [
					'id'         => $demo['id'] . '-' . $content_type,
					'label'      => $name . '<span class="progress"> &nbsp; <span>0</span>%</span>',
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

	/**
	 * Returns done step fields.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function get_done_fields() {
		return [
			[
				'id'         => 'view',
				'element'    => 'a',
				'content'    => __( 'View your site', 'mai-engine' ),
				'attributes' => [
					'class' => 'button button-primary button-hero',
					'href'  => home_url(),
				],
			],
		];
	}

	/**
	 * Renders field markup.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field array.
	 *
	 * @return string
	 */
	public function render( $field ) {
		$skip = [ 'checked', 'disabled', 'required' ];
		$html = '<' . esc_html( $field['element'] );

		foreach ( $field['attributes'] as $attribute => $value ) {
			if ( ! in_array( $attribute, $skip, true ) ) {
				$html .= ' ' . esc_html( $attribute ) . '="' . esc_attr( $value ) . '"';
			} elseif ( $value ) {
				$html .= ' ' . esc_html( $attribute );
			}
		}

		$html .= '>' . $field['content'];

		if ( 'input' !== $field['element'] ) {
			$html .= '</' . $field['element'] . '>';
		}

		if ( ! empty( $field['img'] ) ) {
			$html .= '<img';

			foreach ( $field['img'] as $attribute => $value ) {
				$html .= ' ' . esc_html( $attribute ) . '="' . esc_attr( $value ) . '"';
			}

			$html .= '>';
		}

		if ( $field['label'] ) {
			$html = sprintf(
				'<label for="%s">%s<span class="label">%s</span></label>',
				esc_attr( $field['id'] ),
				$html,
				wp_kses_post( $field['label'] )
			);
		}

		return apply_filters( "mai_setup_wizard_render_{$field['id']}_field", $html );
	}
}
