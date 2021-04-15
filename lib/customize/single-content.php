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
 * @since 2.4.0 Moved defaults to config.
 * @since 2.4.2 Added $name param.
 *
 * @return array
 */
function mai_get_single_content_settings( $name = 'post' ) {
	static $single_settings = null;

	if ( is_array( $single_settings ) && isset( $single_settings[ $name ] ) ) {
		return $single_settings[ $name ];
	}

	if ( ! is_array( $single_settings ) ) {
		$single_settings = [];
	}

	$config   = mai_get_config( 'settings' )['single-content'];
	$defaults = isset( $config[ $name ] ) ? $config[ $name ] : $config['post'];

	if ( 'post' !== $name ) {
		foreach ( $config[ 'post' ] as $key => $value ) {
			if ( isset( $defaults[ $key ] ) ) {
				continue;
			}
			$defaults[ $key ] = $value;
		}
	}

	$settings = [
		[
			'settings'    => 'show',
			'label'       => __( 'Show', 'mai-engine' ),
			'description' => __( 'Show/hide and re-order entry elements. Click "Toggle Hooks" to show Genesis hooks.', 'mai-engine' ),
			'type'        => 'sortable',
			'sanitize'    => 'esc_html',
			'default'     => $defaults['show'],
			'choices'     => 'mai_get_single_show_choices',
		],
		[
			'settings'        => 'image_orientation',
			'label'           => __( 'Image Orientation', 'mai-engine' ),
			'type'            => 'select',
			'sanitize'        => 'esc_html',
			'default'         => $defaults['image_orientation'],
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
			'label'           => __( 'Image Size', 'mai-engine' ),
			'type'            => 'select',
			'sanitize'        => 'esc_html',
			'default'         => $defaults['image_size'],
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
			'label'           => __( 'Header Meta', 'mai-engine' ),
			'description'     => mai_get_entry_meta_setting_description(),
			'type'            => 'textarea',
			'sanitize'        => 'wp_kses_post',
			'default'         => $defaults['header_meta'],
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
			'label'           => __( 'Footer Meta', 'mai-engine' ),
			'description'     => mai_get_entry_meta_setting_description(),
			'type'            => 'textarea',
			'sanitize'        => 'wp_kses_post',
			'default'         => $defaults['footer_meta'],
			'active_callback' => [
				[
					'setting'  => 'show',
					'operator' => 'contains',
					'value'    => 'footer_meta',
				],
			],
		],
		[
			'settings'        => 'custom_content',
			'label'           => __( 'Custom Content', 'mai-engine' ),
			'type'            => 'textarea',
			'sanitize'        => 'wp_kses_post',
			'default'         => $defaults['custom_content'],
			'active_callback' => [
				[
					'setting'  => 'show',
					'operator' => 'contains',
					'value'    => 'custom_content',
				],
			],
		],
		// [
		// 	'settings'        => 'custom_content_2',
		// 	'label'           => __( 'Custom Content 2', 'mai-engine' ),
		// 	'type'            => 'textarea',
		// 	'sanitize'        => 'wp_kses_post',
		// 	'default'         => $defaults['custom_content_2'],
		// 	'active_callback' => [
		// 		[
		// 			'setting'  => 'show',
		// 			'operator' => 'contains',
		// 			'value'    => 'custom_content_2',
		// 		],
		// 	],
		// ],
		[
			'type'     => 'custom',
			'settings' => 'single-content-field-divider',
			'default'  => '<hr>',
		],
		[
			'type'            => 'image',
			'settings'        => 'page-header-image',
			'label'           => __( 'Page Header default image', 'mai-engine' ),
			'default'         => $defaults['page-header-image'],
			'choices'         => [
				'save_as' => 'id',
			],
			'active_callback' => 'mai_has_page_header_support_callback',
		],
		[
			'settings'        => 'page-header-featured',
			'label'           => __( 'Use featured image as page header image', 'mai-engine' ),
			'type'            => 'checkbox',
			'sanitize'        => 'mai_sanitize_bool',
			'default'         => $defaults['page-header-featured'],
			'active_callback' => 'mai_has_page_header_support_callback',
		],
		[
			'settings'        => 'page-header-background-color',
			'label'           => __( 'Background/overlay color', 'mai-engine' ),
			'type'            => 'color',
			'default'         => $defaults['page-header-background-color'],
			'choices'         => [
				'palettes' => mai_get_color_choices(),
			],
			'active_callback' => 'mai_has_page_header_support_callback',
		],
		[
			'settings'        => 'page-header-overlay-opacity',
			'label'           => __( 'Overlay opacity', 'mai-engine' ),
			'description'     => __( 'The background color opacity when page header has an image. Use 0 for none, and 1 for theme default.', 'mai-engine' ),
			'type'            => 'slider',
			'default'         => $defaults['page-header-overlay-opacity'],
			'choices'         => [
				'min'  => 0,
				'max'  => 1,
				'step' => 0.01,
			],
			'active_callback' => 'mai_has_page_header_support_callback',
		],
		[
			'settings'        => 'page-header-text-color',
			'label'           => __( 'Page header text color', 'mai-engine' ),
			'type'            => 'radio-buttonset',
			'default'         => $defaults['page-header-text-color'],
			'choices'         => [
				''      => __( 'Default', 'mai-engine' ),
				'light' => __( 'Light', 'mai-engine' ),
				'dark'  => __( 'Dark', 'mai-engine' ),
			],
			'active_callback' => 'mai_has_page_header_support_callback',
		],
	];

	$single_settings[ $name ] = apply_filters( 'mai_single_content_settings', $settings, $name );

	return $single_settings[ $name ];
}

add_action( 'init', 'mai_add_single_content_settings' );
/**
 * Adds single content settings.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_add_single_content_settings() {
	$handle   = mai_get_handle();
	$panel    = 'single-content';
	$defaults = mai_get_config( 'settings' )['single-content']['enable'];
	$sections = mai_get_option( 'single-settings', $defaults, false );

	// Remove any content types that no longer exist.
	foreach ( $sections as $index => $section ) {
		if ( post_type_exists( $section ) ) {
			continue;
		}
		unset( $sections[ $index ] );
	}

	Kirki::add_panel(
		"{$handle}-{$panel}",
		[
			'title' => mai_convert_case( $panel, 'title' ),
			'panel' => $handle,
		]
	);

	foreach ( $sections as $section ) {
		if ( post_type_exists( $section ) && $post_type = get_post_type_object( $section ) ) {
			$title = $post_type->labels->singular_name;
		} elseif ( taxonomy_exists( $section ) && $taxonomy = get_taxonomy( $section ) ) {
			$title = $taxonomy->label;
		} else {
			$title = mai_convert_case( $section, 'title' );
		}

		Kirki::add_section(
			"{$handle}-{$panel}-{$section}",
			[
				'title' => $title,
				'panel' => "{$handle}-{$panel}",
			]
		);

		$settings = mai_get_single_content_settings( $section );

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

			Kirki::add_field( $handle, $field );
		}
	}
}
