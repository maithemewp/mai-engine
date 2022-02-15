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

add_action( 'init', 'mai_customizer_upsell' );
/**
 * Add upsell for Mai Plugins to customizer settings.
 *
 * @since 2.8.0
 *
 * @return void
 */
function mai_customizer_upsell( $manager ) {
	$handle  = mai_get_handle();
	$section = $handle . '-upsell';
	$args    = [
		'type'        => 'link',
		'title'       => __( 'Mai Design Pack', 'mai-engine' ),
		'button_text' => __( 'Learn More', 'mai-engine' ),
		'button_url'  => add_query_arg(
			[
				'utm_source'   => 'engine',
				'utm_medium'   => 'customizer',
				'utm_campaign' => 'mai-design-pack',
			],
			'https://bizbudding.com/mai-design-pack/'
		),
		'priority'    => 999,
	];

	new \Kirki\Section( $section, $args );

	$args['panel'] = $handle;

	new \Kirki\Section( $handle . '-theme-settings-upsell', $args );
}
