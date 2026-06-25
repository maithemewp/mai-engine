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
		// Broad win: drop SQL_CALC_FOUND_ROWS for every grid that does not need a total
		// (plain "latest", post__not_in, tax, all of them). The count pass forces a full
		// scan of all matching posts ignoring LIMIT, and almost no grid needs it. Gated by
		// the opt-in so load-more and numbered pagination can keep the count.
		if ( ! apply_filters( 'mai_post_grid_found_rows', false, $args ) ) {
			$query_args['no_found_rows'] = true;
		}

		// Targeted win: rewrite the simple single-IN tax filter into a scalar subquery.
		$clause = $this->get_simple_in_clause( $query_args );

		if ( null === $clause ) {
			return $query_args; // not the simple-IN shape; tax stays stock, no_found_rows still applied above.
		}

		$tt_ids = $this->resolve_tt_ids( $clause['taxonomy'], (array) $clause['terms'] );

		if ( ! $tt_ids ) {
			return $query_args; // could not resolve; tax stays stock.
		}

		// Remove the tax_query so WordPress emits no JOIN or GROUP BY; we add our own clause.
		unset( $query_args['tax_query'] );
		$query_args[ self::TT_IDS_VAR ] = $tt_ids;

		return $query_args;
	}

	/**
	 * Resolve term ids to term_taxonomy_ids, including child terms, mirroring the
	 * WP_Tax_Query include_children default for IN queries.
	 *
	 * @return int[]
	 */
	private function resolve_tt_ids( string $taxonomy, array $term_ids ): array {
		$term_ids = array_map( 'intval', $term_ids );

		if ( is_taxonomy_hierarchical( $taxonomy ) ) {
			foreach ( $term_ids as $term_id ) {
				$children = get_term_children( $term_id, $taxonomy );
				if ( ! is_wp_error( $children ) ) {
					$term_ids = array_merge( $term_ids, array_map( 'intval', $children ) );
				}
			}
			$term_ids = array_values( array_unique( $term_ids ) );
		}

		$tt_ids = [];
		foreach ( $term_ids as $term_id ) {
			$term = get_term( $term_id, $taxonomy );
			if ( $term && ! is_wp_error( $term ) ) {
				$tt_ids[] = (int) $term->term_taxonomy_id;
			}
		}

		return array_values( array_unique( $tt_ids ) );
	}

	public function add_subquery_where( string $where, $query ): string {
		$tt_ids = $query->query_vars[ self::TT_IDS_VAR ] ?? null;

		if ( ! is_array( $tt_ids ) || ! $tt_ids ) {
			return $where;
		}

		global $wpdb;
		$ids = implode( ',', array_map( 'intval', $tt_ids ) );

		// Correlated scalar subquery. Not eligible for the semi-join transform, so MySQL
		// keeps it correlated, drives from posts in date order, and stops at LIMIT.
		$where .= " AND ( (SELECT mtr.object_id FROM {$wpdb->term_relationships} mtr"
			. " WHERE mtr.object_id = {$wpdb->posts}.ID AND mtr.term_taxonomy_id IN ({$ids})"
			. ' LIMIT 1) IS NOT NULL )';

		return $where;
	}

	public function add_orderby_tiebreaker( string $orderby, $query ): string {
		$tt_ids = $query->query_vars[ self::TT_IDS_VAR ] ?? null;

		if ( ! is_array( $tt_ids ) || ! $tt_ids ) {
			return $orderby;
		}

		global $wpdb;

		// Already has an ID tiebreaker, or not ordering by date: leave it.
		if ( str_contains( $orderby, "{$wpdb->posts}.ID" ) ) {
			return $orderby;
		}

		if ( ! preg_match( '/' . preg_quote( $wpdb->posts, '/' ) . '\.post_date\s+(ASC|DESC)/i', $orderby, $m ) ) {
			return $orderby;
		}

		// Match the primary direction so it stays a single index scan.
		return $orderby . ", {$wpdb->posts}.ID " . strtoupper( $m[1] );
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
