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
	'demos'              => [],
	'global-styles'      => [
		'colors'            => [
			'background' => '#fcfcfc',
			'alt'        => '#f0f0f0',
			'body'       => '#515151',
			'heading'    => '#323232',
			'link'       => '#067ccc',
			'primary'    => '#067ccc',
			'secondary'  => '#ebe9eb',
		],
		'fonts'             => [
			'body'             => 'Open Sans:300',
			'heading'          => 'Playfair Display:700',
		],
	],
	'theme-support'      => [
		'add'               => [
			'sticky-header',
		],
	],
	'image-sizes'        => [
		'add'               => [
			'landscape'        => '4:3',
			'square'           => '1:1',
		],
	],
	'settings'           => [
		'site-layout'       => [
			'default'          => [
				'site'            => 'standard-content',
				'archive'         => 'wide-content',
			],
		],
		'page-header'       => [
			'archive'          => [ 'post', 'category', 'product', 'product_cat' ],
			'single'           => [ 'page', 'post' ],
			'background-color' => '',
			'image'            => '',
			'overlay-opacity'  => '0',
		],
	],
];
