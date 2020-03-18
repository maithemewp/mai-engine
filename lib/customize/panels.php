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

/**
 * Returns array of default Customizer panels.
 *
 * @since 0.1.0
 *
 * @return array
 */
function mai_get_default_panels() {
	return [
		mai_get_handle() => mai_get_name(),
	];
}

add_action( 'genesis_setup', 'mai_add_panels', 20 );
/**
 * Adds Kirki panels.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_add_panels() {
	$priority = 10;
	$handle   = mai_get_handle();
	$panels   = apply_filters( 'mai_panels', mai_get_default_panels() );

	foreach ( $panels as $panel => $title ) {
		\Kirki::add_panel(
			$handle . "_{$panel}",
			[
				'title'    => $title,
				'priority' => $priority + 10,
				'panel'    => $handle,
			]
		);
	}
}

add_action( 'init', 'mai_header_customizer_settings' );
/**
 * Add header customizer settings.
 *
 * @return  void
 */
function mai_header_customizer_settings() {

	// Bail if no Kirki.
	if ( ! class_exists( 'Kirki' ) ) {
		return;
	}

	$config_id = 'mai_header';

	/**
	 * Kirki Config.
	 */
	Kirki::add_config(
		$config_id,
		[
			'capability'  => 'edit_theme_options',
			'option_type' => 'option',
			'option_name' => $config_id,
		]
	);


	Kirki::add_field(
		$config_id,
		[
			'type'     => 'dimensions',
			'settings' => 'logo_width',
			'label'    => esc_html__( 'Logo Width', 'mai-engine' ),
			'section'  => 'title_tagline',
			'priority' => 60,
			'default'  => [
				'mobile'  => '120px',
				'desktop' => '180px',
			],
			'choices'  => [
				'labels' => [
					'mobile'  => esc_html__( 'Mobile', 'mai-engine' ),
					'desktop' => esc_html__( 'Desktop', 'mai-engine' ),
				],
			],
			'output'   => [
				[
					'choice'   => 'mobile',
					'element'  => ':root',
					'property' => '--custom-logo-width',
				],
				[
					'choice'      => 'desktop',
					'element'     => ':root',
					'property'    => '--custom-logo-width',
					'media_query' => sprintf( '@media (min-width: %spx)', mai_get_breakpoint( 'lg' ) ),
				],
			],
		]
	);

	Kirki::add_field(
		$config_id,
		[
			'type'     => 'dimensions',
			'settings' => 'logo_spacing',
			'label'    => esc_html__( 'Logo Spacing', 'mai-engine' ),
			'section'  => 'title_tagline',
			'priority' => 60,
			'default'  => [
				'mobile_top'     => '16px',
				'mobile_bottom'  => '16px',
				'desktop_top'    => '36px',
				'desktop_bottom' => '36px',
			],
			'choices'  => [
				'labels' => [
					'mobile_top'     => esc_html__( 'Mobile Top', 'mai-engine' ),
					'mobile_bottom'  => esc_html__( 'Mobile Bottom', 'mai-engine' ),
					'desktop_top'    => esc_html__( 'Desktop Top', 'mai-engine' ),
					'desktop_bottom' => esc_html__( 'Desktop Bottom', 'mai-engine' ),
				],
			],
			'output'   => [
				[
					'choice'   => 'mobile_top',
					'element'  => ':root',
					'property' => '--title-area-padding-top',
				],
				[
					'choice'   => 'mobile_bottom',
					'element'  => ':root',
					'property' => '--title-area-padding-bottom',
				],
				[
					'choice'      => 'desktop_top',
					'element'     => ':root',
					'property'    => '--title-area-padding-top',
					'media_query' => sprintf( '@media (min-width: %spx)', mai_get_breakpoint( 'lg' ) ),
				],
				[
					'choice'      => 'desktop_bottom',
					'element'     => ':root',
					'property'    => '--title-area-padding-bottom',
					'media_query' => sprintf( '@media (min-width: %spx)', mai_get_breakpoint( 'lg' ) ),
				],
			],
		]
	);
}
