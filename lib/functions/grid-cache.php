<?php
/**
 * Mai Grid Result Cache registration.
 *
 * @package BizBudding\MaiEngine
 */

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

add_action( 'init', 'mai_register_grid_cache' );
/**
 * Registers the grid result cache.
 *
 * @since 2.40.0
 *
 * @return void
 */
function mai_register_grid_cache() {
	$cache = new Mai_Grid_Cache();

	add_filter( 'posts_pre_query', [ $cache, 'pre_query' ], 10, 2 );
	add_filter( 'the_posts', [ $cache, 'the_posts' ], 10, 2 );

	add_action( 'transition_post_status', [ $cache, 'on_transition' ], 10, 3 );
	add_action( 'deleted_post', [ $cache, 'on_delete' ], 10, 2 );

	if ( defined( 'WP_CLI' ) && WP_CLI && class_exists( 'WP_CLI' ) ) {
		WP_CLI::add_hook( 'after_invoke:cache flush', [ $cache, 'flush_all' ] );
	}
}
