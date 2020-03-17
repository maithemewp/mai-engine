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

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return array
 */
function mai_header_settings_defaults_temp_function() {
	return [
		'logo_width_large'   => '180px',
		'logo_width_small'   => '120px',
		'logo_spacing_large' => [
			'top'    => '36px',
			'bottom' => '36px',
		],
		'logo_spacing_small' => [
			'top'    => '16px',
			'bottom' => '16px',
		],
	];
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

	// Kirki::add_field( $config_id, [
	// 'type'        => 'custom',
	// 'settings'    => 'logo_heading',
	// 'label'       => esc_html__( 'Logo/Title Sizing', 'mai-engine' ),
	// 'section'     => 'title_tagline',
	// 'priority'    => 60,
	// 'default'     => '',
	// ] );

	$defaults = mai_header_settings_defaults_temp_function();

	Kirki::add_field(
		$config_id,
		[
			'type'     => 'text',
			'settings' => 'logo_width_large',
			'label'    => esc_html__( 'Logo Width', 'mai-engine' ),
			'section'  => 'title_tagline',
			'priority' => 60,
			'default'  => $defaults['logo_width_large'],
		]
	);

	Kirki::add_field(
		$config_id,
		[
			'type'     => 'dimensions',
			'settings' => 'logo_spacing_large',
			'label'    => esc_html__( 'Logo Spacing', 'mai-engine' ),
			'section'  => 'title_tagline',
			'priority' => 60,
			'default'  => [
				'top'    => $defaults['logo_spacing_large']['top'],
				'bottom' => $defaults['logo_spacing_large']['bottom'],
			],
			'choices'  => [
				'labels' => [
					'top'    => esc_html__( 'Top', 'mai-engine' ),
					'bottom' => esc_html__( 'Bottom', 'mai-engine' ),
				],
			],
		]
	);

	Kirki::add_field(
		$config_id,
		[
			'type'     => 'text',
			'settings' => 'logo_width_small',
			'label'    => esc_html__( 'Shrink/Mobile Logo Width', 'mai-engine' ),
			'section'  => 'title_tagline',
			'priority' => 60,
			'default'  => $defaults['logo_width_small'],
		]
	);

	Kirki::add_field(
		$config_id,
		[
			'type'     => 'dimensions',
			'settings' => 'logo_spacing_small',
			'label'    => esc_html__( 'Shrink/Mobile Logo Spacing', 'mai-engine' ),
			'section'  => 'title_tagline',
			'priority' => 60,
			'default'  => [
				'top'    => $defaults['logo_spacing_small']['top'],
				'bottom' => $defaults['logo_spacing_small']['bottom'],
			],
			'choices'  => [
				'labels' => [
					'top'    => esc_html__( 'Top', 'mai-engine' ),
					'bottom' => esc_html__( 'Bottom', 'mai-engine' ),
				],
			],
		]
	);
}
