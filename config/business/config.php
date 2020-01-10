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
	],
];

