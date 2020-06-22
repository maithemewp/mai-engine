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
	'demos'             => [
		'podcast' => 12,
		'agency'  => 13,
	],
	'custom-properties' => [
		'color-primary'        => '#7b51ff',
		'color-success'        => '#00cd9e',
		'color-warning'        => '#ffc54e',
		'color-danger'         => '#ff478f',
		'color-info'           => '#0095ed',
		'color-darkest'        => '#4b657e',
		'color-dark'           => '#5f749e',
		'color-lighter'        => '#f7f8fa',
		'text-scale-ratio'     => 1.2,
		'body-font-family'     => 'Karla',
		'heading-font-family'  => 'Karla',
		'heading-font-weight'  => 700,
		'input-border-radius'  => '100px',
		'button-border-radius' => '100px',
	],
	'theme-support'     => [
		'add' => [
			'transparent-header',
		],
	],
	'page-header'       => [
		'archive'                 => '*',
		'single'                  => '*',
		'background-color'        => 'primary',
		'text-color'              => 'light',
		'divider'                 => 'curve',
		'divider-flip-horizontal' => false,
	],
	'plugins'           => [
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
	'custom-functions'  => function () {
		add_filter( 'mai_default_footer_credits', function ( $default ) {
			return $default . mai_back_to_top_shortcode();
		} );
	},
];
