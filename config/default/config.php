<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2019 BizBudding
 * @license   GPL-2.0-or-later
 */

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

return [

	/*
	|--------------------------------------------------------------------------
	| Genesis Settings
	|--------------------------------------------------------------------------
	|
	| Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam scelerisque
	| venenatis augue eget lacinia. Suspendisse eros dui, fringilla si amet
	| ante et, fringilla tristique just. In interdum vitae metus ut fiat.
	|
	*/

	'genesis-settings' => [
		'avatar_size'               => 48,
		'blog_cat_num'              => 9,
		'breadcrumb_home'           => 0,
		'breadcrumb_front_page'     => 0,
		'breadcrumb_posts_page'     => 0,
		'breadcrumb_single'         => 0,
		'breadcrumb_page'           => 0,
		'breadcrumb_archive'        => 0,
		'breadcrumb_404'            => 0,
		'breadcrumb_attachment'     => 0,
		'content_archive'           => 'full',
		'content_archive_limit'     => 200,
		'content_archive_thumbnail' => 1,
		'image_size'                => 'featured',
		'image_alignment'           => 'alignnone',
		'posts_nav'                 => 'numeric',
		'site_layout'               => 'narrow-content',
	],

	/*
	|--------------------------------------------------------------------------
	| Google Fonts
	|--------------------------------------------------------------------------
	|
	| Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam scelerisque
	| venenatis augue eget lacinia. Suspendisse eros dui, fringilla si amet
	| ante et, fringilla tristique just. In interdum vitae metus ut fiat.
	|
	*/

	'google-fonts' => [
		'Source+Sans+Pro:400,600,700',
	],

	/*
	|--------------------------------------------------------------------------
	| Image Sizes
	|--------------------------------------------------------------------------
	|
	| Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam scelerisque
	| venenatis augue eget lacinia. Suspendisse eros dui, fringilla si amet
	| ante et, fringilla tristique just. In interdum vitae metus ut fiat.
	|
	*/

	'image-sizes' => [
		'add'    => [
			'featured' => [ 620, 380, true ],
			'hero'     => [ 1920, 1080, true ],
		],
		'remove' => [],
	],

	/*
	|--------------------------------------------------------------------------
	| Page Layouts
	|--------------------------------------------------------------------------
	|
	| Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam scelerisque
	| venenatis augue eget lacinia. Suspendisse eros dui, fringilla si amet
	| ante et, fringilla tristique just. In interdum vitae metus ut fiat.
	|
	*/

	'page-layouts' => [
		'add'    => [
			[
				'id'    => 'narrow-content',
				'label' => __( 'Narrow Content', 'child-theme-engine' ),
				'img'   => mai_url() . 'assets/img/narrow-content.gif',
			],
		],
		'remove' => [
			'content-sidebar-sidebar',
			'sidebar-sidebar-content',
			'sidebar-content-sidebar',
		],
	],

	/*
	|--------------------------------------------------------------------------
	| Post Type Support
	|--------------------------------------------------------------------------
	|
	| Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam scelerisque
	| venenatis augue eget lacinia. Suspendisse eros dui, fringilla si amet
	| ante et, fringilla tristique just. In interdum vitae metus ut fiat.
	|
	*/

	'post-type-support' => [
		'add'    => [
			'excerpt'                    => [ 'page' ],
			'genesis-layouts'            => [ 'product' ],
			'genesis-seo'                => [ 'product' ],
			'genesis-singular-images'    => [ 'page', 'post' ],
			'genesis-title-toggle'       => [ 'post', 'product' ],
			'genesis-adjacent-entry-nav' => [ 'post', 'product', 'portfolio' ],
			'hero-section-single'        => [ 'page', 'post', 'product', 'portfolio' ],
			'hero-section-archive'       => [ 'page', 'post', 'product', 'portfolio' ],
			'terms-filter'               => [ 'post', 'portfolio' ],
		],
		'remove' => [],
	],

	/*
	|--------------------------------------------------------------------------
	| Responsive Menu
	|--------------------------------------------------------------------------
	|
	| Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam scelerisque
	| venenatis augue eget lacinia. Suspendisse eros dui, fringilla si amet
	| ante et, fringilla tristique just. In interdum vitae metus ut fiat.
	|
	*/

	'responsive-menu' => [
		'script' => [
			'mainMenu'         => sprintf( '<span class="hamburger"> </span><span class="screen-reader-text">%s</span>', __( 'Menu', 'child-theme-engine' ) ),
			'menuIconClass'    => null,
			'subMenuIconClass' => null,
			'menuClasses'      => [
				'combine' => [
					'.nav-primary',
					'.nav-secondary',
				],
			],
			'menuAnimation'    => [
				'effect'   => 'fadeToggle',
				'duration' => 'fast',
				'easing'   => 'swing',
			],
			'subMenuAnimation' => [
				'effect'   => 'slideToggle',
				'duration' => 'fast',
				'easing'   => 'swing',
			],
		],
		'extras' => [
			'media_query_width' => '896px',
		],
	],

	/*
	|--------------------------------------------------------------------------
	| Scripts and Styles
	|--------------------------------------------------------------------------
	|
	| Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam scelerisque
	| venenatis augue eget lacinia. Suspendisse eros dui, fringilla si amet
	| ante et, fringilla tristique just. In interdum vitae metus ut fiat.
	|
	*/

	'scripts-and-styles' => [
		'add'    => [
			[
				'handle' => mai_handle() . '-editor',
				'src'    => mai_url() . 'assets/js/editor.js',
				'deps'   => [ 'wp-blocks' ],
				'editor' => true,
			],
			[
				'handle'    => mai_handle(),
				'src'       => mai_url() . 'assets/js/min/global.min.js',
				'deps'      => [],
				'condition' => function () {
					return ! genesis_is_amp();
				},
			],
			[
				'handle' => mai_handle() . '-critical',
				'src'    => mai_url() . 'assets/css/' . mai_active_theme() . '/critical.css',
			],
			[
				'handle' => mai_handle() . '-header',
				'src'    => mai_url() . 'assets/css/' . mai_active_theme() . '/header.css',
				'hook'   => 'genesis_before_header',
			],
			[
				'handle'   => mai_handle() . '-desktop',
				'src'      => mai_url() . 'assets/css/' . mai_active_theme() . '/desktop.css',
				'media'    => '(min-width:896px)',
				'hook'     => 'mai_after_title_area',
				'priority' => 5,
			],
			[
				'handle' => mai_handle() . '-hero',
				'src'    => mai_url() . 'assets/css/' . mai_active_theme() . '/hero.css',
				'hook'   => 'genesis_before_content_sidebar_wrap',
				'priority' => 5,
			],
			[
				'handle' => mai_handle() . '-content',
				'src'    => mai_url() . 'assets/css/' . mai_active_theme() . '/content.css',
				'hook'   => 'genesis_before_content',
			],
			[
				'handle' => mai_handle() . '-comments',
				'src'    => mai_url() . 'assets/css/' . mai_active_theme() . '/comments.css',
				'hook'   => 'genesis_before_comments',
			],
			[
				'handle' => mai_handle() . '-sidebar',
				'src'    => mai_url() . 'assets/css/' . mai_active_theme() . '/sidebar.css',
				'hook'   => 'genesis_before_sidebar_widget_area',
			],
			[
				'handle' => mai_handle() . '-footer',
				'src'    => mai_url() . 'assets/css/' . mai_active_theme() . '/footer.css',
				'hook'   => 'genesis_before_footer',
			],
		],
		'remove' => [
			'superfish',
			'simple-social-icons-font',
		],
	],

	/*
	|--------------------------------------------------------------------------
	| Simple Social Icons
	|--------------------------------------------------------------------------
	|
	| Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam scelerisque
	| venenatis augue eget lacinia. Suspendisse eros dui, fringilla si amet
	| ante et, fringilla tristique just. In interdum vitae metus ut fiat.
	|
	*/

	'simple-social-icons' => [
		'alignment'              => 'alignleft',
		'background_color'       => '',
		'background_color_hover' => '',
		'border_radius'          => 3,
		'border_width'           => 0,
		'icon_color'             => mai_default_color( 'heading' ),
		'icon_color_hover'       => mai_default_color( 'primary' ),
		'size'                   => 40,
	],

	/*
	|--------------------------------------------------------------------------
	| Theme Support
	|--------------------------------------------------------------------------
	|
	| Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam scelerisque
	| venenatis augue eget lacinia. Suspendisse eros dui, fringilla si amet
	| ante et, fringilla tristique just. In interdum vitae metus ut fiat.
	|
	*/

	'theme-support' => [
		'add'    => [

			// Genesis defaults.
			'menus',
			'post-thumbnails',
			'title-tag',
			'automatic-feed-links',
			'body-open',
			'genesis-inpost-layouts',
			'genesis-archive-layouts',
			'genesis-admin-menu',
			'genesis-seo-settings-menu',
			'genesis-import-export-menu',
			'genesis-readme-menu',
			'genesis-customizer-theme-settings',
			'genesis-customizer-seo-settings',
			'genesis-auto-updates',

			// Custom.
			'align-wide',
			'automatic-feed-links',
			'custom-header'            => [
				'header-selector'  => '.hero-section',
				'default_image'    => mai_url() . 'assets/img/hero.jpg',
				'header-text'      => false,
				'width'            => 1280,
				'height'           => 720,
				'flex-height'      => true,
				'flex-width'       => true,
				'uploads'          => true,
				'video'            => true,
				'wp-head-callback' => 'mai_custom_header',
			],
			'editor-styles',
			'front-page-widgets'       => 5,
			'genesis-accessibility'    => [
				'404-page',
				'drop-down-menu',
				'headings',
				'rems',
				'search-form',
				'skip-links',
			],
			'genesis-after-entry-widget-area',
			'genesis-custom-logo'      => [
				'height'      => 60,
				'width'       => 120,
				'flex-height' => true,
				'flex-width'  => true,
				'header-text' => [
					'.site-title',
					'.site-description',
				],
			],
			'genesis-footer-widgets'   => 3,
			'genesis-menus'            => [
				'primary'   => __( 'Header Menu', 'child-theme-engine' ),
				'secondary' => __( 'After Header Menu', 'child-theme-engine' ),
			],
			'genesis-structural-wraps' => [
				'header',
				'menu-secondary',
				'hero-section',
				'footer-widgets',
				'front-page-widgets',
			],
			'gutenberg'                => [
				'wide-images' => true,
			],
			'html5'                    => [
				'caption',
				'comment-form',
				'comment-list',
				'gallery',
				'search-form',
			],
			'post-thumbnails',
			'responsive-embeds',
			'woocommerce',
			'wc-product-gallery-zoom',
			'wc-product-gallery-lightbox',
			'wc-product-gallery-slider',
			'wp-block-styles',
		],
		'remove' => [],
	],

	/*
	|--------------------------------------------------------------------------
	| Widget Areas
	|--------------------------------------------------------------------------
	|
	| Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam scelerisque
	| venenatis augue eget lacinia. Suspendisse eros dui, fringilla si amet
	| ante et, fringilla tristique just. In interdum vitae metus ut fiat.
	|
	*/

	'widget-areas' => [
		'add'    => [
			[
				'id'          => 'before-header',
				'name'        => __( 'Before Header', 'child-theme-engine' ),
				'description' => __( 'The Before Header widget area.', 'child-theme-engine' ),
			],
			[
				'id'          => 'before-footer',
				'name'        => __( 'Before Footer', 'child-theme-engine' ),
				'description' => __( 'The Before Footer widget area.', 'child-theme-engine' ),
			],
		],
		'remove' => [
			'sidebar-alt',
		],
	],
];

