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
			'excerpt',
			'content',
			'genesis_entry_content',
			'more_link',
			'genesis_after_entry_content',
			'footer_meta',
			'genesis_entry_footer',
		],
		'choices'  => 'mai_get_single_show_choices',
	],
];
