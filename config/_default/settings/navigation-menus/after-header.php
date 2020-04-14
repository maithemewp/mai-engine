<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2019 BizBudding
 * @license   GPL-2.0-or-later
 */

return [
	[
		'type'            => 'radio-buttonset',
		'settings'        => 'alignment',
		'label'           => __( 'Alignment', 'mai-engine' ),
		'default'         => 'flex-start',
		'choices'         => [
			'flex-start' => __( 'Left', 'mai-engine' ),
			'center'     => __( 'Center', 'mai-engine' ),
			'flex-end'   => __( 'Right', 'mai-engine' ),
		],
		'output'          => [
			[
				'element'  => '.nav-after-header',
				'property' => '--menu-justify-content',
			],
		],
		'active_callback' => 'mai_has_after_header_menu',
	],
];
