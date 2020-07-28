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
 * Returns content archive settings.
 *
 * @since 0.1.0
 *
 * @return array
 */
function mai_get_content_archive_settings() {
	return [
		[
			'settings'    => 'show',
			'label'       => esc_html__( 'Show', 'mai-engine' ),
			'description' => esc_html__( 'Show/hide and re-order entry elements. Click "Toggle Hooks" to show Genesis hooks.', 'mai-engine' ),
			'type'        => 'sortable',
			'sanitize'    => 'esc_html',
			'default'     => [
				'image',
				'genesis_entry_header',
				'title',
				'header_meta',
				'genesis_before_entry_content',
				'excerpt',
				'genesis_entry_content',
				'more_link',
				'genesis_after_entry_content',
				'genesis_entry_footer',
			],
			'choices'     => mai_get_archive_show_choices(),
		],
		[
			'settings'        => 'title_size',
			'label'           => esc_html__( 'Title Size', 'mai-engine' ),
			'type'            => 'radio-buttonset',
			'sanitize'        => 'esc_html',
			'default'         => 'lg',
			'choices'         => [
				'sm'  => esc_html__( 'XS', 'mai-engine' ),
				'md'  => esc_html__( 'SM', 'mai-engine' ),
				'lg'  => esc_html__( 'MD', 'mai-engine' ),
				'xl'  => esc_html__( 'LG', 'mai-engine' ),
				'xxl' => esc_html__( 'XL', 'mai-engine' ),
			],
			'active_callback' => [
				[
					[
						'setting'  => 'show',
						'operator' => 'contains',
						'value'    => 'title',
					],
				],
			],
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
			'settings'        => 'image_position',
			'label'           => esc_html__( 'Image Position', 'mai-engine' ),
			'type'            => 'select',
			'sanitize'        => 'esc_html',
			'default'         => 'full',
			'choices'         => [
				'full'         => esc_html__( 'Full', 'mai-engine' ),
				'center'       => esc_html__( 'Center', 'mai-engine' ),
				'left-top'     => esc_html__( 'Left Top', 'mai-engine' ),
				'left-middle'  => esc_html__( 'Left Middle', 'mai-engine' ),
				'left-full'    => esc_html__( 'Left Full', 'mai-engine' ),
				'right-top'    => esc_html__( 'Right Top', 'mai-engine' ),
				'right-middle' => esc_html__( 'Right Middle', 'mai-engine' ),
				'right-full'   => esc_html__( 'Right Full', 'mai-engine' ),
				'background'   => esc_html__( 'Background', 'mai-engine' ),
			],
			'active_callback' => [
				[
					'setting'  => 'show',
					'operator' => 'contains',
					'value'    => 'image',
				],
			],
		],
		[
			'settings'        => 'image_width',
			'label'           => esc_html__( 'Image Width', 'mai-engine' ),
			'type'            => 'radio-buttonset',
			'sanitize'        => 'esc_html',
			'default'         => 'third',
			'choices'         => [
				'fourth' => esc_html__( 'One Fourth', 'mai-engine' ),
				'third'  => esc_html__( 'One Third', 'mai-engine' ),
				'half'   => esc_html__( 'One Half', 'mai-engine' ),
			],
			'active_callback' => [
				[
					[
						'setting'  => 'show',
						'operator' => 'contains',
						'value'    => 'image',
					],
				],
				[
					[
						'setting'  => 'image_position',
						'operator' => '==',
						'value'    => 'left-top',
					],
					[
						'setting'  => 'image_position',
						'operator' => '==',
						'value'    => 'left-middle',
					],
					[
						'setting'  => 'image_position',
						'operator' => '==',
						'value'    => 'left-full',
					],
					[
						'setting'  => 'image_position',
						'operator' => '==',
						'value'    => 'right-top',
					],
					[
						'setting'  => 'image_position',
						'operator' => '==',
						'value'    => 'right-middle',
					],
					[
						'setting'  => 'image_position',
						'operator' => '==',
						'value'    => 'right-full',
					],
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
			'settings'        => 'content_limit',
			'label'           => esc_html__( 'Content Limit', 'mai-engine' ),
			'description'     => esc_html__( 'Limit the number of characters shown for the content or excerpt. Use 0 for no limit.', 'mai-engine' ),
			'type'            => 'text',
			'sanitize'        => 'absint',
			'default'         => 0,
			'active_callback' => [
				[
					[
						'setting'  => 'show',
						'operator' => 'contains',
						'value'    => 'excerpt',
					],
					[
						'setting'  => 'show',
						'operator' => 'contains',
						'value'    => 'content',
					],
				],
			],
		],
		[
			'settings'        => 'more_link_text',
			'label'           => esc_html__( 'More Link Text', 'mai-engine' ),
			'type'            => 'text',
			'sanitize'        => 'esc_attr', // We may want to add icons/spans and HTML in here.
			'default'         => '',
			'active_callback' => [
				[
					'setting'  => 'show',
					'operator' => 'contains',
					'value'    => 'more_link',
				],
			],
			'input_attrs'     => [
				'placeholder' => mai_get_read_more_text(),
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
			'settings' => 'align_text',
			'label'    => esc_html__( 'Align Text', 'mai-engine' ),
			'type'     => 'radio-buttonset',
			'sanitize' => 'esc_html',
			'default'  => 'start',
			'choices'  => [
				'start'  => esc_html__( 'Start', 'mai-engine' ),
				'center' => esc_html__( 'Center', 'mai-engine' ),
				'end'    => esc_html__( 'End', 'mai-engine' ),
			],
		],
		[
			'settings'        => 'align_text_vertical',
			'label'           => esc_html__( 'Align Text (vertical)', 'mai-engine' ),
			'type'            => 'radio-buttonset',
			'sanitize'        => 'esc_html',
			'default'         => '',
			'choices'         => [
				''       => esc_html__( 'Default', 'mai-engine' ),
				'top'    => esc_html__( 'Top', 'mai-engine' ),
				'middle' => esc_html__( 'Middle', 'mai-engine' ),
				'bottom' => esc_html__( 'Bottom', 'mai-engine' ),
			],
			'active_callback' => [
				[
					[
						'setting'  => 'image_position',
						'operator' => '==',
						'value'    => 'left',
					],
					[
						'setting'  => 'image_position',
						'operator' => '==',
						'value'    => 'right',
					],
					[
						'setting'  => 'image_position',
						'operator' => '==',
						'value'    => 'left-top',
					],
					[
						'setting'  => 'image_position',
						'operator' => '==',
						'value'    => 'right-top',
					],
					[
						'setting'  => 'image_position',
						'operator' => '==',
						'value'    => 'left-bottom',
					],
					[
						'setting'  => 'image_position',
						'operator' => '==',
						'value'    => 'right-bottom',
					],
					[
						'setting'  => 'image_position',
						'operator' => '==',
						'value'    => 'left-full',
					],
					[
						'setting'  => 'image_position',
						'operator' => '==',
						'value'    => 'right-full',
					],
					[
						'setting'  => 'image_position',
						'operator' => '==',
						'value'    => 'background',
					],
				],
			],
		],
		[
			'settings'        => 'image_stack_heading',
			'label'           => esc_html__( 'Stack Image', 'mai-engine' ),
			'type'            => 'custom',
			'sanitize'        => 'esc_html',
			'default'         => '',
			'active_callback' => [
				[
					[
						'setting'  => 'image_position',
						'operator' => '==',
						'value'    => 'left',
					],
					[
						'setting'  => 'image_position',
						'operator' => '==',
						'value'    => 'right',
					],
					[
						'setting'  => 'image_position',
						'operator' => '==',
						'value'    => 'left-full',
					],
					[
						'setting'  => 'image_position',
						'operator' => '==',
						'value'    => 'right-full',
					],
					[
						'setting'  => 'image_position',
						'operator' => '==',
						'value'    => 'background',
					],
				],
			],
		],
		[
			'settings'        => 'image_stack',
			'label'           => esc_html__( 'Stack image and content on mobile', 'mai-engine' ),
			'type'            => 'checkbox',
			'sanitize'        => 'mai_sanitize_bool',
			'default'         => true,
			'active_callback' => [
				[
					[
						'setting'  => 'image_position',
						'operator' => '==',
						'value'    => 'left',
					],
					[
						'setting'  => 'image_position',
						'operator' => '==',
						'value'    => 'right',
					],
					[
						'setting'  => 'image_position',
						'operator' => '==',
						'value'    => 'left-full',
					],
					[
						'setting'  => 'image_position',
						'operator' => '==',
						'value'    => 'right-full',
					],
					[
						'setting'  => 'image_position',
						'operator' => '==',
						'value'    => 'background',
					],
				],
			],
		],
		[
			'settings' => 'boxed_heading',
			'label'    => esc_html__( 'Boxed', 'mai-engine' ),
			'type'     => 'custom',
			'sanitize' => 'esc_html',
			'default'  => '',
		],
		[
			'settings' => 'boxed',
			'label'    => esc_html__( 'Display boxed styling', 'mai-engine' ),
			'type'     => 'checkbox',
			'sanitize' => 'mai_sanitize_bool',
			'default'  => true,
		],
		[
			'settings'        => 'border_radius',
			'label'           => esc_html__( 'Border Radius', 'mai-engine' ),
			'description'     => esc_html__( 'Leave empty for theme default. Accepts all unit values (px, rem, em, vw, etc).', 'mai-engine' ),
			'block'           => [ 'post', 'term', 'user' ],
			'type'            => 'text',
			'sanitize'        => 'esc_html',
			'default'         => '',
			'input_attrs'     => [
				'placeholder' => isset( mai_get_global_styles( 'extra' )['border-radius'] ) ? mai_get_global_styles( 'extra' )['border-radius'] : '4px',
			],
			'active_callback' => [
				[
					[
						'setting'  => 'image_position',
						'operator' => '==',
						'value'    => 'background',
					],
					[
						'setting'  => 'boxed',
						'operator' => '==',
						'value'    => true,
					],
				],
			],
		],
		[
			'settings' => 'columns',
			'label'    => esc_html__( 'Columns (desktop)', 'mai-engine' ),
			'type'     => 'radio-buttonset',
			'sanitize' => 'esc_html',
			'default'  => '3',
			'choices'  => mai_get_columns_choices(),
		],
		[
			'settings' => 'columns_responsive',
			'label'    => esc_html__( 'Custom responsive columns', 'mai-engine' ),
			'type'     => 'checkbox',
			'sanitize' => 'mai_sanitize_bool',
			'default'  => '',
		],
		[
			'settings'        => 'columns_md',
			'label'           => esc_html__( 'Columns (lg tablets)', 'mai-engine' ),
			'type'            => 'radio-buttonset',
			'sanitize'        => 'esc_html',
			'default'         => '1',
			'choices'         => mai_get_columns_choices(),
			'active_callback' => [
				[
					'setting'  => 'columns_responsive',
					'operator' => '==',
					'value'    => 1,
				],
			],
		],
		[
			'settings'        => 'columns_sm',
			'label'           => esc_html__( 'Columns (sm tablets)', 'mai-engine' ),
			'type'            => 'radio-buttonset',
			'sanitize'        => 'esc_html',
			'default'         => '1',
			'choices'         => mai_get_columns_choices(),
			'active_callback' => [
				[
					'setting'  => 'columns_responsive',
					'operator' => '==',
					'value'    => 1,
				],
			],
		],
		[
			'settings'        => 'columns_xs',
			'label'           => esc_html__( 'Columns (mobile)', 'mai-engine' ),
			'type'            => 'radio-buttonset',
			'sanitize'        => 'esc_html',
			'default'         => '1',
			'choices'         => mai_get_columns_choices(),
			'active_callback' => [
				[
					'setting'  => 'columns_responsive',
					'operator' => '==',
					'value'    => 1,
				],
			],
		],
		[
			'settings'        => 'align_columns',
			'label'           => esc_html__( 'Align Columns', 'mai-engine' ),
			'type'            => 'radio-buttonset',
			'sanitize'        => 'esc_html',
			'default'         => 'left',
			'choices'         => [
				'left'   => esc_html__( 'Start', 'mai-engine' ),
				'center' => esc_html__( 'Center', 'mai-engine' ),
				'right'  => esc_html__( 'End', 'mai-engine' ),
			],
			'active_callback' => [
				[
					'setting'  => 'columns',
					'operator' => '!=',
					'value'    => 1,
				],
			],
		],
		[
			'settings'        => 'align_columns_vertical',
			'label'           => esc_html__( 'Align Columns (vertical)', 'mai-engine' ),
			'type'            => 'radio-buttonset',
			'sanitize'        => 'esc_html',
			'default'         => '',
			'choices'         => [
				''       => esc_html__( 'Full', 'mai-engine' ),
				'top'    => esc_html__( 'Top', 'mai-engine' ),
				'middle' => esc_html__( 'Middle', 'mai-engine' ),
				'bottom' => esc_html__( 'Bottom', 'mai-engine' ),
			],
			'active_callback' => [
				[
					'setting'  => 'columns',
					'operator' => '!=',
					'value'    => 1,
				],
			],
		],
		[
			'settings' => 'column_gap',
			'label'    => esc_html__( 'Column Gap', 'mai-engine' ),
			'type'     => 'radio-buttonset',
			'sanitize' => 'esc_html',
			'default'  => 'xl',
			'choices'  => [
				''     => esc_html__( 'None', 'mai-engine' ),
				'md'   => esc_html__( 'XS', 'mai-engine' ), // Values mapped to a spacing sizes, labels kept consistent.
				'lg'   => esc_html__( 'SM', 'mai-engine' ),
				'xl'   => esc_html__( 'MD', 'mai-engine' ),
				'xxl'  => esc_html__( 'LG', 'mai-engine' ),
				'xxxl' => esc_html__( 'XL', 'mai-engine' ),
			],
		],
		[
			'settings' => 'row_gap',
			'label'    => esc_html__( 'Row Gap', 'mai-engine' ),
			'type'     => 'radio-buttonset',
			'sanitize' => 'esc_html',
			'default'  => 'xl',
			'choices'  => [
				''     => esc_html__( 'None', 'mai-engine' ),
				'md'   => esc_html__( 'XS', 'mai-engine' ), // Values mapped to a spacing sizes, labels kept consistent.
				'lg'   => esc_html__( 'SM', 'mai-engine' ),
				'xl'   => esc_html__( 'MD', 'mai-engine' ),
				'xxl'  => esc_html__( 'LG', 'mai-engine' ),
				'xxxl' => esc_html__( 'XL', 'mai-engine' ),
			],
		],
		[
			'settings'    => 'posts_per_page',
			'label'       => esc_html__( 'Posts Per Page', 'mai-engine' ),
			'description' => esc_html__( 'Sticky posts are not included in count.', 'mai-engine' ),
			'type'        => 'text',
			'sanitize'    => 'esc_html', // Can't absint cause empty string means to use default.
			'default'     => '',
			'input_attrs' => [
				'placeholder' => get_option( 'posts_per_page' ),
			],
		],
		[
			'type'     => 'custom',
			'settings' => 'content-archives-field-divider',
			'default'  => '<hr>',
		],
		[
			'settings'        => 'page-header-image',
			'label'           => __( 'Page Header default image', 'mai-engine' ),
			'type'            => 'image',
			'default'         => '',
			'choices'         => [
				'save_as' => 'id',
			],
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

add_action( 'init', 'mai_add_content_archive_settings' );
/**
 * Add content archive customizer settings.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_add_content_archive_settings() {
	$handle   = mai_get_handle();
	$panel    = 'content-archives';
	$sections = mai_get_option( 'archive-settings', mai_get_config( 'archive-settings' ), false );

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

		$settings = mai_get_content_archive_settings();

		foreach ( $settings as $field ) {
			if ( 'post' === $section && 'posts_per_page' === $field['settings'] ) {
				continue;
			}

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

add_action( 'init', 'mai_customize_register_posts_per_page', 99 );
/**
 * Adds Posts Per Page option to Customizer > Theme Settings > Content Archives > Default.
 * Saves/manages WP core option.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_customize_register_posts_per_page() {
	$handle = mai_get_handle();

	\Kirki::add_field(
		$handle,
		[
			'default'           => get_option( 'posts_per_page' ),
			'label'             => __( 'Posts Per Page', 'mai-engine' ),
			'section'           => $handle . '-content-archives-post',
			'settings'          => 'posts_per_page',
			'type'              => 'text',
			'priority'          => 99,
			'sanitize_callback' => 'absint',
		]
	);
}
