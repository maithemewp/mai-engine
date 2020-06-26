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

add_action( 'after_setup_theme', 'mai_do_deprecated_functionality', 4 );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_do_deprecated_functionality() {
	$first_version = mai_get_option( 'first-version', '2.0.0' );

	if ( version_compare( $first_version, '2.0.0', '<' ) ) {
		add_filter( 'mai_config', 'mai_deprecated_2_0_0' );
	}
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param array $config
 *
 * @return array
 */
function mai_deprecated_2_0_0( $config ) {
	$config['scripts-and-styles']['add'][] = [
		'handle' => mai_get_handle() . '-deprecated',
		'src'    => mai_get_url() . 'assets/css/deprecated/deprecated.min.css',
	];

	$config['widget-areas'] = [
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
				'id'          => 'mobile-menu',
				'name'        => __( 'Mobile Menu', 'mai-engine' ),
				'description' => __( 'The Mobile Menu widget area.', 'mai-engine' ),
				'location'    => 'mai_after_header_wrap',
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
	];

	return $config;
}
