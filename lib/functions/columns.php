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

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * Gets formatted columns args from block settings
 * and caches value so it can be pulled use by the individual columns.
 *
 * @since 2.10.0
 *
 * @param int $i The columns block instance.
 *
 * @return array
 */
function mai_columns_get_args( $i = null ) {
	static $cache = [];

	if ( ! is_null( $i ) ) {
		if ( isset( $cache[ $i ] ) ) {
			return $cache[ $i ];
		}

		$columns = get_field( 'columns' );
		$columns = ( $columns || '0' === $columns ) ? $columns : 2;

		$cache[ $i ] = [
			'columns' => $columns,
		];

		if ( 'custom' === $cache[ $i ]['columns'] ) {
			$arrangements = [
				'lg' => get_field( 'arrangement' ),
				'md' => get_field( 'arrangement_md' ),
				'sm' => get_field( 'arrangement_sm' ),
				'xs' => get_field( 'arrangement_xs' ),
			];

			foreach ( $arrangements as $break => $arrangement ) {
				foreach ( $arrangement as $columns ) {
					if ( isset( $columns['columns'] ) ) {
						$cache[ $i ]['arrangements'][ $break ][] = $columns['columns'];
					}
				}
			}

		} else {

			$columns = mai_get_breakpoint_columns(
				[
					'columns_responsive' => false,
					'columns'            => $cache[ $i ]['columns'],
				]
			);

			foreach ( $columns as $break => $column ) {
				$cache[ $i ]['arrangements'][ $break ] = 0 === $column ? 'auto' : $column;
			}
		}
	}

	return $cache;
}

/**
 * Gets flex value from column size.
 * If size is a percentage the default is already declared via CSS
 * so function returns false.
 *
 * @since 2.10.0
 *
 * @param string $size
 *
 */
function mai_columns_get_flex( $size ) {
	if ( ! in_array( $size, [ 'auto', 'fill', 'full' ] ) ) {
		return sprintf( '0 0 %s', mai_columns_get_flex_basis( $size ) );
	}

	switch ( $size ) {
		case 'auto':
			return '0 1 auto';
		break;
		case 'fill':
			return '1 0 0';
		break;
		case 'full':
			return '0 0 100%';
		break;
	}
}

/**
 * Gets max width value from column size.
 *
 * Uses: `flex-basis: calc(25% - (var(--column-gap) * 3/4));`
 * This also works: `flex-basis: calc((100% / var(--columns) - ((var(--columns) - 1) / var(--columns) * var(--column-gap))));`
 * but it was easier to use the same formula with fractions. The latter formula is sitll used for entry columns since we can't
 * change it because it would break backwards compatibility.
 *
 * @since TBD
 *
 * @param string|int $size The size from column setting. Either a fraction `1/3` or an integer `3`.
 *
 * @return string
 */
function mai_columns_get_flex_basis( $size ) {
	static $all = [];

	if ( isset( $all[ $size ] ) ) {
		return $all[ $size ];
	}

	$fraction = false;

	if ( is_numeric( $size ) ) {
		$size     = (int) $size;
		$fraction = sprintf( '1/%s', $size );
	} elseif ( mai_has_string( '/', $size ) ) {
		$fraction = $size;
	}

	// Set columns.
	if ( $fraction ) {
		// Get percent.
		$percent = mai_fraction_to_percent( $fraction );
		// Array from fraction.
		$array   = explode( '/', $fraction );
		// Divide fractin to get decimal.
		$float   = (isset( $array[0] ) ? $array[0] : 1) / (isset( $array[1] ) ? $array[1] : 1);
		// Subtract 1 - decimal. Wow this was annoying. @link https://stackoverflow.com/questions/17210787/php-float-calculation-error-when-subtracting
		$float   = bcsub( '1', (string) $float, 6 );
		// Trim trailing zeros.
		$float   = (float) $float;

		$all[ $size ] = sprintf( 'calc(%s - (var(--column-gap) * %s))', $percent, $float );
	}
	// This shouldn't ever happen.
	else {
		$all[ $size ] =  '0';
	}

	return $all[ $size ];
}
