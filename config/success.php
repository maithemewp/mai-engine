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
		'business' => 5,
	],
	'global-styles' => [
		'colors' => [
			'link'      => '#fb2056',
			'primary'   => '#fb2056',
			'secondary' => '#8693a6',
			'heading'   => '#232c39',
			'alt'       => '#f6f7f8',
		],
		'fonts'  => [
			'body'    => 'Hind:400',
			'heading' => 'Montserrat:600',
			'button'  => 'Montserrat:600',
		],
		'extra'  => [
			'border-width'           => '2px',
			'blockquote-border-left' => '0',
			'site-title-font-size'   => 'var(--font-size-md)',
			'menu-font-family'       => 'var(--heading-font-family)',
			'menu-font-size'         => 'var(--font-size-sm)',
			'footer-credits-padding' => '0',
		],
	],
	'theme-support' => [
		'add' => [
			'transparent-header',
			'sticky-header',
		],
	],
	'page-header'   => [
		'archive'          => '*',
		'single'           => '*',
		'image'            => '',
		'background-color' => 'heading',
		'text-color'       => 'light',
		'divider-color'    => 'white',
	],
];
