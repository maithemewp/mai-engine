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
		'color-primary'       => '#067ccc',
		'color-secondary'     => '#6c757d',
		'color-success'       => '#28a745',
		'color-danger'        => '#dc3545',
		'color-warning'       => '#ffc107',
		'color-info'          => '#42a2b8',
		'color-darkest'       => '#323232',
		'color-dark'          => '#515151',
		'color-medium'        => '#ebe9eb',
		'color-lighter'       => '#f0f0f0',
		'body-font-family'    => 'Open Sans',
		'heading-font-family' => 'Playfair Display',
		'heading-font-weight' => 700,
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
