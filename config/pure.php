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
	#f6f7f8
	'global-styles' => [
		'colors' => [
			// 'black'      => '#000000',
			// 'white'      => '#ffffff',
			// 'header'     => '#ffffff', // Site header background.
			// 'background' => '#ffffff', // Body background.
			'alt'        => '#f8f8f8', // Background alt.Gray #f8f8f8. Blue #f8fbff. Tan #fbf8f5.
			'body'       => '#111111', // Body text color.
			'heading'    => '#111111', // Heading text color.
			'link'       => '#02C091', // Link color.
			'primary'    => '#02C091', // Button primary background color.
			'secondary'  => '#737373', // Button secondary background color.
		],
		// Josefin Sans
		'fonts' => [
			'body'    => 'EB Garamond:400',
			'heading' => 'Jost:600,400',
		],
	],
	'image-sizes' => [
		'add'    => [
			'landscape' => '3:2',
			'portrait'  => '2:3',
		],
	],
	'theme-support' => [
		'add' => [
			'transparent-header',
		],
	],
];
