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

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

return [
	'google-fonts'  => [
		'Muli:400,700',
	],
	'theme-support' => [
		'add' => [
			'sticky-header',
		],
	],
	'css'           => [
		'color-blue'                                    => '#0072ff',
		'color-heading'                                 => '#2a3139',
		'color-body'                                    => '#4d5968',
		'border-radius'                                 => '50px',
		'font-family-body'                              => 'Muli, var(--font-family-stack)',
		'font-family-heading'                           => 'Muli, var(--font-family-stack)',
		'font-weight-heading'                           => '700',
		'font-size-xxxxxl'                                  => 'calc(var(--font-size-md) * 3)',
		'page-header-heading-color'                            => 'var(--color-white)',
		'button-font-family'                            => 'var(--font-family-heading)',
		'transparent-header-site-title-color'           => 'var(--color-white)',
		'transparent-header-site-title-color-hover'     => 'var(--color-white)',
		'transparent-header-site-description-color'     => 'var(--color-white)',
		'transparent-header-menu-item-link-color'       => 'var(--color-white)',
		'transparent-header-menu-item-link-color-hover' => 'var(--color-white)',
	],
];

