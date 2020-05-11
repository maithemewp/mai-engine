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
	'google-fonts'     => [
		// TODO: Do we need all of the Work Sans variants? (No, not sure which weights we're using yet so have them all in there - Mike).
		'Josefin+Sans:600,700|Work+Sans:400,400i,500,500i,600,600i,700,700i',
	],
	'image-sizes'      => [
		'add' => [
			'portrait' => '3:4',
		],
	],
	'theme-support'    => [
		'add' => [
			'boxed-container',
			'genesis-footer-widgets' => 4,
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
	'required-plugins' => [
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
