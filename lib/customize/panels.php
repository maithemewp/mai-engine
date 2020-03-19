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
				'desktop' => '180px',
				'mobile'  => '120px',
			],
			'choices'  => [
				'labels' => [
					'desktop' => esc_html__( 'Desktop', 'mai-engine' ),
					'mobile'  => esc_html__( 'Mobile / Sticky', 'mai-engine' ),
				],
			],
			'output'   => [
				[
					'choice'   => 'mobile',
					'element'  => [ ':root', '.is-stuck' ],
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
				'desktop' => '36px',
				'mobile'  => '16px',
			],
			'choices'  => [
				'labels' => [
					'desktop' => esc_html__( 'Desktop', 'mai-engine' ),
					'mobile'  => esc_html__( 'Mobile / Sticky', 'mai-engine' ),
				],
			],
			'output'   => [
				[
					'choice'   => 'mobile',
					'element'  => [ ':root', '.is-stuck' ],
					'property' => '--title-area-padding',
				],
				[
					'choice'      => 'desktop',
					'element'     => ':root',
					'property'    => '--title-area-padding',
					'media_query' => sprintf( '@media (min-width: %spx)', mai_get_breakpoint( 'lg' ) ),
				],
			],
		]
	);
}
