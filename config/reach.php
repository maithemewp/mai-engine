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
		'agency'  => 'https://demo.bizbudding.com/reach-agency/wp-content/uploads/sites/13/mai-engine/',
		'podcast' => 'https://demo.bizbudding.com/reach-podcast/wp-content/uploads/sites/12/mai-engine/',
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
	],
	'theme-support' => [
		'add' => [
			'transparent-header',
		],
	],
	'settings'      => [
		'page-header' => [
			'archive'                 => '*',
			'single'                  => '*',
			'background-color'        => 'primary',
			'text-color'              => 'light',
			'divider'                 => 'curve',
			'divider-height'          => 'sm',
			'divider-color'           => 'white',
			'divider-flip-horizontal' => false,
		],
	],
	'plugins'       => [
		'genesis-enews-extended/plugin.php' => [
			'name'  => 'Genesis eNews Extended',
			'host'  => 'wordpress',
			'uri'   => 'https://wordpress.org/plugins/genesis-enews-extended/',
			'demos' => [ 'agency', 'podcast' ],
		],
	],
];
