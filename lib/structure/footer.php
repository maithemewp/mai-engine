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

// Reposition footer widgets.
remove_action( 'genesis_footer', 'genesis_do_footer' );
remove_action( 'genesis_before_footer', 'genesis_footer_widget_areas' );
add_action( 'genesis_footer', 'genesis_footer_widget_areas', 6 );

add_action( 'genesis_footer', 'mai_before_footer_widget', 5 );
/**
 * Displays before footer widget area.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_before_footer_widget() {
	genesis_widget_area(
		'before-footer',
		[
			'before' => '<div class="before-footer"><div class="wrap">',
			'after'  => '</div></div>',
		]
	);
}

add_action( 'genesis_footer', 'mai_do_footer_credits' );
/**
 * Output custom footer credits.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_do_footer_credits() {
	genesis_markup(
		[
			'open'    => '<div class="footer-credits"><div class="wrap"><p>',
			'context' => 'footer-credits',
		]
	);

	// phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped -- sanitized already.
	echo do_shortcode( genesis_strip_p_tags( wp_kses_post( genesis_get_option( 'footer_text' ) ) ) );

	genesis_markup(
		[
			'close'   => '</p></div></div>',
			'context' => 'footer-credits',
		]
	);
}
