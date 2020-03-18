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
			'genesis_entry_header',
			'title',
			'image',
			'header_meta',
			'genesis_before_entry_content',
			// 'excerpt',
			'content',
			'genesis_entry_content',
			'more_link',
			'genesis_after_entry_content',
			'footer_meta',
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
			'author_box'                   => 'author_box',
			'after_entry'                  => 'after_entry',
			'adjacent_entry_nav'           => 'adjacent_entry_nav',
		],
	],
];
