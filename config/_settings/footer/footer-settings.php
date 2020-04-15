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
		'settings'    => 'widgets',
		'label'       => __( 'Footer widget areas', 'mai-engine' ),
		'description' => __( 'Save and reload customizer to view changes.', 'mai-engine' ),
		'default'     => mai_get_config( 'genesis-footer-widgets' ),
		'choices'     => [
			0 => __( 'None', 'mai-engine' ),
			1 => __( '1', 'mai-engine' ),
			2 => __( '2', 'mai-engine' ),
			3 => __( '3', 'mai-engine' ),
			4 => __( '4', 'mai-engine' ),
			6 => __( '6', 'mai-engine' ),
		],
	],
	[
		'type'        => 'textarea',
		'settings'    => 'text',
		'label'       => __( 'Site footer text', 'mai-engine' ),
		'description' => sprintf( __( 'The text that will appear in your site footer. Can include <a href="%s" target="_blank" rel="noopener noreferrer">footer shortcodes</a>.', 'genesis' ), 'https://studiopress.github.io/genesis/basics/genesis-shortcodes/#footer-shortcodes' ),
		'default'     => 'Copyright [footer_copyright] · [footer_home_link] · All Rights Reserved · Powered by <a target="_blank" rel="nofollow noopener sponsored" href="https://bizbudding.com/mai-theme/">Mai Theme</a>',
	],
];
