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

use Kirki\Util\Helper;

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

add_action( 'init', 'mai_logo_customizer_settings' );
/**
 * Add logo customizer settings.
 *
 * @since 2.3.0 Added show tagline setting.
 *
 * @return  void
 */
function mai_logo_customizer_settings() {
	$defaults = mai_get_config( 'settings' )['logo'];

	new \Kirki\Field\Checkbox(
		mai_parse_kirki_args(
			[
				'settings' => mai_get_kirki_setting( 'show-tagline' ),
				'label'    => esc_html__( 'Show Tagline', 'mai-engine' ),
				'section'  => 'title_tagline',
				'priority' => 30,
				'default'  => $defaults['show-tagline'],
			]
		)
	);

	new \Kirki\Field\Image(
		mai_parse_kirki_args(
			[
				'settings'        => mai_get_kirki_setting( 'logo-scroll' ),
				'label'           => esc_html__( 'Logo on scroll', 'mai-engine' ),
				'section'         => 'title_tagline',
				'priority'        => 60,
				'default'         => '',
				'choices'         => [
					'save_as' => 'id',
				],
				'active_callback' => function() {
					return (bool) mai_has_sticky_header_enabled() && has_custom_logo();
				},
			]
		)
	);

	new \Kirki\Field\Dimensions(
		mai_parse_kirki_args(
			[
				'settings'  => mai_get_kirki_setting( 'logo-width' ),
				'label'     => esc_html__( 'Logo width in pixels', 'mai-engine' ),
				'section'   => 'title_tagline',
				'priority'  => 60,
				// 'transport' => 'auto', // Can't use -- see https://github.com/kirki-framework/kirki/issues/2453.
				'default'   => $defaults['width'],
				'choices'   => [
					'labels' => [
						'desktop' => esc_html__( 'Desktop', 'mai-engine' ),
						'mobile'  => mai_has_sticky_header_enabled() ? esc_html__( 'Mobile / Sticky', 'mai-engine' ) : esc_html__( 'Mobile', 'mai-engine' ),
					],
				],
				'output'    => [
					[
						'choice'            => 'mobile',
						'element'           => [ ':root', '.header-stuck' ],
						'property'          => '--custom-logo-width',
						'sanitize_callback' => function( $value ) {
							return array_map( 'mai_get_unit_value', array_map( 'absint', (array) $value ) );
						},
					],
					[
						'choice'            => 'desktop',
						'element'           => ':root',
						'property'          => '--custom-logo-width',
						'media_query'       => sprintf( '@media (min-width: %spx)', mai_get_breakpoint( 'lg' ) ),
						'sanitize_callback' => function( $value ) {
							return array_map( 'mai_get_unit_value', array_map( 'absint', (array) $value ) );
						},
					],
				],
			]
		)
	);

	new \Kirki\Field\Dimensions(
		mai_parse_kirki_args(
			[
				'settings'  => mai_get_kirki_setting( 'logo-spacing' ),
				'label'     => esc_html__( 'Logo spacing in pixels', 'mai-engine' ),
				'section'   => 'title_tagline',
				'priority'  => 60,
				// 'transport' => 'auto', // Can't use -- see https://github.com/kirki-framework/kirki/issues/2453.
				'default'   => $defaults['spacing'],
				'choices'   => [
					'labels' => [
						'desktop' => esc_html__( 'Desktop', 'mai-engine' ),
						'mobile'  => mai_has_sticky_header_enabled() ? esc_html__( 'Mobile / Sticky', 'mai-engine' ) : esc_html__( 'Mobile', 'mai-engine' ),
					],
				],
				'output'    => [
					[
						'choice'            => 'mobile',
						'element'           => ':root',
						'property'          => '--title-area-padding-mobile',
						'sanitize_callback' => function( $value ) {
							return array_map( 'mai_get_unit_value', array_map( 'absint', (array) $value ) );
						},
					],
					[
						'choice'            => 'desktop',
						'element'           => ':root',
						'property'          => '--title-area-padding-desktop',
						'media_query'       => sprintf( '@media (min-width: %spx)', mai_get_breakpoint( 'lg' ) ),
						'sanitize_callback' => function( $value ) {
							return array_map( 'mai_get_unit_value', array_map( 'absint', (array) $value ) );
						},
					],
				],
			]
		)
	);
}
