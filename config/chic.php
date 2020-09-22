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
	'demos'            => [
		// 'default' => 51,
	],
	'global-styles'    => [
		'colors' => [
			'link'           => '#b0cdbd',
			'primary'        => '#b0cdbd',
			'secondary'      => '#f5f5f5',
			'heading'        => '#173821',
			'body'           => '#173821',
			'alt'            => '#f5f5f5',
			'custom-color-1' => '#545454',
		],
		'fonts'  => [
			'body'        => 'Lato:400',
			'heading'     => 'Lato:700',
			'entry-title' => 'Playfair Display:400',
		],
	],
	'image-sizes'         => [
		'add' => [
			'landscape' => '16:9',
			'square'    => '1:1',
		],
	],
	'custom-functions' => function() {
		add_action( 'init', function() {
			/**
			 * Register block style
			 */
			register_block_style(
				'core/heading',
				array(
					'name'         => 'boxed-heading',
					'label'        => __( 'Boxed Heading', 'mai-engine' ),
					// 'style_handle' => 'prefix-stylesheet',
				)
			);
		});
	},
];
