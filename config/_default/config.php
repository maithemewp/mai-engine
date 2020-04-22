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

	'genesis-settings'    => [
		'avatar_size'           => 48,
		'blog_cat_num'          => 12,
		'breadcrumb_home'       => 0,
		'breadcrumb_front_page' => 0,
		'breadcrumb_posts_page' => 0,
		'breadcrumb_single'     => 0,
		'breadcrumb_page'       => 0,
		'breadcrumb_archive'    => 0,
		'breadcrumb_404'        => 0,
		'breadcrumb_attachment' => 0,
		'site_layout'           => 'standard-content',
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

	'google-fonts'        => [],

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

	'image-sizes'         => [
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

	'page-layouts'        => [
		'add'    => [
			[
				'id'      => 'standard-content',
				'label'   => __( 'Standard Content', 'mai-engine' ),
				'img'     => mai_get_url() . 'assets/img/standard-content.gif',
				'type'    => [ 'site' ],
				'default' => true,
			],
			[
				'id'    => 'narrow-content',
				'label' => __( 'Narrow Content', 'mai-engine' ),
				'img'   => mai_get_url() . 'assets/img/narrow-content.gif',
				'type'  => [ 'site' ],
			],
			[
				'id'    => 'wide-content',
				'label' => __( 'Wide Content', 'mai-engine' ),
				'img'   => GENESIS_ADMIN_IMAGES_URL . '/layouts/c.gif',
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

	'post-type-support'   => [
		'add'    => [
			'excerpt'         => [ 'page' ],
			'genesis-layouts' => [ 'product' ],
			'genesis-seo'     => [ 'product' ],
		],
		'remove' => [],
	],

	/*
	|--------------------------------------------------------------------------
	| Required Plugins
	|--------------------------------------------------------------------------
	|
	| Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam scelerisque
	| venenatis augue eget lacinia. Suspendisse eros dui, fringilla si amet
	| ante et, fringilla tristique just. In interdum vitae metus ut fiat.
	|
	*/

	'required-plugins'    => [],

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

	'responsive-menu'     => [
		'script' => [
			'mainMenu'         => sprintf(
				'<span class="menu-toggle-icon"></span><span class="screen-reader-text">%s</span>',
				__( 'Menu', 'mai-engine' )
			),
			'menuIconClass'    => null,
			'subMenuIconClass' => null,
			'menuClasses'      => [
				'combine' => [
					'.nav-header-left',
					'.nav-header-right',
					'.nav-after-header',
					'.mobile-menu .menu-header-menu-container',
				],
				'others'  => [],
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

	'scripts-and-styles'  => [
		'add'    => [

			// Scripts.
			[
				'handle' => mai_get_handle() . '-global',
				'src'    => mai_get_asset_url( 'global.js' ),
			],
			[
				'handle'   => mai_get_handle() . '-menus',
				'src'      => mai_get_asset_url( 'menus.js' ),
				'localize' => [
					'name' => 'maiMenuVars',
					'data' => [
						'breakpoint'    => mai_get_breakpoint(),
						'ariaLabel'     => __( 'Mobile Menu', 'mai-engine' ),
						'menuToggle'    => sprintf(
							'<span class="menu-toggle-icon"></span><span class="screen-reader-text">%s</span>',
							__( 'Menu', 'mai-engine' )
						),
						'subMenuToggle' => sprintf(
							'<span class="sub-menu-toggle-icon"></span><span class="screen-reader-text">%s</span>',
							__( 'Sub Menu', 'mai-engine' )
						),
						'searchIcon'    => mai_get_svg( 'search', 'regular', 'search-toggle-icon' ),
						'searchBox'     => ! defined( 'STYLESHEETPATH' ) ?:
							get_search_form(
								[
									'aria_label' => esc_html__( 'Menu Search', 'mai-engine' ),
									'echo'       => false,
								]
							),
					],
				],
			],
			[
				'handle'    => mai_get_handle() . '-header',
				'src'       => mai_get_asset_url( 'header.js' ),
				'condition' => function () {
					return mai_has_sticky_header() || mai_has_transparent_header();
				},
			],

			// Customizer scripts.
			[
				'handle'   => mai_get_handle() . '-customizer',
				'src'      => mai_get_asset_url( 'customizer.js' ),
				'deps'     => [ 'jquery' ],
				'location' => 'customizer',
			],

			// Admin scripts.
			[
				'handle'   => mai_get_handle() . '-editor',
				'src'      => mai_get_asset_url( 'editor.js' ),
				'deps'     => [ 'jquery' ],
				'location' => 'editor',
				'localize' => [
					'name' => 'maiEditorVars',
					'data' => 'mai_get_editor_localized_data',
				],
			],

			// Styles.
			[
				'handle' => mai_get_handle(),
				'src'    => mai_get_url() . 'assets/css/themes/' . mai_get_active_theme() . '.min.css',
			],

			// Customizer styles.
			[
				'handle'   => mai_get_handle() . '-kirki',
				'src'      => mai_get_url() . 'assets/css/plugins/kirki.min.css',
				'location' => 'customizer',
			],

			// ACF styles.
			[
				'handle'   => mai_get_handle() . '-advanced-custom-fields',
				'src'      => mai_get_url() . 'assets/css/plugins/advanced-custom-fields.min.css',
				'location' => 'editor',
			],

			// OCDI styles.
			[
				'handle'   => mai_get_handle() . '-one-click-demo-import',
				'src'      => mai_get_url() . 'assets/css/plugins/one-click-demo-import.min.css',
				'location' => 'admin',
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
	| Theme Support
	|--------------------------------------------------------------------------
	|
	| Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam scelerisque
	| venenatis augue eget lacinia. Suspendisse eros dui, fringilla si amet
	| ante et, fringilla tristique just. In interdum vitae metus ut fiat.
	|
	*/

	'theme-support'       => [
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
				'after-header' => __( 'After Header Menu', 'mai-engine' ),
				'footer'       => __( 'Footer Menu', 'mai-engine' ),
			],
			'genesis-structural-wraps' => [
				'header',
				'footer-widgets',
				'menu-after-header',
				'menu-footer',
				'page-header',
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

	'widget-areas'        => [
		'add'    => [
			[
				'id'          => 'before-header',
				'name'        => __( 'Before Header', 'mai-engine' ),
				'description' => __( 'The Before Header widget area.', 'mai-engine' ),
				'location'    => 'genesis_before_header',
			],
			[
				'id'          => 'header-left',
				'name'        => __( 'Header Left', 'mai-engine' ),
				'description' => __( 'The Header Left widget area.', 'mai-engine' ),
				'location'    => 'mai_header_left',
				'args'        => [
					'before' => '<div class="header-widget-area">',
					'after'  => '</div>',
				],
			],
			[
				'id'          => 'header-right',
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
				'location'    => 'mai_after_header_wrap',
			],
		],
		'remove' => [
			'sidebar-alt',
			'header-right',
		],
	],

	/*
	|--------------------------------------------------------------------------
	| Content Types & Settings
	|--------------------------------------------------------------------------
	|
	| Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam scelerisque
	| venenatis augue eget lacinia. Suspendisse eros dui, fringilla si amet
	| ante et, fringilla tristique just. In interdum vitae metus ut fiat.
	|
	*/

	'loop'                => [
		'archive' => [
			'post',
			'category',
			'search',
		],
		'single'  => [
			'page',
			'post',
		],
	],

	'page-header'         => [
		'archive' => [],
		'single'  => [],
	],

	/*
	|--------------------------------------------------------------------------
	| Page Header
	|--------------------------------------------------------------------------
	|
	| Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam scelerisque
	| venenatis augue eget lacinia. Suspendisse eros dui, fringilla si amet
	| ante et, fringilla tristique just. In interdum vitae metus ut fiat.
	|
	*/


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

	'custom-functions'    => '__return_null',
];
