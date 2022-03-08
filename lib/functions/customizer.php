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
 * Sets kirki args option type and name.
 * This is required since Kirki v4 doesn't use a config anymore.
 *
 * @since TBD
 *
 * @param array $args The existing args array.
 *
 * @return array
 */
function mai_parse_kirki_args( $args ) {
	$args['option_type'] = 'option';
	$args['capability']  = 'edit_theme_options';

	return $args;
}

/**
 * Gets setting name for kirki.
 *
 * @since TBD
 *
 * @param string $key  The setting key.
 * @param string $base The setting base.
 *
 * @return string
 */
function mai_get_kirki_setting( $key, $base = '' ) {
	return sprintf( '%s%s[%s]', mai_get_handle(), $base, $key );
}

/**
 * Get kirki class name from v3 type name.
 *
 * @since TBD
 *
 * @param string $type The type name.
 *
 * @return string
 */
function mai_get_kirki_class( $type ) {
	$classes = mai_get_kirki_classes();

	return $classes[ $type ];
}

/**
 * Gets kirki class names from v3 type name.
 *
 * @since TBD
 *
 * @return array
 */
function mai_get_kirki_classes() {
	static $classes = null;

	if ( ! is_null( $classes ) ) {
		return $classes;
	}

	$classes = [
		'checkbox'        => '\Kirki\Field\Checkbox',
		'color'           => '\Kirki\Field\Color',
		'custom'          => '\Kirki\Field\Custom',
		'image'           => '\Kirki\Field\Image',
		'multicheck'      => '\Kirki\Field\Multicheck',
		'radio-buttonset' => '\Kirki\Field\Radio_Buttonset',
		'select'          => '\Kirki\Field\Select',
		'slider'          => '\Kirki\Field\Slider',
		'sortable'        => '\Kirki\Field\Sortable',
		'text'            => '\Kirki\Field\Text',
		'textarea'        => '\Kirki\Field\Textarea',
	];

	return $classes;
}

/**
 * Parses active_callback from our helper functions to get settings.
 *
 * @access private
 *
 * @since TBD
 *
 * @param array  $data    The conditions.
 * @param string $panel   The panel name.
 * @param string $section The section name.
 *
 * @return array
 */
function mai_get_kirki_active_callback( $data, $panel, $section ) {
	foreach ( $data as $data_index => $conditions ) {
		if ( isset( $conditions['setting'] ) ) {
			$data[ $data_index ]['setting'] = mai_get_kirki_setting( $data[ $data_index ]['setting'], "[$panel][$section]" );
		} else {
			foreach ( $conditions as $conditions_index => $condition ) {
				$data[ $data_index ][ $conditions_index ]['setting'] = mai_get_kirki_setting( $data[ $data_index ][ $conditions_index ]['setting'], "[$panel][$section]" );
			}
		}
	}

	return $data;
}

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
	static $archive_settings = null;

	if ( is_array( $archive_settings ) && isset( $archive_settings[ $name ] ) ) {
		return $archive_settings[ $name ];
	}

	if ( ! is_array( $archive_settings ) ) {
		$archive_settings = [];
	}

	$config   = mai_get_config( 'settings' )['content-archives'];
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
					'setting'  => 'show',
					'operator' => 'contains',
					'value'    => 'title',
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
					'setting'  => 'show',
					'operator' => 'contains',
					'value'    => 'image',
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
			'settings'        => 'image_alternate',
			'label'           => __( 'Display images alternating', 'mai-engine' ),
			'type'            => 'checkbox',
			'sanitize'        => 'mai_sanitize_bool',
			'default'         => $defaults['image_alternate'],
			'active_callback' => [
				[
					'setting'  => 'show',
					'operator' => 'contains',
					'value'    => 'image',
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
				'fourth' => esc_html__( '¼', 'mai-engine' ),
				'third'  => esc_html__( '⅓', 'mai-engine' ),
				'half'   => esc_html__( '½', 'mai-engine' ),
			],
			'active_callback' => [
				[
					'setting'  => 'show',
					'operator' => 'contains',
					'value'    => 'image',
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
					'setting'  => 'show',
					'operator' => 'contains',
					'value'    => 'image',
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
					'setting'  => 'show',
					'operator' => 'contains',
					'value'    => 'image',
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
			'settings'        => 'image_stack',
			'label'           => __( 'Stack image and content on mobile', 'mai-engine' ),
			'type'            => 'checkbox',
			'sanitize'        => 'mai_sanitize_bool',
			'default'         => $defaults['image_stack'],
			'active_callback' => [
				[
					'setting'  => 'show',
					'operator' => 'contains',
					'value'    => 'image',
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

	$archive_settings[ $name ] = apply_filters( 'mai_content_archive_settings', $settings, $name );

	return $archive_settings[ $name ];
}

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
