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
					'.nav-after-header',
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
				'after-header' => __( 'After Header Menu', 'mai-engine' ),
				'footer'       => __( 'Footer Menu', 'mai-engine' ),
			],
			'genesis-structural-wraps' => [
				'header',
				'menu-after-header',
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
				'location'    => 'genesis_header',
				'priority'    => 2,
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
	| ACF & Kirki settings.
	|--------------------------------------------------------------------------
	|
	| Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam scelerisque
	| venenatis augue eget lacinia. Suspendisse eros dui, fringilla si amet
	| ante et, fringilla tristique just. In interdum vitae metus ut fiat.
	|
	*/
	'grid-base-settings' => [
		/*********
		 * Display
		 */
		'display_tab'            => [
			'label'   => esc_html__( 'Display', 'mai-engine' ),
			'block'   => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
			'type'    => 'tab',
			'key'     => 'field_5bd51cac98282',
			'default' => '',
		],
		'show'                   => [
			'label'    => esc_html__( 'Show', 'mai-engine' ),
			'block'    => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
			'sanitize' => 'esc_html',
			'type'     => 'checkbox',
			'key'      => 'field_5e441d93d6236',
			'default'  => [ 'image', 'title' ],
			'atts'     => [
				'wrapper' => [
					'width' => '',
					'class' => 'mai-sortable',
					'id'    => '',
				],
			],
		],
		'image_orientation'      => [
			'label'      => esc_html__( 'Image Orientation', 'mai-engine' ),
			'block'      => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
			'sanitize'   => 'esc_html',
			'type'       => 'select',
			'key'        => 'field_5e4d4efe99279',
			'default'    => 'landscape',
			'choices'    => [
				'landscape' => esc_html__( 'Landscape', 'mai-engine' ),
				'portrait'  => esc_html__( 'Portrait', 'mai-engine' ),
				'square'    => esc_html__( 'Square', 'mai-engine' ),
				'custom'    => esc_html__( 'Custom', 'mai-engine' ),
			],
			'conditions' => [
				[
					'setting'  => 'show',
					'operator' => '==',
					'value'    => 'image',
				],
			],
		],
		'image_size'             => [
			'label'      => esc_html__( 'Image Size', 'mai-engine' ),
			'block'      => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
			'sanitize'   => 'esc_html',
			'type'       => 'select',
			'key'        => 'field_5bd50e580d1e9',
			'default'    => 'landscape-md',
			'conditions' => [
				[
					'setting'  => 'show',
					'operator' => '==',
					'value'    => 'image',
				],
				[
					'setting'  => 'image_orientation',
					'operator' => '==',
					'value'    => 'custom',
				],
			],
		],
		'image_position'         => [
			'label'      => esc_html__( 'Image Position', 'mai-engine' ),
			'block'      => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
			'sanitize'   => 'esc_html',
			'type'       => 'select',
			'key'        => 'field_5e2f3adf82130',
			'default'    => 'full',
			'choices'    => [
				'full'       => esc_html__( 'Full', 'mai-engine' ),
				'left'       => esc_html__( 'Left', 'mai-engine' ),
				'center'     => esc_html__( 'Center', 'mai-engine' ),
				'right'      => esc_html__( 'Right', 'mai-engine' ),
				'background' => esc_html__( 'Background', 'mai-engine' ),
			],
			'conditions' => [
				[
					'setting'  => 'show',
					'operator' => '==',
					'value'    => 'image',
				],
			],
		],
		'header_meta'            => [
			'label'      => esc_html__( 'Header Meta', 'mai-engine' ),
			'block'      => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
			'sanitize'   => 'wp_kses_post',
			'type'       => 'text',
			'key'        => 'field_5e2b563a7c6cf',
			// TODO: this should be different, or empty depending on the post type?
			'default'    => '[post_date] [post_author_posts_link before="by "]',
			'conditions' => [
				[
					'setting'  => 'show',
					'operator' => '==',
					'value'    => 'header_meta',
				],
			],
		],
		'content_limit'          => [
			'label'      => esc_html__( 'Content Limit', 'mai-engine' ),
			'desc'       => esc_html__( 'Limit the number of characters shown for the content or excerpt. Use 0 for no limit.', 'mai-engine' ),
			'block'      => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
			'sanitize'   => 'absint',
			'type'       => 'text',
			'key'        => 'field_5bd51ac107244',
			'default'    => 0,
			'conditions' => [
				[
					[
						'setting'  => 'show',
						'operator' => '==',
						'value'    => 'excerpt',
					],
				],
				[
					[
						'setting'  => 'show',
						'operator' => '==',
						'value'    => 'content',
					],
				],
			],
		],
		'more_link_text'         => [
			'label'      => esc_html__( 'More Link Text', 'mai-engine' ),
			'block'      => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
			'sanitize'   => 'esc_attr', // We may want to add icons/spans and HTML in here.
			'type'       => 'text',
			'key'        => 'field_5c85465018395',
			'default'    => '',
			'conditions' => [
				[
					'setting'  => 'show',
					'operator' => '==',
					'value'    => 'more_link',
				],
			],
			// TODO: This text should be filtered, same as the template that outputs it.
			'atts'       => [
				'placeholder' => esc_html__( 'Read More', 'mai-engine' ),
			],
		],
		'footer_meta'            => [
			'label'      => esc_html__( 'Footer Meta', 'mai-engine' ),
			'block'      => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
			'sanitize'   => 'wp_kses_post',
			'type'       => 'text',
			'key'        => 'field_5e2b567e7c6d0',
			// TODO: this should be different, or empty depending on the post type?
			'default'    => '[post_categories]',
			'conditions' => [
				[
					'setting'  => 'show',
					'operator' => '==',
					'value'    => 'footer_meta',
				],
			],
		],
		'boxed'                  => [
			'label'    => esc_html__( 'Boxed', 'mai-engine' ),
			'block'    => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
			'sanitize' => 'esc_html',
			'type'     => 'true_false',
			'key'      => 'field_5e2a08a182c2c',
			'default'  => true, // ACF has 1.
			'atts'     => [
				'message' => __( 'Display boxed', 'mai-engine' ),
			],
		],
		'align_text'             => [
			'label'    => esc_html__( 'Align Text', 'mai-engine' ),
			'block'    => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
			'sanitize' => 'esc_html',
			'type'     => 'button_group',
			'key'      => 'field_5c853f84eacd6',
			'default'  => '',
			'choices'  => [
				''       => esc_html__( 'Clear', 'mai-engine' ),
				'start'  => esc_html__( 'Start', 'mai-engine' ),
				'center' => esc_html__( 'Center', 'mai-engine' ),
				'end'    => esc_html__( 'End', 'mai-engine' ),
			],
		],
		'align_text_vertical'    => [
			'label'      => esc_html__( 'Align Text (vertical)', 'mai-engine' ),
			'block'      => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
			'sanitize'   => 'esc_html',
			'type'       => 'button_group',
			'key'        => 'field_5e2f519edc912',
			'default'    => '',
			'choices'    => [
				''       => esc_html__( 'Clear', 'mai-engine' ),
				'top'    => esc_html__( 'Top', 'mai-engine' ),
				'middle' => esc_html__( 'Middle', 'mai-engine' ),
				'bottom' => esc_html__( 'Bottom', 'mai-engine' ),
			],
			'conditions' => [
				[
					[
						'setting'  => 'image_position',
						'operator' => '==',
						'value'    => 'left',
					],
				],
				[
					[
						'setting'  => 'image_position',
						'operator' => '==',
						'value'    => 'background',
					],
				],
			],
		],
		/********
		 * Layout
		 */
		'layout_tab'             => [
			'label'   => esc_html__( 'Layout', 'mai-engine' ),
			'block'   => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
			'type'    => 'tab',
			'key'     => 'field_5c8549172e6c7',
			'default' => '',
		],
		'columns'                => [
			'label'    => esc_html__( 'Columns (desktop)', 'mai-engine' ),
			'block'    => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
			'sanitize' => 'absint',
			'type'     => 'button_group',
			'key'      => 'field_5c854069d358c',
			'default'  => 3,
		],
		'columns_responsive'     => [
			'label'    => '',
			'block'    => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
			'sanitize' => 'esc_html',
			'type'     => 'true_false',
			'key'      => 'field_5e334124b905d',
			'default'  => '',
			'atts'     => [
				'message' => esc_html__( 'Custom responsive columns', 'mai-engine' ),
			],
		],
		'columns_md'             => [
			'label'      => esc_html__( 'Columns (lg tablets)', 'mai-engine' ),
			'block'      => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
			'sanitize'   => 'absint',
			'type'       => 'button_group',
			'key'        => 'field_5e3305dff9d8b',
			'default'    => 1,
			'conditions' => [
				[
					'setting'  => 'columns_responsive',
					'operator' => '==',
					'value'    => 1,
				],
			],
		],
		'columns_sm'             => [
			'label'      => esc_html__( 'Columns (sm tablets)', 'mai-engine' ),
			'block'      => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
			'sanitize'   => 'absint',
			'type'       => 'button_group',
			'key'        => 'field_5e3305f1f9d8c',
			'default'    => 1,
			'conditions' => [
				[
					'setting'  => 'columns_responsive',
					'operator' => '==',
					'value'    => 1,
				],
			],
		],
		'columns_xs'             => [
			'label'      => esc_html__( 'Columns (mobile)', 'mai-engine' ),
			'block'      => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
			'sanitize'   => 'absint',
			'type'       => 'button_group',
			'key'        => 'field_5e332a5f7fe08',
			'default'    => 1,
			'conditions' => [
				[
					'setting'  => 'columns_responsive',
					'operator' => '==',
					'value'    => 1,
				],
			],
		],
		'align_columns'          => [
			'label'      => esc_html__( 'Align Columns', 'mai-engine' ),
			'block'      => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
			'sanitize'   => 'esc_html',
			'type'       => 'button_group',
			'key'        => 'field_5c853e6672972',
			'default'    => '',
			'choices'    => [
				''       => esc_html__( 'Clear', 'mai-engine' ),
				'left'   => esc_html__( 'Left', 'mai-engine' ),
				'center' => esc_html__( 'Center', 'mai-engine' ),
				'right'  => esc_html__( 'Right', 'mai-engine' ),
			],
			'conditions' => [
				[
					'setting'  => 'columns',
					'operator' => '!=',
					'value'    => 1,
				],
			],
		],
		'align_columns_vertical' => [
			'label'      => esc_html__( 'Align Columns (vertical)', 'mai-engine' ),
			'block'      => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
			'sanitize'   => 'esc_html',
			'type'       => 'button_group',
			'key'        => 'field_5e31d5f0e2867',
			'default'    => '',
			'choices'    => [
				''       => esc_html__( 'Clear', 'mai-engine' ),
				'top'    => esc_html__( 'Top', 'mai-engine' ),
				'middle' => esc_html__( 'Middle', 'mai-engine' ),
				'bottom' => esc_html__( 'Bottom', 'mai-engine' ),
			],
			'conditions' => [
				[
					'setting'  => 'columns',
					'operator' => '!=',
					'value'    => 1,
				],
			],
		],
		'column_gap'             => [
			'label'    => esc_html__( 'Column Gap', 'mai-engine' ),
			'block'    => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
			'sanitize' => 'esc_html',
			'type'     => 'text',
			'key'      => 'field_5c8542d6a67c5',
			'default'  =>'24px',
		],
		'row_gap'                => [
			'label'    => esc_html__( 'Row Gap', 'mai-engine' ),
			'block'    => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
			'sanitize' => 'esc_html',
			'type'     => 'text',
			'key'      => 'field_5e29f1785bcb6',
			'default'  =>'24px',
		],
		/***********
		 * Entries *
		 */
		'entries_tab'            => [
			'label'   => esc_html__( 'Entries', 'mai-engine' ),
			'block'   => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
			'type'    => 'tab',
			'key'     => 'field_5df13446c49cf',
			'default' => '',
		],
	],

	'grid-post-settings' => [
		'post_type'              => [
			'label'    => esc_html__( 'Post Type', 'mai-engine' ),
			'block'    => [ 'mai_post_grid' ],
			'sanitize' => 'esc_html',
			'type'     => 'select',
			'key'      => 'field_5df1053632ca2',
			'default'  => [ 'post' ],
			'atts'     => [
				'multiple' => 1,
				'ui'       => 1,
				'ajax'     => 0,
			],
		],
		'number'               => [
			'label'      => esc_html__( 'Number of Entries', 'mai-engine' ),
			'desc'       => esc_html__( 'Use 0 to show all.', 'mai-engine' ),
			'block'      => [ 'mai_post_grid' ],
			'sanitize'   => 'absint',
			'type'       => 'number',
			'key'        => 'field_5df1053632ca8',
			'default'    => 12,
			'conditions' => [
				[
					'setting'  => 'post_type',
					'operator' => '!=empty',
				],
				[
					'setting'  => 'query_by',
					'operator' => '!=',
					'value'    => 'title',
				],
			],
			'atts'        => [
				'placeholder' => 12,
				'min'         => 0,
			],
		],
		'query_by'               => [
			'label'      => esc_html__( 'Get Entries By', 'mai-engine' ),
			'block'      => [ 'mai_post_grid' ],
			'sanitize'   => 'esc_html',
			'type'       => 'select',
			'key'        => 'field_5df1053632cad',
			'default'    => 'date',
			'choices'    => [
				'date'     => esc_html__( 'Date', 'mai-engine' ),
				'title'    => esc_html__( 'Title', 'mai-engine' ),
				'tax_meta' => esc_html__( 'Taxonomy/Meta', 'mai-engine' ),
				'parent'   => esc_html__( 'Parent', 'mai-engine' ),
			],
			'conditions' => [
				[
					'setting'  => 'post_type',
					'operator' => '!=empty',
				],
			],
		],
		'post__in'               => [
			'label'      => esc_html__( 'Entries', 'mai-engine' ),
			'desc'       => esc_html__( 'Show specific entries. Choose all that apply. If empty, Grid will get entries by date.', 'mai-engine' ),
			'block'      => [ 'mai_post_grid' ],
			'sanitize'   => 'absint',
			'type'       => 'post_object',
			'key'        => 'field_5df1053632cbc',
			'default'    => '', // Can't be empty array.
			'conditions' => [
				[
					'setting'  => 'post_type',
					'operator' => '!=empty',
				],
				[
					'setting'  => 'query_by',
					'operator' => '==',
					'value'    => 'title',
				],
			],
			'atts'        => [
				'multiple'      => 1,
				'return_format' => 'id',
				'ui'            => 1,
			],
		],
		'taxonomies'             => [
			'label'      => esc_html__( 'Taxonomies', 'mai-engine' ),
			'block'      => [ 'mai_post_grid' ],
			'type'       => 'repeater',
			'key'        => 'field_5df1397316270',
			'default'    => '',
			'conditions' => [
				[
					'setting'  => 'post_type',
					'operator' => '!=empty',
				],
				[
					'setting'  => 'query_by',
					'operator' => '==',
					'value'    => 'tax_meta',
				],
			],
			'atts'        => [
				'collapsed'    => 'field_5df1398916271',
				'layout'       => 'block',
				'button_label' => esc_html__( 'Add Condition', 'mai-engine' ),
				'sub_fields'   => [
					'taxonomy' => [
						'label'    => esc_html__( 'Taxonomy', 'mai-engine' ),
						'sanitize' => 'esc_html',
						'type'     => 'select',
						'key'      => 'field_5df1398916271',
						'default'  => '',
						'atts'      => [
							'ui'   => 1,
							'ajax' => 1,

						],
					],
					'terms'    => [
						'label'    => esc_html__( 'Terms', 'mai-engine' ),
						'sanitize' => 'absint',
						'type'     => 'taxonomy',
						'key'      => 'field_5df139a216272',
						'default'  => [],
						'atts'      => [
							'field_type' => 'multi_select',
							'taxonomy'   => 'category',
							'add_term'   => 0,
							'save_terms' => 0,
							'load_terms' => 0,
							'multiple'   => 0,
							'conditions' => [
								[
									'setting'  => 'taxonomy',
									'operator' => '!=empty',
								],
							],
						],
					],
					'operator' => [
						'key'        => 'field_5df18f2305c2c',
						'label'      => esc_html__( 'Operator', 'mai-engine' ),
						'sanitize'   => 'esc_html',
						'type'       => 'select',
						'default'    => 'IN',
						'choices'    => [
							'IN'     => esc_html__( 'In', 'mai-engine' ),
							'NOT IN' => esc_html__( 'Not In', 'mai-engine' ),
						],
						'conditions' => [
							[
								'setting'  => 'taxonomy',
								'operator' => '!=empty',
							],
						],
					],
				],
			],
		],
		'taxonomies_relation'    => [
			'label'      => esc_html__( 'Taxonomies Relation', 'mai-engine' ),
			'block'      => [ 'mai_post_grid' ],
			'sanitize'   => 'esc_html',
			'type'       => 'select',
			'key'        => 'field_5df139281626f',
			'default'    => 'AND',
			'choices'    => [
				'AND' => esc_html__( 'And', 'mai-engine' ),
				'OR'  => esc_html__( 'Or', 'mai-engine' ),
			],
			'conditions' => [
				[
					'setting'  => 'post_type',
					'operator' => '!=empty',
				],
				[
					'setting'  => 'query_by',
					'operator' => '==',
					'value'    => 'tax_meta',
				],
				[
					'setting'  => 'taxonomies',
					'operator' => '>',
					'value'    => '1', // More than 1 row.
				],
			],
		],
		'meta_keys'              => [
			'label'      => esc_html__( 'Meta Keys', 'mai-engine' ),
			'block'      => [ 'mai_post_grid' ],
			'type'       => 'repeater',
			'key'        => 'field_5df2053632dg5',
			'default'    => '',
			'conditions' => [
				[
					'setting'  => 'post_type',
					'operator' => '!=empty',
				],
				[
					'setting'  => 'query_by',
					'operator' => '==',
					'value'    => 'tax_meta',
				],
			],
			'atts'        => [
				'collapsed'    => 'field_5df3398916382',
				'layout'       => 'block',
				'button_label' => esc_html__( 'Add Condition', 'mai-engine' ),
				'sub_fields'   => [
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_key'     => [
						'label'    => esc_html__( 'Meta Key', 'mai-engine' ),
						'sanitize' => 'esc_html',
						'type'     => 'text',
						'key'      => 'field_5df3398916382',
						'default'  => '',
					],
					'meta_compare' => [
						'label'      => esc_html__( 'Compare', 'mai-engine' ),
						'sanitize'   => 'esc_html',
						'type'       => 'select',
						'key'        => 'field_5df29f2315d3d',
						'default'    => '',
						'choices'    => [
							'='          => __( 'Is equal to', 'mai-engine' ),
							'!='         => __( 'Is not equal to', 'mai-engine' ),
							'>'          => __( 'Is greater than', 'mai-engine' ),
							'>='         => __( 'Is great than or equal to', 'mai-engine' ),
							'<'          => __( 'Is less than', 'mai-engine' ),
							'<='         => __( 'Is less than or equal to', 'mai-engine' ),
							'EXISTS'     => __( 'Exists', 'mai-engine' ),
							'NOT EXISTS' => __( 'Does not exist', 'mai-engine' ),
						],
						'conditions' => [
							[
								'setting'  => 'meta_key',
								'operator' => '!=empty',
							],
						],
					],
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					'meta_value'   => [
						'label'      => esc_html__( 'Meta Value', 'mai-engine' ),
						'sanitize'   => 'esc_html',
						'type'       => 'text',
						'key'        => 'field_5df239a217383',
						'default'    => '',
						'conditions' => [
							[
								'setting'  => 'meta_key',
								'operator' => '!=empty',
							],
							[
								'setting'  => 'meta_compare',
								'operator' => '!=',
								'value'    => 'EXISTS',
							],
						],
					],
				],
			],
		],
		'meta_keys_relation'     => [
			'label'      => esc_html__( 'Meta Keys Relation', 'mai-engine' ),
			'block'      => [ 'mai_post_grid' ],
			'sanitize'   => 'esc_html',
			'type'       => 'select',
			'key'        => 'field_5df239282737g',
			'default'    => 'AND',
			'choices'    => [
				'AND' => esc_html__( 'And', 'mai-engine' ),
				'OR'  => esc_html__( 'Or', 'mai-engine' ),
			],
			'conditions' => [
				[
					'setting'  => 'post_type',
					'operator' => '!=empty',
				],
				[
					'setting'  => 'query_by',
					'operator' => '==',
					'value'    => 'tax_meta',
				],
				[
					'setting'  => 'meta_keys',
					'operator' => '>',
					'value'    => '1', // More than 1 row.
				],
			],
		],
		'post_parent__in'        => [
			'label'      => esc_html__( 'Parent', 'mai-engine' ),
			'block'      => [ 'mai_post_grid' ],
			'sanitize'   => 'absint',
			'type'       => 'post_object',
			'key'        => 'field_5df1053632ce4',
			'default'    => '',
			'conditions' => [
				[
					'setting'  => 'post_type',
					'operator' => '!=empty',
				],
				[
					'setting'  => 'query_by',
					'operator' => '==',
					'value'    => 'parent',
				],
			],
			'atts'        => [
				'multiple' => 1,
				'ui'       => 1,
				'ajax'     => 1,
			],
		],
		'offset'                 => [
			'label'      => esc_html__( 'Offset', 'mai-engine' ),
			'desc'       => esc_html__( 'Skip this number of entries.', 'mai-engine' ),
			'block'      => [ 'mai_post_grid' ],
			'sanitize'   => 'absint',
			'type'       => 'number',
			'key'        => 'field_5df1bf01ea1de',
			'default'    => 0,
			'conditions' => [
				[
					'setting'  => 'post_type',
					'operator' => '!=empty',
				],
				[
					'setting'  => 'query_by',
					'operator' => '!=',
					'value'    => 'title',
				],
			],
			'atts'        => [
				'placeholder' => 0,
				'min'         => 0,
			],
		],
		'orderby'                => [
			'label'      => esc_html__( 'Order By', 'mai-engine' ),
			'block'      => [ 'mai_post_grid' ],
			'sanitize'   => 'esc_html',
			'type'       => 'select',
			'key'        => 'field_5df1053632cec',
			'default'    => 'date',
			'choices'    => [
				'title'          => esc_html__( 'Title', 'mai-engine' ),
				'name'           => esc_html__( 'Slug', 'mai-engine' ),
				'date'           => esc_html__( 'Date', 'mai-engine' ),
				'modified'       => esc_html__( 'Modified', 'mai-engine' ),
				'rand'           => esc_html__( 'Random', 'mai-engine' ),
				'comment_count'  => esc_html__( 'Comment Count', 'mai-engine' ),
				'menu_order'     => esc_html__( 'Menu Order', 'mai-engine' ),
				'post__in'       => esc_html__( 'Entries Order', 'mai-engine' ),
				'meta_value_num' => esc_html__( 'Meta Value Number', 'mai-engine' ),
			],
			'conditions' => [
				[
					'setting'  => 'post_type',
					'operator' => '!=empty',
				],
			],
			'atts'        => [
				'ui'   => 1,
				'ajax' => 1,
			],
		],
		'orderby_meta_key'       => [
			'label'      => esc_html__( 'Meta key', 'mai-engine' ),
			'block'      => [ 'mai_post_grid' ],
			'sanitize'   => 'esc_html',
			'type'       => 'text',
			'key'        => 'field_5df1053632cf4',
			'default'    => '',
			'conditions' => [
				[
					'setting'  => 'post_type',
					'operator' => '!=empty',
				],
				[
					'setting'  => 'orderby',
					'operator' => '==',
					'value'    => 'meta_value_num',
				],
			],
		],
		'order'                  => [
			'label'      => esc_html__( 'Order', 'mai-engine' ),
			'block'      => [ 'mai_post_grid' ],
			'sanitize'   => 'esc_html',
			'type'       => 'select',
			'key'        => 'field_5df1053632cfb',
			'default'    => '',
			'conditions' => [
				[
					'setting'  => 'post_type',
					'operator' => '!=empty',
				],
			],
		],
		'post__not_in'           => [
			'label'      => esc_html__( 'Exclude Entries', 'mai-engine' ),
			'desc'       => esc_html__( 'Hide specific entries. Choose all that apply.', 'mai-engine' ),
			'block'      => [ 'mai_post_grid' ],
			'sanitize'   => 'absint',
			'type'       => 'post_object',
			'key'        => 'field_5e349237e1c01',
			'default'    => '',
			'conditions' => [
				[
					'setting'  => 'post_type',
					'operator' => '!=empty',
				],
				[
					'setting'  => 'query_by',
					'operator' => '!=',
					'value'    => 'title',
				],
			],
			'atts'        => [
				'multiple'      => 1,
				'return_format' => 'id',
				'ui'            => 1,
			],
		],
		// TODO: These shoud be separate fields. We can then have desc text and easier to check when building query.
		'exclude'                => [
			'label'      => esc_html__( 'Exclude', 'mai-engine' ),
			'block'      => [ 'mai_post_grid' ],
			'sanitize'   => 'esc_html',
			'type'       => 'checkbox',
			'key'        => 'field_5df1053632d03',
			'default'    => '',
			'choices'    => [
				'exclude_current'   => esc_html__( 'Exclude current', 'mai-engine' ),
				'exclude_displayed' => esc_html__( 'Exclude displayed', 'mai-engine' ),
			],
			'conditions' => [
				[
					'setting'  => 'post_type',
					'operator' => '!=empty',
				],
			],
		],
	],
	'grid-term-settings' => [
		'taxonomy'            => [
			'label'    => esc_html__( 'Taxonomy', 'mai-engine' ),
			'block'    => [ 'mai_term_grid' ],
			'sanitize' => 'esc_html',
			'type'     => 'select',
			'key'      => 'field_5df2063632ca2',
			'default'  => [ 'post' ],
			'atts'     => [
				'multiple' => 1,
				'ui'       => 1,
				'ajax'     => 0,
			],
		],
		'number'               => [
			'label'      => esc_html__( 'Number of Entries', 'mai-engine' ),
			'desc'       => esc_html__( 'Use 0 to show all.', 'mai-engine' ),
			'block'      => [ 'mai_term_grid' ],
			'sanitize'   => 'absint',
			'type'       => 'number',
			'key'        => 'field_5df2064632ca8',
			'default'    => 12,
			'conditions' => [
				[
					'setting'  => 'taxonomy',
					'operator' => '!=empty',
				],
				[
					'setting'  => 'query_by',
					'operator' => '!=',
					'value'    => 'title',
				],
			],
			'atts'       => [
				'placeholder' => 12,
				'min'         => 0,
			],
		],
		'query_by'               => [
			'label'      => esc_html__( 'Get Entries By', 'mai-engine' ),
			'block'      => [ 'mai_term_grid' ],
			'sanitize'   => 'esc_html',
			'type'       => 'select',
			'key'        => 'field_5df1054642cad',
			'default'    => 'date',
			'choices'    => [
				'date'     => esc_html__( 'Date', 'mai-engine' ),
				'title'    => esc_html__( 'Title', 'mai-engine' ),
				// 'tax_meta' => esc_html__( 'Taxonomy/Meta', 'mai-engine' ),
				'parent'   => esc_html__( 'Parent', 'mai-engine' ),
			],
			'conditions' => [
				[
					'setting'  => 'post_type',
					'operator' => '!=empty',
				],
			],
		],
		'exclude'                => [
			'label'      => esc_html__( 'Exclude', 'mai-engine' ),
			'group'      => [ 'mai_term_grid' ],
			'sanitize'   => 'esc_html',
			'type'       => 'checkbox',
			'key'        => 'field_5df2164632d03',
			'default'    => '',
			'choices'    => [
				'exclude_current'   => esc_html__( 'Exclude current', 'mai-engine' ),
				'exclude_displayed' => esc_html__( 'Exclude displayed', 'mai-engine' ),
			],
			'conditions' => [
				[
					'setting'  => 'post_type',
					'operator' => '!=empty',
				],
			],
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
