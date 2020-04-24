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
		'description' => sprintf(
			'%s<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			__( 'The text that will appear in your site footer. Can include ', 'mai-engine' ),
			'https://studiopress.github.io/genesis/basics/genesis-shortcodes/#footer-shortcodes',
			__( 'footer shortcodes.', 'mai-engine' )
		),
		'default'     => mai_default_footer_credits(),
	],
];
