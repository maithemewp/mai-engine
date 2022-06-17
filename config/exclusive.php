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
	'demos' => [
		// 'news'   => 69,
		// 'tech'   => 67,
		// 'sports' => 68,
	],
	'global-styles' => [
		'colors' => [
			'header'     => '#000000', // Site header background.
			'alt'        => '#f1f1f1', // Background alt.
			'body'       => '#000000', // Body text color.
			'heading'    => '#000000', // Heading text color.
			'link'       => '#11be9f', // Link color.
			'primary'    => '#11be9f', // Button primary background color.
			'secondary'  => '#f1f1f1', // Button secondary background color.
		],
		'fonts'  => [
			'heading' => 'Oswald:600',
			'menu'    => 'Oswald:400',
		],
	],
	'image-sizes' => [
		'add' => [
			'landscape' => '16:10',
			'square'    => '1:1',
		],
	],
	'settings' => [
		'site-header-mobile'   => [
			'menu_toggle',
			'title_area',
			'header_search',
		],
		'logo'                => [
			'show-tagline' => false,
		],
		'site-layouts'        => [
			'default' => [
				'site'   => 'content-sidebar',
				'single' => 'standard-content',
			],
		],
		'after-header-menu-alignment' => 'center',
		'mobile-menu-breakpoint'      => '9999px',
		'single-content'              => [
			'post' => [
				'show' => [
					'genesis_entry_header',
					'footer_meta',
					'title',
					'header_meta',
					'image',
					'genesis_before_entry_content',
					'excerpt',
					'content',
					'genesis_entry_content',
					'genesis_after_entry_content',
					'genesis_entry_footer',
					'after_entry',
					'author_box',
					'adjacent_entry_nav',
				],
				'footer_meta' => '[post_terms taxonomy="category" before="" sep=""]',
			],
		],
		'content-archives' => [
			'post' => [
				'show'                         => [
					'image',
					'genesis_entry_header',
					'footer_meta',
					'title',
					'header_meta',
					'genesis_before_entry_content',
					'genesis_entry_content',
					'genesis_after_entry_content',
					'genesis_entry_footer',
				],
				'footer_meta'                  => '[post_terms taxonomy="category" before="" sep=""]',
				'boxed'                        => false,
				'columns'                      => '2',
				'posts_per_page'               => '12',
			],
		],
	],
	'custom-functions' => function() {
		add_action( 'customize_register', 'mai_exclusive_remove_after_header_menu_alignment_settings', 100 );
		/**
		 * Removes after header menu alignment setting from Customizer.
		 *
		 * @since 2.15.0
		 *
		 * @param WP_Customize_Manager $customizer The customizer instance.
		 *
		 * @return void
		 */
		function mai_exclusive_remove_after_header_menu_alignment_settings( $wp_customize ) {
			$wp_customize->remove_control( 'mai-engine[after-header-menu-alignment]' );
		}

		add_filter( 'kirki_styles_array', 'mai_exclusive_remove_after_header_menu_alignment_css' );
		/**
		 * Removes after header menu alignment inline CSS.
		 *
		 * @since 2.15.0
		 *
		 * @param array $css The existing CSS.
		 *
		 * @return void
		 */
		function mai_exclusive_remove_after_header_menu_alignment_css( $css ) {
			unset( $css['global']['.nav-after-header']['--menu-justify-content'] );

			return $css;
		}

		/**
		 * Convert all more links to button-link.
		 *
		 * @since 2.17.0
		 *
		 * @return array
		 */
		add_filter( 'genesis_attr_entry-more-link', 'mai_exclusive_more_link_button', 10, 3 );
		function mai_exclusive_more_link_button( $atts, $context, $args ) {
			$args = isset( $args['params']['args'] ) ? $args['params']['args'] : [];

			// Bail if no custom args.
			if ( ! $args ) {
				return $atts;
			}

			// Remove button-secondary if it exists.
			$atts['class'] = str_replace( ' button-secondary', '', $atts['class'] );

			// Make outline button.
			$atts['class'] .= ' button-link';

			return $atts;
		}
	},
];
