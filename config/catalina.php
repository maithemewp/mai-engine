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
		'default' => 51,
	],
	'global-styles' => [
		'colors' => [
			'link'      => '#000000',
			'primary'   => '#000000',
			'secondary' => '#565b39',
			'heading'   => '#000000',
			'body'      => '#000000',
			'alt'       => '#222222',
		],
		'fonts'  => [
			'body'    => 'sans-serif',
			'heading' => 'DM Serif Display:400',
		],
		'extra'  => [
			'font-size-base'              => '14px',
			'font-scale'                  => '1.3',
			'border-radius'               => '0',
			'body-line-height'            => '1.75',
			'heading-line-height'         => '1.1',
			'button-overlay-color'        => 'var(--color-white)',
			'content-sidebar-wrap-margin' => 'var(--spacing-xxxl) auto',
			'site-header-border-bottom'   => '0',
			'menu-font-size'              => 'var(--font-size-base)',
			'sub-menu-background'         => 'var(--color-heading)',
		],
	],
	'theme-support' => [
		'add' => [
			'sticky-header',
		],
	],
];
