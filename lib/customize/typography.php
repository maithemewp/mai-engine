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

add_action( 'init', 'mai_typography_customizer_settings' );
/**
 * Add base styles customizer settings.
 *
 * @since 0.3.0
 *
 * @return void
 */
function mai_typography_customizer_settings() {
	$handle        = mai_get_handle();
	$section       = $handle . '-typography';
	$global_styles = mai_get_global_styles();

	\Kirki::add_section(
		$section,
		[
			'title' => esc_html__( 'Typography', 'mai-engine' ),
			'panel' => $handle,
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'     => 'number',
			'settings' => 'font-size-base',
			'section'  => $section,
			'label'    => __( 'Base', 'mai-engine' ),
			'default'  => $global_styles['font-sizes']['base'],
			'choices'  => [
				'min'  => 10,
				'max'  => 100,
				'step' => 1,
			],
			'output'   => [
				[
					'element'  => ':root',
					'property' => '--font-size-base',
					'units'    => 'px',
					'context'  => [ 'front', 'editor' ],
				],
			],
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'     => 'slider',
			'settings' => 'font-scale',
			'section'  => $section,
			'label'    => __( 'Scale', 'mai-engine' ),
			'default'  => $global_styles['font-scale'],
			'choices'  => [
				'min'  => 1,
				'max'  => 2,
				'step' => 0.01,
			],
			'output'   => [
				[
					'element'  => ':root',
					'property' => '--font-scale',
					'context'  => [ 'front', 'editor' ],
				],
			],
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'     => 'custom',
			'settings' => 'typography-base-divider',
			'section'  => $section,
			'default'  => '<hr>',
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'     => 'typography',
			'settings' => 'body-typography',
			'section'  => $section,
			'label'    => esc_html__( 'Body', 'mai-engine' ),
			'default'  => [
				'font-family' => $global_styles['fonts']['body'],
				'font-weight' => $global_styles['font-weights']['body'],
			],
			'output'   => [
				[
					'element'  => ':root',
					'property' => '--font-body',
					'choice'   => 'font-family',
					'context'  => [ 'front', 'editor' ],
				],
				[
					'element'  => ':root',
					'property' => '--font-weight-body',
					'choice'   => 'font-weight',
					'context'  => [ 'front', 'editor' ],
				],
			],
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'     => 'typography',
			'settings' => 'italic-typography',
			'section'  => $section,
			'label'    => esc_html__( 'Italic', 'mai-engine' ),
			'default'  => [
				'font-family' => mai_get_option( 'font-body', $global_styles['fonts']['body'] ),
				'variant'     => 'italic',
			],
			'output'   => [
				[
					'element'  => 'i,em',
					'property' => 'font-family',
					'choice'   => 'font-family',
					'context'  => [ 'front', 'editor' ],
				],
				[
					'element'  => 'i,em',
					'property' => 'font-weight',
					'choice'   => 'variant',
					'context'  => [ 'front', 'editor' ],
				],
			],
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'     => 'typography',
			'settings' => 'bold-typography',
			'section'  => $section,
			'label'    => esc_html__( 'Bold', 'mai-engine' ),
			'default'  => [
				'font-family' => mai_get_option( 'font-body', $global_styles['fonts']['body'] ),
				'variant'     => 'bold',
			],
			'output'   => [
				[
					'element'  => 'bold,strong',
					'property' => 'font-family',
					'choice'   => 'font-family',
					'context'  => [ 'front', 'editor' ],
				],
				[
					'element'  => 'bold,strong',
					'property' => 'font-weight',
					'choice'   => 'variant',
					'context'  => [ 'front', 'editor' ],
				],
			],
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'     => 'typography',
			'settings' => 'heading-typography',
			'section'  => $section,
			'label'    => esc_html__( 'Heading', 'mai-engine' ),
			'default'  => [
				'font-family' => $global_styles['fonts']['heading'],
				'font-weight' => $global_styles['font-weights']['heading'],
			],
			'output'   => [
				[
					'element'  => ':root',
					'property' => '--font-heading',
					'choice'   => 'font-family',
					'context'  => [ 'front', 'editor' ],
				],
				[
					'element'  => ':root',
					'property' => '--font-weight-heading',
					'choice'   => 'font-weight',
					'context'  => [ 'front', 'editor' ],
				],
			],
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'     => 'custom',
			'settings' => 'typography-buttons-divider',
			'section'  => $section,
			'default'  => '<hr>',
		]
	);

	\Kirki::add_field(
		$handle,
		[
			'type'        => 'radio-buttonset',
			'settings'    => 'button-font',
			'label'       => __( 'Buttons', 'mai-engine' ),
			'description' => __( 'Select which font buttons should use', 'mai-engine' ),
			'section'     => $section,
			'default'     => 'heading',
			'choices'     => [
				'body'    => __( 'Body', 'mai-engine' ),
				'heading' => __( 'Heading', 'mai-engine' ),
			],
			'output'      => [
				[
					'element'       => ':root',
					'property'      => '--button-font-family',
					'value_pattern' => 'var(--font-$)',
					'context'       => [ 'front', 'editor' ],
				],
			],
		]
	);
}
