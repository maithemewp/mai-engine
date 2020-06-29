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
			'title' => __( 'Typography', 'mai-engine' ),
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
			'label'    => __( 'Body', 'mai-engine' ),
			'default'  => [
				'font-family' => $global_styles['fonts']['body'],
				'font-weight' => $global_styles['font-weights']['body'],
			],
			'output'   => [
				[
					'element'  => ':root',
					'property' => '--body-font-family',
					'choice'   => 'font-family',
					'context'  => [ 'front', 'editor' ],
				],
				[
					'element'  => ':root',
					'property' => '--body-font-weight',
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
			'settings' => 'heading-typography',
			'section'  => $section,
			'label'    => __( 'Heading', 'mai-engine' ),
			'default'  => [
				'font-family' => $global_styles['fonts']['heading'],
				'font-weight' => $global_styles['font-weights']['heading'],
			],
			'output'   => [
				[
					'element'  => ':root',
					'property' => '--heading-font-family',
					'choice'   => 'font-family',
					'context'  => [ 'front', 'editor' ],
				],
				[
					'element'  => ':root',
					'property' => '--heading-font-weight',
					'choice'   => 'font-weight',
					'context'  => [ 'front', 'editor' ],
				],
			],
		]
	);
}
