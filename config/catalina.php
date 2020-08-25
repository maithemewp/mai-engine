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
		'default' => 51,
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
		'logo'           => [
			'show-tagline' => false,
		],
		'single-content' => [
			'image_size' => 'cover',
		],
	],
	'custom-functions' => function () {
		add_filter( 'genesis_attr_entry-more', function ( $atts ) {
			$atts['class'] = 'entry-more' . ( is_admin() ? ' wp-block-button' : '' );

			return $atts;
		} );

		add_filter( 'genesis_attr_entry-more-link', function ( $atts ) {
			$atts['class'] = 'entry-more-link has-xl-margin-top ' . ( is_admin() ? 'wp-block-button__link' : 'button' );

			return $atts;
		} );
	},
];
