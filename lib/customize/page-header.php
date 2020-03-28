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

add_action( 'after_setup_theme', 'mai_page_header_customizer_settings' );
/**
 * Add page_header customizer settings.
 *
 * @return  void
 */
function mai_page_header_customizer_settings() {
	$config_id = mai_get_handle();
	$panel_id  = $config_id;
	$section_id   = $panel_id . '-page-header';

	Kirki::add_section(
		$section_id,
		[
			'title'       => esc_attr__( 'Page Header', 'mai-engine' ),
			'description' => '',
			'priority'    => 60,
			'panel'       => $panel_id,
		]
	);

	Kirki::add_field(
		$config_id,
		[
			'type'     => 'image',
			'settings' => 'page-header-image',
			'label'    => esc_html__( 'Page Header Image', 'mai-engine' ),
			'section'  => $section_id,
			'default'  => '',
			'choices'  => [
				'save_as' => 'id',
			],
		]
	);

	Kirki::add_field(
		$config_id,
		[
			'type'        => 'dimensions',
			'settings'    => 'page-header-spacing',
			'label'       => esc_html__( 'Page Header Spacing', 'mai-engine' ),
			'description' => esc_html__( 'Accepts all unit values (px, rem, em, vw, etc).', 'mai-engine' ),
			'section'     => $section_id,
			'default'     => [
				'top'    => '10vw',
				'bottom' => '10vw',
			],
			'choices'     => [
				'labels' => [
					'top'    => esc_html__( 'Top', 'mai-engine' ),
					'bottom' => esc_html__( 'Bottom', 'mai-engine' ),
				],
			],
			'output'      => [
				[
					'choice'   => 'top',
					'element'  => ':root',
					'property' => '--page-header-padding-top',
				],
				[
					'choice'   => 'bottom',
					'element'  => ':root',
					'property' => '--page-header-padding-bottom',
				],
			],
		]
	);

	// Kirki::add_field(
	// 	$config_id,
	// 	[
	// 		'type'     => 'radio-buttonset',
	// 		'settings' => 'text_align',
	// 		'label'    => esc_html__( 'Text Alignment', 'mai-engine' ),
	// 		'section'  => $config_id,
	// 		'default'  => 'center',
	// 		'choices'  => [
	// 			'start'  => esc_html__( 'Start' ),
	// 			'center' => esc_html__( 'Center' ),
	// 			'end'    => esc_html__( 'End' ),
	// 		],
	// 		'output'   => [
	// 			[
	// 				'choice'   => [ 'start', 'center', 'end' ],
	// 				'element'  => ':root',
	// 				'property' => '--page-header-text-align',
	// 			],
	// 		],
	// 	]
	// );

}
