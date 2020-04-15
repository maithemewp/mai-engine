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

// add_action( 'customize_register', 'mai_add_customizer_upsell_link' );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param WP_Customize_Manager $wp_customize WP Customize Manager object.
 *
 * @return void
 */
function mai_add_customizer_upsell_link( $wp_customize ) {
	if ( function_exists( 'mai_customizer_add_config' ) ) {
		return;
	}

	$handle = mai_get_handle();

	$wp_customize->register_section_type( 'Mai_Link_Section' );

	$wp_customize->add_section(
		new Mai_Link_Section(
			$wp_customize,
			'mai-customizer',
			[
				'title'      => __( 'Customize more now!', 'mai-engine' ),
				'priority'   => 0,
				'type'       => 'mai-link',
				'panel'      => $handle,
				'button_url' => esc_url( 'https://bizbudding.com' ),
			]
		)
	);

	$wp_customize->add_setting(
		'mai-customizer',
		[
			'default' => '',
		]
	);

	$wp_customize->add_control(
		'mai-customizer',
		[
			'type'    => 'text',
			'section' => 'mai-customizer',
		]
	);
}
