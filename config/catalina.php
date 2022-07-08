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
		'default' => 'https://demo.bizbudding.com/catalina/wp-content/uploads/sites/51/mai-engine/',
	],
	'global-styles'    => [
		'colors' => [
			'link'      => '#000000',
			'primary'   => '#000000',
			'secondary' => '#565b39',
			'heading'   => '#000000',
			'body'      => '#000000',
			'alt'       => '#222222',
		],
		'fonts'  => [
			'body'    => 'sans-serif',
			'heading' => 'DM Serif Display:400',
		],
	],
	'theme-support'    => [
		'add' => [
			'sticky-header',
		],
	],
	'settings'         => [
		'logo'             => [
			'show-tagline' => false,
		],
		'site-layouts'      => [
			'default' => [
				'site'    => 'standard-content',
				'archive' => 'narrow-content',
				'single'  => 'standard-content',
			],
			'single'  => [
				'post' => 'narrow-content',
			],
		],
		'single-content'   => [
			'image_size' => 'cover',
		],
		'content-archives' => [
			'enable' => [ 'post' ],
			'post'   => [
				'boxed'      => false,
				'columns'    => '1',
				'title_size' => 'xxl',
				'show'       => [
					'genesis_entry_header',
					'title',
					'header_meta',
					'genesis_before_entry_content',
					'content',
					'genesis_entry_content',
					'genesis_after_entry_content',
					'genesis_entry_footer',
				],
			],
		],
	],
	'custom-functions' => function () {
		add_filter( 'genesis_attr_entry-more', function ( $atts ) {
			$atts['class'] = 'entry-more' . ( is_admin() ? ' wp-block-button' : '' );

			return $atts;
		});

		add_filter( 'genesis_attr_entry-more-link', function ( $atts ) {
			$atts['class'] = 'entry-more-link has-xl-margin-top ' . ( is_admin() ? 'wp-block-button__link' : 'button' );

			return $atts;
		});
	},
];
