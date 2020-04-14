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
	'google-fonts'        => [
		'Playfair+Display:700|Open+Sans:400',
	],
	'theme-support'       => [
		'add' => [
			'sticky-header',
			// 'transparent-header',
			'genesis-footer-widgets' => 2,
		],
	],
	'image-sizes'         => [
		'add' => [
			'landscape' => '4:3',
			'portrait'  => '3:4',
			'square'    => '1:1',
		],
	],
	'page-header-single'  => [ 'page', 'post' ],
	'page-header-archive' => [ 'category', 'product', 'post' ],
];
