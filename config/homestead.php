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
		'farm'      => 'https://demo.bizbudding.com/homestead-farm/wp-content/uploads/sites/80/mai-engine/',
		'lifestyle' => 'https://demo.bizbudding.com/homestead-lifestyle/wp-content/uploads/sites/79/mai-engine/',
	],
	'global-styles' => [
		'fonts' => [
			'body'    => 'IBM Plex Sans:400',
			'heading' => 'Prata:400',
		],
		'colors' => [
			'primary'   => '#bd5d0f',
			'secondary' => '#efeddc',
			'alt'       => '#efeddc',
			'link'      => '#9a3b19',
			'heading'   => '#271916',
			'body'      => '#34221d',
		],
	],
	'settings' => [
		'page-header' => [
			'single' => [
				'post',
				'page',
			],
			'archive' => [
				'post',
				'product',
				'category',
				'post_tag',
				'search',
				'author',
				'date',
			],
			'background-color' => '#34221d',
			'overlay-opacity'  => '0.7',
			'text-color'       => 'light',
			'spacing'          => [
				'top'    => '15vw',
				'bottom' => '5vw',
			],
			'content-align' => 'start',
			'text-align'    => 'start',
		],
		'single-content' => [
			'page' => [
				'show' => [
					'genesis_entry_header',
					'title',
					'genesis_before_entry_content',
					'excerpt',
					'content',
					'genesis_entry_content',
					'genesis_after_entry_content',
					'genesis_entry_footer',
				],
				'page-header-featured' => true,
			],
			'post' => [
				'show' => [
					'genesis_entry_header',
					'title',
					'header_meta',
					'genesis_before_entry_content',
					'excerpt',
					'content',
					'image',
					'footer_meta',
					'adjacent_entry_nav',
					'genesis_entry_content',
					'genesis_after_entry_content',
					'genesis_entry_footer',
					'after_entry',
				],
				'page-header-featured' => true,
				'header_meta'          => '[mai_date]',
			],
		],
		'site-layouts' => [
			'default' => [
				'archive' => 'wide-content',
			],
			'single' => [
				'product' => 'wide-content',
			],
		],
		'content-archives' => [
			'post' => [
				'header_meta'     => '[mai_date]',
				'more_link_style' => 'button_link',
				'columns'         => '1',
				'image_position'  => 'left-full',
			],
		],
	],
	'theme-support' => [
		'add' => [
			'transparent-header',
		],
	],
	'plugins' => [
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
