<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2021 BizBudding
 * @license   GPL-2.0-or-later
 */

return [
	'demos'         => [],
	'global-styles' => [
		'colors' => [
			'background' => '#ffffff', // Body background.
			'alt'        => '#f3f3f3', // Background alt.
			'body'       => '#333333', // Body text color.
			'heading'    => '#111111', // Heading text color.
			'link'       => '#e50000', // Link color.
			'primary'    => '#999999', // Button primary background color.
			'secondary'  => '#999999', // Button secondary background color.
			'custom-1'   => '#999999'
		],
		// 'fonts'  => [
		// 	'body'    => 'Karla:400',
		// 	'heading' => 'Karla:700',
		// ],
	],
	'image-sizes' => [
		'add' => [
			'portrait' => '3:4',
			'square'   => '1:1',
		],
	],
	'custom-functions' => function() {
		add_filter( 'genesis_attr_entries-wrap', 'mai_sleek_entries_wrap_attributes', 10, 3 );
		/**
		 * Adds class to show if a grid has no content or excerpt.
		 *
		 * @param array  $attributes The markup attributes.
		 * @param string $context    The markup content context.
		 * @param array  $args       The markup args.
		 *
		 * @return array
		 */
		function mai_sleek_entries_wrap_attributes( $attributes, $context, $args ) {
			if ( ! ( isset( $args['params']['args']['show'] ) && $args['params']['args'] ) ) {
				return $attributes;
			}
			$show = array_flip( $args['params']['args']['show'] );
			if ( isset( $show['content'] ) || isset( $show['excerpt'] ) ) {
				return $attributes;
			}
			$attributes['class'] .= ' has-no-content-excerpt';
			return $attributes;
		}
	},
];
