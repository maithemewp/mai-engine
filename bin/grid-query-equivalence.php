<?php
/**
 * Mai post grid query equivalence + EXPLAIN check (dev/verification helper).
 *
 * Proves the optimizer returns the same posts as stock WordPress for a simple-IN
 * grid, and prints the EXPLAIN for the optimized query so you can confirm the plan
 * (Backward index scan, no "Using temporary; Using filesort"). Not shipped: this is
 * a WP-CLI eval-file helper for validating the feature on a real site.
 *
 * Usage (on a real site, from the WP root):
 *   wp eval-file wp-content/plugins/mai-engine/bin/grid-query-equivalence.php category 1,94150,33844
 *
 * @package BizBudding\MaiEngine
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

global $wpdb;

$taxonomy = $args[0] ?? 'category';
$term_ids = array_values( array_filter( array_map( 'intval', explode( ',', (string) ( $args[1] ?? '' ) ) ) ) );

$base = [
	'post_type'           => 'post',
	'post_status'         => 'publish',
	'posts_per_page'      => 6,
	'ignore_sticky_posts' => true,
	'fields'              => 'ids',
	'orderby'             => 'date',
	'order'               => 'DESC',
	// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
	'tax_query'           => [
		[ 'taxonomy' => $taxonomy, 'field' => 'term_id', 'terms' => $term_ids, 'operator' => 'IN' ],
	],
];

// Stock: bypass the grid args filter entirely, so no tt_ids var is set and the
// posts_where/posts_orderby filters stay no-ops. This is the un-optimized query.
$stock = ( new WP_Query( $base ) )->posts;

// Optimized: run the args through the grid filter (maybe_optimize strips tax_query,
// sets no_found_rows, and stashes the resolved tt_ids), so the clause filters fire.
$optimized_args = apply_filters( 'mai_post_grid_query_args', $base, [] );
$optimized      = ( new WP_Query( $optimized_args ) )->posts;
$optimized_sql  = $wpdb->last_query;

WP_CLI::log( 'taxonomy:  ' . $taxonomy . '   terms: ' . implode( ',', $term_ids ) );
WP_CLI::log( 'stock:     ' . implode( ',', $stock ) );
WP_CLI::log( 'optimized: ' . implode( ',', $optimized ) );

// Compare as sets: the optimizer adds an ID tiebreaker, so order can differ on
// tied post_date values, but the set of posts must be identical.
$stock_sorted = $stock;
$opt_sorted   = $optimized;
sort( $stock_sorted );
sort( $opt_sorted );
WP_CLI::log( $stock_sorted === $opt_sorted ? 'RESULT: MATCH (set-equal)' : 'RESULT: MISMATCH' );

WP_CLI::log( '' );
WP_CLI::log( '--- EXPLAIN (optimized) ---' );
// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
foreach ( (array) $wpdb->get_results( "EXPLAIN {$optimized_sql}" ) as $row ) {
	WP_CLI::log( print_r( $row, true ) );
}
