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
		'colors'  => [
			'primary' => '#fb2056',
			'warning' => '#ffce53',
			'darkest' => '#232c39',
			'medium'  => '#647585',
			'light'   => '#eff1f3',
			'lighter' => '#f6f7f8',
		],
		'body'    => [
			'font-family'    => 'Hind',
			'font-weight'    => 'regular',
			'line-height'    => 1.5,
			'color'          => 'dark',
			'text-transform' => 'none',
		],
		'heading' => [
			'font-family' => 'Montserrat',
		],
	],
	'google-fonts'      => [
		'https://fonts.googleapis.com/css2?family=Hind&family=Montserrat:wght@700&display=swap',
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

