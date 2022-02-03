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
	'demos'         => [],
	'global-styles' => [
		'colors' => [
			'background' => '#f9f9f9', // Body background.
			'alt'        => '#f9f9f9', // Background alt.
			'body'       => '#2c2d2f', // Body text color.
			'heading'    => '#192d69', // Heading text color.
			'link'       => '#dcae41', // Link color.
			'primary'    => '#6ecfbe', // Button primary background color.
			'secondary'  => '#f4f4f4', // Button secondary background color.
		],
		'custom-colors' => [
			[
				'color' => '#fb1490', // var(--color-custom-1).
			],
		],
		'fonts' => [
			'body'    => 'Montserrat:400',
			'heading' => 'Pathway Gothic One:400',
		],
	],
	'image-sizes' => [
		'add' => [
			'portrait' => '3:4',
			'square'   => '1:1',
		],
	],
	'site-header-mobile' => [
		'menu_toggle',
		'title_area',
		'header_search',
	],
	'settings' => [
		'logo'         => [
			'show-tagline' => false,
		],
		'site-layouts' => [
			'default'     => [
				'site'       => 'content-sidebar',
				'archive'    => 'wide-content',
			],
			'archive'     => [
				'post'       => 'content-sidebar',
			],
		],
		'mobile-menu-breakpoint' => '1040',
		'content-archives'       => [
			'category' => [
				'image_orientation' => 'portrait',
				'boxed'             => false,
				'column_gap'        => 'xxl',
				'row_gap'           => 'xxl',
				'posts_nav'         => 'numeric',
				'posts_per_page'    => '9',
				'show'              => [
					'image',
					'genesis_entry_header',
					'title',
					'header_meta',
					'genesis_before_entry_content',
					'excerpt',
					'genesis_entry_content',
					'more_link',
					'genesis_after_entry_content',
					'genesis_entry_footer',
					'footer_meta',
				],
				'content_limit' => '120',
			],
			'post' => [
				'image_orientation' => 'portrait',
				'image_position'    => 'left-middle',
				'columns'           => '1',
				'show'              => [
					'image',
					'genesis_entry_header',
					'header_meta',
					'title',
					'genesis_before_entry_content',
					'excerpt',
					'genesis_entry_content',
					'more_link',
					'genesis_after_entry_content',
					'genesis_entry_footer',
					'footer_meta',
				],
				'boxed'               => false,
				'column_gap'          => 'xxl',
				'row_gap'             => 'xxxl',
				'image_width'         => 'half',
				'align_text_vertical' => 'middle',
			],
		],
	],
	'plugins'           => [
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
