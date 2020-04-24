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

return [
	[
		'type'        => 'select',
		'settings'    => 'widget-areas',
		'label'       => __( 'Footer widget areas', 'mai-engine' ),
		'description' => __( 'Save and reload customizer to view changes.', 'mai-engine' ),
		'default'     => (string) get_theme_support( 'genesis-footer-widgets' )[0],
		'choices'     => [
			'0' => __( 'None', 'mai-engine' ),
			'1' => __( '1', 'mai-engine' ),
			'2' => __( '2', 'mai-engine' ),
			'3' => __( '3', 'mai-engine' ),
			'4' => __( '4', 'mai-engine' ),
			'6' => __( '6', 'mai-engine' ),
		],
	],
];
