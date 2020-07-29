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
	'demos'         => [],
	'global-styles' => [
		'colors'     => [
			'link'      => '#067ccc',
			'primary'   => '#067ccc',
			'secondary' => '#ebe9eb',
			'heading'   => '#323232',
			'body'      => '#515151',
		],
		'fonts'      => [
			'body'    => 'Open Sans:300',
			'heading' => 'Playfair Display:700',
		],
		'extra'      => [
			'font-scale'                      => '1.2',
			'font-size-base'                  => '15px',
			'body-font-weight'                => 'var(--font-weight-light)',
			'shadow'                          => '0 4px 24px rgba(0, 0, 0, 0.025)',
			'page-header-overlay'             => 'transparent',
			'page-header-overlay-opacity'     => '1',
			'page-header-inner-background'    => 'rgba(255, 255, 255, 0.9)',
			'page-header-inner-max-width'     => '800px',
			'page-header-inner-padding'       => 'var(--spacing-xl) var(--spacing-xl)',
			'page-header-inner-border-radius' => 'var(--border-radius)',
			'page-header-inner-box-shadow'    => '0 0 8px rgba(0, 0, 0, 0.1)',
		],
	],
	'theme-support' => [
		'add' => [
			'sticky-header',
		],
	],
	'image-sizes'   => [
		'add' => [
			'landscape' => '4:3',
			'portrait'  => '3:4',
		],
	],
	'page-header'   => [
		'archive'         => [ 'category', 'product', 'post' ],
		'single'          => [ 'page', 'post' ],
		'overlay-opacity' => '0.1',
	],
	'extra'         => [
		'border-radius' => '2px',
	],
];
