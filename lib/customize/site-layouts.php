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

add_action( 'init', 'mai_site_layouts_customizer_settings' );
/**
 * Add base styles customizer settings.
 *
 * @since 0.3.0
 *
 * @return void
 */
function mai_site_layouts_customizer_settings() {
	$handle  = mai_get_handle();
	$section = $handle . '-site-layouts';

	\Kirki::add_section(
		$section,
		[
			'title' => esc_html__( 'Site Layouts', 'mai-engine' ),
			'panel' => $handle,
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'     => 'checkbox',
			'settings' => 'boxed-container',
			'section'  => $section,
			'label'    => __( 'Enable boxed site container', 'mai-engine' ),
			'default'  => current_theme_supports( 'boxed-container' ),
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'     => 'select',
			'settings' => 'site',
			'section'  => $section,
			'label'    => __( 'Site Default', 'mai-engine' ),
			'default'  => isset( $layouts['default']['site'] ) && ! empty( $layouts['default']['site'] ) ? $layouts['default']['site'] : 'standard-content',
			'choices'  => mai_get_site_layout_choices(),
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'     => 'select',
			'settings' => 'archive',
			'section'  => $section,
			'label'    => __( 'Content Archives', 'mai-engine' ),
			'default'  => isset( $layouts['default']['archive'] ) && ! empty( $layouts['default']['archive'] ) ? $layouts['default']['archive'] : 'wide-content',
			'choices'  => mai_get_site_layout_choices(),
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'     => 'select',
			'settings' => 'single',
			'section'  => $section,
			'label'    => __( 'Single Content', 'mai-engine' ),
			'default'  => isset( $layouts['default']['single'] ) && ! empty( $layouts['default']['single'] ) ? $layouts['default']['single'] : '',
			'choices'  => mai_get_site_layout_choices(),
		]
	);

	/*
	 * Archive fields.
	 */

	\Kirki::add_field(
		$handle,
		[
			'type'     => 'custom',
			'settings' => 'archive-layout-divider',
			'section'  => $section,
			'default'  => '<hr>',
		]
	);

	$layouts = mai_get_config( 'site-layouts' );
	$archive = mai_get_content_type_choices( true );
	$single  = mai_get_content_type_choices( false );

	foreach ( $archive as $type => $label ) {
		\Kirki::add_field(
			$handle,
			[
				'type'     => 'select',
				'settings' => $type,
				'section'  => $section,
				'label'    => $label,
				'default'  => isset( $layouts['archive'][ $type ] ) && ! empty( $layouts['archive'][ $type ] ) ? $layouts['archive'][ $type ] : '',
				'choices'  => mai_get_site_layout_choices(),
			]
		);
	}

	foreach ( $single as $type => $label ) {
		\Kirki::add_field(
			$handle,
			[
				'type'     => 'select',
				'settings' => $type,
				'section'  => $section,
				'label'    => $label,
				'default'  => isset( $layouts['single'][ $type ] ) && ! empty( $layouts['single'][ $type ] ) ? $layouts['single'][ $type ] : '',
				'choices'  => mai_get_site_layout_choices(),
			]
		);
	}
}
