<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2019 BizBudding
 * @license   GPL-2.0-or-later
 */

return [
	[
		'name'     => 'site_layout',
		'label'    => esc_html__( 'Layout', 'mai-engine' ),
		'desc'     => esc_html__( '"Site Default" will use the setting in Customizer > Theme Settings > Site Layout.', 'mai-engine' ),
		'type'     => 'select',
		'sanitize' => 'esc_html',
		'default'  => 'wide-content',
		'choices'  => 'mai_get_site_layout_choices',
	],
	[
		'name'     => 'show',
		'label'    => esc_html__( 'Show', 'mai-engine' ),
		'type'     => 'sortable',
		'sanitize' => 'esc_html',
		'default'  => [
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
		'choices'  => [
			'image'                        => esc_html__( 'Image', 'mai-engine' ),
			'genesis_entry_header'         => 'genesis_entry_header',
			'title'                        => esc_html__( 'Title', 'mai-engine' ),
			'header_meta'                  => esc_html__( 'Header Meta', 'mai-engine' ),
			'genesis_before_entry_content' => 'genesis_before_entry_content',
			'excerpt'                      => esc_html__( 'Excerpt', 'mai-engine' ),
			'content'                      => esc_html__( 'Content', 'mai-engine' ),
			'genesis_entry_content'        => 'genesis_entry_content',
			'more_link'                    => esc_html__( 'Read More link', 'mai-engine' ),
			'genesis_after_entry_content'  => 'genesis_after_entry_content',
			'footer_meta'                  => esc_html__( 'Footer Meta', 'mai-engine' ),
			'genesis_entry_footer'         => 'genesis_entry_footer',
		],
	],
	[
		'name'       => 'image_orientation',
		'label'      => esc_html__( 'Image Orientation', 'mai-engine' ),
		'type'       => 'select',
		'sanitize'   => 'esc_html',
		'default'    => 'landscape',
		'choices'    => 'mai_get_image_orientation_choices',
		'conditions' => [
			[
				'setting'  => 'show',
				'operator' => 'contains',
				'value'    => 'image',
			],
		],
	],
	[
		'name'       => 'image_size',
		'label'      => esc_html__( 'Image Size', 'mai-engine' ),
		'type'       => 'select',
		'sanitize'   => 'esc_html',
		'default'    => 'landscape-md',
		'choices'    => 'mai_get_image_size_choices',
		'conditions' => [
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
		'name'       => 'image_position',
		'label'      => esc_html__( 'Image Position', 'mai-engine' ),
		'type'       => 'select',
		'sanitize'   => 'esc_html',
		'default'    => 'full',
		'choices'    => [
			'full'       => esc_html__( 'Full', 'mai-engine' ),
			'left'       => esc_html__( 'Left', 'mai-engine' ),
			'center'     => esc_html__( 'Center', 'mai-engine' ),
			'right'      => esc_html__( 'Right', 'mai-engine' ),
			'background' => esc_html__( 'Background', 'mai-engine' ),
		],
		'conditions' => [
			[
				'setting'  => 'show',
				'operator' => 'contains',
				'value'    => 'image',
			],
		],
	],
	[
		'name'       => 'header_meta',
		'label'      => esc_html__( 'Header Meta', 'mai-engine' ),
		'type'       => 'text',
		'sanitize'   => 'wp_kses_post',
		// TODO: this should be different, or empty depending on the post type?
		'default'    => '[post_date] [post_author_posts_link before="by "]',
		'conditions' => [
			[
				'setting'  => 'show',
				'operator' => 'contains',
				'value'    => 'header_meta',
			],
		],
	],
	[
		'name'       => 'content_limit',
		'label'      => esc_html__( 'Content Limit', 'mai-engine' ),
		'desc'       => esc_html__( 'Limit the number of characters shown for the content or excerpt. Use 0 for no limit.', 'mai-engine' ),
		'type'       => 'text',
		'sanitize'   => 'absint',
		'default'    => 0,
		'conditions' => [
			[
				[
					'setting'  => 'show',
					'operator' => 'contains',
					'value'    => 'excerpt',
				],
			],
			[
				[
					'setting'  => 'show',
					'operator' => 'contains',
					'value'    => 'content',
				],
			],
		],
	],
	[
		'name'       => 'more_link_text',
		'label'      => esc_html__( 'More Link Text', 'mai-engine' ),
		'type'       => 'text',
		'sanitize'   => 'esc_attr', // We may want to add icons/spans and HTML in here.
		'default'    => '',
		'conditions' => [
			[
				'setting'  => 'show',
				'operator' => 'contains',
				'value'    => 'more_link',
			],
		],
		'atts'       => [
			// TODO: This text should be filtered, same as the template that outputs it.
			'input_attrs' => [
				'placeholder' => esc_html__( 'Read More', 'mai-engine' ),
			],
		],
	],
	[
		'name'       => 'footer_meta',
		'label'      => esc_html__( 'Footer Meta', 'mai-engine' ),
		'type'       => 'text',
		'sanitize'   => 'wp_kses_post',
		// TODO: this should be different, or empty depending on the post type?
		'default'    => '[post_categories]',
		'conditions' => [
			[
				'setting'  => 'show',
				'operator' => 'contains',
				'value'    => 'footer_meta',
			],
		],
	],
	[
		'name'     => 'boxed',
		// 'label'    => esc_html__( 'Boxed', 'mai-engine' ),
		'label'    => esc_html__( 'Display boxed', 'mai-engine' ),
		'type'     => 'checkbox',
		'sanitize' => 'esc_html',
		'default'  => true,
	],
	[
		'name'     => 'align_text',
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
		'name'       => 'align_text_vertical',
		'label'      => esc_html__( 'Align Text (vertical)', 'mai-engine' ),
		'type'       => 'radio-buttonset',
		'sanitize'   => 'esc_html',
		'default'    => '',
		'choices'    => [
			''       => esc_html__( 'Clear', 'mai-engine' ),
			'top'    => esc_html__( 'Top', 'mai-engine' ),
			'middle' => esc_html__( 'Middle', 'mai-engine' ),
			'bottom' => esc_html__( 'Bottom', 'mai-engine' ),
		],
		'conditions' => [
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
					'value'    => 'background',
				],
			],
		],
	],
	[
		'name'     => 'columns',
		'label'    => esc_html__( 'Columns (desktop)', 'mai-engine' ),
		'type'     => 'radio-buttonset',
		'sanitize' => 'absint',
		'default'  => 3,
		'choices'  => 'mai_get_columns_choices',
	],
	[
		'name'     => 'columns_responsive',
		'label'    => esc_html__( 'Custom responsive columns', 'mai-engine' ),
		'type'     => 'checkbox',
		'sanitize' => 'mai_sanitize_bool',
		'default'  => '',
	],
	[
		'name'       => 'columns_md',
		'label'      => esc_html__( 'Columns (lg tablets)', 'mai-engine' ),
		'type'       => 'radio-buttonset',
		'sanitize'   => 'absint',
		'default'    => 1,
		'choices'    => 'mai_get_columns_choices',
		'conditions' => [
			[
				'setting'  => 'columns_responsive',
				'operator' => '==',
				'value'    => 1,
			],
		],
	],
	[
		'name'       => 'columns_sm',
		'label'      => esc_html__( 'Columns (sm tablets)', 'mai-engine' ),
		'type'       => 'radio-buttonset',
		'sanitize'   => 'absint',
		'default'    => 1,
		'choices'    => 'mai_get_columns_choices',
		'conditions' => [
			[
				'setting'  => 'columns_responsive',
				'operator' => '==',
				'value'    => 1,
			],
		],
	],
	[
		'name'       => 'columns_xs',
		'label'      => esc_html__( 'Columns (mobile)', 'mai-engine' ),
		'type'       => 'radio-buttonset',
		'sanitize'   => 'absint',
		'default'    => 1,
		'choices'    => 'mai_get_columns_choices',
		'conditions' => [
			[
				'setting'  => 'columns_responsive',
				'operator' => '==',
				'value'    => 1,
			],
		],
	],
	[
		'name'       => 'align_columns',
		'label'      => esc_html__( 'Align Columns', 'mai-engine' ),
		'type'       => 'radio-buttonset',
		'sanitize'   => 'esc_html',
		'default'    => '',
		'choices'    => [
			''       => esc_html__( 'Clear', 'mai-engine' ),
			'left'   => esc_html__( 'Left', 'mai-engine' ),
			'center' => esc_html__( 'Center', 'mai-engine' ),
			'right'  => esc_html__( 'Right', 'mai-engine' ),
		],
		'conditions' => [
			[
				'setting'  => 'columns',
				'operator' => '!=',
				'value'    => 1,
			],
		],
	],
	[
		'name'       => 'align_columns_vertical',
		'label'      => esc_html__( 'Align Columns (vertical)', 'mai-engine' ),
		'type'       => 'radio-buttonset',
		'sanitize'   => 'esc_html',
		'default'    => '',
		'choices'    => [
			''       => esc_html__( 'Clear', 'mai-engine' ),
			'top'    => esc_html__( 'Top', 'mai-engine' ),
			'middle' => esc_html__( 'Middle', 'mai-engine' ),
			'bottom' => esc_html__( 'Bottom', 'mai-engine' ),
		],
		'conditions' => [
			[
				'setting'  => 'columns',
				'operator' => '!=',
				'value'    => 1,
			],
		],
	],
	[
		'name'     => 'column_gap',
		'label'    => esc_html__( 'Column Gap', 'mai-engine' ),
		'type'     => 'text',
		'sanitize' => 'esc_html',
		'default'  => '36px',
	],
	[
		'name'     => 'row_gap',
		'label'    => esc_html__( 'Row Gap', 'mai-engine' ),
		'type'     => 'text',
		'sanitize' => 'esc_html',
		'default'  => '64px',
	],
	[
		// TODO: On Post Archives, this should save to the direct posts_per_page option (same as main Settings > Reading option).
		'name'     => 'posts_per_page',
		'label'    => esc_html__( 'Posts Per Page', 'mai-engine' ),
		'desc'     => esc_html__( 'Sticky posts are not included in count.', 'mai-engine' ),
		'sanitize' => 'esc_html', // Can't absint cause empty string means to use default.
		'type'     => 'text',
		'default'  => '',
		'atts'     => [
			'input_attrs' => [
				'placeholder' => get_option( 'posts_per_page' ),
			],
		],
	],

];
