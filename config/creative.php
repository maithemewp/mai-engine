<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https:    //bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2020 BizBudding
 * @license   GPL-2.0-or-later
 */

return [
	'demos' => [
		'portfolio' => 59,
	],
	'global-styles' => [
		'colors' => [
			'alt'       => '#f5f5f5',
			'body'      => '#4e4e4e',
			'heading'   => '#2c2c2c',
			'primary'   => '#2c2c2c',
			'secondary' => '#e6e7e9',
			'link'      => '#a6a59b',
		],
		'fonts' => [
			'body'    => 'Open Sans:400',
			'heading' => 'Montserrat:600,500',
		],
		'font-variants'  => [
			'heading' => [
				'light' => '400', // Always loads regular weight since this is used for menus.
			],
		],
	],
	'image-sizes' => [
		'add'  => [
			'square' => '1:1',
		],
	],
	'settings' => [
		'logo' => [
			'width' => [
				'desktop' => '300px',
			],
		],
		'site-layouts' => [
			'default'  => [
				'site' => 'content-sidebar',
			],
		],
		'content-archives' => [
			'post' => [
				'columns' => '1',
				'boxed'   => false,
			],
		],
	],
];
