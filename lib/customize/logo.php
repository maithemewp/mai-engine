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
 * @since 2.3.0 Added show tagline setting.
 *
 * @return  void
 */
function mai_logo_customizer_settings() {
	$config_id = mai_get_handle();
	$defaults  = mai_get_config( 'settings' )['logo'];

	Kirki::add_field(
		$config_id,
		[
			'type'     => 'checkbox',
			'settings' => 'show-tagline',
			'label'    => esc_html__( 'Show Tagline', 'mai-engine' ),
			'section'  => 'title_tagline',
			'priority' => 30,
			'default'  => $defaults['show-tagline'],
		]
	);

	Kirki::add_field(
		$config_id,
		[
			'type'     => 'image',
			'settings' => 'logo-scroll',
			'label'    => esc_html__( 'Logo on scroll (beta)', 'mai-engine' ),
			'section'  => 'title_tagline',
			'priority' => 60,
			'default'  => '',
			'output'   => [
				[
					'element'       => '.custom-logo-link',
					'property'      => '--background-image',
					'value_pattern' => 'url($)',
				],
			],
			'active_callback' => function() {
				return (bool) mai_has_sticky_header_enabled() && has_custom_logo();
			},
		]
	);

	Kirki::add_field(
		$config_id,
		[
			'type'     => 'dimensions',
			'settings' => 'logo-width',
			'label'    => esc_html__( 'Logo Width', 'mai-engine' ),
			'section'  => 'title_tagline',
			'priority' => 60,
			'default'  => $defaults['width'],
			'choices'  => [
				'labels' => [
					'desktop' => esc_html__( 'Desktop', 'mai-engine' ),
					'mobile'  => mai_has_sticky_header_enabled() ? esc_html__( 'Mobile / Sticky', 'mai-engine' ) : esc_html__( 'Mobile', 'mai-engine' ),
				],
			],
			'output'   => [
				[
					'choice'   => 'mobile',
					'element'  => [ ':root', '.header-stuck' ],
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
			'settings' => 'logo-spacing',
			'label'    => esc_html__( 'Logo Spacing', 'mai-engine' ),
			'section'  => 'title_tagline',
			'priority' => 60,
			'default'  => $defaults['spacing'],
			'choices'  => [
				'labels' => [
					'desktop' => esc_html__( 'Desktop', 'mai-engine' ),
					'mobile'  => mai_has_sticky_header_enabled() ? esc_html__( 'Mobile / Sticky', 'mai-engine' ) : esc_html__( 'Mobile', 'mai-engine' ),
				],
			],
			'output'   => [
				[
					'choice'   => 'mobile',
					'element'  => ':root',
					'property' => '--title-area-padding-mobile',
				],
				[
					'choice'      => 'desktop',
					'element'     => ':root',
					'property'    => '--title-area-padding-desktop',
					'media_query' => sprintf( '@media (min-width: %spx)', mai_get_breakpoint( 'lg' ) ),
				],
			],
		]
	);

}
