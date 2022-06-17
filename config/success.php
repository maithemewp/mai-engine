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
		// 'local'    => 3,
		// 'coaching' => 4,
		// 'business' => 5,
	],
	'global-styles' => [
		'colors' => [
			'alt'       => '#f6f7f8',
			'header'    => '#232c39',
			'heading'   => '#232c39',
			'link'      => '#fb2056',
			'primary'   => '#fb2056',
			'secondary' => '#8693a6',
		],
		'fonts'  => [
			'body'    => 'Hind:400',
			'heading' => 'Montserrat:600',
		],
		'font-variants'  => [
			'heading' => [
				'light' => '600', // Always loads 600 weight since this is used for menus.
			],
		],
	],
	'theme-support' => [
		'add' => [
			'transparent-header',
			'sticky-header',
		],
	],
	'settings'      => [
		'page-header' => [
			'archive'          => '*',
			'single'           => '*',
			'image'            => '',
			'background-color' => 'heading',
			'text-color'       => 'light',
			'divider-color'    => 'white',
		],
	],
];
