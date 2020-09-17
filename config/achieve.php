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
		'law'      => 9,
		// 'news'     => 10,
		// 'personal' => 11,
	],
	'global-styles' => [
		'colors' => [
			'link'       => '#2386dd',
			'primary'    => '#191919',
			'secondary'  => '#777777',
			'heading'    => '#242424',
			'body'       => '#777777',
			'alt'        => '#eeeeee',
			'background' => '#fcfcfc',
		],
		'fonts'  => [
			'body'    => 'Roboto:400',
			'heading' => 'Roboto Slab:700',
		],
	],
	'theme-support' => [
		'add' => [
			'sticky-header',
		],
	],
	'settings'      => [
		'logo'             => [
			'show-tagline' => false,
		],
		'site-layouts'      => [
			'default' => [
				'site' => 'wide-content',
			],
			'single'  => [
				'post' => 'content-sidebar',
			],
		],
		'page-header'      => [
			'single'     => [ 'post', 'page' ],
			'archive'    => [ 'post' ],
			'text-align' => 'start',
			'spacing'    => [
				'top'    => '2em',
				'bottom' => '2em',
			],
		],
		'genesis'          => [
			'breadcrumb_front_page' => 0,
			'breadcrumb_home'       => 1,
			'breadcrumb_posts_page' => 1,
			'breadcrumb_single'     => 1,
			'breadcrumb_page'       => 1,
			'breadcrumb_archive'    => 1,
			'breadcrumb_404'        => 1,
			'breadcrumb_attachment' => 1,
		],
	],
];
