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
 *
 * @return void
 */
function mai_register_query_cache() {
	$cache = new Mai_Query_Cache();

	add_filter( 'posts_pre_query', [ $cache, 'pre_query' ], 10, 2 );

	// Latest priority on purpose: a kept-only grid's miss is answered only once every other
	// posts_pre_query callback has had the chance to answer it first.
	add_filter( 'posts_pre_query', [ $cache, 'pre_query_kept' ], PHP_INT_MAX, 2 );

	// Earliest priority on purpose: the grid's own statement has to still be the last one run.
	add_filter( 'posts_results', [ $cache, 'posts_results' ], PHP_INT_MIN, 2 );

	add_filter( 'the_posts', [ $cache, 'the_posts' ], 10, 2 );

	add_action( 'transition_post_status', [ $cache, 'on_transition' ], 10, 3 );
	add_action( 'deleted_post', [ $cache, 'on_delete' ], 10, 2 );

	if ( defined( 'WP_CLI' ) && WP_CLI && class_exists( 'WP_CLI' ) ) {
		WP_CLI::add_hook( 'after_invoke:cache flush', [ $cache, 'flush_all' ] );
	}
}
