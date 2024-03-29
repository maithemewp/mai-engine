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
		'creative' => 'https://demo.bizbudding.com/sparkle-creative/wp-content/uploads/sites/2/mai-engine/',
		'fashion'  => 'https://demo.bizbudding.com/sparkle-fashion/wp-content/uploads/sites/18/mai-engine/',
	],
	'global-styles' => [
		'colors' => [
			'background' => '#fdf3f2',
			'alt'        => '#faf7f6',
			'primary'    => '#f98588',
			'link'       => '#f98588',
			'heading'    => '#000000',
			'body'       => '#7f7d7e',
		],
		'fonts'  => [
			'body'    => 'Work Sans:400',
			'heading' => 'Josefin Sans:600',
		],
		'font-variants'  => [
			'heading' => [
				'light' => '400', // Always loads regular weight since this is used for menus.
			],
		],
	],
	'image-sizes'   => [
		'add' => [
			'portrait' => '3:4',
		],
	],
	'theme-support' => [
		'add' => [
			'boxed-container',
			'sticky-header',
		],
	],
	'settings'      => [
		'content-archives' => [
			'enable' => [ 'post', 'category' ],
		],
		'page-header'      => [
			'single' => [ 'page' ],
		],
	],
	'plugins'       => [
		'wpforms-lite/wpforms.php' => [
			'name'  => 'WP Forms Lite',
			'host'  => 'wordpress',
			'uri'   => 'https://wordpress.org/plugins/wpforms-lite/',
			'demos' => [ 'creative', 'fashion' ],
		],
		'woocommerce/woocommerce.php' => [
			'name'  => 'WooCommerce',
			'host'  => 'wordpress',
			'uri'   => 'https://wordpress.org/plugins/woocommerce/',
			'demos' => [ 'creative' ],
		],
		'genesis-connect-woocommerce/genesis-connect-woocommerce.php' => [
			'name'  => 'Genesis Connect for WooCommerce',
			'host'  => 'wordpress',
			'uri'   => 'https://wordpress.org/plugins/genesis-connect-woocommerce/',
			'demos' => [ 'creative' ],
		],
	],
];
