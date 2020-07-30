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
		'studio' => 52,
	],
	'global-styles' => [
		'colors' => [
			'alt'       => '#f6f7f8',
			'body'      => '#393e4b',
			'heading'   => '#393e4b',
			'link'      => '#0cb4ce', // Cyan.
			'primary'   => '#6442ff', // Purple.
			'secondary' => '#006cff', // Blue.
		],
		'fonts'  => [
			'body'    => 'Roboto:300,500',
			'heading' => 'Playfair Display:400',
		],
	],
	'theme-support' => [
		'add' => [
			'transparent-header',
			'editor-gradient-presets' => [
				[
					'name'     => __( 'Studio', 'mai-engine' ),
					'gradient' => '-webkit-radial-gradient(left top, circle cover, var(--color-primary) 15%, var(--color-secondary) 50%, var(--color-link) 85%)',
					'slug'     => 'studio',
				],
			],
		],
	],
	'page-header'   => [
		'archive'          => '*',
		'single'           => '*',
		'image'            => '',
		'background-color' => 'primary',
		'text-color'       => 'light',
		'divider-color'    => 'white',
		'spacing'          => [
			'top'    => '5vw',
			'bottom' => '5vw',
		],
	],
];
