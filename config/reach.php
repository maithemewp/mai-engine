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
		'agency'  => 13,
		'podcast' => 12,
	],
	'global-styles' => [
		'colors' => [
			'alt'       => '#f7f8fa',
			'link'      => '#7b51ff',
			'primary'   => '#7b51ff',
			'secondary' => '#8f98a3',
			'heading'   => '#4b657e',
			'body'      => '#5f749e',
		],
		'fonts'  => [
			'body'    => 'Karla:400',
			'heading' => 'Karla:700',
		],
		'extra'  => [
			'button-transform-hover'          => 'scale(1.05)',
			'site-header-border-bottom'       => '0',
			'page-header-border-bottom'       => '0',
			'content-sidebar-wrap-margin-top' => 'var(--spacing-xxl)',
			'site-footer-background'          => 'var(--color-alt)',
		],
	],
	'theme-support' => [
		'add' => [
			'transparent-header',
		],
	],
	'page-header'   => [
		'archive'                 => '*',
		'single'                  => '*',
		'background-color'        => 'primary',
		'text-color'              => 'light',
		'divider'                 => 'curve',
		'divider-height'          => 'sm',
		'divider-color'           => 'white',
		'divider-flip-horizontal' => false,
	],
	'plugins'       => [
		[
			'name'  => 'Genesis eNews Extended',
			'slug'  => 'genesis-enews-extended/plugin.php',
			'uri'   => 'https://wordpress.org/plugins/genesis-enews-extended/',
			'demos' => [ 'agency', 'podcast' ],
		],
	],
];
