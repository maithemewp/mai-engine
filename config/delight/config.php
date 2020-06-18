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
	'google-fonts'  => [
		'https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,600;1,300;1,400&family=Playfair+Display:wght@700&display=swap',
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
