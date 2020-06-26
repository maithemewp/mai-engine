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
		'business' => 5,
	],
	'global-styles' => [
		'breakpoint' => 1200,
		'colors'     => [
			'button'     => '#fb2056',
			'link'       => mai_get_color_variant( '#fb2056', 'dark' ),
			'heading'    => '#232c39',
		],
		'fonts'      => [
			'body'    => 'Hind',
			'heading' => 'Montserrat',
		],
	],
	'theme-support'     => [
		'add' => [
			'transparent-header',
		],
	],
	'page-header'       => [
		'archive'          => '*',
		'single'           => '*',
		'background-color' => 'heading',
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

