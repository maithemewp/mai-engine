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
	$handle  = mai_get_handle();
	$section = $handle . '-typography';

	\Kirki::add_section(
		$section,
		[
			'title' => __( 'Typography', 'mai-engine' ),
			'panel' => $handle,
		]
	);

	$body_font_family = mai_get_default_font_family( 'body' );
	$body_font_weight = mai_get_default_font_weight( 'body' );

	\Kirki::add_field(
		$handle,
		[
			'type'        => 'typography',
			'settings'    => 'body-typography',
			'section'     => $section,
			'label'       => __( 'Body', 'mai-engine' ),
			'description' => __( 'Default: ', 'mai-engine' ) . $body_font_family . ' ' . $body_font_weight,
			'default'     => [
				'font-family' => $body_font_family,
				'font-weight' => $body_font_weight,
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

	$heading_font_family = mai_get_default_font_family( 'heading' );
	$heading_font_weight = mai_get_default_font_weight( 'heading' );

	\Kirki::add_field(
		$handle,
		[
			'type'        => 'typography',
			'settings'    => 'heading-typography',
			'section'     => $section,
			'label'       => __( 'Heading', 'mai-engine' ),
			'description' => __( 'Default: ', 'mai-engine' ) . $heading_font_family . ' ' . $heading_font_weight,
			'default'     => [
				'font-family' => $heading_font_family,
				'font-weight' => $heading_font_weight,
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
