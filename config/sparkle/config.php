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

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

return [
	'demos'            => [
		'creative' => 2,
		'fashion'  => 18,
	],
	'google-fonts'     => [
		'Josefin+Sans:600|Work+Sans:400,400i,700',
	],
	'image-sizes'      => [
		'add' => [
			'portrait' => '3:4',
		],
	],
	'theme-support'    => [
		'add' => [
			'boxed-container',
			'sticky-header',
		],
	],
	'archive-settings' => [
		'post',
		'category',
	],
	'page-header'      => [
		'single' => [ 'page' ],
	],
	'plugins'          => [
		[
			'name'  => 'Genesis Connect for WooCommerce',
			'slug'  => 'genesis-connect-woocommerce/genesis-connect-woocommerce.php',
			'uri'   => 'https://wordpress.org/plugins/genesis-connect-woocommerce/',
			'demos' => [ 'creative' ],
		],
		[
			'name'  => 'Simple Social Icons',
			'slug'  => 'simple-social-icons/simple-social-icons.php',
			'uri'   => 'https://wordpress.org/plugins/simple-social-icons/',
			'demos' => [ 'creative' ],
		],
		[
			'name'  => 'WP Forms Lite',
			'slug'  => 'wpforms-lite/wpforms.php',
			'uri'   => 'https://wordpress.org/plugins/wpforms-lite/',
			'demos' => [ 'creative', 'fashion' ],
		],
		[
			'name'  => 'WooCommerce',
			'slug'  => 'woocommerce/woocommerce.php',
			'uri'   => 'https://wordpress.org/plugins/woocommerce/',
			'demos' => [ 'creative' ],
		],
	],
];
