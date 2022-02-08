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

use Kirki\Util\Helper;

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

// add_action( 'genesis_before_loop', function() {
// 	$data = get_option( 'mai-engine' );
// 	ray( $data );
// });

// add_action( 'after_setup_theme', 'mai_menus_customizer_settings' );
/**
 * Add menu customizer settings.
 *
 * @since 0.3.0
 *
 * @return void
 */
// function mai_menus_customizer_settings() {
	$handle  = mai_get_handle();
	$section = $handle . '-menus';

	new \Kirki\Section(
		$section,
		[
			'title' => esc_html__( 'Menus', 'mai-engine' ),
			'panel' => $handle,
		]
	);

	new \Kirki\Field\Text(
		mai_parse_kirki_args(
			[
				'settings'          => 'mobile-menu-breakpoint',
				'label'             => __( 'Mobile Menu Breakpoint', 'mai-engine' ),
				'section'           => $section,
				'description'       => __( 'The largest screen width at which the mobile menu becomes active, in pixels.', 'mai-engine' ),
				'sanitize_callback' => 'absint',
				'default'           => mai_get_mobile_menu_breakpoint(),
			]
		)
	);

	new \Kirki\Field\Radio_Buttonset(
		mai_parse_kirki_args(
			[
				'settings'        => 'header-left-menu-alignment',
				'section'         => $section,
				'label'           => __( 'Header Left Menu Alignment', 'mai-engine' ),
				'default'         => mai_get_config( 'settings' )['header-left-menu-alignment'],
				'choices'         => [
					'flex-start' => __( 'Left', 'mai-engine' ),
					'center'     => __( 'Center', 'mai-engine' ),
					'flex-end'   => __( 'Right', 'mai-engine' ),
				],
				'output'          => [
					[
						'element'  => '.header-left',
						'property' => '--menu-justify-content',
					],
				],
				// 'active_callback' => function() {
				// 	return has_nav_menu( 'header-left' );
				// },
			]
		)
	);

	new \Kirki\Field\Radio_Buttonset(
		mai_parse_kirki_args(
			[
				'settings'        => 'header-right-menu-alignment',
				'section'         => $section,
				'label'           => __( 'Header Right Menu Alignment', 'mai-engine' ),
				'default'         => mai_get_config( 'settings' )['header-right-menu-alignment'],
				'choices'         => [
					'flex-start' => __( 'Left', 'mai-engine' ),
					'center'     => __( 'Center', 'mai-engine' ),
					'flex-end'   => __( 'Right', 'mai-engine' ),
				],
				'output'          => [
					[
						'element'  => '.header-right',
						'property' => '--menu-justify-content',
					],
				],
				// 'active_callback' => function() {
				// 	return has_nav_menu( 'header-right' );
				// },
			]
		)
	);

	new \Kirki\Field\Radio_Buttonset(
		mai_parse_kirki_args(
			[
				'settings'        => 'after-header-menu-alignment',
				'section'         => $section,
				'label'           => __( 'After Header Menu Alignment', 'mai-engine' ),
				'default'         => mai_get_config( 'settings' )['after-header-menu-alignment'],
				'choices'         => [
					'flex-start' => __( 'Left', 'mai-engine' ),
					'center'     => __( 'Center', 'mai-engine' ),
					'flex-end'   => __( 'Right', 'mai-engine' ),
				],
				'output'          => [
					[
						'element'  => '.nav-after-header',
						'property' => '--menu-justify-content',
					],
				],
				// 'active_callback' => function() {
				// 	return has_nav_menu( 'after-header' );
				// },
			]
		)
	);
// }
