<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2021 BizBudding
 * @license   GPL-2.0-or-later
 */

return [
	'demos'         => [
		'freelance'  => 61,
		'company'    => 62,
		'consulting' => 63,
	],
	'global-styles' => [
		'colors' => [
			'background' => '#ffffff', // Body background.
			'alt'        => '#fafafa', // Background alt.
			'body'       => '#333333', // Body text color.
			'heading'    => '#111111', // Heading text color.
			'link'       => '#0066cc', // Link color.
			'primary'    => '#111111', // Button primary background color.
			'secondary'  => '#000000', // Button secondary background color.
		],
		'custom-colors' => [
			[
				'color' => '#999999', // var(--color-custom-1) for Subheading.
			],
		],
		'fonts' => [
			'body'    => 'Noto Sans:400',
			'heading' => 'Montserrat:700',
		],
	],
	'image-sizes' => [
		'add' => [
			'portrait' => '3:4',
			'square'   => '1:1',
		],
	],
	'settings' => [
		'content-archives' => [
			'post' => [
				'show' => [
					'image',
					'genesis_entry_header',
					'title',
					'header_meta',
					'genesis_before_entry_content',
					'genesis_entry_content',
					'genesis_after_entry_content',
					'genesis_entry_footer',
				],
				'header_meta' => '<em>by</em> [post_author_posts_link before=""] <em>on</em> [post_date before=""]',
				'footer_meta' => '[post_terms taxonomy="category" before="Category: "][post_terms taxonomy="post_tag" before="Tag: "]',
				'boxed'       => false,
				'columns'     => '2',
			],
			'portfolio' => [
				'show' => [
					'image',
					'genesis_entry_header',
					'title',
					'genesis_before_entry_content',
					'genesis_entry_content',
					'genesis_after_entry_content',
					'genesis_entry_footer',
					'footer_meta',
				],
				'title_size'        => 'lg',
				'image_orientation' => 'square',
				'image_position'    => 'full',
				'footer_meta'       => '[post_terms taxonomy="portfolio_type" before=""]',
				'boxed'             => false,
				'columns'           => '2',
				'column_gap'        => 'xl',
				'row_gap'           => 'xl',
			],
		],
		'single-content' => [
			'page' => [
				'show' => [
					'genesis_entry_header',
					'title',
					'genesis_before_entry_content',
					'excerpt',
					'content',
					'genesis_entry_content',
					'genesis_after_entry_content',
					'genesis_entry_footer',
				],
				'page-header-featured'        => true,
				'page-header-overlay-opacity' => '1',
			],
			'post' => [
				'show' => [
					'genesis_entry_header',
					'title',
					'header_meta',
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
				'header_meta'                 => '<em>by</em> [post_author_posts_link before=""] <em>on</em> [post_date before=""]',
				'footer_meta'                 => '[post_terms taxonomy="category" before="→ "][post_terms taxonomy="post_tag" before="→ "]',
				'page-header-featured'        => true,
				'page-header-overlay-opacity' => '1',
			],
			'portfolio' => [
				'show' => [
					'genesis_entry_header',
					'title',
					'header_meta',
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
				'image_orientation'           => 'square',
				'footer_meta'                 => '[post_terms taxonomy="portfolio_type" before="Portfolio Type: "][post_terms taxonomy="portfolio_tag" before="Portfolio Tag: "]',
				'page-header-featured'        => true,
				'page-header-overlay-opacity' =>  '1',
			],
		],
		'site-layouts' => [
			'default' => [
				'archive' => 'wide-content',
			],
			'archive' => [
				'portfolio' => 'standard-content',
			],
		],
		'page-header' => [
			'archive'          => '*',
			'single'           => '*',
			'content-width'    => '',
			'content-align'    => '',
			'text-align'       => 'start',
			'background-color' => '#000000',
			'overlay-opacity'  => '0.6',
			'text-color'       => 'light',
		],
	],
	'plugins'       => [
		[
			'name'     => 'Mai Portfolio',
			'host'     => 'github',
			'slug'     => 'maithemewp/mai-portfolio.php',
			'uri'      => 'maithemewp/mai-portfolio',
			'url'      => 'https://bizbudding.com/mai-theme/plugins/mai-portfolio/',
			'branch'   => 'master',
			'required' => false,
			'token'    => null,
			'demos'    => [ 'freelance', 'company', 'consulting' ],
		],
	],
	'custom-functions' => function() {
		add_filter( 'mai_page_header_image_size', 'mai_sleek_page_header_image_size' );
		/**
		 * Changes page header image size to something smaller since it's not full width.
		 *
		 * @return string
		 */
		function mai_sleek_page_header_image_size() {
			return 'landscape-md';
		}

		add_filter( 'genesis_attr_entries-wrap', 'mai_sleek_entries_wrap_attributes', 10, 3 );
		/**
		 * Adds class to show if a grid has no content or excerpt.
		 *
		 * @param array  $attributes The markup attributes.
		 * @param string $context    The markup content context.
		 * @param array  $args       The markup args.
		 *
		 * @return array
		 */
		function mai_sleek_entries_wrap_attributes( $attributes, $context, $args ) {
			if ( ! ( isset( $args['params']['args']['show'] ) && $args['params']['args'] ) ) {
				return $attributes;
			}
			$show = array_flip( $args['params']['args']['show'] );
			if ( isset( $show['content'] ) || isset( $show['excerpt'] ) ) {
				return $attributes;
			}
			$attributes['class'] .= ' has-no-content-excerpt';
			return $attributes;
		}

		/**
		 * Removes page header content width and align values.
		 * This removes the inlines styles from being added in `mai_add_page_header_content_type_css()`
		 *
		 * @since 2.13.0
		 *
		 * @param string $value
		 *
		 * @return string
		 */
		add_filter( 'mai_get_option_page-header-content-width', '__return_empty_string' );
		add_filter( 'mai_get_option_page-header-content-align', '__return_empty_string' );

		add_action( 'customize_register', 'mai_sleek_remove_page_header_settings', 100 );
		/**
		 * Removes page header content width and content align settings from Customizer.
		 *
		 * @since 2.13.0
		 *
		 * @param WP_Customize_Manager $customizer The customizer instance.
		 *
		 * @return void
		 */
		function mai_sleek_remove_page_header_settings( $wp_customize ) {
			$wp_customize->remove_control( 'mai-engine[page-header-content-width]' );
			$wp_customize->remove_control( 'mai-engine[page-header-content-align]' );
		}
	},
];
