<?php
/**
 * Mai Grid Result Cache registration.
 *
 * @package BizBudding\MaiEngine
 */

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

add_action( 'init', 'mai_register_query_cache' );
/**
 * Registers the grid result cache.
 *
 * @since 2.40.0
 * @since 2.41.0 Registers the grid ID tiebreaker.
 * @since 2.41.0 Uses the shared instance, and runs its queue on shutdown.
 *
 * @return void
 */
function mai_register_query_cache() {
	$cache = Mai_Query_Cache::instance();

	add_filter( 'posts_pre_query', [ $cache, 'pre_query' ], 10, 2 );

	// Latest priority on purpose: a kept-only grid's miss is answered only once every other
	// posts_pre_query callback has had the chance to answer it first.
	add_filter( 'posts_pre_query', [ $cache, 'pre_query_kept' ], PHP_INT_MAX, 2 );

	// Earliest priority on purpose: the grid's own statement has to still be the last one run.
	add_filter( 'posts_results', [ $cache, 'posts_results' ], PHP_INT_MIN, 2 );

	add_filter( 'the_posts', [ $cache, 'the_posts' ], 10, 2 );

	// Registered for every query and left on, so an ID-only copy of a grid's query run after the
	// page gets the grid's ORDER BY. It only touches queries with the mai_grid_tiebreak var.
	// A function name, so a second call replaces this entry rather than adding another.
	add_filter( 'posts_orderby', 'mai_add_grid_orderby_tiebreaker', 99, 2 );

	// Latest priority on purpose: after WordPress flushes the output buffers (priority 1), and
	// after a page cache plugin has saved the page from its output buffer callback.
	add_action( 'shutdown', [ $cache, 'run_queue' ], PHP_INT_MAX );

	add_action( 'transition_post_status', [ $cache, 'on_transition' ], 10, 3 );
	add_action( 'deleted_post', [ $cache, 'on_delete' ], 10, 2 );

	if ( defined( 'WP_CLI' ) && WP_CLI && class_exists( 'WP_CLI' ) ) {
		WP_CLI::add_hook( 'after_invoke:cache flush', [ $cache, 'flush_all' ] );
	}
}

/**
 * Appends the grid ID tiebreaker to a query's ORDER BY, when the query asks for it.
 *
 * Runs on every filtered query, so it checks the mai_grid_tiebreak query var first and only
 * then loads Mai_Grid. A query without the var never loads Mai_Grid, so a page with no post grid
 * does not pay for the class on every filtered query.
 *
 * Another plugin's posts_orderby filter can hand down any value, so anything that is not a string,
 * or a query that is not a WP_Query, comes back unchanged.
 *
 * @since 2.41.0
 *
 * @param mixed $orderby The ORDER BY clause.
 * @param mixed $query   The query.
 *
 * @return mixed
 */
function mai_add_grid_orderby_tiebreaker( mixed $orderby, mixed $query ): mixed {
	if ( ! is_string( $orderby ) || ! $query instanceof WP_Query ) {
		return $orderby;
	}

	if ( empty( $query->query_vars['mai_grid_tiebreak'] ) ) {
		return $orderby;
	}

	return Mai_Grid::add_grid_orderby_tiebreaker( $orderby, $query );
}
