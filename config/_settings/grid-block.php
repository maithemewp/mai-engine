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
	'field_5bd51cac98282' => [
		'name'    => 'display_tab',
		'label'   => esc_html__( 'Display', 'mai-engine' ),
		'block'   => [ 'post', 'term', 'user' ],
		'type'    => 'tab',
		'default' => '',
	],
	'field_5e441d93d6236' => [
		'name'     => 'show',
		'label'    => esc_html__( 'Show', 'mai-engine' ),
		'desc'     => esc_html__( 'Show/hide and re-order elements.', 'mai-engine' ),
		'block'    => [ 'post', 'term', 'user' ],
		'type'     => 'checkbox',
		'sanitize' => 'esc_html',
		'default'  => [ 'image', 'title' ],
		'choices'  => 'mai_get_grid_show_choices',
		'atts'     => [
			'wrapper' => [
				'width' => '',
				'class' => 'mai-sortable',
				'id'    => '',
			],
		],
	],
	'field_5e4d4efe99279' => [
		'name'       => 'image_orientation',
		'label'      => esc_html__( 'Image Orientation', 'mai-engine' ),
		'block'      => [ 'post', 'term', 'user' ],
		'type'       => 'select',
		'sanitize'   => 'esc_html',
		'default'    => 'landscape',
		'choices'    => 'mai_get_image_orientation_choices',
		'conditions' => [
			[
				'field'    => 'field_5e441d93d6236', // Show.
				'operator' => '==',
				'value'    => 'image',
			],
		],
		'atts'       => [
			'wrapper' => [
				'width' => '',
				'class' => 'mai-grid-show-conditional',
				'id'    => '',
			],
		],
	],
	'field_5bd50e580d1e9' => [
		'name'       => 'image_size',
		'label'      => esc_html__( 'Image Size', 'mai-engine' ),
		'block'      => [ 'post', 'term', 'user' ],
		'type'       => 'select',
		'sanitize'   => 'esc_html',
		'default'    => 'landscape-md',
		'choices'    => 'mai_get_image_size_choices',
		'conditions' => [
			[
				'field'    => 'field_5e441d93d6236', // Show.
				'operator' => '==',
				'value'    => 'image',
			],
			[
				'field'    => 'field_5e4d4efe99279', // Image_orientation.
				'operator' => '==',
				'value'    => 'custom',
			],
		],
		'atts'       => [
			'wrapper' => [
				'width' => '',
				'class' => 'mai-grid-show-conditional',
				'id'    => '',
			],
		],
	],
	'field_5e2f3adf82130' => [
		'name'       => 'image_position',
		'label'      => esc_html__( 'Image Position', 'mai-engine' ),
		'block'      => [ 'post', 'term', 'user' ],
		'type'       => 'select',
		'sanitize'   => 'esc_html',
		'default'    => 'full',
		'choices'    => [
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
		'conditions' => [
			[
				'field'    => 'field_5e441d93d6236', // Show.
				'operator' => '==',
				'value'    => 'image',
			],
		],
		'atts'       => [
			'wrapper' => [
				'width' => '',
				'class' => 'mai-grid-show-conditional',
				'id'    => '',
			],
		],
	],
	'field_5e2f3beg93241' => [
		'name'       => 'image_width',
		'label'      => esc_html__( 'Image Width', 'mai-engine' ),
		'block'      => [ 'post', 'term', 'user' ],
		'type'       => 'button_group',
		'sanitize'   => 'esc_html',
		'default'    => 'third',
		'choices'    => [
			'fourth' => esc_html__( 'One Fourth', 'mai-engine' ),
			'third'  => esc_html__( 'One Third', 'mai-engine' ),
			'half'   => esc_html__( 'One Half', 'mai-engine' ),
		],
		'conditions' => [
			[
				[
					'field'    => 'field_5e441d93d6236', // Show.
					'operator' => '==',
					'value'    => 'image',
				],
				[
					'field'    => 'field_5e2f3adf82130', // Image Position.
					'operator' => '==',
					'value'    => 'left-top',
				],
			],
			[
				[
					'field'    => 'field_5e441d93d6236', // Show.
					'operator' => '==',
					'value'    => 'image',
				],
				[
					'field'    => 'field_5e2f3adf82130', // Image Position.
					'operator' => '==',
					'value'    => 'left-middle',
				],
			],
			[
				[
					'field'    => 'field_5e441d93d6236', // Show.
					'operator' => '==',
					'value'    => 'image',
				],
				[
					'field'    => 'field_5e2f3adf82130', // Image Position.
					'operator' => '==',
					'value'    => 'left-full',
				],
			],
			[
				[
					'field'    => 'field_5e441d93d6236', // Show.
					'operator' => '==',
					'value'    => 'image',
				],
				[
					'field'    => 'field_5e2f3adf82130', // Image Position.
					'operator' => '==',
					'value'    => 'right-top',
				],
			],
			[
				[
					'field'    => 'field_5e441d93d6236', // Show.
					'operator' => '==',
					'value'    => 'image',
				],
				[
					'field'    => 'field_5e2f3adf82130', // Image Position.
					'operator' => '==',
					'value'    => 'right-middle',
				],
			],
			[
				[
					'field'    => 'field_5e441d93d6236', // Show.
					'operator' => '==',
					'value'    => 'image',
				],
				[
					'field'    => 'field_5e2f3adf82130', // Image Position.
					'operator' => '==',
					'value'    => 'right-full',
				],
			],
		],
	],
	'field_5e2b563a7c6cf' => [
		'name'       => 'header_meta',
		'label'      => esc_html__( 'Header Meta', 'mai-engine' ),
		'block'      => [ 'post', 'term', 'user' ],
		'type'       => 'text',
		'sanitize'   => 'wp_kses_post',
		// TODO: this should be different, or empty depending on the post type?
		'default'    => '[post_date] [post_author_posts_link before="by "]',
		'conditions' => [
			[
				'field'    => 'field_5e441d93d6236', // Show.
				'operator' => '==',
				'value'    => 'header_meta',
			],
		],
		'atts'       => [
			'wrapper' => [
				'width' => '',
				'class' => 'mai-grid-show-conditional',
				'id'    => '',
			],
		],
	],
	'field_5bd51ac107244' => [
		'name'       => 'content_limit',
		'label'      => esc_html__( 'Content Limit', 'mai-engine' ),
		'desc'       => esc_html__( 'Limit the number of characters shown for the content or excerpt. Use 0 for no limit.', 'mai-engine' ),
		'block'      => [ 'post', 'term', 'user' ],
		'type'       => 'text',
		'sanitize'   => 'absint',
		'default'    => 0,
		'conditions' => [
			[
				[
					'field'    => 'field_5e441d93d6236', // Show.
					'operator' => '==',
					'value'    => 'excerpt',
				],
			],
			[
				[
					'field'    => 'field_5e441d93d6236', // Show.
					'operator' => '==',
					'value'    => 'content',
				],
			],
		],
		'atts'       => [
			'wrapper' => [
				'width' => '',
				'class' => 'mai-grid-show-conditional',
				'id'    => '',
			],
		],
	],
	'field_5c85465018395' => [
		'name'       => 'more_link_text',
		'label'      => esc_html__( 'More Link Text', 'mai-engine' ),
		'block'      => [ 'post', 'term', 'user' ],
		'type'       => 'text',
		'sanitize'   => 'esc_attr', // We may want to add icons/spans and HTML in here.
		'default'    => '',
		'conditions' => [
			[
				'field'    => 'field_5e441d93d6236', // Show.
				'operator' => '==',
				'value'    => 'more_link',
			],
		],
		'atts'       => [
			// TODO: This text should be filtered, same as the template that outputs it.
			'placeholder' => esc_html__( 'Read More', 'mai-engine' ),
			'wrapper'     => [
				'width' => '',
				'class' => 'mai-grid-show-conditional',
				'id'    => '',
			],
		],
	],
	'field_5e2b567e7c6d0' => [
		'name'       => 'footer_meta',
		'label'      => esc_html__( 'Footer Meta', 'mai-engine' ),
		'block'      => [ 'post', 'term', 'user' ],
		'type'       => 'text',
		'sanitize'   => 'wp_kses_post',
		// TODO: this should be different, or empty depending on the post type?
		'default'    => '[post_categories]',
		'conditions' => [
			[
				'field'    => 'field_5e441d93d6236', // Show.
				'operator' => '==',
				'value'    => 'footer_meta',
			],
		],
		'atts'       => [
			'wrapper' => [
				'width' => '',
				'class' => 'mai-grid-show-conditional',
				'id'    => '',
			],
		],
	],
	'field_5e2a08a182c2c' => [
		'name'     => 'boxed',
		'label'    => esc_html__( 'Boxed', 'mai-engine' ),
		'block'    => [ 'post', 'term', 'user' ],
		'type'     => 'true_false',
		'sanitize' => 'esc_html',
		'default'  => 1, // True.
		'atts'     => [
			'message' => __( 'Display boxed styling', 'mai-engine' ),
		],
	],
	'field_5c853f84eacd6' => [
		'name'     => 'align_text',
		'label'    => esc_html__( 'Align Text', 'mai-engine' ),
		'block'    => [ 'post', 'term', 'user' ],
		'type'     => 'button_group',
		'sanitize' => 'esc_html',
		'default'  => '',
		'choices'  => [
			''       => esc_html__( 'Clear', 'mai-engine' ),
			'start'  => esc_html__( 'Start', 'mai-engine' ),
			'center' => esc_html__( 'Center', 'mai-engine' ),
			'end'    => esc_html__( 'End', 'mai-engine' ),
		],
		'atts'     => [
			'wrapper' => [
				'width' => '',
				'class' => 'mai-grid-button-group mai-grid-button-group-clear',
				'id'    => '',
			],
		],
	],
	'field_5e2f519edc912' => [
		'name'       => 'align_text_vertical',
		'label'      => esc_html__( 'Align Text (vertical)', 'mai-engine' ),
		'block'      => [ 'post', 'term', 'user' ],
		'type'       => 'button_group',
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
					'field'    => 'field_5e441d93d6236', // Show.
					'operator' => '==',
					'value'    => 'image',
				],
				[
					'field'    => 'field_5e2f3adf82130', // Image Position.
					'operator' => '==',
					'value'    => 'left-top',
				],
			],
			[
				[
					'field'    => 'field_5e441d93d6236', // Show.
					'operator' => '==',
					'value'    => 'image',
				],
				[
					'field'    => 'field_5e2f3adf82130', // Image Position.
					'operator' => '==',
					'value'    => 'left-middle',
				],
			],
			[
				[
					'field'    => 'field_5e441d93d6236', // Show.
					'operator' => '==',
					'value'    => 'image',
				],
				[
					'field'    => 'field_5e2f3adf82130', // Image Position.
					'operator' => '==',
					'value'    => 'left-full',
				],
			],
			[
				[
					'field'    => 'field_5e441d93d6236', // Show.
					'operator' => '==',
					'value'    => 'image',
				],
				[
					'field'    => 'field_5e2f3adf82130', // Image Position.
					'operator' => '==',
					'value'    => 'right-top',
				],
			],
			[
				[
					'field'    => 'field_5e441d93d6236', // Show.
					'operator' => '==',
					'value'    => 'image',
				],
				[
					'field'    => 'field_5e2f3adf82130', // Image Position.
					'operator' => '==',
					'value'    => 'right-middle',
				],
			],
			[
				[
					'field'    => 'field_5e441d93d6236', // Show.
					'operator' => '==',
					'value'    => 'image',
				],
				[
					'field'    => 'field_5e2f3adf82130', // Image Position.
					'operator' => '==',
					'value'    => 'right-full',
				],
			],
			[
				[
					'field'    => 'field_5e441d93d6236', // Show.
					'operator' => '==',
					'value'    => 'image',
				],
				[
					'field'    => 'field_5e2f3adf82130', // Image_position.
					'operator' => '==',
					'value'    => 'background',
				],
			],
		],
		'atts'       => [
			'wrapper' => [
				'width' => '',
				'class' => 'mai-grid-button-group mai-grid-button-group-clear',
				'id'    => '',
			],
		],
	],
	/********
	 * Layout
	 */
	'field_5c8549172e6c7' => [
		'name'    => 'layout_tab',
		'label'   => esc_html__( 'Layout', 'mai-engine' ),
		'block'   => [ 'post', 'term', 'user' ],
		'type'    => 'tab',
		'default' => '',
	],
	'field_5c854069d358c' => [
		'name'     => 'columns',
		'label'    => esc_html__( 'Columns (desktop)', 'mai-engine' ),
		'block'    => [ 'post', 'term', 'user' ],
		'type'     => 'button_group',
		'sanitize' => 'absint',
		'default'  => 3,
		'choices'  => 'mai_get_columns_choices',
		'atts'     => [
			'wrapper' => [
				'width' => '',
				'class' => 'mai-grid-button-group',
				'id'    => '',
			],
		],
	],
	'field_5e334124b905d' => [
		'name'     => 'columns_responsive',
		'label'    => '',
		'block'    => [ 'post', 'term', 'user' ],
		'type'     => 'true_false',
		'sanitize' => 'mai_sanitize_bool',
		'default'  => 0,
		'atts'     => [
			'message' => esc_html__( 'Custom responsive columns', 'mai-engine' ),
		],
	],
	'field_5e3305dff9d8b' => [
		'name'       => 'columns_md',
		'label'      => esc_html__( 'Columns (lg tablets)', 'mai-engine' ),
		'block'      => [ 'post', 'term', 'user' ],
		'type'       => 'button_group',
		'sanitize'   => 'absint',
		'default'    => 1,
		'choices'    => 'mai_get_columns_choices',
		'conditions' => [
			[
				'field'    => 'field_5e334124b905d', // Columns_responsive.
				'operator' => '==',
				'value'    => 1,
			],
		],
		'atts'       => [
			'wrapper' => [
				'width' => '',
				'class' => 'mai-grid-button-group mai-grid-nested-columns mai-grid-nested-columns-first',
				'id'    => '',
			],
		],
	],
	'field_5e3305f1f9d8c' => [
		'name'       => 'columns_sm',
		'label'      => esc_html__( 'Columns (sm tablets)', 'mai-engine' ),
		'block'      => [ 'post', 'term', 'user' ],
		'type'       => 'button_group',
		'sanitize'   => 'absint',
		'default'    => 1,
		'choices'    => 'mai_get_columns_choices',
		'conditions' => [
			[
				'field'    => 'field_5e334124b905d', // Columns_responsive.
				'operator' => '==',
				'value'    => 1,
			],
		],
		'atts'       => [
			'wrapper' => [
				'width' => '',
				'class' => 'mai-grid-button-group mai-grid-nested-columns',
				'id'    => '',
			],
		],
	],
	'field_5e332a5f7fe08' => [
		'name'       => 'columns_xs',
		'label'      => esc_html__( 'Columns (mobile)', 'mai-engine' ),
		'block'      => [ 'post', 'term', 'user' ],
		'type'       => 'button_group',
		'sanitize'   => 'absint',
		'default'    => 1,
		'choices'    => 'mai_get_columns_choices',
		'conditions' => [
			[
				'field'    => 'field_5e334124b905d', // Columns_responsive.
				'operator' => '==',
				'value'    => 1,
			],
		],
		'atts'       => [
			'wrapper' => [
				'width' => '',
				'class' => 'mai-grid-button-group mai-grid-nested-columns mai-grid-nested-columns-last',
				'id'    => '',
			],
		],
	],
	'field_5c853e6672972' => [
		'name'       => 'align_columns',
		'label'      => esc_html__( 'Align Columns', 'mai-engine' ),
		'block'      => [ 'post', 'term', 'user' ],
		'type'       => 'button_group',
		'sanitize'   => 'esc_html',
		'default'    => '',
		'choices'    => [
			''       => esc_html__( 'Clear', 'mai-engine' ),
			'start'  => esc_html__( 'Start', 'mai-engine' ),
			'center' => esc_html__( 'Center', 'mai-engine' ),
			'end'    => esc_html__( 'End', 'mai-engine' ),
		],
		'conditions' => [
			[
				'field'    => 'field_5c854069d358c', // Columns.
				'operator' => '!=',
				'value'    => 1,
			],
		],
		'atts'       => [
			'wrapper' => [
				'width' => '',
				'class' => 'mai-grid-button-group mai-grid-button-group-clear',
				'id'    => '',
			],
		],
	],
	'field_5e31d5f0e2867' => [
		'name'       => 'align_columns_vertical',
		'label'      => esc_html__( 'Align Columns (vertical)', 'mai-engine' ),
		'block'      => [ 'post', 'term', 'user' ],
		'type'       => 'button_group',
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
				'field'    => 'field_5c854069d358c', // Columns.
				'operator' => '!=',
				'value'    => 1,
			],
		],
		'atts'       => [
			'wrapper' => [
				'width' => '',
				'class' => 'mai-grid-button-group mai-grid-button-group-clear',
				'id'    => '',
			],
		],
	],
	'field_5c8542d6a67c5' => [
		'name'     => 'column_gap',
		'label'    => esc_html__( 'Column Gap', 'mai-engine' ),
		'block'    => [ 'post', 'term', 'user' ],
		'type'     => 'text',
		'sanitize' => 'esc_html',
		'default'  => '24px',
	],
	'field_5e29f1785bcb6' => [
		'name'     => 'row_gap',
		'label'    => esc_html__( 'Row Gap', 'mai-engine' ),
		'block'    => [ 'post', 'term', 'user' ],
		'type'     => 'text',
		'sanitize' => 'esc_html',
		'default'  => '24px',
	],
	/***********
	 * Entries *
	 */
	'field_5df13446c49cf' => [
		'name'    => 'entries_tab',
		'label'   => esc_html__( 'Entries', 'mai-engine' ),
		'block'   => [ 'post', 'term', 'user' ],
		'type'    => 'tab',
		'default' => '',
	],
	/*********
	 * Posts *
	 */
	'field_5df1053632ca2' => [
		'name'     => 'post_type',
		'label'    => esc_html__( 'Post Type', 'mai-engine' ),
		'block'    => [ 'post' ],
		'type'     => 'select',
		'sanitize' => 'esc_html',
		'default'  => [ 'post' ],
		'choices'  => 'mai_get_post_type_choices',
		'atts'     => [
			'multiple' => 1,
			'ui'       => 1,
			'ajax'     => 0,
		],
	],
	'field_5df1053632cad' => [
		'name'       => 'query_by',
		'label'      => esc_html__( 'Get Entries By', 'mai-engine' ),
		'block'      => [ 'post' ],
		'type'       => 'select',
		'sanitize'   => 'esc_html',
		'default'    => '',
		'choices'    => [
			''         => esc_html__( 'Default Query', 'mai-engine' ),
			'id'       => esc_html__( 'Choice', 'mai-engine' ),
			'tax_meta' => esc_html__( 'Taxonomy/Meta', 'mai-engine' ),
			'parent'   => esc_html__( 'Parent', 'mai-engine' ),
		],
		'conditions' => [
			[
				'field'    => 'field_5df1053632ca2', // Post_type.
				'operator' => '!=empty',
			],
		],
	],
	'field_5df1053632ca8' => [
		'name'       => 'posts_per_page',
		'label'      => esc_html__( 'Number of Entries', 'mai-engine' ),
		'desc'       => esc_html__( 'Use 0 to show all.', 'mai-engine' ),
		'block'      => [ 'post' ],
		'type'       => 'number',
		'sanitize'   => 'absint',
		'default'    => 12,
		'conditions' => [
			[
				'field'    => 'field_5df1053632ca2', // Post_type.
				'operator' => '!=empty',
			],
			[
				'field'    => 'field_5df1053632cad', // Query_by.
				'operator' => '!=',
				'value'    => 'id',
			],
		],
		'atts'       => [
			'placeholder' => 12,
			'min'         => 0,
		],
	],
	'field_5df1053632cbc' => [
		'name'       => 'post__in',
		'label'      => esc_html__( 'Choose Entries', 'mai-engine' ),
		'desc'       => esc_html__( 'Show specific entries. Choose all that apply.', 'mai-engine' ),
		'block'      => [ 'post' ],
		'type'       => 'post_object',
		'sanitize'   => 'absint',
		'default'    => '',
		// 'choices'    => 'mai_get_acf_post_choices',
		'conditions' => [
			[
				'field'    => 'field_5df1053632ca2', // Post_type.
				'operator' => '!=empty',
			],
			[
				'field'    => 'field_5df1053632cad', // Query_by.
				'operator' => '==',
				'value'    => 'id',
			],
		],
		'atts'       => [
			'multiple'      => 1,
			'return_format' => 'id',
			'ui'            => 1,
		],
	],
	'field_5df1397316270' => [
		'name'       => 'taxonomies',
		'label'      => esc_html__( 'Taxonomies', 'mai-engine' ),
		'block'      => [ 'post' ],
		'type'       => 'repeater',
		'default'    => '',
		'conditions' => [
			[
				'field'    => 'field_5df1053632ca2', // Post_type.
				'operator' => '!=empty',
			],
			[
				'field'    => 'field_5df1053632cad', // Query_by.
				'operator' => '==',
				'value'    => 'tax_meta',
			],
		],
		'atts'       => [
			'collapsed'    => 'field_5df1398916271',
			'layout'       => 'block',
			'button_label' => esc_html__( 'Add Condition', 'mai-engine' ),
			'sub_fields'   => [
				'field_5df1398916271' => [
					'name'     => 'taxonomy',
					'label'    => esc_html__( 'Taxonomy', 'mai-engine' ),
					'block'    => [ 'post' ],
					'type'     => 'select',
					'sanitize' => 'esc_html',
					'default'  => '',
					'choices'  => 'mai_get_post_type_taxonomy_choices',
					'atts'     => [
						'ui'   => 1,
						'ajax' => 1,
					],
				],
				'field_5df139a216272' => [
					'name'       => 'terms',
					'label'      => esc_html__( 'Terms', 'mai-engine' ),
					'block'      => [ 'post' ],
					'type'       => 'taxonomy',
					'sanitize'   => 'absint',
					'default'    => [],
					'conditions' => [
						[
							'field'    => 'field_5df1398916271', // Taxonomy.
							'operator' => '!=empty',
						],
					],
					'atts'       => [
						'field_type' => 'multi_select',
						'add_term'   => 0,
						'save_terms' => 0,
						'load_terms' => 0,
						'multiple'   => 0,
					],
				],
				'field_5df18f2305c2c' => [
					'name'       => 'operator',
					'label'      => esc_html__( 'Operator', 'mai-engine' ),
					'block'      => [ 'post' ],
					'type'       => 'select',
					'sanitize'   => 'esc_html',
					'default'    => 'IN',
					'choices'    => [
						'IN'     => esc_html__( 'In', 'mai-engine' ),
						'NOT IN' => esc_html__( 'Not In', 'mai-engine' ),
					],
					'conditions' => [
						[
							'field'    => 'field_5df1398916271', // Taxonomy.
							'operator' => '!=empty',
						],
					],
				],
			],
		],
	],
	'field_5df139281626f' => [
		'name'       => 'taxonomies_relation',
		'label'      => esc_html__( 'Taxonomies Relation', 'mai-engine' ),
		'block'      => [ 'post' ],
		'type'       => 'select',
		'sanitize'   => 'esc_html',
		'default'    => 'AND',
		'choices'    => [
			'AND' => esc_html__( 'And', 'mai-engine' ),
			'OR'  => esc_html__( 'Or', 'mai-engine' ),
		],
		'conditions' => [
			[
				'field'    => 'field_5df1053632ca2', // Post_type.
				'operator' => '!=empty',
			],
			[
				'field'    => 'field_5df1053632cad', // Query_by.
				'operator' => '==',
				'value'    => 'tax_meta',
			],
			[
				'field'    => 'field_5df1397316270', // Taxonomies.
				'operator' => '>',
				'value'    => '1', // More than 1 row.
			],
		],
	],
	'field_5df2053632dg5' => [
		'name'       => 'meta_keys',
		'label'      => esc_html__( 'Meta Keys', 'mai-engine' ),
		'block'      => [ 'post' ],
		'type'       => 'repeater',
		'default'    => '',
		'conditions' => [
			[
				'field'    => 'field_5df1053632ca2', // Post_type.
				'operator' => '!=empty',
			],
			[
				'field'    => 'field_5df1053632cad', // Query_by.
				'operator' => '==',
				'value'    => 'tax_meta',
			],
		],
		'atts'       => [
			'collapsed'    => 'field_5df3398916382',
			'layout'       => 'block',
			'button_label' => esc_html__( 'Add Condition', 'mai-engine' ),
			'sub_fields'   => [
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'field_5df3398916382' => [
					'name'     => 'meta_key',
					'label'    => esc_html__( 'Meta Key', 'mai-engine' ),
					'block'    => [ 'post' ],
					'type'     => 'text',
					'sanitize' => 'esc_html',
					'default'  => '',
				],
				'field_5df29f2315d3d' => [
					'name'       => 'meta_compare',
					'label'      => esc_html__( 'Compare', 'mai-engine' ),
					'block'      => [ 'post' ],
					'type'       => 'select',
					'sanitize'   => 'esc_html',
					'default'    => '',
					'choices'    => [
						'='          => __( 'Is equal to', 'mai-engine' ),
						'!='         => __( 'Is not equal to', 'mai-engine' ),
						'>'          => __( 'Is greater than', 'mai-engine' ),
						'>='         => __( 'Is great than or equal to', 'mai-engine' ),
						'<'          => __( 'Is less than', 'mai-engine' ),
						'<='         => __( 'Is less than or equal to', 'mai-engine' ),
						'EXISTS'     => __( 'Exists', 'mai-engine' ),
						'NOT EXISTS' => __( 'Does not exist', 'mai-engine' ),
					],
					'conditions' => [
						[
							'field'    => 'field_5df3398916382', // Meta_key.
							'operator' => '!=empty',
						],
					],
				],
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'field_5df239a217383' => [
					'name'       => 'meta_value',
					'label'      => esc_html__( 'Meta Value', 'mai-engine' ),
					'block'      => [ 'post' ],
					'type'       => 'text',
					'sanitize'   => 'esc_html',
					'default'    => '',
					'conditions' => [
						[
							'field'    => 'field_5df3398916382', // Meta_key.
							'operator' => '!=empty',
						],
						[
							'field'    => 'field_5df29f2315d3d', // Meta_compare.
							'operator' => '!=',
							'value'    => 'EXISTS',
						],
					],
				],
			],
		],
	],
	'field_5df239282737g' => [
		'name'       => 'meta_keys_relation',
		'label'      => esc_html__( 'Meta Keys Relation', 'mai-engine' ),
		'block'      => [ 'post' ],
		'type'       => 'select',
		'sanitize'   => 'esc_html',
		'default'    => 'AND',
		'choices'    => [
			'AND' => esc_html__( 'And', 'mai-engine' ),
			'OR'  => esc_html__( 'Or', 'mai-engine' ),
		],
		'conditions' => [
			[
				'field'    => 'field_5df1053632ca2', // Post_type.
				'operator' => '!=empty',
			],
			[
				'field'    => 'field_5df1053632cad', // Query_by.
				'operator' => '==',
				'value'    => 'tax_meta',
			],
			[
				'field'    => 'field_5df2053632dg5', // Meta_keys.
				'operator' => '>',
				'value'    => '1', // More than 1 row.
			],
		],
	],
	'field_5df1053632ce4' => [
		'name'       => 'post_parent__in',
		'label'      => esc_html__( 'Parent', 'mai-engine' ),
		'block'      => [ 'post' ],
		'type'       => 'post_object',
		'sanitize'   => 'absint',
		'default'    => '',
		'conditions' => [
			[
				'field'    => 'field_5df1053632ca2', // Post_type.
				'operator' => '!=empty',
			],
			[
				'field'    => 'field_5df1053632cad', // Query_by.
				'operator' => '==',
				'value'    => 'parent',
			],
		],
		'atts'       => [
			'multiple'      => 1, // WP_Query allows multiple parents.
			'return_format' => 'id',
			'ui'            => 1,
		],
	],
	'field_5df1bf01ea1de' => [
		'name'       => 'offset',
		'label'      => esc_html__( 'Offset', 'mai-engine' ),
		'desc'       => esc_html__( 'Skip this number of entries.', 'mai-engine' ),
		'block'      => [ 'post' ],
		'type'       => 'number',
		'sanitize'   => 'absint',
		'default'    => 0,
		'conditions' => [
			[
				'field'    => 'field_5df1053632ca2', // Post_type.
				'operator' => '!=empty',
			],
			[
				'field'    => 'field_5df1053632cad', // Query_by.
				'operator' => '!=',
				'value'    => 'id',
			],
		],
		'atts'       => [
			'placeholder' => 0,
			'min'         => 0,
		],
	],
	'field_5df1053632cec' => [
		'name'       => 'orderby',
		'label'      => esc_html__( 'Order By', 'mai-engine' ),
		'block'      => [ 'post' ],
		'type'       => 'select',
		'sanitize'   => 'esc_html',
		'default'    => 'date',
		'choices'    => [
			'title'          => esc_html__( 'Title', 'mai-engine' ),
			'name'           => esc_html__( 'Slug', 'mai-engine' ),
			'date'           => esc_html__( 'Date', 'mai-engine' ),
			'modified'       => esc_html__( 'Modified', 'mai-engine' ),
			'rand'           => esc_html__( 'Random', 'mai-engine' ),
			'comment_count'  => esc_html__( 'Comment Count', 'mai-engine' ),
			'menu_order'     => esc_html__( 'Menu Order', 'mai-engine' ),
			'meta_value_num' => esc_html__( 'Meta Value Number', 'mai-engine' ),
		],
		'conditions' => [
			[
				'field'    => 'field_5df1053632ca2', // Post_type.
				'operator' => '!=empty',
			],
			[
				'field'    => 'field_5df1053632cad', // Query_by.
				'operator' => '!=',
				'value'    => 'id',
			],
		],
		'atts'       => [
			'ui'   => 1,
			'ajax' => 1,
		],
	],
	'field_5df1053632cf4' => [
		'name'       => 'orderby_meta_key',
		'label'      => esc_html__( 'Meta key', 'mai-engine' ),
		'block'      => [ 'post' ],
		'type'       => 'text',
		'sanitize'   => 'esc_html',
		'default'    => '',
		'conditions' => [
			[
				'field'    => 'field_5df1053632ca2', // Post_type.
				'operator' => '!=empty',
			],
			[
				'field'    => 'field_5df1053632cec', // Orderby.
				'operator' => '==',
				'value'    => 'meta_value_num',
			],
		],
	],
	'field_5df1053632cfb' => [
		'name'       => 'order',
		'label'      => esc_html__( 'Order', 'mai-engine' ),
		'block'      => [ 'post' ],
		'type'       => 'select',
		'sanitize'   => 'esc_html',
		'default'    => 'DESC',
		'choices'    => [
			'ASC'  => esc_html__( 'Ascending', 'mai-engine' ),
			'DESC' => esc_html__( 'Descending', 'mai-engine' ),
		],
		'conditions' => [
			[
				'field'    => 'field_5df1053632ca2', // Post_type.
				'operator' => '!=empty',
			],
			[
				'field'    => 'field_5df1053632cad', // Query_by.
				'operator' => '!=',
				'value'    => 'id',
			],
		],
	],
	'field_5e349237e1c01' => [
		'name'       => 'post__not_in',
		'label'      => esc_html__( 'Exclude Entries', 'mai-engine' ),
		'desc'       => esc_html__( 'Hide specific entries. Choose all that apply.', 'mai-engine' ),
		'block'      => [ 'post' ],
		'type'       => 'post_object',
		'sanitize'   => 'absint',
		'default'    => '',
		'conditions' => [
			[
				'field'    => 'field_5df1053632ca2', // Post_type.
				'operator' => '!=empty',
			],
			[
				'field'    => 'field_5df1053632cad', // Query_by.
				'operator' => '!=',
				'value'    => 'id',
			],
		],
		'atts'       => [
			'multiple'      => 1,
			'return_format' => 'id',
			'ui'            => 1,
		],
	],
	'field_5df1053632d03' => [
		'name'       => 'excludes',
		'label'      => esc_html__( 'Exclude', 'mai-engine' ),
		'block'      => [ 'post' ],
		'type'       => 'checkbox',
		'sanitize'   => 'esc_html',
		'default'    => '',
		'choices'    => [
			'exclude_displayed' => esc_html__( 'Exclude displayed', 'mai-engine' ),
			'exclude_current'   => esc_html__( 'Exclude current', 'mai-engine' ),
		],
		'conditions' => [
			[
				'field'    => 'field_5df1053632ca2', // Post_type.
				'operator' => '!=empty',
			],
		],
	],
	/*********
	 * Terms *
	 */
	'field_5df2063632ca2' => [
		'name'     => 'taxonomy',
		'label'    => esc_html__( 'Taxonomy', 'mai-engine' ),
		'block'    => [ 'term' ],
		'type'     => 'select',
		'sanitize' => 'esc_html',
		'default'  => [ 'category' ],
		'choices'  => 'mai_get_taxonomy_choices',
		'atts'     => [
			'multiple' => 1,
			'ui'       => 1,
			'ajax'     => 0,
		],
	],
	'field_5df1054642cad' => [
		'name'       => 'query_by',
		'label'      => esc_html__( 'Get Entries By', 'mai-engine' ),
		'block'      => [ 'term' ],
		'type'       => 'select',
		'sanitize'   => 'esc_html',
		'default'    => 'date',
		'choices'    => [
			'name'   => esc_html__( 'Taxonomy', 'mai-engine' ),
			'id'     => esc_html__( 'Name', 'mai-engine' ),
			'parent' => esc_html__( 'Parent', 'mai-engine' ),
		],
		'conditions' => [
			[
				'field'    => 'field_5df2063632ca2', // Taxonomy.
				'operator' => '!=empty',
			],
		],
	],
	'field_5df2065733db9' => [
		'name'       => 'number',
		'label'      => esc_html__( 'Number of Entries', 'mai-engine' ),
		'desc'       => esc_html__( 'Use 0 to show all.', 'mai-engine' ),
		'block'      => [ 'term' ],
		'type'       => 'number',
		'sanitize'   => 'absint',
		'default'    => 12,
		'conditions' => [
			[
				'field'    => 'field_5df2063632ca2', // Taxonomy.
				'operator' => '!=empty',
			],
			[
				'field'    => 'field_5df1054642cad', // Query_by.
				'operator' => '!=',
				'value'    => 'id',
			],
		],
		'atts'       => [
			'placeholder' => 12,
			'min'         => 0,
		],
	],
	'field_5df10647743cb' => [
		'name'       => 'include',
		'label'      => esc_html__( 'Entries', 'mai-engine' ),
		'desc'       => esc_html__( 'Show specific entries. Choose all that apply. If empty, Grid will get entries by date.', 'mai-engine' ),
		'block'      => [ 'term' ],
		'type'       => 'taxonomy',
		'sanitize'   => 'absint',
		'default'    => '',
		'conditions' => [
			[
				'field'    => 'field_5df2063632ca2', // Taxonomy.
				'operator' => '!=empty',
			],
			[
				'field'    => 'field_5df1054642cad', // Query_by.
				'operator' => '==',
				'value'    => 'id',
			],
		],
		'atts'       => [
			'field_type' => 'multi_select',
			'add_term'   => 0,
			'save_terms' => 0,
			'load_terms' => 0,
			'multiple'   => 1,
		],
	],
	'field_5df1054743df5' => [
		'name'       => 'parent',
		'label'      => esc_html__( 'Parent', 'mai-engine' ),
		'block'      => [ 'term' ],
		'type'       => 'taxonomy',
		'sanitize'   => 'absint',
		'default'    => '',
		'conditions' => [
			[
				'field'    => 'field_5df2063632ca2', // Taxonomy.
				'operator' => '!=empty',
			],
			[
				'field'    => 'field_5df1054642cad', // Query_by.
				'operator' => '==',
				'value'    => 'parent',
			],
		],
		'atts'       => [
			'field_type' => 'select',
			'add_term'   => 0,
			'save_terms' => 0,
			'load_terms' => 0,
			'multiple'   => 0, // WP_Term_Query only allows 1.
		],
	],
	'field_5df2cg12fb2ef' => [
		'name'       => 'offset',
		'label'      => esc_html__( 'Offset', 'mai-engine' ),
		'desc'       => esc_html__( 'Skip this number of entries.', 'mai-engine' ),
		'block'      => [ 'term' ],
		'type'       => 'number',
		'sanitize'   => 'absint',
		'default'    => 0,
		'conditions' => [
			[
				'field'    => 'field_5df2063632ca2', // Taxonomy.
				'operator' => '!=empty',
			],
			[
				'field'    => 'field_5df1053632cad', // Query_by.
				'operator' => '!=',
				'value'    => 'id',
			],
		],
		'atts'       => [
			'placeholder' => 0,
			'min'         => 0,
		],
	],
	'field_5dg2164743dfd' => [
		'name'       => 'orderby',
		'label'      => esc_html__( 'Order By', 'mai-engine' ),
		'block'      => [ 'term' ],
		'type'       => 'select',
		'sanitize'   => 'esc_html',
		'default'    => 'date',
		'choices'    => [
			'name'  => esc_html__( 'Title', 'mai-engine' ),
			'slug'  => esc_html__( 'Slug', 'mai-engine' ),
			'count' => esc_html__( 'Entry Totals', 'mai-engine' ),
			'id'    => esc_html__( 'Term ID', 'mai-engine' ),
		],
		'conditions' => [
			[
				'field'    => 'field_5df2063632ca2', // Taxonomy.
				'operator' => '!=empty',
			],
			[
				'field'    => 'field_5df1054642cad', // Query_by.
				'operator' => '!=',
				'value'    => 'id',
			],
		],
		'atts'       => [
			'ui'   => 1,
			'ajax' => 1,
		],
	],
	'field_5df2164743dgc' => [
		'name'       => 'order',
		'label'      => esc_html__( 'Order', 'mai-engine' ),
		'block'      => [ 'term' ],
		'type'       => 'select',
		'sanitize'   => 'esc_html',
		'default'    => '',
		'choices'    => [
			'ASC'  => esc_html__( 'Ascending', 'mai-engine' ),
			'DESC' => esc_html__( 'Descending', 'mai-engine' ),
		],
		'conditions' => [
			[
				'field'    => 'field_5df2063632ca2', // Taxonomy.
				'operator' => '!=empty',
			],
			[
				'field'    => 'field_5df1054642cad', // Query_by.
				'operator' => '!=',
				'value'    => 'id',
			],
		],
	],
	'field_5e459348f2d12' => [
		'name'       => 'exclude',
		'label'      => esc_html__( 'Exclude Entries', 'mai-engine' ),
		'desc'       => esc_html__( 'Hide specific entries. Choose all that apply.', 'mai-engine' ),
		'block'      => [ 'term' ],
		'type'       => 'taxonomy',
		'sanitize'   => 'absint',
		'default'    => '',
		'conditions' => [
			[
				'field'    => 'field_5df2063632ca2', // Taxonomy.
				'operator' => '!=empty',
			],
			[
				'field'    => 'field_5df1054642cad', // Query_by.
				'operator' => '!=',
				'value'    => 'id',
			],
		],
		'atts'       => [
			'field_type' => 'multi_select',
			'add_term'   => 0,
			'save_terms' => 0,
			'load_terms' => 0,
			'multiple'   => 1,
		],
	],
	// TODO: Shoud these be separate fields? We can then have desc text and easier to check when building query.
	'field_5df21757632e1' => [
		'name'       => 'excludes',
		'label'      => esc_html__( 'Exclude', 'mai-engine' ),
		'block'      => [ 'term' ],
		'type'       => 'checkbox',
		'sanitize'   => 'esc_html',
		'default'    => [
			'hide_empty',
		],
		'choices'    => [
			'hide_empty'        => esc_html__( 'Exclude terms with no posts', 'mai-engine' ),
			'exclude_displayed' => esc_html__( 'Exclude displayed', 'mai-engine' ),
			'exclude_current'   => esc_html__( 'Exclude current', 'mai-engine' ),
		],
		'conditions' => [
			[
				'field'    => 'field_5df2063632ca2', // Taxonomy.
				'operator' => '!=empty',
			],
		],
	],
];
