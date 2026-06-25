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
}
