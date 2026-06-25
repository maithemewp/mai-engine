<?php
/**
 * Mai Engine Kirki CSS cache helpers.
 *
 * @package   BizBudding\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright (c) BizBudding
 * @license   GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

/**
 * Deep-merges mai's cached additions onto Kirki's per-context styles array, base-first.
 *
 * Kirki's own keys keep their positions; keys present only in $additions are appended;
 * mai wins on a leaf conflict (the old in-place pipeline overwrote). mai's :root custom
 * properties therefore all land together in the single global > :root block. Pure helper
 * (no WordPress), kept in its own file so it can be unit-tested in isolation.
 *
 * @since 2.40.0
 *
 * @param array $base      Kirki's styles array for the current context.
 * @param array $additions mai's settings-derived additions.
 *
 * @return array
 */
function mai_merge_kirki_css( array $base, array $additions ) {
	foreach ( $additions as $key => $value ) {
		if ( is_array( $value ) && isset( $base[ $key ] ) && is_array( $base[ $key ] ) ) {
			$base[ $key ] = mai_merge_kirki_css( $base[ $key ], $value );
		} else {
			$base[ $key ] = $value;
		}
	}

	return $base;
}
