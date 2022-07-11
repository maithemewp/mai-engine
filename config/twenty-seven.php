<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https:       //bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2020 BizBudding
 * @license   GPL-2.0-or-later
 */

return [
	'demos'            => [
		'default' => 'https://demo.bizbudding.com/twenty-seven/wp-content/uploads/sites/55/mai-engine/',
	],
	'global-styles'    => [
		'breakpoint' => 800,
		'colors'     => [
			'link'      => '#a74165',
			'primary'   => '#ffffff',
			'secondary' => '#ffffff',
			'heading'   => '#272727',
			'body'      => '#272727',
			'alt'       => '#e5e5e5',
		],
		'fonts'      => [
			'body'    => 'Inter:400',
			'heading' => 'Inter:400',
		],
	],
	'image-sizes'      => [
		'add'    => [
			'square' => '1:1',
		],
		'remove' => [
			'landscape-sm',
			'landscape-md',
			'landscape-lg',
		],
	],
	'settings'         => [
		'logo'             => [
			'width' => [
				'desktop' => '60px',
				'mobile'  => '60px',
			],
		],
		'single-content'   => [
			'enable' => [ 'post' ],
			'page'   => [
				'image_orientation' => 'square',
				'image_size'        => 'medium',
			],
			'post'   => [
				'show' => [
					'genesis_entry_header',
					'title',
					'header_meta',
					'footer_meta',
					'excerpt',
					'image',
					'genesis_before_entry_content',
					'content',
					'genesis_entry_content',
					'genesis_after_entry_content',
					'genesis_entry_footer',
				],
			],
		],
		'content-archives' => [
			'more_link_text' => __( 'Read the Post →', 'mai-engine' ),
			'post'           => [
				'boxed'             => false,
				'columns'           => '1',
				'image_orientation' => 'custom',
				'image_size'        => 'large',
				'title_size'        => 'xl',
				'show'              => [
					'genesis_entry_header',
					'title',
					'header_meta',
					'footer_meta',
					'image',
					'genesis_before_entry_content',
					'excerpt',
					'genesis_entry_content',
					'genesis_after_entry_content',
					'more_link',
					'genesis_entry_footer',
				],
			],
		],
	],
	'custom-functions' => function () {
		/**
		 * Removes screen reader text class from mobile toggle text.
		 *
		 * @since 2.11.0
		 *
		 * @param string $text The existing text.
		 *
		 * @return string
		 */
		add_filter( 'mai_menu_toggle_text', 'mai_twenty_seven_menu_toggle_text' );
		function mai_twenty_seven_menu_toggle_text( $text ) {
			return sprintf( '<span>%s</span>', __( 'Menu', 'mai-engine' ) );
		}

		add_filter( 'genesis_attr_entry-more', function ( $atts ) {
			$atts['class'] = 'entry-more' . ( is_admin() ? ' wp-block-button' : '' );
			return $atts;
		} );

		add_filter( 'genesis_attr_entry-more-link', function ( $atts ) {
			$atts['class'] = 'entry-more-link has-xl-margin-top ' . ( is_admin() ? 'wp-block-button__link' : 'button-link' );
			return $atts;
		} );
	},
];
