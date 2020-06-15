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
	'demos'         => [
		// 'local'    => 3,
		// 'coaching' => 4,
		'agency' => 5,
	],
	'google-fonts'  => [
		'https://fonts.googleapis.com/css2?family=Hind&family=Montserrat:wght@700&display=swap',
	],
	'theme-support' => [
		'add' => [
			'sticky-header',
			'transparent-header',
		],
	],
	'widget-areas'   => [
		'remove' => [
			'before-header',
			'header-left',
			'header-right',
			'before-footer',
			'footer',
			'footer-credits',
		],
	],
	'template-parts' => [
		[
			'id'       => 'before-header',
			'location' => 'genesis_before_header',
			'default'  => '',
		],
		[
			'id'       => 'header-left',
			'location' => 'mai_header_left',
			'default'  => '',
		],
		[
			'id'       => 'header-right',
			'location' => 'mai_header_right',
			'default'  => '',
		],
		[
			'id'       => 'before-footer',
			'location' => 'genesis_footer',
			'priority' => 5,
			'default'  => '',
		],
		[
			'id'       => 'footer',
			'location' => 'genesis_footer',
			'default'  => '',
		],
		[
			'id'       => 'footer-credits',
			'location' => 'genesis_footer',
			'priority' => 12,
			'default'  => '',
		],
	],
];
