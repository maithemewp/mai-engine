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
 * Add Customizer font settings.
 *
 * @since 2.0.0
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
			'type'        => 'typography',
			'settings'    => 'body-typography',
			'section'     => $section,
			'label'       => __( 'Body', 'mai-engine' ),
			'description' => __( 'Default: ', 'mai-engine' ) . mai_get_default_font_family( 'body' ) . ' ' . mai_get_default_font_weight( 'body' ),
			'default'     => [
				'font-family' => mai_get_default_font_family( 'body' ),
				'font-weight' => mai_get_default_font_weight( 'body' ),
			],
			'output'      => [
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
			'type'        => 'typography',
			'settings'    => 'heading-typography',
			'section'     => $section,
			'label'       => __( 'Heading', 'mai-engine' ),
			'description' => __( 'Default: ', 'mai-engine' ) . mai_get_default_font_family( 'heading' ) . ' ' . mai_get_default_font_weight( 'heading' ),
			'default'     => [
				'font-family' => mai_get_default_font_family( 'heading' ),
				'font-weight' => mai_get_default_font_weight( 'heading' ),
			],
			'output'      => [
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
