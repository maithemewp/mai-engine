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
		// 'local'    => 3,
		// 'coaching' => 4,
		'business' => 5,
	],
	'global-styles' => [
		'colors'     => [
			'link'              => '#fb2056',
			'button-background' => '#fb2056',
			'heading'           => '#232c39',
		],
		'fonts'      => [
			'body'    => 'Hind:400',
			'heading' => 'Montserrat:600',
			'button'  => 'Montserrat:600',
		],
	],
	'theme-support' => [
		'add' => [
			'transparent-header',
		],
	],
	'page-header'   => [
		'archive'          => '*',
		'single'           => '*',
		'background-color' => '#232c39',
		'image'            => '',
		'text-color'       => 'light',
	],
	'plugins'       => [
		[
			'name'  => 'Genesis eNews Extended',
			'slug'  => 'genesis-enews-extended/plugin.php',
			'uri'   => 'https://wordpress.org/plugins/genesis-enews-extended/',
			'demos' => [ 'agency' ],
		],
	],
];

