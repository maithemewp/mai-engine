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

add_filter( 'genesis_before', 'mai_structural_wrap_hooks' );
/**
 * Add hooks before and after structural wraps.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_structural_wrap_hooks() {
	$wraps = get_theme_support( 'genesis-structural-wraps' );

	foreach ( $wraps[0] as $context ) {
		add_filter(
			"genesis_structural_wrap-{$context}",
			function ( $output, $original ) use ( $context ) {
				$position = ( 'open' === $original ) ? 'before' : 'after';
				ob_start();
				do_action( "mai_{$position}_{$context}_wrap" );
				if ( 'open' === $original ) {
					return ob_get_clean() . $output;
				} else {
					return $output . ob_get_clean();
				}
			},
			10,
			2
		);
	}
}

add_filter( 'genesis_attr_content-sidebar-wrap', 'mai_content_sidebar_wrap', 10, 1 );
/**
 * Change content-sidebar-wrap class to wrap.
 *
 * @since 0.1.0
 *
 * @param array $atts Default element attributes.
 *
 * @return mixed
 */
function mai_content_sidebar_wrap( $atts ) {
	$atts['class'] = 'wrap';

	return $atts;
}
