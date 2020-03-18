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
		'choices'    => [
			'landscape' => esc_html__( 'Landscape', 'mai-engine' ),
			'portrait'  => esc_html__( 'Portrait', 'mai-engine' ),
			'square'    => esc_html__( 'Square', 'mai-engine' ),
			'custom'    => esc_html__( 'Custom', 'mai-engine' ),
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
	// [
	// 	'name'       => 'header_meta',
	// 	'label'      => esc_html__( 'Header Meta', 'mai-engine' ),
	// 	'type'       => 'text',
	// 	'sanitize'   => 'wp_kses_post',
	// 	// TODO: this should be different, or empty depending on the post type?
	// 	'default'    => '[post_date] [post_author_posts_link before="by "]',
	// 	'conditions' => [
	// 		[
	// 			'setting'  => 'show',
	// 			'operator' => '==',
	// 			'value'    => 'header_meta',
	// 		],
	// 	],
	// 	'atts'       => [
	// 		'wrapper' => [
	// 			'width' => '',
	// 			'class' => 'mai-grid-show-conditional',
	// 			'id'    => '',
	// 		],
	// 	],
	// ],
	// [
	// 	'name'       => 'content_limit',
	// 	'label'      => esc_html__( 'Content Limit', 'mai-engine' ),
	// 	'desc'       => esc_html__( 'Limit the number of characters shown for the content or excerpt. Use 0 for no limit.', 'mai-engine' ),
	// 	'type'       => 'text',
	// 	'sanitize'   => 'absint',
	// 	'default'    => 0,
	// 	'conditions' => [
	// 		[
	// 			[
	// 				'setting'  => 'show',
	// 				'operator' => '==',
	// 				'value'    => 'excerpt',
	// 			],
	// 		],
	// 		[
	// 			[
	// 				'setting'  => 'show',
	// 				'operator' => '==',
	// 				'value'    => 'content',
	// 			],
	// 		],
	// 	],
	// 	'atts'       => [
	// 		'wrapper' => [
	// 			'width' => '',
	// 			'class' => 'mai-grid-show-conditional',
	// 			'id'    => '',
	// 		],
	// 	],
	// ],
	// [
	// 	'name'       => 'more_link_text',
	// 	'label'      => esc_html__( 'More Link Text', 'mai-engine' ),
	// 	'type'       => 'text',
	// 	'sanitize'   => 'esc_attr', // We may want to add icons/spans and HTML in here.
	// 	'default'    => '',
	// 	'conditions' => [
	// 		[
	// 			'setting'  => 'show',
	// 			'operator' => '==',
	// 			'value'    => 'more_link',
	// 		],
	// 	],
	// 	'atts'       => [
	// 		// TODO: This text should be filtered, same as the template that outputs it.
	// 		'placeholder' => esc_html__( 'Read More', 'mai-engine' ),
	// 		'wrapper'     => [
	// 			'width' => '',
	// 			'class' => 'mai-grid-show-conditional',
	// 			'id'    => '',
	// 		],
	// 	],
	// ],
	// [
	// 	'name'       => 'footer_meta',
	// 	'label'      => esc_html__( 'Footer Meta', 'mai-engine' ),
	// 	'type'       => 'text',
	// 	'sanitize'   => 'wp_kses_post',
	// 	// TODO: this should be different, or empty depending on the post type?
	// 	'default'    => '[post_categories]',
	// 	'conditions' => [
	// 		[
	// 			'setting'  => 'show',
	// 			'operator' => '==',
	// 			'value'    => 'footer_meta',
	// 		],
	// 	],
	// 	'atts'       => [
	// 		'wrapper' => [
	// 			'width' => '',
	// 			'class' => 'mai-grid-show-conditional',
	// 			'id'    => '',
	// 		],
	// 	],
	// ],
	// [
	// 	'name'     => 'boxed',
	// 	'label'    => esc_html__( 'Boxed', 'mai-engine' ),
	// 	'type'     => 'true_false',
	// 	'sanitize' => 'esc_html',
	// 	'default'  => 1, // True.
	// 	'atts'     => [
	// 		'message' => __( 'Display boxed', 'mai-engine' ),
	// 	],
	// ],
	// [
	// 	'name'     => 'align_text',
	// 	'label'    => esc_html__( 'Align Text', 'mai-engine' ),
	// 	'type'     => 'button_group',
	// 	'sanitize' => 'esc_html',
	// 	'default'  => '',
	// 	'choices'  => [
	// 		''       => esc_html__( 'Clear', 'mai-engine' ),
	// 		'start'  => esc_html__( 'Start', 'mai-engine' ),
	// 		'center' => esc_html__( 'Center', 'mai-engine' ),
	// 		'end'    => esc_html__( 'End', 'mai-engine' ),
	// 	],
	// 	'atts'     => [
	// 		'wrapper' => [
	// 			'width' => '',
	// 			'class' => 'mai-grid-button-group mai-grid-button-group-clear',
	// 			'id'    => '',
	// 		],
	// 	],
	// ],
	// [
	// 	'name'       => 'align_text_vertical',
	// 	'label'      => esc_html__( 'Align Text (vertical)', 'mai-engine' ),
	// 	'type'       => 'button_group',
	// 	'sanitize'   => 'esc_html',
	// 	'default'    => '',
	// 	'choices'    => [
	// 		''       => esc_html__( 'Clear', 'mai-engine' ),
	// 		'top'    => esc_html__( 'Top', 'mai-engine' ),
	// 		'middle' => esc_html__( 'Middle', 'mai-engine' ),
	// 		'bottom' => esc_html__( 'Bottom', 'mai-engine' ),
	// 	],
	// 	'conditions' => [
	// 		[
	// 			[
	// 				'setting'  => 'field_5e2f3adf82130', // Image_position.
	// 				'operator' => '==',
	// 				'value'    => 'left',
	// 			],
	// 		],
	// 		[
	// 			[
	// 				'setting'  => 'field_5e2f3adf82130', // Image_position.
	// 				'operator' => '==',
	// 				'value'    => 'background',
	// 			],
	// 		],
	// 	],
	// 	'atts'       => [
	// 		'wrapper' => [
	// 			'width' => '',
	// 			'class' => 'mai-grid-button-group mai-grid-button-group-clear',
	// 			'id'    => '',
	// 		],
	// 	],
	// ],
	// /********
	//  * Layout
	//  */
	// [
	// 	'name'    => 'layout_tab',
	// 	'label'   => esc_html__( 'Layout', 'mai-engine' ),
	// 	'type'    => 'tab',
	// 	'default' => '',
	// ],
	// [
	// 	'name'     => 'columns',
	// 	'label'    => esc_html__( 'Columns (desktop)', 'mai-engine' ),
	// 	'type'     => 'button_group',
	// 	'sanitize' => 'absint',
	// 	'default'  => 3,
	// 	'choices'  => [
	// 		1 => esc_html__( '1', 'mai-engine' ),
	// 		2 => esc_html__( '2', 'mai-engine' ),
	// 		3 => esc_html__( '3', 'mai-engine' ),
	// 		4 => esc_html__( '4', 'mai-engine' ),
	// 		5 => esc_html__( '5', 'mai-engine' ),
	// 		6 => esc_html__( '6', 'mai-engine' ),
	// 		0 => esc_html__( 'Auto', 'mai-engine' ),
	// 	],
	// 	'atts'     => [
	// 		'wrapper' => [
	// 			'width' => '',
	// 			'class' => 'mai-grid-button-group',
	// 			'id'    => '',
	// 		],
	// 	],
	// ],
	// [
	// 	'name'     => 'columns_responsive',
	// 	'label'    => '',
	// 	'type'     => 'true_false',
	// 	'sanitize' => 'mai_sanitize_bool',
	// 	'default'  => 0,
	// 	'atts'     => [
	// 		'message' => esc_html__( 'Custom responsive columns', 'mai-engine' ),
	// 	],
	// ],
	// [
	// 	'name'       => 'columns_md',
	// 	'label'      => esc_html__( 'Columns (lg tablets)', 'mai-engine' ),
	// 	'type'       => 'button_group',
	// 	'sanitize'   => 'absint',
	// 	'default'    => 1,
	// 	'choices'    => [
	// 		1 => esc_html__( '1', 'mai-engine' ),
	// 		2 => esc_html__( '2', 'mai-engine' ),
	// 		3 => esc_html__( '3', 'mai-engine' ),
	// 		4 => esc_html__( '4', 'mai-engine' ),
	// 		5 => esc_html__( '5', 'mai-engine' ),
	// 		6 => esc_html__( '6', 'mai-engine' ),
	// 		0 => esc_html__( 'Auto', 'mai-engine' ),
	// 	],
	// 	'conditions' => [
	// 		[
	// 			'setting'  => 'field_5e334124b905d', // Columns_responsive.
	// 			'operator' => '==',
	// 			'value'    => 1,
	// 		],
	// 	],
	// 	'atts'       => [
	// 		'wrapper' => [
	// 			'width' => '',
	// 			'class' => 'mai-grid-button-group mai-grid-nested-columns mai-grid-nested-columns-first',
	// 			'id'    => '',
	// 		],
	// 	],
	// ],
	// [
	// 	'name'       => 'columns_sm',
	// 	'label'      => esc_html__( 'Columns (sm tablets)', 'mai-engine' ),
	// 	'type'       => 'button_group',
	// 	'sanitize'   => 'absint',
	// 	'default'    => 1,
	// 	'choices'    => [
	// 		1 => esc_html__( '1', 'mai-engine' ),
	// 		2 => esc_html__( '2', 'mai-engine' ),
	// 		3 => esc_html__( '3', 'mai-engine' ),
	// 		4 => esc_html__( '4', 'mai-engine' ),
	// 		5 => esc_html__( '5', 'mai-engine' ),
	// 		6 => esc_html__( '6', 'mai-engine' ),
	// 		0 => esc_html__( 'Auto', 'mai-engine' ),
	// 	],
	// 	'conditions' => [
	// 		[
	// 			'setting'  => 'field_5e334124b905d', // Columns_responsive.
	// 			'operator' => '==',
	// 			'value'    => 1,
	// 		],
	// 	],
	// 	'atts'       => [
	// 		'wrapper' => [
	// 			'width' => '',
	// 			'class' => 'mai-grid-button-group mai-grid-nested-columns',
	// 			'id'    => '',
	// 		],
	// 	],
	// ],
	// [
	// 	'name'       => 'columns_xs',
	// 	'label'      => esc_html__( 'Columns (mobile)', 'mai-engine' ),
	// 	'type'       => 'button_group',
	// 	'sanitize'   => 'absint',
	// 	'default'    => 1,
	// 	'choices'    => [
	// 		1 => esc_html__( '1', 'mai-engine' ),
	// 		2 => esc_html__( '2', 'mai-engine' ),
	// 		3 => esc_html__( '3', 'mai-engine' ),
	// 		4 => esc_html__( '4', 'mai-engine' ),
	// 		5 => esc_html__( '5', 'mai-engine' ),
	// 		6 => esc_html__( '6', 'mai-engine' ),
	// 		0 => esc_html__( 'Auto', 'mai-engine' ),
	// 	],
	// 	'conditions' => [
	// 		[
	// 			'setting'  => 'field_5e334124b905d', // Columns_responsive.
	// 			'operator' => '==',
	// 			'value'    => 1,
	// 		],
	// 	],
	// 	'atts'       => [
	// 		'wrapper' => [
	// 			'width' => '',
	// 			'class' => 'mai-grid-button-group mai-grid-nested-columns mai-grid-nested-columns-last',
	// 			'id'    => '',
	// 		],
	// 	],
	// ],
	// [
	// 	'name'       => 'align_columns',
	// 	'label'      => esc_html__( 'Align Columns', 'mai-engine' ),
	// 	'type'       => 'button_group',
	// 	'sanitize'   => 'esc_html',
	// 	'default'    => '',
	// 	'choices'    => [
	// 		''       => esc_html__( 'Clear', 'mai-engine' ),
	// 		'left'   => esc_html__( 'Left', 'mai-engine' ),
	// 		'center' => esc_html__( 'Center', 'mai-engine' ),
	// 		'right'  => esc_html__( 'Right', 'mai-engine' ),
	// 	],
	// 	'conditions' => [
	// 		[
	// 			'setting'  => 'field_5c854069d358c', // Columns.
	// 			'operator' => '!=',
	// 			'value'    => 1,
	// 		],
	// 	],
	// 	'atts'       => [
	// 		'wrapper' => [
	// 			'width' => '',
	// 			'class' => 'mai-grid-button-group mai-grid-button-group-clear',
	// 			'id'    => '',
	// 		],
	// 	],
	// ],
	// [
	// 	'name'       => 'align_columns_vertical',
	// 	'label'      => esc_html__( 'Align Columns (vertical)', 'mai-engine' ),
	// 	'type'       => 'button_group',
	// 	'sanitize'   => 'esc_html',
	// 	'default'    => '',
	// 	'choices'    => [
	// 		''       => esc_html__( 'Clear', 'mai-engine' ),
	// 		'top'    => esc_html__( 'Top', 'mai-engine' ),
	// 		'middle' => esc_html__( 'Middle', 'mai-engine' ),
	// 		'bottom' => esc_html__( 'Bottom', 'mai-engine' ),
	// 	],
	// 	'conditions' => [
	// 		[
	// 			'setting'  => 'field_5c854069d358c', // Columns.
	// 			'operator' => '!=',
	// 			'value'    => 1,
	// 		],
	// 	],
	// 	'atts'       => [
	// 		'wrapper' => [
	// 			'width' => '',
	// 			'class' => 'mai-grid-button-group mai-grid-button-group-clear',
	// 			'id'    => '',
	// 		],
	// 	],
	// ],
	// [
	// 	'name'     => 'column_gap',
	// 	'label'    => esc_html__( 'Column Gap', 'mai-engine' ),
	// 	'type'     => 'text',
	// 	'sanitize' => 'esc_html',
	// 	'default'  => '24px',
	// ],
	// [
	// 	'name'     => 'row_gap',
	// 	'label'    => esc_html__( 'Row Gap', 'mai-engine' ),
	// 	'type'     => 'text',
	// 	'sanitize' => 'esc_html',
	// 	'default'  => '24px',
	// ],

];
