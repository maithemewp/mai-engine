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
		'default' => 53,
	],
	'global-styles' => [
		'breakpoint' => 1360,
		'colors'     => [
			'link'      => '#ffe1ce',
			'primary'   => '#000000',
			'secondary' => '#f5f5f5',
			'body'      => '#000000',
			'heading'   => '#000000',
			'alt'       => '#f5f5f5',
		],
		'fonts'      => [
			'body'    => 'Lato:400',
			'heading' => 'Playfair Display:400',
			'menu'    => 'Playfair Display:700',
		],
	],
	'theme-support' => [
		'add' => [
			'sticky-header',
		],
	],
	'scripts'       => [
		'menus' => [
			'localize' => [
				'data' => [
					'menuToggle' => sprintf(
						'<span class="menu-toggle-icon"></span> &nbsp; %s',
						__( 'Menu', 'mai-engine' )
					),
				],
			],
		],
	],
	'settings'      => [
		'logo'             => [
			'show-tagline' => false,
			'spacing'      => [
				'desktop' => '25px',
			],
		],
		'site-layouts'      => [
			'default' => [
				'site'    => 'standard-content',
				'archive' => 'wide-content',
				'single'  => 'wide-content',
			],
		],
		'single-content'   => [
			'post' => [
				'show'                         => [
					'genesis_entry_header',
					'image',
					'header_meta',
					'title',
					'excerpt',
					'genesis_before_entry_content',
					'content',
					'genesis_entry_content',
					'genesis_after_entry_content',
					'footer_meta',
					'genesis_entry_footer',
				],
				'image_orientation' => 'custom',
				'image_size'        => 'cover',
			],
		],
		'content-archives' => [
			'post' => [
				'show'              => [
					'image',
					'genesis_entry_header',
					'header_meta',
					'title',
					'excerpt',
					'genesis_before_entry_content',
					'content',
					'genesis_entry_content',
					'genesis_after_entry_content',
					'footer_meta',
					'genesis_entry_footer',
				],
				'image_orientation' => 'custom',
				'image_size'        => 'cover',
				'title_size'        => 'xxxxl',
				'boxed'             => false,
				'columns'           => '1',
			],
		],
		'header-left-menu-alignment'  => 'flex-end',
		'header-right-menu-alignment' => 'flex-start',
		'after-header-menu-alignment' => 'center',
	],
];
