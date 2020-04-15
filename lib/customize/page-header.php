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

add_action( 'after_setup_theme', 'mai_page_header_customizer_settings' );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_page_header_customizer_settings() {
	if ( ! mai_has_any_page_header_types() ) {
		return;
	}

	$handle  = mai_get_handle();
	$section = $handle . '-page-header';
	$config  = mai_get_config( 'page-header' );
	$default = [];

	if ( isset( $config['archive'] ) && ( ! empty( $config['archive'] ) || '*' === $config['archive'] ) ) {
		array_push( $default, 'archive' );
	}

	if ( isset( $config['single'] ) && ( ! empty( $config['single'] ) || '*' === $config['single'] ) ) {
		array_push( $default, 'single' );
	}

	if ( '*' === $config ) {
		$default = [ 'archive', 'single' ];
	}

	Kirki::add_section(
		$section,
		[
			'title' => __( 'Page Header', 'mai-engine' ),
			'panel' => $handle,
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'        => 'multicheck',
			'settings'    => 'page-header',
			'section'     => $section,
			'label'       => __( 'Enable on', 'mai-engine' ),
			'description' => __( 'These settings can be overridden on a per post basis.', 'mai-engine' ),
			'default'     => $default,
			'choices'     => [
				'single'  => __( 'Single Content', 'mai-engine' ),
				'archive' => __( 'Content Archives', 'mai-engine' ),
			],
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'     => 'image',
			'settings' => 'page-header-image',
			'section'  => $section,
			'label'    => __( 'Default Image', 'mai-engine' ),
			'default'  => '',
			'choices'  => [
				'save_as' => 'id',
			],
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'        => 'dimensions',
			'settings'    => 'page-header-spacing',
			'section'     => $section,
			'label'       => __( 'Vertical Spacing', 'mai-engine' ),
			'description' => __( 'Accepts all unit values (px, rem, em, vw, etc).', 'mai-engine' ),
			'default'     => [
				'top'    => '',
				'bottom' => '',
			],
			'choices'     => [
				'top'    => __( 'Top', 'mai-engine' ),
				'bottom' => __( 'Bottom', 'mai-engine' ),
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
			'input_attrs' => [
				'placeholder' => '10vw',
			],
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'     => 'radio-buttonset',
			'settings' => 'page-header-text-align',
			'section'  => $section,
			'label'    => __( 'Text Alignment', 'mai-engine' ),
			'default'  => '',
			'choices'  => [
				'start'  => __( 'Start', 'mai-engine' ),
				'center' => __( 'Center', 'mai-engine' ),
				'end'    => __( 'End', 'mai-engine' ),
			],
			'output'   => [
				[
					'choice'   => [ 'start', 'center', 'end' ],
					'element'  => ':root',
					'property' => '--page-header-text-align',
				],
			],
		]
	);
}
