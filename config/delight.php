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
	'demos'         => [],
	'global-styles' => [
		'colors' => [
			'link'      => '#067ccc',
			'primary'   => '#067ccc',
			'secondary' => '#6c757d',
			'heading'   => '#323232',
			'body'      => '#515151',
		],
		'fonts'  => [
			'body'    => 'Open Sans:400',
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
			'square'    => '1:1',
		],
	],
	'page-header'   => [
		'archive' => [ 'category', 'product', 'post' ],
		'single'  => [ 'page', 'post' ],
	],
];
