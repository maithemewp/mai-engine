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
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_register_button_styles() {
	$styles = [
		'secondary',
		'tertiary',
		'white',
		'white-outline',
		'ghost',
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
