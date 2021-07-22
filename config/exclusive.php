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
		// 'travel'      => 0,
		// 'home-garden' => 0,
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
	// 	'custom-colors' => [
	// 		[
	// 			'color' => '#bcda83', // var(--color-custom-1).
	// 		],
	// ],
		'fonts'  => [
			// 'body'    => 'Roboto:300,500', // 300 is regular and 500 is bold.
			'heading' => 'Oswald:600',
			'menu'    => 'Oswald:400',
		],
	],
	// 'theme-support' => [
	// 	'add' => [
	// 		'sticky-header',
	// 		'transparent-header',
	// 	],
	// ],
	'image-sizes' => [
		'add' => [
			'landscape' => '16:10',
			// 'portrait'  => '3:4',
			// 'square'    => '1:1',
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
			// 'enable' => [ 'page', 'post' ],
			// 'page'   => [
			// 	'show' => [
			// 		'genesis_entry_header',
			// 		'title',
			// 		'image',
			// 		'genesis_before_entry_content',
			// 		'excerpt',
			// 		'content',
			// 		'genesis_entry_content',
			// 		'genesis_after_entry_content',
			// 		'genesis_entry_footer',
			// 	],
			// 	'image_orientation'            => 'landscape',
			// 	'image_size'                   => 'landscape-md',
			// 	'header_meta'                  => '',
			// 	'footer_meta'                  => '',
			// 	'custom_content'               => '',
			// 	'page-header-image'            => '',
			// 	'page-header-featured'         => false,
			// 	'page-header-background-color' => '',
			// 	'page-header-overlay-opacity'  => '',
			// 	'page-header-text-color'       => '',
			// ],
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
			// 	'image_orientation'            => 'landscape',
			// 	'image_size'                   => 'landscape-md',
			// 	'header_meta'                  => 'mai_get_header_meta_default',
				'footer_meta'                  => '[post_terms taxonomy="category" before="" sep=""]',
			// 	'custom_content'               => '',
			// 	'page-header-image'            => '',
			// 	'page-header-featured'         => false,
			// 	'page-header-background-color' => '',
			// 	'page-header-overlay-opacity'  => '',
			// 	'page-header-text-color'       => '',
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
					// 'excerpt',
					'genesis_entry_content',
					// 'more_link',
					'genesis_after_entry_content',
					'genesis_entry_footer',
				],
				// 'title_size'                   => 'lg',
				// 'image_orientation'            => 'landscape',
				// 'image_size'                   => 'landscape-md',
				// 'image_position'               => 'full',
				// 'image_alternate'              => false,
				// 'image_width'                  => 'third',
				// 'header_meta'                  => 'mai_get_header_meta_default',
				// 'content_limit'                => 0,
				// 'custom_content'               => '',
				// 'more_link_text'               => '',
				'footer_meta'                  => '[post_terms taxonomy="category" before="" sep=""]',
				// 'align_text'                   => 'start',
				// 'align_text_vertical'          => '',
				// 'image_stack'                  => true,
				'boxed'                        => false,
				// 'border_radius'                => '',
				'columns'                      => '2',
				// 'columns_responsive'           => '',
				// 'columns_md'                   => '1',
				// 'columns_sm'                   => '1',
				// 'columns_xs'                   => '1',
				// 'align_columns'                => 'left',
				// 'align_columns_vertical'       => '',
				// 'column_gap'                   => 'xl',
				// 'row_gap'                      => 'xl',
				'posts_per_page'               => '12',
				// 'posts_nav'                    => 'numeric',
				// 'page-header-image'            => '',
				// 'page-header-background-color' => '',
				// 'page-header-overlay-opacity'  => '',
				// 'page-header-text-color'       => '',
			],
		],
	],
	'template-parts' => [
		'after-header' => [
			'contdition' => function() {
				return is_singular( 'post' );
			}
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
	},
];
