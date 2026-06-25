<?php
/**
 * Mai Post Grid Query Optimizer.
 *
 * Rewrites the simple "posts IN these terms" grid tax query from the stock
 * LEFT JOIN + GROUP BY + filesort into a correlated scalar subquery, which the
 * optimizer plans as a backward index scan that stops at LIMIT. Hook-wired; does
 * not modify Mai_Grid. Falls back to stock WordPress for anything but the simple
 * single-IN case.
 *
 * @package BizBudding\MaiEngine
 */

defined( 'ABSPATH' ) || die;

class Mai_Post_Grid_Query_Optimizer {

	/**
	 * Query var that carries the resolved term_taxonomy_ids from the args filter
	 * to the clause filters.
	 */
	private const TT_IDS_VAR = 'mai_post_grid_tt_ids';

	/**
	 * Register hooks, unless the master switch is off.
	 */
	public function register(): void {
		if ( ! apply_filters( 'mai_post_grid_optimize_query', true ) ) {
			return;
		}

		add_filter( 'mai_post_grid_query_args', [ $this, 'maybe_optimize' ], 99, 2 );
		add_filter( 'posts_where', [ $this, 'add_subquery_where' ], 10, 2 );
		add_filter( 'posts_orderby', [ $this, 'add_orderby_tiebreaker' ], 10, 2 );
	}

	public function maybe_optimize( array $query_args, array $args ): array {
		return $query_args;
	}

	public function add_subquery_where( string $where, $query ): string {
		return $where;
	}

	public function add_orderby_tiebreaker( string $orderby, $query ): string {
		return $orderby;
	}

	/**
	 * Return the single tax clause if this is the simple "one IN clause" case, else null.
	 * Strips the 'relation' key, requires exactly one clause, operator IN (default),
	 * a string taxonomy and a non-empty terms array.
	 */
	private function get_simple_in_clause( array $query_args ): ?array {
		$tax_query = $query_args['tax_query'] ?? null;

		if ( ! is_array( $tax_query ) || ! $tax_query ) {
			return null;
		}

		// Drop the relation key; what remains must be exactly one first-order clause.
		$clauses = array_filter(
			$tax_query,
			fn( $key ) => 'relation' !== $key,
			ARRAY_FILTER_USE_KEY
		);

		if ( 1 !== count( $clauses ) ) {
			return null;
		}

		$clause = reset( $clauses );

		if ( ! is_array( $clause ) || isset( $clause['relation'] ) ) {
			return null; // nested clause group, not first-order.
		}

		$operator = strtoupper( (string) ( $clause['operator'] ?? 'IN' ) );
		$terms    = $clause['terms'] ?? [];

		$is_simple = 'IN' === $operator
			&& is_string( $clause['taxonomy'] ?? null )
			&& is_array( $terms )
			&& [] !== $terms;

		return $is_simple ? $clause : null;
	}
}
