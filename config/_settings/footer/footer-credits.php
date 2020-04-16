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
		'type'        => 'textarea',
		'settings'    => 'text',
		'label'       => __( 'Site footer text', 'mai-engine' ),
		'description' => sprintf( __( 'The text that will appear in your site footer. Can include <a href="%s" target="_blank" rel="noopener noreferrer">footer shortcodes</a>.', 'genesis' ), 'https://studiopress.github.io/genesis/basics/genesis-shortcodes/#footer-shortcodes' ),
		'default'     => 'Copyright [footer_copyright] · [footer_home_link] · All Rights Reserved · Powered by <a target="_blank" rel="nofollow noopener sponsored" href="https://bizbudding.com/mai-theme/">Mai Theme</a>',
	],
];
