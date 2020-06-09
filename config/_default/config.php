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

	'genesis-settings' => [
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

	'google-fonts' => [],

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
	| Site Layouts
	|--------------------------------------------------------------------------
	|
	| Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam scelerisque
	| venenatis augue eget lacinia. Suspendisse eros dui, fringilla si amet
	| ante et, fringilla tristique just. In interdum vitae metus ut fiat.
	|
	*/

	'site-layouts' => [
		'default' => [
			'site'    => 'standard-content',
			'archive' => 'wide-content',
			'single'  => '',
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

	'plugins' => [],

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

	'scripts-and-styles' => [
		'add'    => [

			// Scripts.
			[
				'handle' => mai_get_handle() . '-global',
				'src'    => mai_get_asset_url( 'global.js' ),
				'async'  => true,
			],
			[
				'handle'   => mai_get_handle() . '-menus',
				'src'      => mai_get_asset_url( 'menus.js' ),
				'async'    => true,
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
						'searchIcon'    => mai_get_svg_icon( 'search', 'regular', 'search-toggle-icon' ),
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
				'async'     => true,
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

			// Editor scripts.
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

			// Fonts.
			[
				'handle'    => mai_get_handle() . '-google-fonts',
				'src'       => content_url( 'mai-fonts/style.min.css?display=swap' ),
				'editor'    => 'both',
				'media'     => 'print',
				'onload'    => "this.media='all'",
				'condition' => function () {
					return file_exists( WP_CONTENT_DIR . '/mai-fonts/style.min.css' );
				},
			],

			// Customizer styles.
			[
				'handle'   => mai_get_handle() . '-kirki',
				'src'      => mai_get_url() . 'assets/css/plugins/kirki.min.css',
				'location' => 'customizer',
			],

			// Admin styles.
			[
				'handle'   => mai_get_handle() . '-admin',
				'src'      => mai_get_url() . 'assets/css/admin/admin.min.css',
				'location' => 'admin',
			],

			// ACF styles.
			[
				'handle'   => mai_get_handle() . '-advanced-custom-fields',
				'src'      => mai_get_url() . 'assets/css/plugins/advanced-custom-fields.min.css',
				'location' => 'editor',
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
			'editor-styles',
			'editor-color-palette'     => mai_get_color_palette(),
			'genesis-accessibility'    => [
				'404-page',
				'headings',
				'search-form',
				'skip-links',
			],
			'',
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
			'genesis-menus'            => [
				'header-left'  => __( 'Header Left Menu', 'mai-engine' ),
				'header-right' => __( 'Header Right Menu', 'mai-engine' ),
				'after-header' => __( 'After Header Menu', 'mai-engine' ),
			],
			'genesis-structural-wraps' => [
				'header',
				'menu-after-header',
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
		'remove' => [
			'genesis-footer-widgets',
		],
	],

	/*
	|--------------------------------------------------------------------------
	| Archive & Single Settings Content Types
	|--------------------------------------------------------------------------
	|
	| Enable (archive/single) settings for content types.
	| This is only for defaults when activating the theme.
	| These can be added/removed via:
	| Customizer > Theme Settings > Content Archives
	| or
	| Customizer > Theme Settings > Single Content
	|
	| Archive can be any of the following:
	| 1. any post_type name as long as the post type is public and has an archive,
	| 2. any public taxonomy name,
	| 3. 'search' for search results,
	| 4. 'author' for author archives,
	| 5. 'date' for date archives.
	|
	| Single can be any of the following:
	| 1. any public post_type name,
	| 2. any public taxonomy name,
	| 3. '404-page' for 404.
	*/

	'archive-settings' => [
		'post',
	],

	'single-settings' => [
		'page',
		'post',
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

	'page-header' => [
		'archive'                 => [],
		'single'                  => [],
		'background-color'        => mai_get_color( 'medium' ),
		'image'                   => '',
		'overlay-opacity'         => '0.5',
		'text-color'              => 'dark',
		'spacing'                 => [
			'top'    => '10vw',
			'bottom' => '10vw',
		],
		'text-align'              => '',
		'divider'                 => '',
		'divider-color'           => mai_get_color( 'lightest' ),
		'divider-flip-horizontal' => false,
		'divider-flip-vertical'   => false,
		'divider-overlay-opacity' => 0.5,
		'divider-text-align'      => '',
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
				'id'          => 'mobile-menu',
				'name'        => __( 'Mobile Menu', 'mai-engine' ),
				'description' => __( 'The Mobile Menu widget area.', 'mai-engine' ),
				'location'    => 'mai_after_header_wrap',
			],
			[
				'id'          => 'sidebar',
				'name'        => __( 'Sidebar', 'mai-engine' ),
				'description' => __( 'The Sidebar widget area.', 'mai-engine' ),
				'location'    => '',
			],
			[
				'id'          => 'after-entry',
				'name'        => __( 'After Entry', 'mai-engine' ),
				'description' => __( 'The After Entry widget area.', 'mai-engine' ),
				'location'    => '',
			],
			[
				'id'          => 'before-footer',
				'name'        => __( 'Before Footer', 'mai-engine' ),
				'description' => __( 'The Before Footer widget area.', 'mai-engine' ),
				'location'    => 'genesis_footer',
				'priority'    => 5,
			],
			[
				'id'          => 'footer',
				'name'        => __( 'Footer', 'mai-engine' ),
				'description' => __( 'The Footer widget area.', 'mai-engine' ),
				'location'    => 'genesis_footer',
				'priority'    => 10,
				'args'        => [
					'before' => '',
					'after'  => '',
				],
			],
			[
				'id'          => 'footer-credits',
				'name'        => __( 'Footer Credits', 'mai-engine' ),
				'description' => __( 'The Footer Credits widget area.', 'mai-engine' ),
				'location'    => 'genesis_footer',
				'priority'    => 12,
				'default'     => sprintf(
					'%s [footer_copyright] · [footer_home_link] · %s · %s <a target="_blank" rel="nofollow noopener sponsored" href="https://bizbudding.com/mai-theme/">%s</a>',
					__( 'Copyright', 'mai-engine' ),
					__( 'All Rights Reserved', 'mai-engine' ),
					__( 'Powered by', 'mai-engine' ),
					__( 'Mai Theme', 'mai-engine' )
				),
			],
		],
		'remove' => [],
	],
];
