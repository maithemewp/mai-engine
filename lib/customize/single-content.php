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
 * Returns single content settings.
 *
 * @since 1.0.0
 *
 * @return array
 */
function mai_get_single_content_settings() {
	return [
		[
			'settings'    => 'show',
			'label'       => esc_html__( 'Show', 'mai-engine' ),
			'description' => esc_html__( 'Show/hide and re-order entry elements. Click "Toggle Hooks" to show Genesis hooks.', 'mai-engine' ),
			'type'        => 'sortable',
			'sanitize'    => 'esc_html',
			'default'     => 'mai_get_single_show_defaults',
			'choices'     => 'mai_get_single_show_choices',
		],
		[
			'settings'        => 'image_orientation',
			'label'           => esc_html__( 'Image Orientation', 'mai-engine' ),
			'type'            => 'select',
			'sanitize'        => 'esc_html',
			'default'         => 'landscape',
			'choices'         => mai_get_image_orientation_choices(),
			'active_callback' => [
				[
					'setting'  => 'show',
					'operator' => 'contains',
					'value'    => 'image',
				],
			],
		],
		[
			'settings'        => 'image_size',
			'label'           => esc_html__( 'Image Size', 'mai-engine' ),
			'type'            => 'select',
			'sanitize'        => 'esc_html',
			'default'         => 'landscape-md',
			'choices'         => mai_get_image_size_choices(),
			'active_callback' => [
				[
					'setting'  => 'show',
					'operator' => 'contains',
					'value'    => 'image',
				],
				[
					'setting'  => 'image_orientation',
					'operator' => '==',
					'value'    => 'custom',
				],
			],
		],
		[
			'settings'        => 'header_meta',
			'label'           => esc_html__( 'Header Meta', 'mai-engine' ),
			'type'            => 'text',
			'sanitize'        => 'wp_kses_post',
			'default'         => 'mai_get_header_meta_default',
			'active_callback' => [
				[
					'setting'  => 'show',
					'operator' => 'contains',
					'value'    => 'header_meta',
				],
			],
		],
		[
			'settings'        => 'footer_meta',
			'label'           => esc_html__( 'Footer Meta', 'mai-engine' ),
			'type'            => 'text',
			'sanitize'        => 'wp_kses_post',
			'default'         => 'mai_get_footer_meta_default',
			'active_callback' => [
				[
					'setting'  => 'show',
					'operator' => 'contains',
					'value'    => 'footer_meta',
				],
			],
		],
		[
			'type'    => 'divider',
			'default' => '<hr>',
		],
		[
			'type'            => 'image',
			'settings'        => 'page-header-image',
			'label'           => __( 'Page Header default image', 'mai-engine' ),
			'default'         => '',
			'choices'         => [
				'save_as' => 'id',
			],
			'active_callback' => 'mai_has_page_header_support_callback',
		],
		[
			'settings'        => 'page-header-featured',
			'label'           => esc_html__( 'Use featured image as page header image', 'mai-engine' ),
			'type'            => 'checkbox',
			'sanitize'        => 'mai_sanitize_bool',
			'default'         => false,
			'active_callback' => 'mai_has_page_header_support_callback',
		],
		[
			'settings'        => 'page-header-background-color',
			'label'           => esc_html__( 'Background/overlay color', 'mai-engine' ),
			'type'            => 'color',
			'default'         => '',
			'active_callback' => 'mai_has_page_header_support_callback',
		],
		[
			'settings'        => 'page-header-overlay-opacity',
			'label'           => esc_html__( 'The background color opacity when page header has an image', 'mai-engine' ),
			'type'            => 'slider',
			'default'         => '',
			'choices'         => [
				'min'  => 0,
				'max'  => 1,
				'step' => 0.01,
			],
			'active_callback' => 'mai_has_page_header_support_callback',
		],
		[
			'settings'        => 'page-header-text-color',
			'label'           => esc_html__( 'Page header text color', 'mai-engine' ),
			'type'            => 'radio-buttonset',
			'default'         => '',
			'choices'         => [
				''      => __( 'Default', 'mai-engine' ),
				'light' => __( 'Light', 'mai-engine' ),
				'dark'  => __( 'Dark', 'mai-engine' ),
			],
			'active_callback' => 'mai_has_page_header_support_callback',
		],
	];
}

add_action( 'init', 'mai_add_single_content_settings' );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_add_single_content_settings() {
	$handle   = mai_get_handle();
	$panel    = 'single-content';
	$sections = mai_get_option( 'single-settings', mai_get_config( 'single-settings' ), false );

	\Kirki::add_panel(
		"{$handle}-{$panel}",
		[
			'title' => mai_convert_case( $panel, 'title' ),
			'panel' => $handle,
		]
	);

	foreach ( $sections as $section ) {
		\Kirki::add_section(
			"{$handle}-{$panel}-{$section}",
			[
				'title' => mai_convert_case( $section, 'title' ),
				'panel' => "{$handle}-{$panel}",
			]
		);

		$settings = mai_get_single_content_settings();

		foreach ( $settings as $field ) {
			$settings         = isset( $field['settings'] ) ? $field['settings'] : '';
			$field['section'] = "{$handle}-{$panel}-{$section}";

			if ( $settings ) {
				$field['settings'] = $section . '-' . mai_convert_case( $settings, 'kebab' );
			}

			$field['option_type'] = 'option';
			$field['option_name'] = $handle . '[' . $panel . '][' . $section . ']';
			$field['settings']    = $settings;

			if ( isset( $field['default'] ) && is_string( $field['default'] ) && mai_has_string( 'mai_', $field['default'] ) && is_callable( $field['default'] ) ) {
				$field['default'] = call_user_func_array( $field['default'], [ 'name' => $section ] );
			}

			if ( isset( $field['choices'] ) && is_string( $field['choices'] ) && mai_has_string( 'mai_', $field['choices'] ) && is_callable( $field['choices'] ) ) {
				$field['choices'] = call_user_func_array( $field['choices'], [ 'name' => $section ] );
			}

			if ( isset( $field['sanitize'] ) ) {
				$field['sanitize_callback'] = $field['sanitize'];
			}

			// Workaround to fix active callback function with nested options.
			if ( isset( $field['active_callback'] ) ) {
				if ( is_array( $field['active_callback'] ) ) {
					foreach ( $field['active_callback'] as $index => $condition ) {
						foreach ( $condition as $key => $value ) {
							if ( 'setting' === $key ) {
								$field['active_callback'][ $index ][ $key ] = "{$handle}[$panel][$section][$value]";
							}

							if ( is_array( $value ) ) {
								foreach ( $value as $nested_key => $nested_value ) {
									if ( 'setting' === $nested_key ) {
										$field['active_callback'][ $index ][ $key ][ $nested_key ] = "{$handle}[$panel][$section][$nested_value]";
									}
								}
							}
						}
					}
				}
			}

			\Kirki::add_field( $handle, $field );
		}
	}
}

