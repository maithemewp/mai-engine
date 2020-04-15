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

return [
	[
		'settings'    => 'site_layout',
		'label'       => esc_html__( 'Layout', 'mai-engine' ),
		'description' => esc_html__( '"Site Default" will use the setting in Customizer > Theme Settings > Site Layout.', 'mai-engine' ),
		'type'        => 'select',
		'sanitize'    => 'esc_html',
		'default'     => 'wide-content',
		'choices'     => mai_get_site_layout_choices(),
	],
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
		// TODO: This text should be filtered, same as the template that outputs it.
		'input_attrs'     => [
			'placeholder' => esc_html__( 'Read More', 'mai-engine' ),
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
		'sanitize' => 'esc_html',
		'default'  => true,
	],
	[
		'settings' => 'align_text',
		'label'    => esc_html__( 'Align Text', 'mai-engine' ),
		'type'     => 'radio-buttonset',
		'sanitize' => 'esc_html',
		'default'  => '',
		'choices'  => [
			''       => esc_html__( 'Clear', 'mai-engine' ),
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
			''       => esc_html__( 'Clear', 'mai-engine' ),
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
		'default'         => '',
		'choices'         => [
			''       => esc_html__( 'Clear', 'mai-engine' ),
			'left'   => esc_html__( 'Left', 'mai-engine' ),
			'center' => esc_html__( 'Center', 'mai-engine' ),
			'right'  => esc_html__( 'Right', 'mai-engine' ),
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
			''       => esc_html__( 'Clear', 'mai-engine' ),
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
		'type'     => 'text',
		'sanitize' => 'esc_html',
		'default'  => '36px',
	],
	[
		'settings' => 'row_gap',
		'label'    => esc_html__( 'Row Gap', 'mai-engine' ),
		'type'     => 'text',
		'sanitize' => 'esc_html',
		'default'  => '64px',
	],
	[
		'settings'    => 'posts_per_page',
		'label'       => esc_html__( 'Posts Per Page', 'mai-engine' ),
		'description' => esc_html__( 'Sticky posts are not included in count.', 'mai-engine' ),
		'sanitize'    => 'esc_html', // Can't absint cause empty string means to use default.
		'type'        => 'text',
		'default'     => '',
		'input_attrs' => [
			'placeholder' => get_option( 'posts_per_page' ),
		],
	],
	[
		'type'            => 'image',
		'settings'        => 'page-header-image',
		'label'           => __( 'Page Header Image', 'mai-engine' ),
		'default'         => '',
		'choices'         => [
			'save_as' => 'id',
		],
		'active_callback' => mai_has_any_page_header_types(),
	],
];

