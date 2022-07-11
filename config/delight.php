<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https:          //bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2020 BizBudding
 * @license   GPL-2.0-or-later
 */

return [
	'demos'                   => [
		'recipes' => 'https://demo.bizbudding.com/delight-recipes/wp-content/uploads/sites/6/mai-engine/',
		'travel'  => 'https://demo.bizbudding.com/delight-travel/wp-content/uploads/sites/7/mai-engine/',
		// 'home'    => 8,
	],
	'global-styles'           => [
		'colors'                 => [
			'background'            => '#fcfcfc',
			'alt'                   => '#f0f0f0',
			'body'                  => '#515151',
			'heading'               => '#323232',
			'link'                  => '#067ccc',
			'primary'               => '#067ccc',
			'secondary'             => '#ebe9eb',
		],
		'fonts'                  => [
			'body'                  => 'Open Sans:300',
			'heading'               => 'Playfair Display:700',
		],
	],
	'theme-support'           => [
		'add'                    => [
			'sticky-header',
		],
	],
	'image-sizes'             => [
		'add'                    => [
			'landscape'             => '4:3',
			'square'                => '1:1',
		],
	],
	'settings'                => [
		'site-layouts'            => [
			'default'               => [
				'site'                 => 'standard-content',
				'archive'              => 'wide-content',
			],
		],
		'page-header'            => [
			'archive'               => [ 'post', 'category', 'product', 'product_cat' ],
			'single'                => [ 'page', 'post' ],
			'background-color'      => '',
			'image'                 => 'https://source.unsplash.com/kXQ3J7_2fpc/1600x900',
			'overlay-opacity'       => '0',
		],
		'content-archives'       => [
			'post'                  => [
				'title_size'           => 'md',
				'columns'              => 4,
			],
		],
		'single-content'         => [
			'post'                  => [
				'show'                 => [
					'genesis_entry_header',
					'title',
					'header_meta',
					'genesis_before_entry_content',
					'excerpt',
					'content',
					'genesis_entry_content',
					'genesis_after_entry_content',
					'footer_meta',
					'genesis_entry_footer',
					'author_box',
					'after_entry',
					'adjacent_entry_nav',
				],
				'page-header-featured' => '1',
			],
		],
	],
];
