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
		'type'     => 'checkbox',
		'settings' => 'sticky',
		'label'    => __( 'Enable sticky header?', 'mai-engine' ),
		'default'  => current_theme_supports( 'sticky-header' ),
	],
	[
		'type'     => 'checkbox',
		'settings' => 'transparent',
		'label'    => __( 'Enable transparent header?', 'mai-engine' ),
		'default'  => current_theme_supports( 'transparent-header' ),
	],
];
