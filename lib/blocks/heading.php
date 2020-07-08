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

add_action( 'init', 'mai_register_heading_styles' );
/**
 * Add sub heading custom style.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_register_heading_styles() {
	if ( ! function_exists( 'register_block_style' ) ) {
		return;
	}

	register_block_style(
		'core/heading',
		[
			'name'  => 'subheading',
			'label' => __( 'Subheading', 'mai-engine' ),
		]
	);
}
