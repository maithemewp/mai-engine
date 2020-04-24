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
	'google-fonts'     => [
		'Karla:400,700',
	],
	'theme-support'    => [
		'add' => [
			'transparent-header',
			'genesis-footer-widgets' => 4,
		],
	],
	'page-header'      => '*',
	'required-plugins' => [
		[
			'name'       => 'Genesis Connect for WooCommerce',
			'slug'       => 'genesis-connect-woocommerce/genesis-connect-woocommerce.php',
			'public_url' => 'https://wordpress.org/plugins/genesis-connect-woocommerce/',
			'demos'      => [ 'agency' ],
		],
		[
			'name'       => 'Simple Social Icons',
			'slug'       => 'simple-social-icons/simple-social-icons.php',
			'public_url' => 'https://wordpress.org/plugins/simple-social-icons/',
			'demos'      => [ 'agency' ],
		],
		[
			'name'       => 'WP Forms Lite',
			'slug'       => 'wpforms-lite/wpforms.php',
			'public_url' => 'https://wordpress.org/plugins/wpforms-lite/',
			'demos'      => [ 'agency' ],
		],
		[
			'name'       => 'WooCommerce',
			'slug'       => 'woocommerce/woocommerce.php',
			'public_url' => 'https://wordpress.org/plugins/woocommerce/',
			'demos'      => [ 'agency' ],
		],
	],
	'custom-functions' => function () {
		add_filter( 'mai_default_footer_credits', function ( $default ) {
			return $default . mai_back_to_top_shortcode();
		} );
	},
];
