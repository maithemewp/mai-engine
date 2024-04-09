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
	'demos' => [
		'influencer' => 'https://demo.bizbudding.com/side-hustle-influencer/wp-content/uploads/sites/83/mai-engine/',
	],
	'global-styles' => [
		'colors' => [
			'link'      => '#ac0254',
			'primary'   => '#ac0254',
			'secondary' => '#e4e4dd',
			'alt'       => '#f7f7f5',
			'header'    => '#000000',
			'body'      => '#000000',
			'heading'   => '#000000',
		],
		'fonts' => [
			'body'    => 'Raleway:400',
			'heading' => 'Playfair Display:400',
			'alt'     => 'Oooh Baby:400',
		],
	],
	'image-sizes' => [
		'add' => [
			'landscape' => '4:3',
			'portrait'  => '3:4',
			'square'    => '1:1',
		],
	],
	'settings' => [
		'site-layouts' => [
			'default' => [
				'archive' => 'wide-content',
			],
			'single' => [
				'post'    => 'narrow-content',
				'product' => 'wide-content',
			],
		],
		'content-archives' => [
			'post' => [
				'show' => [
					'image',
					'genesis_entry_header',
					'title',
					'genesis_before_entry_content',
					'genesis_entry_content',
					'genesis_after_entry_content',
					'genesis_entry_footer',
				],
				'title_size'         => 'xxl',
				'align_text'         => 'center',
				'boxed'              => false,
				'columns'            => '2',
				'column_gap'         => 'xxxl',
				'row_gap'            => 'xxxl',
				'header_meta'        => '[post_date] [post_author_posts_link before="by "]',
				'footer_meta'        => '[post_terms taxonomy="category" before="Category: "][post_terms taxonomy="post_tag" before="Tag: "]',
				'columns_responsive' => false,
			],
			'enable' => [
				'post',
			],
		],
		'single-content' => [
			'post' => [
				'show' => [
					'genesis_entry_header',
					'image',
					'excerpt',
					'genesis_before_entry_content',
					'content',
					'genesis_entry_content',
					'genesis_entry_footer',
					'after_entry',
					'genesis_after_entry_content',
				],
				'header_meta' => '[post_date] [post_author_posts_link before="by "]',
				'footer_meta' => '[post_terms taxonomy="category" before="Category: "][post_terms taxonomy="post_tag" before="Tag: "]',
			],
			'page' => [
				'show' => [
					'genesis_entry_header',
					'title',
					'image',
					'genesis_before_entry_content',
					'excerpt',
					'content',
					'genesis_entry_content',
					'genesis_after_entry_content',
					'genesis_entry_footer',
				],
			],
			'enable' => [
				'page',
				'post',
			],
		],
	],
	'theme-support' => [
		'add' => [
			'transparent-header',
		],
	],
	'plugins'           => [
		'shared-counts/shared-counts.php' => [
			'name'  => 'Shared Counts',
			'host'  => 'wordpress',
			'uri'   => 'https://wordpress.org/plugins/shared-counts/',
			'demos' => [],
		],
		'woocommerce/woocommerce.php' => [
			'name'  => 'WooCommerce',
			'host'  => 'wordpress',
			'uri'   => 'https://wordpress.org/plugins/woocommerce/',
			'demos' => [],
		],
		'genesis-connect-woocommerce/genesis-connect-woocommerce.php' => [
			'name'  => 'Genesis Connect for WooCommerce',
			'host'  => 'wordpress',
			'uri'   => 'https://wordpress.org/plugins/genesis-connect-woocommerce/',
			'demos' => [],
		],
	],
];
