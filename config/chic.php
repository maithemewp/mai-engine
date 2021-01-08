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
	'demos'             => [
		'blog'             => 60,
	],
	'global-styles'     => [
		'colors'           => [
			'link'            => '#b0cdbd',
			'primary'         => '#b0cdbd',
			'secondary'       => '#f5f5f5',
			'heading'         => '#173821',
			'body'            => '#173821',
			'alt'             => '#f5f5f5',
			'custom-color-1'  => '#545454',
		],
		'fonts'            => [
			'body'            => 'Lato:400',
			'heading'         => 'Lato:700',
			'entry-title'     => 'Playfair Display:400',
		],
	],
	'image-sizes'       => [
		'add'              => [
			'landscape'       => '16:9',
			'square'          => '1:1',
		],
	],
	'settings'          => [
		'site-layouts'     => [
			'default'         => [
				'site'           => 'content-sidebar',
			],
			'single'          => [
				'page'           => 'standard-content',
			],
		],
		'content-archives' => [
			'post'            => [
				'show'           => [
					'genesis_entry_header',
					'header_meta',
					'title',
					'image',
					'genesis_before_entry_content',
					'excerpt',
					'genesis_entry_content',
					'more_link',
					'genesis_after_entry_content',
					'genesis_entry_footer',
				],
				'title_size'     => 'xxl',
				'boxed'          => false,
				'columns'        => '1',
				'row_gap'        => 'xxxl',
			],
		],
		'single-content'   => [
			'post'            => [
				'show'           => [
					'genesis_entry_header',
					'header_meta',
					'title',
					'image',
					'genesis_before_entry_content',
					'excerpt',
					'content',
					'genesis_entry_content',
					'genesis_after_entry_content',
					'footer_meta',
					'genesis_entry_footer',
					'after_entry',
					'author_box',
					'adjacent_entry_nav',
				],
			],
		],
	],
	'plugins'           => [
		[
			'name'            => 'Genesis eNews Extended',
			'host'            => 'wordpress',
			'slug'            => 'genesis-enews-extended/plugin.php',
			'uri'             => 'https://wordpress.org/plugins/genesis-enews-extended/',
			'demos'           => [],
		],
		[
			'name'            => 'WooCommerce',
			'host'            => 'wordpress',
			'slug'            => 'woocommerce/woocommerce.php',
			'uri'             => 'https://wordpress.org/plugins/woocommerce/',
			'demos'           => [],
		],
	],
];
