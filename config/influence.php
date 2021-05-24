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
		// 'travel'      => 0,
		// 'home-garden' => 0,
	],
	'global-styles' => [
		'colors' => [
			'alt'        => '#f9f9f9', // Background alt.
			'body'       => '#333333', // Body text color.
			'heading'    => '#333333', // Heading text color.
			'link'       => '#a0d9ca', // Link color.
			'primary'    => '#a0d9ca', // Button primary background color.
			'secondary'  => '#173c6e', // Button secondary background color.
		],
		'fonts' => [
			'body'       => 'Karla:400',
			'heading'    => 'Montserrat:400',
			// 'subheading' => 'Playfair Display:500italic',
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
				'columns' => 1,
			],
		],
		'site-layouts' => [
			'default' => [
				'site' => 'content-sidebar',
			],
		],
	],
	'plugins'           => [
		[
			'name'  => 'Genesis eNews Extended',
			'host'  => 'wordpress',
			'slug'  => 'genesis-enews-extended/plugin.php',
			'uri'   => 'https://wordpress.org/plugins/genesis-enews-extended/',
			'demos' => [],
		],
		[
			'name'  => 'Widget Shortcode',
			'host'  => 'wordpress',
			'slug'  => 'widget-shortcode/init.php',
			'uri'   => 'https://wordpress.org/plugins/widget-shortcode/',
			'demos' => [],
		],
	],
	'custom-functions' => [],
];
