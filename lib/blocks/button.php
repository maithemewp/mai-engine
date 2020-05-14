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

add_action( 'init', 'mai_register_button_styles' );
/**
 * Register additional button styles.
 *
 * @since 0.3.0
 *
 * @return void
 */
function mai_register_button_styles() {
	$styles = [
		'secondary',
		'tertiary',
	];

	foreach ( $styles as $style ) {
		register_block_style(
			'core/button',
			[
				'name'  => $style,
				'label' => mai_convert_case( $style, 'title' ),
			]
		);
	}
}
