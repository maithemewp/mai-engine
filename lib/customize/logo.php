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

add_action( 'init', 'mai_logo_customizer_settings' );
/**
 * Add logo customizer settings.
 *
 * @return  void
 */
function mai_logo_customizer_settings() {
	$config_id = mai_get_handle();

	Kirki::add_field(
		$config_id,
		[
			'type'     => 'dimensions',
			'settings' => 'logo-width',
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
					'mobile'  => mai_has_sticky_header() ? esc_html__( 'Mobile / Sticky', 'mai-engine' ) : esc_html__( 'Mobile', 'mai-engine' ),
				],
			],
			'output'   => [
				[
					'choice'   => 'mobile',
					'element'  => [ ':root', '.is-stuck' ],
					'property' => '--custom-logo-width',
					'exclude'  => [
						'120px',
					],
				],
				[
					'choice'      => 'desktop',
					'element'     => ':root',
					'property'    => '--custom-logo-width',
					'media_query' => sprintf( '@media (min-width: %spx)', mai_get_breakpoint( 'lg' ) ),
					'exclude'  => [
						'180px',
					],
				],
			],
		]
	);

	Kirki::add_field(
		$config_id,
		[
			'type'     => 'dimensions',
			'settings' => 'logo-spacing',
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
					'mobile'  => mai_has_sticky_header() ? esc_html__( 'Mobile / Sticky', 'mai-engine' ) : esc_html__( 'Mobile', 'mai-engine' ),
				],
			],
			'output'   => [
				[
					'choice'   => 'mobile',
					'element'  => [ ':root', '.is-stuck' ],
					'property' => '--title-area-padding',
					'exclude' => [
						'16px'
					],
				],
				[
					'choice'      => 'desktop',
					'element'     => ':root',
					'property'    => '--title-area-padding',
					'media_query' => sprintf( '@media (min-width: %spx)', mai_get_breakpoint( 'lg' ) ),
					'exclude' => [
						'36px'
					],
				],
			],
		]
	);

}
