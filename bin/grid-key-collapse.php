<?php
/**
 * Counts how many distinct grid cache keys a run of real articles produces, with deferring on
 * and off. Run with:
 *   wp --path=<site> eval-file bin/grid-key-collapse.php
 *
 * Lab tool. It says nothing about what any other site has installed.
 */

$sample_size = 300;

$ids = $GLOBALS['wpdb']->get_col(
	"SELECT ID FROM {$GLOBALS['wpdb']->posts} WHERE post_type='post' AND post_status='publish' ORDER BY post_date DESC"
);

$step   = max( 1, (int) floor( count( $ids ) / $sample_size ) );
$sample = [];

for ( $i = 0; $i < count( $ids ) && count( $sample ) < $sample_size; $i += $step ) {
	$sample[] = (int) $ids[ $i ];
}

// Capture the key the same way Mai_Query_Cache::pre_query() computes it: during the query,
// from query_vars, before get_query() restores them.
$key     = '';
$capture = static function ( $posts, $query ) use ( &$key ) {
	$key = ( new Mai_Query_Cache() )->cache_key( $query->query_vars, (string) $query->request );

	return $posts;
};

add_filter( 'posts_pre_query', $capture, 9, 2 );

foreach ( [ 'deferred' => null, 'today' => '__return_false' ] as $label => $off ) {
	if ( $off ) {
		add_filter( 'mai_post_grid_defer_excludes', $off );
	}

	$keys = [];

	foreach ( $sample as $id ) {
		$cats = wp_get_post_terms( $id, 'category', [ 'fields' => 'ids' ] );

		if ( is_wp_error( $cats ) || ! $cats ) {
			continue;
		}

		// exclude_current is gated on is_singular(), which is false under WP-CLI unless the
		// main query is set up. Without this the two runs are identical and measure nothing.
		//
		// Guard the have_posts(). next_post() indexes $this->posts unconditionally, so on an
		// empty result it warns and leaves $GLOBALS['post'] pointing at the PREVIOUS article,
		// which would silently measure this iteration against the wrong exclusion.
		$previous            = $GLOBALS['wp_query'];
		$GLOBALS['wp_query'] = new WP_Query( [ 'p' => $id, 'post_type' => 'post' ] );

		if ( ! $GLOBALS['wp_query']->have_posts() ) {
			$GLOBALS['wp_query'] = $previous;
			continue;
		}

		$GLOBALS['wp_query']->the_post();

		$key = '';

		( new Mai_Grid( [
			'type'           => 'post',
			'post_type'      => [ 'post' ],
			'query_by'       => 'tax_meta',
			'posts_per_page' => 6,
			'excludes'       => [ 'exclude_current' ],
			'taxonomies'     => [ [ 'taxonomy' => 'category', 'terms' => $cats, 'current' => false, 'operator' => 'IN' ] ],
		] ) )->get_query();

		wp_reset_postdata();

		$GLOBALS['wp_query'] = $previous;

		if ( '' !== $key ) {
			$keys[] = $key;
		}
	}

	if ( $off ) {
		remove_filter( 'mai_post_grid_defer_excludes', $off );
	}

	printf( "%-9s: %d articles -> %d distinct cache keys\n", $label, count( $keys ), count( array_unique( $keys ) ) );
}

remove_filter( 'posts_pre_query', $capture, 9 );
