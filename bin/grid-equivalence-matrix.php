<?php
/**
 * Mai post grid optimizer equivalence MATRIX (dev/verification helper, not shipped).
 *
 * Runs a set of real grid configurations through stock WP_Query vs the optimizer and,
 * for each, reports: whether the optimizer activated, whether the rendered window
 * (first N posts in deterministic date+ID order, i.e. what a grid actually shows) is
 * identical, the result size (and whether the window is the full set), and whether the
 * optimized plan avoids "Using temporary; Using filesort".
 *
 * Usage (on a real site, from WP root):
 *   wp eval-file wp-content/plugins/mai-engine/bin/grid-equivalence-matrix.php
 *
 * The term/tag/meta IDs are totalprosports-specific; edit for another site.
 *
 * @package BizBudding\MaiEngine
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

global $wpdb;

$cat_nfl     = 6;      // category: NFL (parent term -> exercises include_children).
$cat_cowboys = 44576;  // category: Dallas Cowboys (leaf term).
$tag_qb      = 44529;  // post_tag: NFL Quarterbacks (a second, different taxonomy).
$meta        = 'mai_views';
$after       = '2025-01-01';
$n           = 100;

$base = function ( array $extra = [] ) use ( $n ) {
	return array_merge(
		[
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => $n,
			'fields'              => 'ids',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => [ 'date' => 'DESC', 'ID' => 'DESC' ],
		],
		$extra
	);
};

$cat_in = function ( $ids ) {
	return [ [ 'taxonomy' => 'category', 'field' => 'term_id', 'terms' => (array) $ids, 'operator' => 'IN' ] ];
};

// A few recent NFL posts to exercise post__not_in (exclude) coexistence.
$recent = ( new WP_Query( $base( [ 'tax_query' => $cat_in( $cat_nfl ), 'posts_per_page' => 5 ] ) ) )->posts;

$two_tax = function ( $relation ) use ( $cat_nfl, $tag_qb ) {
	return [
		'relation' => $relation,
		[ 'taxonomy' => 'category', 'field' => 'term_id', 'terms' => [ $cat_nfl ], 'operator' => 'IN' ],
		[ 'taxonomy' => 'post_tag', 'field' => 'term_id', 'terms' => [ $tag_qb ], 'operator' => 'IN' ],
	];
};

$configs = [
	'single-IN (NFL, incl children)'   => $base( [ 'tax_query' => $cat_in( $cat_nfl ) ] ),
	'single-IN leaf (Cowboys)'         => $base( [ 'tax_query' => $cat_in( $cat_cowboys ) ] ),
	'single-IN + meta EXISTS'          => $base( [ 'tax_query' => $cat_in( $cat_nfl ), 'meta_query' => [ [ 'key' => $meta, 'compare' => 'EXISTS' ] ] ] ),
	'single-IN + date after 2025'      => $base( [ 'tax_query' => $cat_in( $cat_nfl ), 'date_query' => [ [ 'after' => $after ] ] ] ),
	'single-IN + meta + date'          => $base( [ 'tax_query' => $cat_in( $cat_nfl ), 'meta_query' => [ [ 'key' => $meta, 'compare' => 'EXISTS' ] ], 'date_query' => [ [ 'after' => $after ] ] ] ),
	'single-IN + exclude (not_in)'     => $base( [ 'tax_query' => $cat_in( $cat_nfl ), 'post__not_in' => $recent ] ),
	'single-IN orderby meta_value_num' => $base( [ 'tax_query' => $cat_in( $cat_nfl ), 'meta_key' => $meta, 'orderby' => [ 'meta_value_num' => 'DESC', 'ID' => 'DESC' ] ] ),
	'multi-tax AND (cat + tag)'        => $base( [ 'tax_query' => $two_tax( 'AND' ) ] ),
	'multi-tax OR (cat + tag)'         => $base( [ 'tax_query' => $two_tax( 'OR' ) ] ),
	'NOT IN (category != NFL)'         => $base( [ 'tax_query' => [ [ 'taxonomy' => 'category', 'field' => 'term_id', 'terms' => [ $cat_nfl ], 'operator' => 'NOT IN' ] ] ] ),
];

WP_CLI::log( sprintf( '%-34s | %-4s | %-5s | %-13s | %s', 'CONFIG', 'opt?', 'match', 'size', 'optimized plan' ) );
WP_CLI::log( str_repeat( '-', 88 ) );

foreach ( $configs as $label => $args ) {
	// Stock: no grid filter, so the optimizer's posts_where/posts_orderby filters no-op.
	$stock = ( new WP_Query( $args ) )->posts;

	// Optimized: run the args through the grid filter so maybe_optimize can rewrite.
	$opt_args  = apply_filters( 'mai_post_grid_query_args', $args, [] );
	$activated = ! isset( $opt_args['tax_query'] ) && isset( $opt_args['mai_post_grid_tt_ids'] );
	$optimized = ( new WP_Query( $opt_args ) )->posts;
	$opt_sql   = $wpdb->last_query;

	$match = ( $stock === $optimized );
	$size  = count( $stock );
	$cover = $size < $n ? 'full set' : "first $n";

	$plan = '?';
	$rows = $wpdb->get_results( "EXPLAIN {$opt_sql}", ARRAY_A ); // phpcs:ignore
	if ( $rows ) {
		$extra = strtolower( implode( ' ', array_map( function ( $r ) { return (string) ( $r['Extra'] ?? '' ); }, $rows ) ) );
		$plan  = ( false !== strpos( $extra, 'temporary' ) || false !== strpos( $extra, 'filesort' ) ) ? 'temp/filesort' : 'clean scan';
	}

	WP_CLI::log( sprintf(
		'%-34s | %-4s | %-5s | %4d (%-8s) | %s',
		$label,
		$activated ? 'YES' : 'no',
		$match ? 'OK' : 'DIFF',
		$size,
		$cover,
		$plan
	) );

	if ( ! $match ) {
		$only_stock = array_slice( array_values( array_diff( $stock, $optimized ) ), 0, 5 );
		$only_opt   = array_slice( array_values( array_diff( $optimized, $stock ) ), 0, 5 );
		WP_CLI::log( sprintf( '    DIFF: stock=%d opt=%d | only-in-stock: %s | only-in-opt: %s',
			count( $stock ), count( $optimized ), implode( ',', $only_stock ), implode( ',', $only_opt ) ) );
	}
}
