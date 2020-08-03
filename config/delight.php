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
	'demos'         => [],
	'global-styles' => [
		'colors'     => [
			'link'      => '#067ccc',
			'primary'   => '#067ccc',
			'secondary' => '#ebe9eb',
			'heading'   => '#323232',
			'body'      => '#515151',
		],
		'fonts'      => [
			'body'    => 'Open Sans:300',
			'heading' => 'Playfair Display:700',
		],
	],
	'theme-support' => [
		'add' => [
			'sticky-header',
		],
	],
	'image-sizes'   => [
		'add' => [
			'landscape' => '4:3',
			'portrait'  => '3:4',
		],
	],
	'page-header'   => [
		'archive'          => [ 'category', 'product', 'post' ],
		'single'           => [ 'page', 'post' ],
		'background-color' => '',
		'image'            => '',
		'overlay-opacity'  => '0',
	],
	'extra'         => [
		'border-radius' => '2px',
	],
];
