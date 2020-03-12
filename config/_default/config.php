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
		'content_archive'           => 'full',      // TODO: Not sure we need this with new archive settings.
		'content_archive_limit'     => 200,         // TODO: Same as above.
		'content_archive_thumbnail' => 1,           // TODO: Same as above.
		'image_size'                => 'featured',  // TODO: Same as above.
		'image_alignment'           => 'alignnone', // TODO: Same as above.
		'posts_nav'                 => 'numeric',   // TODO: Same as above.
		'site_layout'               => 'standard-content',
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
		// TODO: Should this be empty to start, incase a custom site doesn't want a google font? Or that site should just filter this and return empty array?
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
			'cover'     => [ 1600, 900, true ],
			'landscape' => '4:3',
			'tiny'      => [ 80, 80, true ],
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
				'id'      => 'standard-content',
				'label'   => __( 'Standard Content', 'mai-engine' ),
				'img'     => mai_get_url() . 'assets/img/standard-content.gif',
				'type'    => [ 'site' ],
				'default' => true,
			],
			[
				'id'    => 'wide-content',
				'label' => __( 'Wide Content', 'mai-engine' ),
				'img'   => GENESIS_ADMIN_IMAGES_URL . '/layouts/c.gif',
				'type'  => [ 'site' ],
			],
			[
				'id'    => 'narrow-content',
				'label' => __( 'Narrow Content', 'mai-engine' ),
				'img'   => mai_get_url() . 'assets/img/narrow-content.gif',
				'type'  => [ 'site' ],
			],
			[
				'id'    => 'content-sidebar',
				'label' => __( 'Content, Sidebar', 'mai-engine' ),
				'img'   => GENESIS_ADMIN_IMAGES_URL . '/layouts/cs.gif',
				'type'  => [ 'site' ],
			],
			[
				'id'    => 'sidebar-content',
				'label' => __( 'Sidebar, Content', 'mai-engine' ),
				'img'   => GENESIS_ADMIN_IMAGES_URL . '/layouts/sc.gif',
				'type'  => [ 'site' ],
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
			'page-header-single'         => [ 'page', 'post', 'product', 'portfolio' ],
			'page-header-archive'        => [ 'page', 'post', 'product', 'portfolio' ],
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
			'mainMenu'         => sprintf(
				'<span class="menu-toggle-icon"> </span><span class="screen-reader-text">%s</span>',
				__( 'Menu', 'mai-engine' )
			),
			'menuIconClass'    => null,
			'subMenuIconClass' => null,
			'menuClasses'      => [
				'combine' => [],
				'others'  => [
					'.nav-header-left',
					'.nav-header-right',
					'.nav-below-header',
					'.mobile-menu .menu-header-menu-container',
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
			'media_query_width' => mai_get_breakpoint( 'md' ),
			'css'               => '',
			'enable_AMP'        => true,
			'enable_non_AMP'    => true,
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

			// Scripts.
			[
				'handle' => mai_get_handle() . '-editor',
				'src'    => mai_get_url() . 'assets/js/editor.js',
				'deps'   => [ 'jquery', 'wp-blocks' ],
				'editor' => true,
			],
			[
				'handle' => mai_get_handle(),
				'src'    => mai_get_url() . 'assets/js/min/global.min.js',
				'deps'   => [],
			],

			// Customizer scripts.

			[
				'handle'     => mai_get_handle() . '-customizer',
				'src'        => mai_get_url() . 'assets/js/customizer.js',
				'deps'       => [],
				'customizer' => true,
			],

			// Grid scripts.

			[
				'handle' => mai_get_handle() . '-sortable',
				'src'    => mai_get_url() . 'assets/js/sortable.js',
				'deps'   => [],
				'editor' => true, // Only load in the admin editor.
			],
			[
				'handle'   => mai_get_handle() . '-wp-query',
				'src'      => mai_get_url() . 'assets/js/wp-query.js',
				'deps'     => [],
				'editor'   => true, // Only load in the admin editor.
				'localize' => [
					'name' => 'maiGridWPQueryVars',
					'data' => [
						'keys' => mai_get_settings_keys( 'block' ),
					],
				],
			],

			// Styles.
			[
				'handle' => mai_get_handle(),
				'src'    => mai_get_url() . 'assets/css/themes/' . mai_get_active_theme() . '.min.css',
			],

			// Customizer styles.
			[
				'handle'     => mai_get_handle() . '-kirki',
				'src'        => mai_get_url() . 'assets/css/plugins/kirki.min.css',
				'customizer' => true,
			],

			// Grid styles.
			[
				'handle' => mai_get_handle() . '-advanced-custom-fields',
				'src'    => mai_get_url() . 'assets/css/plugins/advanced-custom-fields.min.css',
				'editor' => true,
			],

			// Plugin styles.
			[
				'handle'    => mai_get_handle() . '-atomic-blocks',
				'src'       => mai_get_url() . 'assets/css/plugins/atomic-blocks.min.css',
				'condition' => function () {
					return function_exists( 'atomic_blocks_main_plugin_file' );
				},
			],
			[
				'handle'    => mai_get_handle() . '-seo-slider',
				'src'       => mai_get_url() . 'assets/css/plugins/seo-slider.min.css',
				'condition' => function () {
					return defined( 'SEO_SLIDER_VERSION' );
				},
			],
			[
				'handle'    => mai_get_handle() . '-simple-social-icons',
				'src'       => mai_get_url() . 'assets/css/plugins/simple-social-icons.min.css',
				'condition' => function () {
					return class_exists( 'Simple_Social_Icons_Widget' );
				},
			],
			[
				'handle'    => mai_get_handle() . '-woocommerce',
				'src'       => mai_get_url() . 'assets/css/plugins/woocommerce.min.css',
				'condition' => function () {
					return class_exists( 'WooCommerce' );
				},
			],

		],
		'remove' => [
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
		'icon_color'             => mai_get_color( 'heading' ),
		'icon_color_hover'       => mai_get_color( 'primary' ),
		'size'                   => 40,
	],

	/*
	|--------------------------------------------------------------------------
	| Template Settings
	|--------------------------------------------------------------------------
	|
	| Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam scelerisque
	| venenatis augue eget lacinia. Suspendisse eros dui, fringilla si amet
	| ante et, fringilla tristique just. In interdum vitae metus ut fiat.
	|
	*/

	'archive-settings' => [
		'post',
		'category',
		'portfolio',
		'page',
		'search',
		'author',
	],

	'single-settings' => [
		'page',
		'post',
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
				'header-selector' => '.page-header',
				'default_image'   => mai_get_url() . 'assets/img/page-header.jpg',
				'header-text'     => false,
				'width'           => 1280,
				'height'          => 720,
				'flex-height'     => true,
				'flex-width'      => true,
				'uploads'         => true,
				'video'           => false,
			],
			'editor-styles',
			'editor-color-palette'     => mai_get_color_palette(),
			'genesis-accessibility'    => [
				'404-page',
				'headings',
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
				'header-left'  => __( 'Header Left Menu', 'mai-engine' ),
				'header-right' => __( 'Header Right Menu', 'mai-engine' ),
				'below-header' => __( 'Below Header Menu', 'mai-engine' ),
				'footer'       => __( 'Footer Menu', 'mai-engine' ),
			],
			'genesis-structural-wraps' => [
				'header',
				'menu-below-header',
				'menu-footer',
				'page-header',
				'footer-widgets',
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
				'name'        => __( 'Before Header', 'mai-engine' ),
				'description' => __( 'The Before Header widget area.', 'mai-engine' ),
				'location'    => 'mai_before_header_wrap',
			],
			[
				'id'          => 'header_left',
				'name'        => __( 'Header Left', 'mai-engine' ),
				'description' => __( 'The Header Left widget area.', 'mai-engine' ),
				'location'    => 'mai_header_left',
				'args'        => [
					'before' => '<div class="header-widget-area">',
					'after'  => '</div>',
				],
			],
			[
				'id'          => 'header_right',
				'name'        => __( 'Header Right', 'mai-engine' ),
				'description' => __( 'The Header Right widget area.', 'mai-engine' ),
				'location'    => 'mai_header_right',
				'args'        => [
					'before' => '<div class="header-widget-area">',
					'after'  => '</div>',
				],
			],
			[
				'id'          => 'before-footer',
				'name'        => __( 'Before Footer', 'mai-engine' ),
				'description' => __( 'The Before Footer widget area.', 'mai-engine' ),
				'location'    => 'genesis_footer',
				'priority'    => 5,
			],
			[
				'id'          => 'mobile-menu',
				'name'        => __( 'Mobile Menu', 'mai-engine' ),
				'description' => __( 'The Mobile Menu widget area.', 'mai-engine' ),
				'location'    => 'genesis_header',
			],
		],
		'remove' => [
			'sidebar-alt',
			'header-right',
		],
	],

	/*
	|--------------------------------------------------------------------------
	| Custom functions
	|--------------------------------------------------------------------------
	|
	| Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam scelerisque
	| venenatis augue eget lacinia. Suspendisse eros dui, fringilla si amet
	| ante et, fringilla tristique just. In interdum vitae metus ut fiat.
	|
	*/

	'custom-functions' => '__return_null',
];
