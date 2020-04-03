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
		'default'  => '',
		'choices'  => 'mai_get_site_layout_choices',
	],
	[
		'name'     => 'show',
		'label'    => esc_html__( 'Show', 'mai-engine' ),
		'desc'     => esc_html__( 'Show/hide and re-order entry elements. Click "Toggle Hooks" to show Genesis hooks.', 'mai-engine' ),
		'type'     => 'sortable',
		'sanitize' => 'esc_html',
		'default'  => [
			'genesis_entry_header',
			'title',
			'header_meta',
			'image',
			'genesis_before_entry_content',
			'excerpt',
			'content',
			'genesis_entry_content',
			'genesis_after_entry_content',
			'footer_meta',
			'genesis_entry_footer',
			'author_box',
			'after_entry',
			'adjacent_entry_nav',
		],
		'choices'  => 'mai_get_single_show_choices',
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

];
