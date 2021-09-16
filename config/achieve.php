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
			'background' => '#fcfcfc',
			'alt'        => '#f5f5f5',
			'body'       => '#323232',
			'heading'    => '#191919',
			'link'       => '#067ccc',
			'primary'    => '#323232',
			'secondary'  => '#ebe9eb',
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
			'single' => [
				'post' => 'content-sidebar',
			],
			'archive' => [
				'post' => 'content-sidebar',
			],
		],
		'page-header'      => [
			'single'        => [ 'post', 'page' ],
			'archive'       => [ 'post' ],
			'content-width' => 'xl',
			'text-align'    => 'start',
			'spacing'       => [
				'top'    => '2em',
				'bottom' => '2em',
			],
		],
		'content-archives' => [
			'post' => [
				'show'    => [
					'image',
					'genesis_entry_header',
					'title',
					'genesis_before_entry_content',
					'excerpt',
					'genesis_entry_content',
					'more_link',
					'genesis_after_entry_content',
					'genesis_entry_footer',
				],
				'boxed'   => false,
				'columns' => '2',
				'row_gap' => 'xxxl',
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
