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
	'demos'         => [
		'default' => 55,
	],
	'global-styles' => [
		'breakpoint'     => 800,
		'contrast-limit' => 200,
		'colors'         => [
			'link'      => '#a74165',
			'primary'   => '#ffffff',
			'secondary' => '#ffffff',
			'heading'   => '#272727',
			'body'      => '#272727',
			'alt'       => '#e5e5e5',
		],
		'fonts'          => [
			'body'    => 'Inter:400',
			'heading' => 'Inter:400',
		],
	],
	'scripts'       => [
		'menus' => [
			'localize' => [
				'data' => [
					'menuToggle' => '<span class="menu-toggle-icon"></span> &nbsp; ' . __( 'Menu', 'mai-engine' ),
				],
			],
		],
	],
	'settings'      => [
		'logo'             => [
			'width' => [
				'desktop' => '60px',
				'mobile'  => '60px',
			],
		],
		'single-content'   => [
			'enable' => [ 'post' ],
			'show'   => [
				'genesis_entry_header',
				'title',
				'header_meta',
				'footer_meta',
				'excerpt',
				'image',
				'genesis_before_entry_content',
				'content',
				'genesis_entry_content',
				'genesis_after_entry_content',
				'genesis_entry_footer',
			],
		],
		'content-archives' => [
			'boxed'             => false,
			'columns'           => '1',
			'image_orientation' => 'custom',
			'image_size'        => 'large',
		],
	],
];
