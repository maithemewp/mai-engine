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
		// 'local'    => 3,
		// 'coaching' => 4,
		'agency' => 5,
	],
	'custom-properties' => [
		'color-primary'       => '#fb2056',
		'color-warning'       => '#ffce53',
		'color-darkest'       => '#232c39',
		'color-medium'        => '#647585',
		'color-light'         => '#eff1f3',
		'color-lighter'       => '#f6f7f8',
		'text-md'             => 18,
		'body-font-family'    => 'Hind',
		'body-font-weight'    => 'regular',
		'body-line-height'    => 1.5,
		'body-color'          => 'dark',
		'body-text-transform' => 'none',
		'heading-font-family' => 'Montserrat',
		'heading-font-weight' => 600,
	],
	'theme-support'     => [
		'add' => [
			'transparent-header',
		],
	],
	'page-header'       => [
		'archive'          => '*',
		'single'           => '*',
		'background-color' => 'darkest',
		'image'            => '',
		'text-color'       => 'light',
	],
	'plugins'           => [
		[
			'name'  => 'Genesis eNews Extended',
			'slug'  => 'genesis-enews-extended/plugin.php',
			'uri'   => 'https://wordpress.org/plugins/genesis-enews-extended/',
			'demos' => [ 'agency' ],
		],
	],
];

