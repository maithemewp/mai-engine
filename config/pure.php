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
	'global-styles' => [
		'colors' => [
			'black'      => '#111111',
			'white'      => '#ffffff',
			'header'     => '#ffffff', // Site header background.
			'background' => '#ffffff', // Body background.
			'alt'        => '#f8f8f8', // Background alt.Gray #f8f8f8. Blue #f8fbff. Tan #fbf8f5.
			'body'       => '#111111', // Body text color.
			'heading'    => '#111111', // Heading text color.
			'link'       => '#02C091', // Link color.
			'primary'    => '#02C091', // Button primary background color.
			'secondary'  => '#737373', // Button secondary background color.
		],
		'fonts' => [
			'body'    => 'EB Garamond:400',
			'heading' => 'Jost:600',
		],
		'font-variants' => [
			'heading' => [
				'light' => '400',
			],
		],
	],
	'image-sizes' => [
		'add'    => [
			'landscape' => '3:2',
			'portrait'  => '2:3',
			'square'    => '1:1',
		],
	],
	'theme-support' => [
		'add' => [
			'transparent-header',
		],
	],
	'settings' => [
		'single-content' => [
			'post' => [
				'image_orientation' => 'square',
				'header_meta'       => '[mai_avatar size="32"][post_date][post_author_posts_link before=" · "]',
				'show'              => [
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
					// 'author_box',
					// 'adjacent_entry_nav',
				],
			],
		],
	],
];
