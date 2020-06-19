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

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

return [
	'demos'             => [],
	'custom-properties' => [
		'colors' => [
			'primary'   => '#067ccc',
			'secondary' => '#6c757d',
			'success'   => '#28a745',
			'danger'    => '#dc3545',
			'warning'   => '#ffc107',
			'info'      => '#42a2b8',
			'darkest'   => '#323232',
			'dark'      => '#515151',
			'medium'    => '#ebe9eb',
			'lighter'   => '#f0f0f0',
			'lightest'  => '#ffffff',
		],
		'fonts' => [
			'body' => [
				'font-family' => 'Open Sans',
				'variant' => ''
			],
		],
	],
	'google-fonts'      => [
		'https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,600;1,300;1,400&family=Playfair+Display:wght@700&display=swap',
	],
	'theme-support'     => [
		'add' => [
			'sticky-header',
		],
	],
	'image-sizes'       => [
		'add' => [
			'landscape' => '4:3',
			'portrait'  => '3:4',
			'square'    => '1:1',
		],
	],
	'page-header'       => [
		'archive' => [ 'category', 'product', 'post' ],
		'single'  => [ 'page', 'post' ],
	],
];
