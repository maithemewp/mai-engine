<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https:         //bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2020 BizBudding
 * @license   GPL-2.0-or-later
 */

return [
	'demos'                  => [
		'agency'                => 23,
	],
	'global-styles'          => [
		'colors'                => [
			'link'                 => '#1ced8a',
			'primary'              => '#1ced8a',
			'secondary'            => '#141618',
			'heading'              => '#141618',
			'body'                 => '#5a636b',
			'alt'                  => '#1b1e20',
		],
		'fonts'                 => [
			'body'                 => 'Hind:400',
			'heading'              => 'Poppins:700,600',
		],
	],
	'theme-support'          => [
		'add'                   => [
			'sticky-header',
			'transparent-header',
		],
	],
	'settings'               => [
		'logo'                  => [
			'show-tagline'         => false,
		],
		'site-layouts'           => [
			'default'              => [
				'site'                => 'standard-content',
				'archive'             => 'wide-content',
				'single'              => 'narrow-content',
			],
		],
		'single-content'        => [
			'enable'               => [ 'post' ],
			'post'                 => [
				'show'                => [
					'genesis_entry_header',
					'title',
					'header_meta',
					'excerpt',
					'image',
					'genesis_before_entry_content',
					'content',
					'genesis_entry_content',
					'genesis_after_entry_content',
					'footer_meta',
					'genesis_entry_footer',
				],
			],
		],
		'content-archives'      => [
			'post'                 => [
				'show'                => [
					'image',
					'genesis_entry_header',
					'footer_meta',
					'title',
					'genesis_before_entry_content',
					'genesis_entry_content',
					'genesis_after_entry_content',
					'genesis_entry_footer',
				],
				'image_orientation'   => 'custom',
				'image_size'          => 'large',
				'image_position'      => 'background',
				'align_text_vertical' => 'bottom',
			],
		],
		'page-header'           => [
			'archive'              => '*',
			'single'               => [ 'page' ],
			'image'                => '',
			'background-color'     => 'heading',
			'text-color'           => 'light',
			'divider-color'        => 'white',
		],
	],
	'custom-functions'       => function () {

		// Remove posts in Portfolio category from blog.
		// TODO:                   Should this be added to all themes?
		add_filter( 'pre_get_posts', function ( $query ) {
			/**
			 * @var WP_Query $query WordPress query object.
			 */
			if ( $query->is_home ) {
				$id                    = get_cat_ID( 'portfolio' );

				$query->set( 'cat', '-' . $id );
			}

			return $query;
		} );
	},
];
