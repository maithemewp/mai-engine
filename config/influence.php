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
		'travel'  => 'https://demo.bizbudding.com/influence-travel/wp-content/uploads/sites/64/mai-engine/',
		'podcast' => 'https://demo.bizbudding.com/influence-podcast/wp-content/uploads/sites/65/mai-engine/',
		'fashion' => 'https://demo.bizbudding.com/influence-fashion/wp-content/uploads/sites/66/mai-engine/',
	],
	'global-styles' => [
		'colors' => [
			'alt'       => '#f9f9f9', // Background alt.
			'body'      => '#333333', // Body text color.
			'heading'   => '#333333', // Heading text color.
			'link'      => '#a0d9ca', // Link color.
			'primary'   => '#a0d9ca', // Button primary background color.
			'secondary' => '#173c6e', // Button secondary background color.
		],
		'fonts' => [
			'body'    => 'Karla:400',
			'heading' => 'Montserrat:400',
		],
	],
	'image-sizes' => [
		'add' => [
			'landscape' => '3:2',
			'portrait'  => '2:3',
		],
	],
	'settings' => [
		'content-archives' => [
			'post' => [
				'show'        => [
					'header_meta',
					'title',
					'footer_meta',
					'image',
					'genesis_entry_header',
					'genesis_before_entry_content',
					'excerpt',
					'genesis_entry_content',
					'more_link',
					'genesis_after_entry_content',
					'genesis_entry_footer',
				],
				'boxed'       => false,
				'columns'     => 1,
				'footer_meta' => '[mai_icon icon="location-arrow" style="solid" size=".5em" margin_right="4px" display="inline-flex"][post_categories before="Location: "]',
				'header_meta' => '[post_date]',
				'align_text'  => 'center',
				'row_gap'     => 'xxxl',
			],
		],
		'site-layouts' => [
			'default' => [
				'site' => 'content-sidebar',
			],
		],
	],
	'plugins'           => [
		'genesis-enews-extended/plugin.php' => [
			'name'  => 'Genesis eNews Extended',
			'host'  => 'wordpress',
			'uri'   => 'https://wordpress.org/plugins/genesis-enews-extended/',
			'demos' => [],
		],
		'widget-shortcode/init.php' => [
			'name'  => 'Widget Shortcode',
			'host'  => 'wordpress',
			'uri'   => 'https://wordpress.org/plugins/widget-shortcode/',
			'demos' => [],
		],
	],
	'custom-functions' => [],
];
