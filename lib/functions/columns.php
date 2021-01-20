<?php

/**
 * Gets formatted columns args from block settings
 * and caches value so it can be pulled use by the individual columns.
 *
 * @since TBD
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

		$cache[ $i ] = [
			'columns' => get_field( 'columns' ),
		];


		if ( 'custom' === $cache[ $i ]['columns'] ) {
			$arrangements = [
				'lg' => get_field( 'arrangement' ),
				'md' => get_field( 'arrangement_md' ),
				'sm' => get_field( 'arrangement_sm' ),
				'xs' => get_field( 'arrangement_xs' ),
			];

			foreach ( $arrangements as $break => $arrangement ) {
				$break_arrangment = [];
				foreach ( $arrangement as $columns ) {
					$cache[ $i ]['arrangements'][ $break ][] = $columns['columns'];
				}
			}

		} else {
			$cache[ $i ]['arrangements'] = mai_get_breakpoint_columns(
				[
					'columns_responsive' => false,
					'columns'            => $cache[ $i ]['columns'],
				]
			);
		}
	}

	return $cache;
}

/**
 * Gets flex value from column size.
 * If size is a percentage the default is already declared via CSS
 * so function returns false.
 *
 * @since TBD
 *
 * @param string|false $size
 *
 */
function mai_columns_get_flex( $size ) {
	if ( ! in_array( $size, [ 'auto', 'fill', 'full' ] ) ) {
		return false;
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
 * @since TBD
 *
 * @param string|int $size The size from column setting.
 *
 * @return string|false
 */
function mai_columns_get_max_width( $size ) {
	if ( mai_has_string( '/', $size ) ) {
		return mai_fraction_to_percent( $size );
	}

	if ( is_numeric( $size ) ) {
		return ( $size ? (100 / (int) $size) : '100' ) . '%';
	}

	return false;
}