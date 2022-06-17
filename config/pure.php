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
		// 'creator'     => 75,
		// 'author'      => 76,
		// 'photography' => 77,
	],
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
		'logo'             => [
			'show-tagline' => false,
		],
		'page-header'      => [
			'archive'          => '*',
			'background-color' => 'alt',
			'text-color'       => 'dark',
			'content-width'    => 'xl',
			'text-align'       => 'center',
			'spacing'          => [
				'top'    => '2em',
				'bottom' => '2em',
			],
		],
		'content-archives' => [
			'post' => [
				'show' => [
					'image',
					'genesis_entry_header',
					'title',
					'genesis_before_entry_content',
					'excerpt',
					'genesis_entry_content',
					'genesis_after_entry_content',
					'genesis_entry_footer',
				],
				'image_orientation'   => 'square',
				'image_position'      => 'left-middle',
				'image_width'         => 'fourth',
				'image_alternate'     => false,
				'header_meta'         => '[post_date format="M j, Y"] [post_author_posts_link before="by "]',
				'footer_meta'         => '[post_terms taxonomy="category" before="Category: "][post_terms taxonomy="post_tag" before="Tag: "]',
				'align_text_vertical' => 'middle',
				'boxed'               => false,
				'columns'             => '1',
				'row_gap'             => 'xxxl',
			],
		],
		'single-content' => [
			'post' => [
				'image_orientation' => 'square',
				'header_meta'       => '[mai_avatar size="32"][post_date format="M j, Y"][post_author_posts_link before=" · "]',
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
