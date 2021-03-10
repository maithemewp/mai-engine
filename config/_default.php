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
			'background' => '#ffffff', // Body background.
			'alt'        => '#f8f9fa', // Background alt.
			'body'       => '#6c747d', // Body text color.
			'heading'    => '#343a40', // Heading text color.
			'link'       => '#007bff', // Link color.
			'primary'    => '#007bff', // Button primary background color.
			'secondary'  => '#6c747d', // Button secondary background color.
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
	| Scripts
	|--------------------------------------------------------------------------
	|
	| All of the scripts to be added or removed.
	*/

	'scripts' => [
		'global'     => [
			'async' => true,
		],
		'menus'      => [
			'async'    => true,
			'localize' => [
				'name' => 'maiMenuVars',
				'data' => [
					'ariaLabel'     => __( 'Mobile Menu', 'mai-engine' ),
					'subMenuToggle' => sprintf(
						'<span class="sub-menu-toggle-icon"></span><span class="screen-reader-text">%s</span>',
						__( 'Sub Menu', 'mai-engine' )
					),
				],
			],
		],
		'header'     => [
			'async'     => true,
			'condition' => function () {
				return mai_has_sticky_header_enabled() || mai_has_transparent_header_enabled();
			},
		],
		'customizer' => [
			'deps'     => [ 'jquery' ],
			'location' => 'customizer',
		],
		'wptrt-customize-section-button' => [
			'src'      => mai_get_url() . 'vendor/wptrt/customize-section-button/public/js/customize-controls.js',
			'location' => 'customizer',
		],
		'editor'     => [
			'deps'     => [ 'jquery', 'jquery-ui-sortable', 'wp-blocks', 'wp-dom' ],
			'location' => 'editor',
			'localize' => [
				'name' => 'maiEditorVars',
				'data' => 'mai_get_editor_localized_data',
			],
		],
		'blocks'     => [
			'src'      => mai_get_url() . 'assets/js/min/blocks.js',
			'deps'     => [ 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ],
			'location' => 'editor',
		],
	],

	/*
	|--------------------------------------------------------------------------
	| Styles.
	|--------------------------------------------------------------------------
	|
	| All of the styles to be added or removed.
	*/

	'styles' => [
		'main'                           => [
			'async' => true,
		],
		'header'                         => [
			'async'     => true,
			'condition' => function () {
				return ! mai_is_element_hidden( 'transparent_header' );
			},
		],
		'blocks'                         => [
			'async' => true,
		],
		'utilities'                      => [
			'async' => true,
		],
		'theme'                          => [
			'async'     => mai_get_option( 'genesis-style-trump', true ),
			'src'       => 'default' !== mai_get_active_theme() ? mai_get_url() . 'assets/css/themes/' . mai_get_active_theme() . '.min.css' : '',
			'deps'      => [
				'mai-engine-desktop',
			],
			'condition' => function() {
				return 'default' !== mai_get_active_theme();
			},
		],
		'footer'                         => [
			'in_footer' => true,
		],
		'admin'                          => [
			'location' => 'admin',
		],
		'kirki'                          => [
			'location' => 'customizer',
		],
		'wptrt-customize-section-button' => [
			'src'      => mai_get_url() . 'vendor/wptrt/customize-section-button/public/css/customize-controls.css',
			'location' => 'customizer',
		],
		'advanced-custom-fields'         => [
			'location' => 'editor',
		],
		'atomic-blocks'                  => [
			'async'     => true,
			'condition' => function () {
				return function_exists( 'atomic_blocks_main_plugin_file' );
			},
		],
		'facetwp'                => [
			'async'     => true,
			'condition' => function () {
				return class_exists( 'FacetWP' );
			},
		],
		'genesis-enews-extended' => [
			'async'     => true,
			'location'  => [ 'public', 'editor' ],
			'condition' => function () {
				return class_exists( 'BJGK_Genesis_ENews_Extended' );
			},
		],
		'learndash' => [
			'async'     => true,
			'condition' => function () {
				return class_exists( 'SFWD_LMS' );
			},
		],
		'seo-slider'             => [
			'async'     => true,
			'condition' => function () {
				return defined( 'SEO_SLIDER_VERSION' );
			},
		],
		'simple-social-icons'    => [
			'async'     => true,
			'condition' => function () {
				return class_exists( 'Simple_Social_Icons_Widget' );
			},
		],
		'woocommerce-global'     => [
			'async'     => true,
			'condition' => function () {
				return class_exists( 'WooCommerce' );
			},
		],
		'woocommerce-products'   => [
			'async'     => true,
			'condition' => function () {
				return class_exists( 'WooCommerce' ) && ( is_shop() || is_product_taxonomy() || is_product() || is_cart() );
			},
		],
		'woocommerce-cart'       => [
			'async'     => true,
			'condition' => function () {
				return class_exists( 'WooCommerce' ) && ( is_cart() || is_checkout() );
			},
		],
		'woocommerce-account'    => [
			'async'     => true,
			'condition' => function () {
				return class_exists( 'WooCommerce' ) && is_account_page();
			},
		],
		'wp-block-library-theme' => [
			'handle'   => 'wp-block-library-theme',
			'src'      => '',
			'location' => 'editor',
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

			// Engine.
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
		'before-header' => [
			'hook'       => 'genesis_before_header',
			'menu_order' => 5,
			'before'     => '<div class="before-header template-part">',
			'after'      => '</div>',
		],
		'header-left' => [
			'hook'       => 'mai_header_left',
			'menu_order' => 10,
		],
		'header-right' => [
			'hook'       => 'mai_header_right',
			'menu_order' => 15,
		],
		'mobile-menu' => [
			'hook'       => 'mai_after_header_wrap',
			'before'     => '<div class="mobile-menu template-part"><div class="wrap">',
			'after'      => '</div></div>',
			'menu_order' => 20,
		],
		'after-header' => [
			'hook'       => 'genesis_after_header',
			'priority'   => 12,
			'menu_order' => 25,
			'before'     => '<div class="after-header template-part">',
			'after'      => '</div>',
		],
		'after-entry' => [
			'menu_order' => 30,
		],
		'before-footer' => [
			'hook'       => 'genesis_footer',
			'priority'   => 5,
			'menu_order' => 35,
		],
		'footer' => [
			'hook'       => 'genesis_footer',
			'menu_order' => 40,
		],
		'footer-credits' => [
			'hook'       => 'genesis_footer',
			'priority'   => 12,
			'menu_order' => 45,
			'default'    => '<!-- wp:group {"align":"full","verticalSpacingTop":"xs","verticalSpacingBottom":"xs"} -->
				<div class="wp-block-group alignfull"><div class="wp-block-group__inner-container"><!-- wp:paragraph {"align":"center","fontSize":"sm"} -->
				<p class="has-text-align-center has-sm-font-size">Copyright [footer_copyright] · [footer_home_link] · All Rights Reserved · Powered by <a rel="noreferrer noopener" target="_blank" href="https://bizbudding.com/mai-theme/">Mai Theme</a></p>
				<!-- /wp:paragraph --></div></div>
				<!-- /wp:group -->',
		],
		'404-page' => [
			'menu_order' => 60,
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
			'sidebar' => [
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
		'logo'                => [
			'show-tagline' => true,
			'width'        => [
				'desktop' => '180px',
				'mobile'  => '120px',
			],
			'spacing'      => [
				'desktop' => '36px',
				'mobile'  => '16px',
			],
		],
		'site-header-mobile'   => [
			'title_area',
			'menu_toggle',
		],
		'site-header-mobile-content' => '',
		'site-layouts'         => [
			'default' => [
				'site'    => 'standard-content',
				'archive' => '',
				'single'  => '',
			],
			'archive' => [
				'post'     => '',
				'category' => '',
				'post_tag' => '',
				'search'   => '',
				'author'   => '',
				'date'     => '',
			],
			'single'  => [
				'page'     => '',
				'post'     => '',
				'404-page' => '',
			],
		],
		'single-content'      => [
			'enable' => [ 'page', 'post' ],
			'page'   => [
				'show'                         => [
					'genesis_entry_header',
					'title',
					'image',
					'genesis_before_entry_content',
					'excerpt',
					'content',
					'genesis_entry_content',
					'genesis_after_entry_content',
					'genesis_entry_footer',
				],
				'image_orientation'            => 'landscape',
				'image_size'                   => 'landscape-md',
				'header_meta'                  => '',
				'footer_meta'                  => '',
				'custom_content'               => '',
				'page-header-image'            => '',
				'page-header-featured'         => false,
				'page-header-background-color' => '',
				'page-header-overlay-opacity'  => '',
				'page-header-text-color'       => '',
			],
			'post'   => [
				'show'                         => [
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
					'author_box',
					'adjacent_entry_nav',
				],
				'image_orientation'            => 'landscape',
				'image_size'                   => 'landscape-md',
				'header_meta'                  => 'mai_get_header_meta_default',
				'footer_meta'                  => 'mai_get_footer_meta_default',
				'custom_content'               => '',
				'page-header-image'            => '',
				'page-header-featured'         => false,
				'page-header-background-color' => '',
				'page-header-overlay-opacity'  => '',
				'page-header-text-color'       => '',
			],
		],
		'content-archives'    => [
			'enable'         => [ 'post' ],
			'more_link_text' => __( 'Read more', 'mai-engine' ),
			'post'           => [
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
				'image_alternate'              => false,
				'image_width'                  => 'third',
				'header_meta'                  => 'mai_get_header_meta_default',
				'content_limit'                => 0,
				'custom_content'               => '',
				'more_link_text'               => '',
				'footer_meta'                  => 'mai_get_footer_meta_default',
				'align_text'                   => 'start',
				'align_text_vertical'          => '',
				'image_stack'                  => true,
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
				'posts_per_page'               => '24',
				'posts_nav'                    => 'numeric',
				'page-header-image'            => '',
				'page-header-background-color' => '',
				'page-header-overlay-opacity'  => '',
				'page-header-text-color'       => '',
			],
		],
		'page-header'         => [
			'archive'                 => [],
			'single'                  => [],
			'background-color'        => 'alt',
			'image'                   => '',
			'overlay-opacity'         => 0.5,
			'text-color'              => 'heading',
			'spacing'                 => [
				'top'    => '10vw',
				'bottom' => '10vw',
			],
			'content-width'           => 'md',
			'content-align'           => 'center',
			'text-align'              => 'center',
			'divider'                 => '',
			'divider-height'          => 'md',
			'divider-color'           => 'white',
			'divider-flip-horizontal' => false,
			'divider-flip-vertical'   => false,
			'divider-overlay-opacity' => 0.5,
			'divider-text-align'      => '',
		],
		'header-left-menu-alignment'  => 'flex-start',
		'header-right-menu-alignment' => 'flex-end',
		'after-header-menu-alignment' => 'flex-start',
		'performance'         => [
			'genesis-style-trump'        => true,
			'remove-menu-item-classes'   => true,
			'remove-template-classes'    => true,
			'disable-emojis'             => true,
			'remove-recent-comments-css' => true,
		],
		'genesis'             => [
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
