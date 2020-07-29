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
		'default' => 53,
	],
	'global-styles' => [
		'colors' => [
			'link'      => '#ffb282',
			'primary'   => '#000000',
			'secondary' => '#f5f5f5',
			'body'      => '#000000',
			'heading'   => '#000000',
			'alt'       => '#f5f5f5',
		],
		'fonts'  => [
			'body'    => 'Lato:400',
			'heading' => 'Playfair Display:400',
		],
		'extra'  => [
			'border-radius'            => '0',
			'body-line-height'         => '1.875',
			'blockquote-font-family'   => 'var(--heading-font-family)',
			'blockquote-font-style'    => 'normal',
			'blockquote-border-top'    => 'var(--border)',
			'blockquote-border-bottom' => 'var(--border)',
			'blockquote-border-left'   => '0',
			'cite-font-family'         => 'var(--body-font-family)',
			'menu-font-family'         => 'var(--heading-font-family)',
		],
	],
	'theme-support' => [
		'add' => [
			'sticky-header',
		],
	],
];
