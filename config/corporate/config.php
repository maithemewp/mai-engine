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
	'plugins'       => [],
	'image-sizes'   => [
		'add'    => [
			'cover'     => [ 1600, 900, true ],
			'landscape' => '4:3',
			'portrait'  => '3:4',
			'portfolio' => '1:2',
			'square'    => '1:1',
			'tiny'      => [ 80, 80, true ],
		],
		'remove' => [],
	],
];
