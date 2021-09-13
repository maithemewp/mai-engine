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
		'creative' => 2,
		'fashion'  => 18,
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
		'genesis-connect-woocommerce/genesis-connect-woocommerce.php' => [
			'name'  => 'Genesis Connect for WooCommerce',
			'host'  => 'wordpress',
			'uri'   => 'https://wordpress.org/plugins/genesis-connect-woocommerce/',
			'demos' => [ 'creative' ],
		],
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
	],
];
