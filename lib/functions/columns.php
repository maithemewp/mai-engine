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
 * @since 2.10.0
 *
 * @param string|int $size The size from column setting.
 *
 * @return string
 */
function mai_columns_get_flex_basis( $size ) {
	// flex-basis: calc((100% / var(--columns) - ((var(--columns) - 1) / var(--columns) * var(--column-gap))));
	if ( is_numeric( $size ) ) {
		$size = (int) $size;
		return sprintf( 'calc((100%%/%s - (%s/%s * var(--column-gap))))', $size, $size - 1, $size );
	}

	// flex-basis: calc(25% - (24px * 3/4));
	if ( mai_has_string( '/', $size ) ) {
		// Get percent.
		$percent = mai_fraction_to_percent( $size );
		// Array from fraction.
		$array   = explode( '/', $size );
		// Divide fractin to get decimal.
		$float   = (isset( $array[0] ) ? $array[0] : 1) / (isset( $array[1] ) ? $array[1] : 1);
		// Subtract 1 - decimal. Wow this was annoying. @link https://stackoverflow.com/questions/17210787/php-float-calculation-error-when-subtracting
		$float   = bcsub( '1', (string) $float, 6 );
		// Trim trailing zeros.
		$float   = (float) $float;

		return sprintf( 'calc(%s - (var(--column-gap) * %s))', $percent, $float );
	}

	// If fill or full?
	if ( 'auto' !== $size ) {
		return '100%';
	}

	return 'unset';
}
