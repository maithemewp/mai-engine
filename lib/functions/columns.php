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
 * Gets inline styles for reusable responsive columns data.
 * Margins must be handled separately because they may be
 * applied to a different container element.
 *
 * @since 2.21.0
 *
 * @param array $atts   The markup atts.
 * @param array $args   The columns args data.
 * @param bool  $nested Whether the columns are built via nested ACF blocks.
 *
 * @return string
 */
function mai_get_columns_atts( $atts, $args, $nested = false ) {
	$atts['class'] = isset( $atts['class'] ) ? $atts['class'] : '';
	$atts['style'] = isset( $atts['style'] ) ? $atts['style'] : '';

	// Columns class.
	$atts['class'] = mai_add_classes( 'has-columns', $atts['class'] );

	// Get columns arrangement.
	$columns = mai_get_breakpoint_columns( $args );

	// If preview.
	$preview = isset( $args['preview'] ) && $args['preview'];

	// Not preview.
	if ( ! $preview ) {
		// Set columns properties. Separate loops so it's more readable in the markup.
		foreach ( $columns as $break => $value ) {
			$atts['style'] .= mai_columns_get_columns( $break, $value );
		}

		// Set flex properties. Separate loops so it's more readable in the markup.
		foreach ( $columns as $break => $value ) {
			$atts['style'] .= mai_columns_get_flex( $break, $value );
		}
	}
	// Temp workaround for ACF nested block markup.
	// Can't do $nested && $args['preview'] because it broke Mai Gallery/List (not nested) inside Mai Columns (nested).
	// So we always need the explicit flex items. Boo.
	// Separate loops so it's more readable in the markup.
	else {
		foreach ( $columns as $break => $value ) {
			for ( $i = 1; $i < 24; $i++ ) {
				$atts['style'] .= mai_columns_get_columns( sprintf( '%s-%s', $break, $i ), $value );
			}

			for ( $i = 1; $i < 24; $i++ ) {
				if ( in_array( $value, [ 'auto', 'fill', 'full' ] ) ) {
					$atts['style'] .= mai_columns_get_flex( sprintf( '%s-%s', $break, $i ), $value );
				}
			}
		}
	}

	// Temp workaround for ACF nested block markup.
	if ( $nested && $preview ) {
		$atts['class'] = mai_add_classes( 'has-columns-nested', $atts['class'] );
	}

	// Column/Row gap.
	$column_gap     = isset( $args['column_gap'] ) && $args['column_gap'] && mai_is_valid_size( $args['column_gap'] ) ? sprintf( 'var(--spacing-%s)', $args['column_gap'] ) : '0px'; // Needs 0px for calc().
	$row_gap        = isset( $args['row_gap'] ) && $args['row_gap'] && mai_is_valid_size( $args['row_gap'] ) ? sprintf( 'var(--spacing-%s)', $args['row_gap'] ) : '0px'; // Needs 0px for calc().
	$atts['style'] .= sprintf( '--column-gap:%s;', $column_gap  );
	$atts['style'] .= sprintf( '--row-gap:%s;', $row_gap );

	// Align columns.
	if ( isset( $args['align_columns'] ) && $args['align_columns'] ) {
		$atts['style'] .= sprintf( '--align-columns:%s;', mai_get_flex_align( $args['align_columns'] ) );
	}

	if ( isset( $args['align_columns_vertical'] ) && $args['align_columns_vertical'] ) {
		$atts['style'] .= sprintf( '--align-columns-vertical:%s;', mai_get_flex_align( $args['align_columns_vertical'] ) );
	}

	return $atts;
}

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
 * Gets inline styles for reusable responsive columns count, fraction, or flex value.
 *
 * @access private
 *
 * @since TBD
 *
 * @param string     $break The breakpoint value. Either xs, sm, md, etc.
 * @param int|string $size The columns args data.
 *
 * @return string
 */
function mai_columns_get_columns( $break, $size ) {
	$style = '';

	if ( is_numeric( $size ) ) {
		$style .= sprintf( '--columns-%s:1/%s;', $break, $size );
	} elseif ( mai_has_string( '/', $size ) ) {
		$style .= sprintf( '--columns-%s:%s;', $break, $size );
	}

	return $style;
}

/**
 * Gets flex value from column size.
 *
 * @access private
 *
 * @since 2.10.0
 * @since TBD Added $break to stay consistent with `mai_columns_get_columns()`.
 *
 * @param string $break Either xs, sm, md, etc.
 * @param string $size
 *
 * @return string
 */
function mai_columns_get_flex( $break, $size ) {
	$style = '';
	$basis = mai_columns_get_flex_basis( $size );

	switch ( $size ) {
		case 'auto':
			// $style .= sprintf( '0 1 %s', $basis );
			$style .= sprintf( '--flex-%s:0 1 %s;', $break, $basis );
		break;
		case 'fill':
			// $style .= sprintf( '1 0 %s', $basis );
			$style .= sprintf( '--flex-%s:1 0 %s;', $break, $basis );
		break;
		case 'full':
			// $style .= sprintf( '0 0 %s', $basis );
			$style .= sprintf( '--flex-%s:0 0 %s;', $break, $basis );
		break;
		default:
			// $style .= sprintf( '0 0 %s', $basis );
			$style .= sprintf( '--flex-%s:0 0 %s;', $break, $basis );
	}

	return $style;
}

/**
 * Gets flex basis value from column size.
 *
 * Uses: `flex-basis: calc(25% - (var(--column-gap) * 3/4));`
 * This also works: `flex-basis: calc((100% / var(--columns) - ((var(--columns) - 1) / var(--columns) * var(--column-gap))));`
 * but it was easier to use the same formula with fractions. The latter formula is still used for entry columns since we can't
 * change it because it would break backwards compatibility.
 *
 * @since 2.19.0
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

	if ( in_array( $size, [ 'auto', 'fill', 'full' ] ) ) {
		switch ( $size ) {
			case 'auto':
				$all[ $size ] = 'auto';
			break;
			case 'fill':
				$all[ $size ] = '0';
			break;
			case 'full':
				$all[ $size ] = '100%';
			break;
		}

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

		// ray( $fraction );

		// Temp disable.
		// if ( false === true ) {
			// Get percent.
			// $percent = mai_fraction_to_percent( $fraction );
			// Array from fraction.
			// $array   = explode( '/', $fraction );
			// $top     = (int) (isset( $array[0] ) ? $array[0] : 1);
			// $bottom  = (int) (isset( $array[1] ) ? $array[1] : 1);
			// $top     = 0 === $top ? 1 : $top;
			// $bottom  = 0 === $bottom ? 1 : $bottom;
			// Divide fraction to get decimal.
			// $float   = $top / $bottom;
			// Trim to 6 places.
			// $float   = number_format( $float, 6, '.', '' ); // No need to do this since using the calculation before * 1000000.
			// Subtract 1 - {decimal}. Wow this was annoying. @link https://stackoverflow.com/questions/17210787/php-float-calculation-error-when-subtracting
			// $float   = bcsub( '1', (string) $float, 6 ); // Can't use this because it's not available on all hosts. @link https://stackoverflow.com/questions/63593354/undefined-function-bcsub
			// $float   = ( ( 1000000 - floor($float * 1000000) ) / 1000000 );
			// Trim trailing zeros.
			// $float   = (float) $float;
			// Converts 0.0 to 0.
			// $float   = $float > 0 ? $float : '0';

			// $all[ $size ] = sprintf( 'calc(%s - (var(--column-gap) * %s))', $percent, $float );

			// $percent = sprintf( '((%s/%s * 100) * 1%%)', $top, $bottom );
			// $percent = sprintf( '(((%s) * 100 * 1%%))', $fraction );
			// $all[ $size ] = sprintf( 'calc(((%s/%s * 100) * 1%%) - (var(--column-gap) * (1 - (%s/%s))))', $top, $bottom, $top, $bottom );
			// $all[ $size ] = 'calc((100% * var(--columns) - (var(--column-gap) * (1 - var(--columns)))) - 0.025px)';
			$all[ $size ] = 'var(--flex-basis)';

		// } else {


		// }
	}
	// This shouldn't ever happen.
	else {
		$all[ $size ] =  '0';
	}

	return $all[ $size ];
}
