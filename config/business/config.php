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
		'Montserrat:600|Hind:400',
	],
	'theme-support' => [
		'add' => [
			'sticky-header',
			'transparent-header',
		],
	],
	'css'           => [
		'color-primary'                                 => '#fb2056',
		'color-heading'                                 => '#232c39',
		'color-body'                                    => '#647585',
		'page-header-heading-color'                            => 'var(--color-white)',
		'font-family-body'                              => 'Hind, var(--font-family-stack)',
		'font-family-heading'                           => 'Montserrat, var(--font-family-body)',
		'button-font-family'                            => 'var(--font-family-heading)',
		'transparent-header-site-title-color'           => 'var(--color-white)',
		'transparent-header-site-title-color-hover'     => 'var(--color-white)',
		'transparent-header-site-description-color'     => 'var(--color-white)',
		'transparent-header-menu-item-link-color'       => 'var(--color-white)',
		'transparent-header-menu-item-link-color-hover' => 'var(--color-white)',
	],
];

