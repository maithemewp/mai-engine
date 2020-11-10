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
			'alt'       => '#f3f2ed',
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
		'single-content' => [
			'page' => [
				'image_orientation' => 'square',
			],
			'post' => [
				'show' => [
					'genesis_entry_header',
					'title',
					'header_meta',
					'image',
					'genesis_before_entry_content',
					'excerpt',
					'content',
					'genesis_entry_content',
					'genesis_after_entry_content',
					'footer_meta',
					'genesis_entry_footer',
					'after_entry',
					'author_box',
				],
				'image_orientation' => 'square',
			],
		],
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
		 * @since TBD
		 *
		 * @param   array  The existing body classes.
		 *
		 * @return  array  Modified classes.
		 */
		add_filter( 'body_class', 'mai_inspire_body_class' );
		function mai_inspire_body_class( $classes ) {
			$classes[] = 'has-dark-header';
			return $classes;
		}
	},
];
