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
		// 'default' => 51,
	],
	'global-styles'    => [
		'colors' => [
			// 'link'           => '#b0cdbd',
			'primary'        => '#002627',
			'secondary'      => '#894b32',
			'heading'        => '#002627',
			'body'           => '#252323',
			// 'alt'            => '#f5f5f5',
			// 'custom-color-1' => '#545454',
			'black'           => '#222222',

		],
		'fonts'  => [
			'body'        => 'Lato:400',
			'heading'     => 'Playfair Display:900',
			'subheading'  => 'Playfair Display:500italic',
			// 'entry-title' => 'Playfair Display:400',
		],
	],
	'image-sizes'         => [
		'add' => [
			// 'landscape' => '16:9',
			'square'    => '1:1',
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
