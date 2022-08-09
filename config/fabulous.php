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
		'creative' => 'https://demo.bizbudding.com/fabulous-creative/wp-content/uploads/sites/31/mai-engine/',
		'personal' => 'https://demo.bizbudding.com/fabulous-personal/wp-content/uploads/sites/30/mai-engine/',
		'fitness'  => 'https://demo.bizbudding.com/fabulous-fitness/wp-content/uploads/sites/29/mai-engine/',
	],
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
		'font-variants'  => [
			'heading' => [
				'light' => '400', // Always loads regular weight since this is used for menus.
			],
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
			'enable' => [ 'post', 'category' ],
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
				'content_limit'       => '200',
				'boxed'               => false,
				'column_gap'          => 'xxl',
				'row_gap'             => 'xxxl',
				'image_width'         => 'half',
				'align_text_vertical' => 'middle',
			],
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
					'header_meta',
					'title',
					'genesis_before_entry_content',
					'excerpt',
					'genesis_entry_content',
					'more_link',
					'genesis_after_entry_content',
					'genesis_entry_footer',
				],
				'content_limit'  => '120',
				'image_position' => 'center',
				'header_meta'    => '[post_date]',
				'footer_meta'    => '[post_categories]',
				'columns'        => '3',
			],
		],
		'single-content' => [
			'post' => [
				'image_orientation' => 'portrait',
				'header_meta'       => '[mai_avatar size="32"][post_date] [post_author_posts_link before="by "]',
			],
		],
		'icons'                 => [
			'button-link'         => [
				'icon'  => 'chevron-right',
				'style' => 'regular',
			],
			'pagination-next'     => [
				'icon'  => 'chevron-right',
				'style' => 'regular',
			],
			'pagination-previous' => [
				'icon'  => 'chevron-left',
				'style' => 'regular',
			],
			'entry-next'          => [
				'icon'  => 'chevron-right',
				'style' => 'regular',
			],
			'entry-previous'      => [
				'icon'  => 'chevron-left',
				'style' => 'regular',
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
