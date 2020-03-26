<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2019 BizBudding
 * @license   GPL-2.0-or-later
 */

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

return [
	'google-fonts' => [
		'https://fonts.googleapis.com/css?family=Josefin+Sans:600,700|Work+Sans:400,400i,500,500i,600,600i,700,700i&display=swap',
	],
	'image-sizes' => [
		'add'    => [
			'portrait'  => '3:4',
			// 'square'    => '1:1',
		],
	],
	'theme-support' => [
		'add' => [ 'boxed-container' ],
	],
	'page-header-single'  => [],
	'page-header-archive' => [],
];
