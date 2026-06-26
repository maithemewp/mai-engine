<?php
/**
 * Mai grid cache benefit measurement (dev/verification helper, not shipped).
 *
 * Validates the premise of the grid result cache: that the cache-hit path (a post__in
 * fetch of known IDs) is materially cheaper than the filtering query it replaces. For a
 * few real configs it times the actual grid query (run through mai_post_grid_query_args,
 * so the optimizer/EP transforms apply) vs a post__in fetch of the same result IDs, and
 * prints the speedup. fields=ids isolates the matching cost; the per-hit Redis GET that
 * precedes the post__in is sub-millisecond and not modeled here.
 *
 * Usage (from WP root):
 *   wp eval-file wp-content/plugins/mai-engine/bin/grid-cache-benefit.php
 *
 * Term/tag/meta IDs are totalprosports-specific; edit for another site.
 *
 * @package BizBudding\MaiEngine
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

$cat_nfl = 6;
$tag_qb  = 44529;
$meta    = 'mai_views';
$after   = '2025-01-01';
$n       = 12;   // a realistic grid size.
$iters   = 7;    // a few warm iterations; report median + min.

$timeit = function ( array $args, int $iters ) {
	$times = [];
	$ids   = [];
	for ( $i = 0; $i < $iters; $i++ ) {
		$t       = microtime( true );
		$q       = new WP_Query( $args );
		$times[] = ( microtime( true ) - $t ) * 1000.0;
		$ids     = $q->posts;
	}
	sort( $times );
	return [ 'median' => $times[ intdiv( count( $times ), 2 ) ], 'min' => $times[0], 'ids' => $ids ];
};

$base = [
	'post_type'           => 'post',
	'post_status'         => 'publish',
	'posts_per_page'      => $n,
	'fields'              => 'ids',
	'ignore_sticky_posts' => true,
	'no_found_rows'       => false, // measure the pagination-count cost (SQL_CALC_FOUND_ROWS).
	'orderby'             => [ 'date' => 'DESC', 'ID' => 'DESC' ],
];

$cat_in = function ( $id ) {
	return [ [ 'taxonomy' => 'category', 'field' => 'term_id', 'terms' => (array) $id, 'operator' => 'IN' ] ];
};

$configs = [
	'single-IN (NFL)' => array_merge( $base, [ 'tax_query' => $cat_in( $cat_nfl ) ] ),
	'multi-tax AND'   => array_merge( $base, [ 'tax_query' => [ 'relation' => 'AND',
		[ 'taxonomy' => 'category', 'field' => 'term_id', 'terms' => [ $cat_nfl ], 'operator' => 'IN' ],
		[ 'taxonomy' => 'post_tag', 'field' => 'term_id', 'terms' => [ $tag_qb ], 'operator' => 'IN' ],
	] ] ),
	'meta + date'     => array_merge( $base, [
		'tax_query'  => $cat_in( $cat_nfl ),
		'meta_query' => [ [ 'key' => $meta, 'compare' => 'EXISTS' ] ],
		'date_query' => [ [ 'after' => $after ] ],
	] ),
];

WP_CLI::log( sprintf( '%-16s | %-16s | %-16s | %s', 'config', 'filter ms (med/min)', 'post__in ms (med/min)', 'speedup (median)' ) );
WP_CLI::log( str_repeat( '-', 78 ) );

foreach ( $configs as $label => $args ) {
	$filter_args = apply_filters( 'mai_post_grid_query_args', $args, [] ); // optimizer/EP transforms apply.
	$f           = $timeit( $filter_args, $iters );
	$ids         = $f['ids'];

	if ( ! $ids ) {
		WP_CLI::log( sprintf( '%-16s | no results', $label ) );
		continue;
	}

	$pin_args = array_merge( $base, [ 'post__in' => $ids, 'orderby' => 'post__in', 'ep_integrate' => false, 'no_found_rows' => true ] );
	$p        = $timeit( $pin_args, $iters );
	$speedup  = $p['median'] > 0 ? round( $f['median'] / $p['median'], 1 ) . 'x' : 'n/a';

	WP_CLI::log( sprintf(
		'%-16s | %6.2f / %-6.2f   | %6.2f / %-6.2f   | %s',
		$label, $f['median'], $f['min'], $p['median'], $p['min'], $speedup
	) );
}
