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
	if ( ! $wraps ) {
		return;
	}
	foreach ( $wraps[0] as $context ) {
		add_filter( "genesis_structural_wrap-{$context}", function ( $output, $original ) use ( $context ) {
			$position = ( 'open' === $original ) ? 'before' : 'after';
			ob_start();
			do_action( "mai_{$position}_{$context}_wrap" );
			if ( 'open' === $original ) {
				return ob_get_clean() . $output;
			} else {
				return $output . ob_get_clean();
			}
		}, 10, 2 );
	}
}

add_filter( 'genesis_structural_wrap-header', 'mai_site_header_row_class', 10, 2 );
/**
 * Convert to site-header-wrap class.
 *
 * @since 2.0.0
 *
 * @param string $output   The wrap HTML.
 * @param string $original Whether it's open or closing wrap.
 *
 * @return string
 */
function mai_site_header_row_class( $output, $original ) {
	if ( 'open' === $original ) {
		$output = str_replace( 'class="wrap', 'class="site-header-wrap', $output );
	}

	return $output;
}

add_filter( 'genesis_markup_site-header_close', 'mai_header_spacer', 10, 2 );
/**
 * Add header spacer element.
 *
 * @since 2.7.0
 *
 * @param string $close HTML tag being processed by the API.
 * @param array  $args  Array with markup arguments.
 *
 * @return string
 */
function mai_header_spacer( $close, $args ) {
	if ( ! $args['close'] ) {
		return $close;
	}

	return $close . '<span class="header-spacer"></span>';
}
