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
	'demos'            => [
		'podcast' => 12,
		'agency'  => 13,
	],
	'google-fonts'     => [
		'Karla:wght@400;700',
	],
	'theme-support'    => [
		'add' => [
			'transparent-header',
		],
	],
	'page-header'      => [
		'archive'                 => '*',
		'single'                  => '*',
		'background-color'        => mai_get_color( 'primary' ),
		'text-color'              => 'light',
		'divider'                 => 'curve',
		'divider-flip-horizontal' => false,
	],
	'plugins'          => [
		[
			'name'  => 'Genesis Connect for WooCommerce',
			'slug'  => 'genesis-connect-woocommerce/genesis-connect-woocommerce.php',
			'uri'   => 'https://wordpress.org/plugins/genesis-connect-woocommerce/',
			'demos' => [ 'agency' ],
		],
		[
			'name'  => 'Simple Social Icons',
			'slug'  => 'simple-social-icons/simple-social-icons.php',
			'uri'   => 'https://wordpress.org/plugins/simple-social-icons/',
			'demos' => [ 'agency', 'podcast' ],
		],
		[
			'name'  => 'WP Forms Lite',
			'slug'  => 'wpforms-lite/wpforms.php',
			'uri'   => 'https://wordpress.org/plugins/wpforms-lite/',
			'demos' => [ 'agency', 'podcast' ],
		],
		[
			'name'  => 'WooCommerce',
			'slug'  => 'woocommerce/woocommerce.php',
			'uri'   => 'https://wordpress.org/plugins/woocommerce/',
			'demos' => [ 'agency' ],
		],
	],
	'custom-functions' => function () {
		add_filter( 'mai_default_footer_credits', function ( $default ) {
			return $default . mai_back_to_top_shortcode();
		} );
	},
];
