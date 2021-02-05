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
 * @since 2.4.0 Moved defaults to config.
 * @since 2.4.2 Added $name param.
 *
 * @return array
 */
function mai_get_content_archive_settings( $name = 'post' ) {
	static $settings = null;
	if ( ! is_null( $settings ) ) {
		return $settings;
	}

	$defaults = mai_get_config( 'settings' )['content-archives'];
	$defaults = isset( $defaults[ $name ] ) ? $defaults[ $name ] : $defaults[ 'post' ];
	$settings = [
		[
			'settings'    => 'show',
			'label'       => __( 'Show', 'mai-engine' ),
			'description' => __( 'Show/hide and re-order entry elements. Click "Toggle Hooks" to show Genesis hooks.', 'mai-engine' ),
			'type'        => 'sortable',
			'sanitize'    => 'esc_html',
			'default'     => $defaults['show'],
			'choices'     => mai_get_archive_show_choices(),
		],
		[
			'settings'        => 'title_size',
			'label'           => __( 'Title Size', 'mai-engine' ),
			'type'            => 'radio-buttonset',
			'sanitize'        => 'esc_html',
			'default'         => $defaults['title_size'],
			'choices'         => [
				'md'    => __( 'XS', 'mai-engine' ),
				'lg'    => __( 'S', 'mai-engine' ),
				'xl'    => __( 'M', 'mai-engine' ),
				'xxl'   => __( 'L', 'mai-engine' ),
				'xxxxl' => __( 'XL', 'mai-engine' ),
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
					'value'    => 'custom',
					'setting'  => 'image_orientation',
					'operator' => '==',
				],
			],
		],
		[
			'settings'        => 'image_position',
			'label'           => __( 'Image Position', 'mai-engine' ),
			'type'            => 'select',
			'sanitize'        => 'esc_html',
			'default'         => $defaults['image_position'],
			'choices'         => [
				'full'         => __( 'Full', 'mai-engine' ),
				'center'       => __( 'Center', 'mai-engine' ),
				'left-top'     => __( 'Left Top', 'mai-engine' ),
				'left-middle'  => __( 'Left Middle', 'mai-engine' ),
				'left-full'    => __( 'Left Full', 'mai-engine' ),
				'right-top'    => __( 'Right Top', 'mai-engine' ),
				'right-middle' => __( 'Right Middle', 'mai-engine' ),
				'right-full'   => __( 'Right Full', 'mai-engine' ),
				'background'   => __( 'Background', 'mai-engine' ),
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
			'type'            => 'custom',
			'settings'        => 'image_alternate_heading',
			'label'           => __( 'Images Alternating', 'mai-engine' ),
			'default'         => '',
			'active_callback' => [
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
			'settings'        => 'image_alternate',
			'label'           => __( 'Display images alternating', 'mai-engine' ),
			'type'            => 'checkbox',
			'sanitize'        => 'mai_sanitize_bool',
			'default'         => $defaults['image_alternate'],
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
			'settings'        => 'image_width',
			'label'           => __( 'Image Width', 'mai-engine' ),
			'type'            => 'radio-buttonset',
			'sanitize'        => 'esc_html',
			'default'         => $defaults['image_width'],
			'choices'         => [
				'fourth' => __( 'One Fourth', 'mai-engine' ),
				'third'  => __( 'One Third', 'mai-engine' ),
				'half'   => __( 'One Half', 'mai-engine' ),
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
			'settings'        => 'content_limit',
			'label'           => __( 'Content Limit', 'mai-engine' ),
			'description'     => __( 'Limit the number of characters shown for the content or excerpt. Use 0 for no limit.', 'mai-engine' ),
			'type'            => 'text',
			'sanitize'        => 'absint',
			'default'         => $defaults['content_limit'],
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
		[
			'settings'        => 'more_link_text',
			'label'           => __( 'More Link Text', 'mai-engine' ),
			'type'            => 'text',
			'sanitize'        => 'wp_kses_post', // We may want to add icons/spans and HTML in here.
			'default'         => $defaults['more_link_text'],
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
			'settings' => 'align_text',
			'label'    => __( 'Align Text', 'mai-engine' ),
			'type'     => 'radio-buttonset',
			'sanitize' => 'esc_html',
			'default'  => $defaults['align_text'],
			'choices'  => [
				'start'  => __( 'Start', 'mai-engine' ),
				'center' => __( 'Center', 'mai-engine' ),
				'end'    => __( 'End', 'mai-engine' ),
			],
		],
		[
			'settings'        => 'align_text_vertical',
			'label'           => __( 'Align Text (vertical)', 'mai-engine' ),
			'type'            => 'radio-buttonset',
			'sanitize'        => 'esc_html',
			'default'         => $defaults['align_text_vertical'],
			'choices'         => [
				''       => __( 'Default', 'mai-engine' ),
				'top'    => __( 'Top', 'mai-engine' ),
				'middle' => __( 'Middle', 'mai-engine' ),
				'bottom' => __( 'Bottom', 'mai-engine' ),
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
			'type'            => 'custom',
			'settings'        => 'image_stack_heading',
			'label'           => __( 'Stack Image', 'mai-engine' ),
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
			'label'           => __( 'Stack image and content on mobile', 'mai-engine' ),
			'type'            => 'checkbox',
			'sanitize'        => 'mai_sanitize_bool',
			'default'         => $defaults['image_stack'],
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
			'type'     => 'custom',
			'label'    => __( 'Boxed', 'mai-engine' ),
			'default'  => '',
		],
		[
			'settings' => 'boxed',
			'label'    => __( 'Display boxed styling', 'mai-engine' ),
			'type'     => 'checkbox',
			'sanitize' => 'mai_sanitize_bool',
			'default'  => $defaults['boxed'],
		],
		[
			'settings'        => 'border_radius',
			'label'           => __( 'Border Radius', 'mai-engine' ),
			'description'     => __( 'Leave empty for theme default. Accepts all unit values (px, rem, em, vw, etc).', 'mai-engine' ),
			'block'           => [ 'post', 'term', 'user' ],
			'type'            => 'text',
			'sanitize'        => 'esc_html',
			'default'         => $defaults['border_radius'],
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
			'label'    => __( 'Columns (desktop)', 'mai-engine' ),
			'type'     => 'radio-buttonset',
			'sanitize' => 'esc_html',
			'default'  => $defaults['columns'],
			'choices'  => mai_get_columns_choices(),
		],
		[
			'settings' => 'columns_responsive',
			'label'    => __( 'Custom responsive columns', 'mai-engine' ),
			'type'     => 'checkbox',
			'sanitize' => 'mai_sanitize_bool',
			'default'  => $defaults['columns_responsive'],
		],
		[
			'settings'        => 'columns_md',
			'label'           => __( 'Columns (lg tablets)', 'mai-engine' ),
			'type'            => 'radio-buttonset',
			'sanitize'        => 'esc_html',
			'default'         => $defaults['columns_md'],
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
			'label'           => __( 'Columns (sm tablets)', 'mai-engine' ),
			'type'            => 'radio-buttonset',
			'sanitize'        => 'esc_html',
			'default'         => $defaults['columns_sm'],
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
			'label'           => __( 'Columns (mobile)', 'mai-engine' ),
			'type'            => 'radio-buttonset',
			'sanitize'        => 'esc_html',
			'default'         => $defaults['columns_xs'],
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
			'label'           => __( 'Align Columns', 'mai-engine' ),
			'type'            => 'radio-buttonset',
			'sanitize'        => 'esc_html',
			'default'         => $defaults['align_columns'],
			'choices'         => [
				'left'   => __( 'Start', 'mai-engine' ),
				'center' => __( 'Center', 'mai-engine' ),
				'right'  => __( 'End', 'mai-engine' ),
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
			'label'           => __( 'Align Columns (vertical)', 'mai-engine' ),
			'type'            => 'radio-buttonset',
			'sanitize'        => 'esc_html',
			'default'         => $defaults['align_columns_vertical'],
			'choices'         => [
				''       => __( 'Full', 'mai-engine' ),
				'top'    => __( 'Top', 'mai-engine' ),
				'middle' => __( 'Middle', 'mai-engine' ),
				'bottom' => __( 'Bottom', 'mai-engine' ),
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
			'label'    => __( 'Column Gap', 'mai-engine' ),
			'type'     => 'radio-buttonset',
			'sanitize' => 'esc_html',
			'default'  => $defaults['column_gap'],
			'choices'  => [
				''     => __( 'None', 'mai-engine' ),
				'md'   => __( 'XS', 'mai-engine' ), // Values mapped to a spacing sizes, labels kept consistent.
				'lg'   => __( 'S', 'mai-engine' ),
				'xl'   => __( 'M', 'mai-engine' ),
				'xxl'  => __( 'L', 'mai-engine' ),
				'xxxl' => __( 'XL', 'mai-engine' ),
			],
		],
		[
			'settings' => 'row_gap',
			'label'    => __( 'Row Gap', 'mai-engine' ),
			'type'     => 'radio-buttonset',
			'sanitize' => 'esc_html',
			'default'  => $defaults['row_gap'],
			'choices'  => [
				''     => __( 'None', 'mai-engine' ),
				'md'   => __( 'XS', 'mai-engine' ), // Values mapped to a spacing sizes, labels kept consistent.
				'lg'   => __( 'S', 'mai-engine' ),
				'xl'   => __( 'M', 'mai-engine' ),
				'xxl'  => __( 'L', 'mai-engine' ),
				'xxxl' => __( 'XL', 'mai-engine' ),
			],
		],
		[
			'settings'    => 'posts_nav',
			'label'       => __( 'Entry Pagination Type', 'mai-engine' ),
			'type'        => 'radio-buttonset',
			'sanitize'    => 'esc_html',
			'default'     => $defaults['posts_nav'],
			'choices'     => [
				'numeric'   => __( 'Numeric', 'mai-engine' ),
				'prev-next' => __( 'Previous / Next', 'mai-engine' ),
			],
		],
		[
			'settings'    => 'posts_per_page',
			'label'       => __( 'Posts Per Page', 'mai-engine' ),
			'description' => __( 'Sticky posts are not included in count.', 'mai-engine' ),
			'type'        => 'text',
			'sanitize'    => 'esc_html', // Can't absint cause empty string means to use default.
			'default'     => $defaults['posts_per_page'],
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
			'default'         => $defaults['page-header-image'],
			'choices'         => [
				'save_as' => 'id',
			],
			'active_callback' => 'mai_has_page_header_support_callback',
		],
		[
			'settings'        => 'page-header-background-color',
			'label'           => __( 'Background/overlay color', 'mai-engine' ),
			'type'            => 'color',
			'default'         => $defaults['page-header-background-color'],
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
			'sanitize_callback' => function( $value ) {
				return (float) $value; // Remove trailing zeros.
			},
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

	$settings = apply_filters( 'mai_content_archive_settings', $settings, $name );

	return $settings;
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
	$defaults = mai_get_config( 'settings' )['content-archives']['enable'];
	$sections = mai_get_option( 'archive-settings', $defaults, false );

	Kirki::add_panel(
		"{$handle}-{$panel}",
		[
			'title' => mai_convert_case( $panel, 'title' ),
			'panel' => $handle,
		]
	);

	foreach ( $sections as $section ) {
		if ( post_type_exists( $section ) && $post_type = get_post_type_object( $section ) ) {
			$title = $post_type->label;
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

		$settings = mai_get_content_archive_settings( $section );

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

			Kirki::add_field( $handle, $field );
		}
	}
}

/**
 * Adds Posts Per Page option to Customizer > Theme Settings > Content Archives > Post.
 * Saves/manages WP core option.
 *
 * @since 0.1.0
 * @since 2.4.4 Changed to customize_register hook and use default API to register field,
 *              So it can be saved directly to the core posts_per_page option.
 *
 * @param WP_Customize_Manager $wp_customize WP_Customize_Manager instance.
 *
 * @return void
 */
add_action( 'customize_register', 'mai_customize_register_posts_per_page', 999 );
function mai_customize_register_posts_per_page( $wp_customize ) {
	$handle = mai_get_handle();

	$wp_customize->add_setting(
		'posts_per_page',
		[
			'default'           => absint( get_option( 'posts_per_page' ) ),
			'type'              => 'option',
			'sanitize_callback' => 'absint',
		]
	);
	$wp_customize->add_control(
		'posts_per_page',
		[
			'label'    => __( 'Posts Per Page', 'mai-engine' ),
			'section'  => $handle . '-content-archives-post',
			'settings' => 'posts_per_page',
			'type'     => 'text',
		]
	);
}
