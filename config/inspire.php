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
	'demos' => [
		'home-garden'   => 35,
		// 'health-beauty' => 36,
		// 'travel'        => 37,
	],
	'global-styles' => [
		'colors' => [
			'link'      => '#894b32',
			'primary'   => '#002627',
			'secondary' => '#894b32',
			'heading'   => '#002627',
			'body'      => '#252323',
			// 'alt'    => '#e6e0ce',
			'black'     => '#222222',
		],
		'fonts' => [
			'body'       => 'Lato:400',
			'heading'    => 'Playfair Display:900',
			'subheading' => 'Playfair Display:500italic',
		],
	],
	'image-sizes' => [
		'add' => [
			'square' => '1:1',
		],
	],
	'settings' => [
		'content-archives' => [
			'post' => [
				'boxed'   => false,
				'columns' => 2,
				'row_gap' => 'xxxl',
			],
		],
	],
	'custom-functions' => function() {
		/**
		 * Add custom body class.
		 *
		 * @param   array  The existing body classes.
		 *
		 * @return  array  Modified classes.
		 */
		add_filter( 'body_class', function( $classes ) {
			$classes[] = 'has-dark-header';
			return $classes;
		});
	},
];
