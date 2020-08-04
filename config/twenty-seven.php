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
		'breakpoint' => 800,
		'colors'     => [
			'link'      => '#a74165',
			'primary'   => '#ffffff',
			'secondary' => '#272727',
			'heading'   => '#272727',
			'body'      => '#272727',
			'alt'       => '#e5e5e5',
		],
		'fonts'      => [
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
		'content-archives' => [
			'boxed' => false,
		],
	],
];
