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

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

use WPTRT\Customize\Section\Button;

add_action( 'customize_register', 'mai_customizer_upsell' );
/**
 * Add upsell for Mai Plugins to customizer settings.
 *
 * @since 2.8.0
 *
 * @return void
 */
function mai_customizer_upsell( $manager ) {
	$config_id = mai_get_handle();
	$manager->register_section_type( Button::class );

	$manager->add_section(
		new Button( $manager, $config_id, [
			'title'       => __( 'Mai Design Pack', 'mai-engine' ),
			'button_text' => __( 'Learn More', 'mai-engine' ),
			'button_url'  => add_query_arg(
				[
					'utm_source'   => 'engine',
					'utm_medium'   => 'customizer',
					'utm_campaign' => 'mai-design-pack',
				],
				'https://bizbudding.com/mai-design-pack/'
			)
		] )
	);
}
