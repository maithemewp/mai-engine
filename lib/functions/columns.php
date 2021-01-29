<?php

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

		$cache[ $i ] = [
			'columns' => $columns ?: 2,
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
					$cache[ $i ]['arrangements'][ $break ][] = $columns['columns'];
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
		return '1 0 100%';
	}

	switch ( $size ) {
		case 'auto':
			return '0 1 auto';
		break;
		case 'fill':
			return '1 0 auto';
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
function mai_columns_get_max_width( $size ) {
	if ( mai_has_string( '/', $size ) ) {
		return mai_fraction_to_percent( $size );
	}

	if ( is_numeric( $size ) ) {
		return ( $size ? (100 / (int) $size) : '100' ) . '%';
	}

	return '100%';
}
