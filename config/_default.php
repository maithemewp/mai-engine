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
	| Global Styles
	|--------------------------------------------------------------------------
	|
	| Default global styles controlled by the theme.
	*/

	'global-styles' => [
		'breakpoint'     => 1200,
		'contrast-limit' => 160,
		'colors'         => [
			'black'      => '#000000',
			'white'      => '#ffffff',
			'background' => '#ffffff',
			'alt'        => '#f8f9fa', // Background alt.
			'body'       => '#6c747d',
			'heading'    => '#343a40',
			'link'       => '#007bff',
			'primary'    => '#007bff', // Button primary.
			'secondary'  => '#6c747d', // Button secondary.
		],
		'fonts'          => [
			'body'    => 'sans-serif:400',
			'heading' => 'sans-serif:600',
		],
		'extra'          => [],
	],

	/*
	|--------------------------------------------------------------------------
	| Image Sizes
	|--------------------------------------------------------------------------
	|
	| Image sizes. When adding or modifying 'landscape', 'portrait', or 'square'
	| you must use an aspect ratio, not actual dimensions.
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
	| Available page layouts.
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
	| Post Type Support
	|--------------------------------------------------------------------------
	|
	| Add/remove post type support for various post types.
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
	| Required plugins to be installed during the plugins step in the setup wizard.
	*/

	'plugins' => [],

	/*
	|--------------------------------------------------------------------------
	| Scripts and Styles
	|--------------------------------------------------------------------------
	|
	| All of the scripts and styles to be added or removed.
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
						'ariaLabel'     => __( 'Mobile Menu', 'mai-engine' ),
						'menuToggle'    => sprintf(
							'<span class="menu-toggle-icon"></span><span class="screen-reader-text">%s</span>',
							__( 'Menu', 'mai-engine' )
						),
						'subMenuToggle' => sprintf(
							'<span class="sub-menu-toggle-icon"></span><span class="screen-reader-text">%s</span>',
							__( 'Sub Menu', 'mai-engine' )
						),
						'searchIcon'    => mai_get_svg_icon(
							'search',
							'regular',
							[
								'class' => 'search-toggle-icon',
							]
						),
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
					return mai_has_sticky_header_enabled() || mai_has_transparent_header_enabled();
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
				'deps'     => [ 'jquery', 'jquery-ui-sortable' ],
				'location' => 'editor',
				'localize' => [
					'name' => 'maiEditorVars',
					'data' => 'mai_get_editor_localized_data',
				],
			],

			// Block scripts.
			[
				'handle'   => mai_get_handle() . '-blocks',
				'src'      => mai_get_url() . 'assets/js/min/blocks.js',
				'deps'     => [ 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ],
				'location' => 'editor',
			],

			// Main styles.
			[
				'handle' => mai_get_handle(),
				'src'    => mai_get_url() . 'assets/css/main.min.css',
			],

			// Theme specific styles.
			[
				'handle' => mai_get_handle() . '-theme',
				'src'    => mai_get_url() . 'assets/css/themes/' . mai_get_active_theme() . '.min.css',
			],

			// Admin styles.
			[
				'handle'   => mai_get_handle() . '-admin',
				'src'      => mai_get_url() . 'assets/css/admin.min.css',
				'location' => 'admin',
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

			// Plugin styles.
			[
				'handle'    => mai_get_handle() . '-amp',
				'src'       => mai_get_url() . 'assets/css/plugins/amp.min.css',
				'condition' => function () {
					return genesis_is_amp();
				},
			],
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

			// Remove block library theme CSS in editor.
			[
				'handle'   => 'wp-block-library-theme',
				'src'      => '',
				'location' => 'editor',
			],
		],
		'remove' => [
			'simple-social-icons-font',
			'wp-block-library-theme',
		],
	],

	/*
	|--------------------------------------------------------------------------
	| Theme Support
	|--------------------------------------------------------------------------
	|
	| Default theme supports. You probably shouldn't mess with these.
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
	| Custom functions
	|--------------------------------------------------------------------------
	|
	| Default callable functions and filters to be run after_theme_setup.
	*/

	'custom-functions' => '__return_null',

	/*
	|--------------------------------------------------------------------------
	| Template Parts
	|--------------------------------------------------------------------------
	|
	| Default template parts to be created and available for use.
	*/

	'template-parts' => [
		[
			'id'         => 'before-header',
			'location'   => 'genesis_before_header',
			'menu_order' => 5,
			'before'     => '<div class="before-header">',
			'after'      => '</div>',
		],
		[
			'id'         => 'header-left',
			'location'   => 'mai_header_left',
			'menu_order' => 10,
		],
		[
			'id'         => 'header-right',
			'location'   => 'mai_header_right',
			'menu_order' => 15,
		],
		[
			'id'         => 'mobile-menu',
			'location'   => 'mai_after_header_wrap',
			'before'     => '<div class="mobile-menu"><div class="wrap">',
			'after'      => '</div></div>',
			'menu_order' => 20,
		],
		[
			'id'         => 'after-entry',
			'name'       => __( 'After Entry', 'mai-engine' ),
			'menu_order' => 25,
		],
		[
			'id'         => 'before-footer',
			'location'   => 'genesis_footer',
			'priority'   => 5,
			'menu_order' => 30,
		],
		[
			'id'         => 'footer',
			'location'   => 'genesis_footer',
			'menu_order' => 35,
		],
		[
			'id'         => 'footer-credits',
			'location'   => 'genesis_footer',
			'priority'   => 12,
			'menu_order' => 40,
			'default'    => '<!-- wp:group {"backgroundColor":"white","className":"has-xs-padding-bottom has-xs-padding-top has-lg-content-width","contentWidth":"lg","verticalSpacingTop":"xs","verticalSpacingBottom":"xs"} -->
			<div class="wp-block-group has-white-background-color has-background has-xs-padding-bottom has-xs-padding-top has-lg-content-width"><div class="wp-block-group__inner-container"><!-- wp:paragraph {"align":"center","fontSize":"sm"} -->
			<p class="has-text-align-center has-sm-font-size">Copyright [footer_copyright] · [footer_home_link] · All Rights Reserved · Powered by <a rel="noreferrer noopener" target="_blank" href="https://bizbudding.com/mai-theme/">Mai Theme</a></p>
			<!-- /wp:paragraph --></div></div>
			<!-- /wp:group -->',
		],
	],

	/*
	|--------------------------------------------------------------------------
	| Widget Areas
	|--------------------------------------------------------------------------
	|
	| The available widget areas, including their output location.
	|
	*/

	'widget-areas' => [
		'add'    => [
			[
				'id'          => 'sidebar',
				'name'        => __( 'Sidebar', 'mai-engine' ),
				'description' => __( 'The Sidebar widget area.', 'mai-engine' ),
				'location'    => '',
			],
		],
		'remove' => [],
	],

	/*
	|--------------------------------------------------------------------------
	| Settings defaults.
	|--------------------------------------------------------------------------
	|
	| Default values for Customizer settings.
	|
	*/

	'settings' => [
		'site-layout'      => [
			'site'    => 'standard-content',
			'archive' => 'wide-content',
			'single'  => '',
			'search'  => '',
			'author'  => '',
			'date'    => '',
			'404'     => '',
		],
		'single-content'   => [
			'enable'                       => [ 'page', 'post' ],
			'show'                         => 'mai_get_single_show_defaults',
			'image_orientation'            => 'landscape',
			'image_size'                   => 'landscape-md',
			'header_meta'                  => 'mai_get_header_meta_default',
			'footer_meta'                  => 'mai_get_footer_meta_default',
			'page-header-image'            => '',
			'page-header-featured'         => false,
			'page-header-background-color' => '',
			'page-header-overlay-opacity'  => '',
			'page-header-text-color'       => '',
		],
		'content-archives' => [
			'enable'                       => [ 'post' ],
			'show'                         => [
				'image',
				'genesis_entry_header',
				'title',
				'header_meta',
				'genesis_before_entry_content',
				'excerpt',
				'genesis_entry_content',
				'more_link',
				'genesis_after_entry_content',
				'genesis_entry_footer',
			],
			'title_size'                   => 'lg',
			'image_orientation'            => 'landscape',
			'image_size'                   => 'landscape-md',
			'image_position'               => 'full',
			'image_width'                  => 'third',
			'header_meta'                  => 'mai_get_header_meta_default',
			'content_limit'                => 0,
			'more_link_text'               => '',
			'footer_meta'                  => 'mai_get_footer_meta_default',
			'align_text'                   => 'start',
			'align_text_vertical'          => '',
			'image_stack_heading'          => '',
			'image_stack'                  => true,
			'boxed_heading'                => '',
			'boxed'                        => true,
			'border_radius'                => '',
			'columns'                      => '3',
			'columns_responsive'           => '',
			'columns_md'                   => '1',
			'columns_sm'                   => '1',
			'columns_xs'                   => '1',
			'align_columns'                => 'left',
			'align_columns_vertical'       => '',
			'column_gap'                   => 'xl',
			'row_gap'                      => 'xl',
			'posts_per_page'               => '',
			'page-header-image'            => '',
			'page-header-background-color' => '',
			'page-header-overlay-opacity'  => '',
			'page-header-text-color'       => '',
		],
		'page-header'      => [
			'archive'                 => [],
			'single'                  => [],
			'background-color'        => 'alt',
			'image'                   => '',
			'overlay-opacity'         => 0.5,
			'text-color'              => 'dark',
			'spacing'                 => [
				'top'    => '10vw',
				'bottom' => '10vw',
			],
			'text-align'              => 'center',
			'divider'                 => '',
			'divider-height'          => 'md',
			'divider-color'           => 'white',
			'divider-flip-horizontal' => false,
			'divider-flip-vertical'   => false,
			'divider-overlay-opacity' => 0.5,
			'divider-text-align'      => '',
		],
		'performance'      => [
			'genesis-style-trump'        => true,
			'remove-menu-item-classes'   => true,
			'remove-template-classes'    => true,
			'disable-emojis'             => true,
			'remove-recent-comments-css' => true,
		],
		'genesis' => [
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
		'simple-social-icons' => [
			'alignment'              => 'alignleft',
			'background_color'       => '',
			'background_color_hover' => '',
			'border_radius'          => 3,
			'border_width'           => 0,
			'icon_color'             => 'heading',
			'icon_color_hover'       => 'primary',
			'size'                   => 40,
		],
	],
];
