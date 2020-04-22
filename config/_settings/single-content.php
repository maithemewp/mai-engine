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
		'default'     => '',
		'choices'     => mai_get_site_layout_choices(),
	],
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
];
