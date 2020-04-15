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

// Disable Genesis Footer Widgets toggle setting.
add_filter( 'genesis_footer_widgets_toggle_enabled', '__return_false' );

add_action( 'genesis_before', 'mai_reposition_footer_widgets' );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_reposition_footer_widgets() {
	remove_action( 'genesis_footer', 'genesis_do_footer' );
	remove_action( 'genesis_before_footer', 'genesis_footer_widget_areas' );

	if ( ! mai_is_element_hidden( 'footer_widgets' ) ) {
		add_action( 'genesis_footer', 'genesis_footer_widget_areas', 6 );
	}
}

add_action( 'genesis_footer', 'mai_do_footer_credits', 11 );
/**
 * Output custom footer credits.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_do_footer_credits() {
	if ( mai_is_element_hidden( 'footer_credits' ) ) {
		return;
	}

	genesis_markup(
		[
			'open'    => '<div class="footer-credits"><div class="wrap"><p>',
			'context' => 'footer-credits',
		]
	);

	// phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped -- sanitized already.
	echo do_shortcode( genesis_strip_p_tags( wp_kses_post( mai_get_option( 'footer-settings-text' ) ) ) );

	genesis_markup(
		[
			'close'   => '</p></div></div>',
			'context' => 'footer-credits',
		]
	);
}
