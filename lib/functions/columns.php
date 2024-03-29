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
 * Get the columns at different breakpoints.
 *
 * @since 0.1.0
 *
 * @param array $args Column args.
 *
 * @return array
 */
function mai_get_breakpoint_columns( $args ) {
	$args = wp_parse_args(
		$args,
		[
			'columns_responsive' => false,
			'columns'            => 3,
			'columns_md'         => 1,
			'columns_sm'         => 1,
			'columns_xs'         => 1,
		]
	);

	$columns = [
		'lg' => $args['columns'],
	];

	if ( $args['columns_responsive'] ) {
		$columns['md'] = $args['columns_md'];
		$columns['sm'] = $args['columns_sm'];
		$columns['xs'] = $args['columns_xs'];
	} else {
		switch ( (int) $args['columns'] ) {
			case 6:
				$columns['md'] = 4;
				$columns['sm'] = 3;
				$columns['xs'] = 2;
			break;
			case 5:
				$columns['md'] = 3;
				$columns['sm'] = 2;
				$columns['xs'] = 2;
			break;
			case 4:
				$columns['md'] = 4;
				$columns['sm'] = 2;
				$columns['xs'] = 1;
			break;
			case 3:
				$columns['md'] = 3;
				$columns['sm'] = mai_is_type_archive() ? 2 : 1;
				$columns['xs'] = 1;
			break;
			case 2:
				$columns['md'] = 2;
				$columns['sm'] = 2;
				$columns['xs'] = 1;
			break;
			case 1:
				$columns['md'] = 1;
				$columns['sm'] = 1;
				$columns['xs'] = 1;
			break;
			case 0: // Fit/Auto.
				$columns['md'] = 0;
				$columns['sm'] = 0;
				$columns['xs'] = 0;
			break;
			default:
				$columns['md'] = 4;
				$columns['sm'] = 2;
				$columns['xs'] = 1;
		}
	}

	$columns = array_map( 'absint', $columns );

	return $columns;
}

/**
 * Gets inline styles for reusable responsive columns data.
 * Margins must be handled separately because they may be
 * applied to a different container element.
 *
 * This does not apply for Mai Column since there are custom arrangements involved.
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

	// Get columns arrangement. Reverse order so it's mobile first.
	$columns = array_reverse( mai_get_breakpoint_columns( $args ) );

	// Set columns properties. Separate loops so it's more readable in the markup.
	foreach ( $columns as $break => $value ) {
		$atts['style'] .= mai_columns_get_columns( $break, $value );
	}

	// Set flex properties. Separate loops so it's more readable in the markup.
	foreach ( $columns as $break => $value ) {
		$atts['style'] .= mai_columns_get_flex( $break, $value );
	}

	// If preview.
	$preview = isset( $args['preview'] ) && $args['preview'];

	// Workaround for ACF nested block markup.
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
 * Gets column index.
 * This determines which column/item is currently
 * being rendered inside the parent.
 *
 * @since 2.25.0
 *
 * @param string $hash  The parent hash.
 * @param bool   $reset Whether to reset the index.
 *
 * @return int
 */
function mai_column_get_index( $hash, $reset = false ) {
	static $indexes = [];

	if ( isset( $indexes[ $hash ] ) ) {
		if ( $reset ) {
			$indexes[ $hash ] = 0;
		} else {
			$indexes[ $hash ]++;
		}

		return $indexes[ $hash ];
	}

	$indexes[ $hash ] = 0;

	return $indexes[ $hash ];
}

/**
 * Gets columns arrangement whether it's custom, responsive, or default.
 *
 * @since 2.25.0
 *
 * @param array $args The columns settings args.
 *
 * @return array
 */
function mai_columns_get_arrangement( $args ) {
	static $cache = [];

	// Parse.
	$args = wp_parse_args( $args,
		[
			'columns'            => 2,
			'columns_responsive' => false,
			'columns_md'         => 2,
			'columns_sm'         => 2,
			'columns_xs'         => 2,
			'arrangement'        => [ '1/2' ],
			'arrangement_md'     => [ '1/2' ],
			'arrangement_sm'     => [ '1/2' ],
			'arrangement_xs'     => [ 'full' ],
		]
	);

	// Only the args we want, sanitized.
	$args = [
		'columns'            => is_numeric( $args['columns'] ) ? absint( $args['columns'] ): esc_html( $args['columns'] ),
		'columns_responsive' => mai_sanitize_bool( $args['columns_responsive'] ),
		'columns_md'         => absint( $args['columns_md'] ),
		'columns_sm'         => absint( $args['columns_sm'] ),
		'columns_xs'         => absint( $args['columns_xs'] ),
		'arrangement'        => mai_array_map_recursive( 'esc_html', (array) $args['arrangement'] ),
		'arrangement_md'     => mai_array_map_recursive( 'esc_html', (array) $args['arrangement_md'] ),
		'arrangement_sm'     => mai_array_map_recursive( 'esc_html', (array) $args['arrangement_sm'] ),
		'arrangement_xs'     => mai_array_map_recursive( 'esc_html', (array) $args['arrangement_xs'] ),
	];

	// Columns fix. Is this needed?
	// $args['columns'] = $args['columns'] || 0 ===  absint( $args['columns'] ) ? $args['columns'] : 2;

	// Get hash from args.
	$hash = md5( serialize( $args ) );

	// Return if already cached.
	if ( isset( $cache[ $hash ] ) ) {
		return $cache[ $hash ];
	}

	if ( 'custom' === $args['columns'] ) {

		$cache[ $hash ][ 'xs' ] = wp_list_pluck( $args['arrangement_xs'], 'columns' );
		$cache[ $hash ][ 'sm' ] = wp_list_pluck( $args['arrangement_sm'], 'columns' );
		$cache[ $hash ][ 'md' ] = wp_list_pluck( $args['arrangement_md'], 'columns' );
		$cache[ $hash ][ 'lg' ] = wp_list_pluck( $args['arrangement'], 'columns' );

	} else {

		$columns = array_reverse( mai_get_breakpoint_columns( $args ) );

		foreach ( $columns as $break => $column ) {
			// 0 is Fit for responsive columns.
			$cache[ $hash ][ $break ] = [ 0 === $column ? 'auto' : $column ];
		}
	}

	return $cache[ $hash ];
}

/**
 * Gets inline styles for reusable responsive columns count, fraction, or flex value.
 *
 * @access private
 *
 * @since 2.22.0
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
 * @since 2.22.0 Added $break to stay consistent with `mai_columns_get_columns()`.
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
			$style .= sprintf( '--flex-%s:0 1 %s;', $break, $basis );
		break;
		case 'fill':
			$style .= sprintf( '--flex-%s:1 0 %s;', $break, $basis );
		break;
		case 'full':
			$style .= sprintf( '--flex-%s:0 0 %s;', $break, $basis );
		break;
		default:
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
		$all[ $size ] = 'var(--flex-basis)';
	}
	// This shouldn't ever happen.
	else {
		$all[ $size ] =  '0';
	}

	return $all[ $size ];
}
