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
		'block'    => [ 'post', 'term', 'user' ],
		'type'     => 'checkbox',
		'sanitize' => 'esc_html',
		'default'  => [ 'image', 'title' ],
		'choices'  => [
			'image'       => esc_html__( 'Image', 'mai-engine' ),
			'title'       => esc_html__( 'Title', 'mai-engine' ),
			'header_meta' => esc_html__( 'Header Meta', 'mai-engine' ),
			'excerpt'     => esc_html__( 'Excerpt', 'mai-engine' ),
			'content'     => esc_html__( 'Content', 'mai-engine' ),
			'more_link'   => esc_html__( 'Read More link', 'mai-engine' ),
			'footer_meta' => esc_html__( 'Footer Meta', 'mai-engine' ),
		],
		'atts'     => [
			'wrapper' => [
				'width' => '',
				'class' => 'mai-sortable',
				'id'    => '',
			],
		],
	],
];
