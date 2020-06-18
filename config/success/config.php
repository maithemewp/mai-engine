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
	'demos'          => [
		// 'local'    => 3,
		// 'coaching' => 4,
		'agency' => 5,
	],
	'google-fonts'   => [
		'https://fonts.googleapis.com/css2?family=Hind&family=Montserrat:wght@700&display=swap',
	],
	'theme-support'  => [
		'add' => [
			'transparent-header',
		],
	],
	'page-header'    => [
		'archive'          => '*',
		'single'           => '*',
		'background-color' => mai_get_color( 'darkest' ),
		'image'            => '',
		'text-color'       => 'light',
	],
	'plugins' => [
		[
			'name'  => 'Genesis eNews Extended',
			'slug'  => 'genesis-enews-extended/plugin.php',
			'uri'   => 'https://wordpress.org/plugins/genesis-enews-extended/',
			'demos' => [ 'agency' ],
		],
	],
];

