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

add_action( 'init', 'mai_register_social_icon_block_styles' );
/**
 * Register additional button styles.
 *
 * @since 0.3.0
 *
 * @return void
 */
function mai_register_social_icon_block_styles() {
	register_block_style(
		'core/social-links',
		[
			'name'  => 'no-background',
			'label' => __('No Background', 'mai-engine'),
		]
	);
}
