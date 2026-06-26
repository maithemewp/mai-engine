<?php
/**
 * Mai Grid Result Cache.
 *
 * Caches resolved grid query results (ordered post IDs + found count) under a version
 * token we control (via the mai-cache SWR primitive), with stable keys and
 * stale-while-revalidate, so grids survive the last_changed churn that defeats core's
 * query caching on write-heavy sites. Activated per query by the `mai_cache` query var.
 *
 * @package BizBudding\MaiEngine
 */

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

// HOUR_IN_SECONDS is defined by WordPress; guard for unit-test environments that load
// this file without booting WP.
defined( 'HOUR_IN_SECONDS' ) || define( 'HOUR_IN_SECONDS', 3600 );

class Mai_Grid_Cache {

	/**
	 * mai-cache group. Transient mode: Redis when present, wp_options otherwise.
	 */
	private const GROUP = 'grid';

	/**
	 * Default TTL backstop (filterable per grid via `mai_post_grid_cache_ttl`).
	 */
	private const TTL = 4 * HOUR_IN_SECONDS;

	/**
	 * Query vars that do not change which posts are returned, removed before hashing.
	 * Mirrors WP_Query::generate_cache_key().
	 */
	private const VOLATILE = [
		'cache_results',
		'fields',
		'update_post_meta_cache',
		'update_post_term_cache',
		'update_menu_item_cache',
		'lazy_load_term_meta',
		'suppress_filters',
		'mai_cache',
	];

	/**
	 * Order-insensitive id arrays, sorted before hashing.
	 */
	private const SORTABLE = [ 'post__in', 'post_parent__in', 'post_name__in' ];

	/**
	 * Build a stable cache key from the query vars and final SQL.
	 *
	 * The volatile-var denylist and the post_type / post__in normalization below are
	 * copied from WordPress core's WP_Query::generate_cache_key() (wp-includes/class-wp-query.php,
	 * verified against WP 6.x/7.0). Kept in sync by hand: if core changes how it derives its
	 * query cache key, re-check this method against it to avoid drift.
	 *
	 * @param array  $query_vars The WP_Query vars.
	 * @param string $sql        The final SQL request.
	 *
	 * @return string
	 */
	public function cache_key( array $query_vars, string $sql ): string {
		foreach ( self::VOLATILE as $key ) {
			unset( $query_vars[ $key ] );
		}

		$query_vars['post_type'] = (array) ( $query_vars['post_type'] ?? 'post' );
		sort( $query_vars['post_type'] );

		foreach ( self::SORTABLE as $key ) {
			if ( isset( $query_vars[ $key ] ) && is_array( $query_vars[ $key ] ) ) {
				$query_vars[ $key ] = array_values( array_unique( array_map( 'intval', $query_vars[ $key ] ) ) );
				sort( $query_vars[ $key ] );
			}
		}

		ksort( $query_vars );

		return md5( serialize( $query_vars ) . $sql );
	}

	/**
	 * Whether this query should use the grid cache.
	 *
	 * @param array $query_vars The WP_Query vars.
	 *
	 * @return bool
	 */
	public function is_cacheable( array $query_vars ): bool {
		$cacheable = true;

		if ( isset( $query_vars['query_by'] ) && 'id' === $query_vars['query_by'] ) {
			$cacheable = false;
		}

		// ElasticPress offloads to ES and hooks posts_pre_query itself; that interaction is
		// unverified (eurweb is not on EP), so skip ep_integrate grids until it is tested.
		if ( ! empty( $query_vars['ep_integrate'] ) ) {
			$cacheable = false;
		}

		// Optimizer already made this a post__in fast path (marker present, no meta JOIN).
		if ( isset( $query_vars['mai_post_grid_tt_ids'] ) && empty( $query_vars['meta_query'] ) ) {
			$cacheable = false;
		}

		$orderby = $query_vars['orderby'] ?? '';
		if ( is_string( $orderby ) && false !== stripos( $orderby, 'RAND(' ) ) {
			$cacheable = false;
		}

		return (bool) apply_filters( 'mai_post_grid_cache', $cacheable, $query_vars );
	}

	/**
	 * posts_pre_query: serve from cache (fresh or stale) or flag a miss for storage.
	 *
	 * @param array|null $posts Posts (null to run the query normally).
	 * @param WP_Query   $query The query.
	 *
	 * @return array|null
	 */
	public function pre_query( $posts, $query ) {
		if ( empty( $query->query_vars['mai_cache'] ) || ! $this->is_cacheable( $query->query_vars ) ) {
			return $posts;
		}

		$cache   = mai_cache( self::GROUP );
		$key     = $this->cache_key( $query->query_vars, (string) $query->request );
		$version = $cache->version( (array) ( $query->query_vars['post_type'] ?? 'post' ) );
		$hit     = $cache->read_swr( $key, $version );

		// Cold, or a stale entry where we won the single-flight lock: recompute.
		if ( null === $hit || ( ! $hit['fresh'] && $cache->lock( $key ) ) ) {
			$query->mai_cache_store_key     = $key;
			$query->mai_cache_store_version = $version;
			return $posts; // null -> WP runs the real query; the_posts stores it.
		}

		// Fresh hit, or a stale hit served while another request refreshes.
		$value                = $hit['value'];
		$query->found_posts   = (int) ( $value['found'] ?? count( $value['ids'] ) );
		$query->max_num_pages = ( ( $query->query_vars['posts_per_page'] ?? 0 ) > 0 )
			? (int) ceil( $query->found_posts / $query->query_vars['posts_per_page'] )
			: 1;

		return $this->hydrate( array_map( 'intval', $value['ids'] ) );
	}

	/**
	 * the_posts: store the freshly computed result for a flagged miss.
	 *
	 * @param array    $posts The posts.
	 * @param WP_Query $query The query.
	 *
	 * @return array
	 */
	public function the_posts( $posts, $query ) {
		if ( empty( $query->mai_cache_store_key ) ) {
			return $posts;
		}

		$ttl = (int) apply_filters( 'mai_post_grid_cache_ttl', self::TTL, $query->query_vars );

		mai_cache( self::GROUP )->write_swr(
			$query->mai_cache_store_key,
			[ 'ids' => wp_list_pluck( $posts, 'ID' ), 'found' => (int) $query->found_posts ],
			$query->mai_cache_store_version,
			$ttl
		);

		unset( $query->mai_cache_store_key, $query->mai_cache_store_version );

		return $posts;
	}

	/**
	 * Hydrate post objects from cached IDs in stored order, with no query.
	 *
	 * @param int[] $ids Ordered post IDs.
	 *
	 * @return WP_Post[]
	 */
	public function hydrate( array $ids ): array {
		if ( ! $ids ) {
			return [];
		}

		_prime_post_caches( $ids, true, true );

		return array_values( array_filter( array_map( 'get_post', $ids ) ) );
	}

	/**
	 * Rotate a post type's token on a publish-affecting transition (publish/unpublish/trash).
	 *
	 * @param string  $new_status New status.
	 * @param string  $old_status Old status.
	 * @param WP_Post $post       The post.
	 *
	 * @return void
	 */
	public function on_transition( $new_status, $old_status, $post ) {
		if ( 'publish' !== $new_status && 'publish' !== $old_status ) {
			return;
		}
		if ( wp_is_post_revision( $post->ID ) || 'revision' === $post->post_type ) {
			return;
		}
		mai_cache( self::GROUP )->bump( $post->post_type );
	}

	/**
	 * Rotate on hard delete of a published post.
	 *
	 * @param int     $post_id The post ID.
	 * @param WP_Post $post    The post.
	 *
	 * @return void
	 */
	public function on_delete( $post_id, $post ) {
		if ( $post && 'publish' === $post->post_status && 'revision' !== $post->post_type ) {
			mai_cache( self::GROUP )->bump( $post->post_type );
		}
	}

	/**
	 * Rotate on a save to an already-published post (live edit). Drafts, autosaves, and
	 * revisions are excluded.
	 *
	 * @param int     $post_id The post ID.
	 * @param WP_Post $post    The post.
	 *
	 * @return void
	 */
	public function on_save( $post_id, $post ) {
		if ( wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return;
		}
		if ( 'publish' === $post->post_status && 'revision' !== $post->post_type ) {
			mai_cache( self::GROUP )->bump( $post->post_type );
		}
	}

	/**
	 * Full reset of the grid group (wp cache flush / wp mai flush). Orphans everything;
	 * the cold-start case, not per-type invalidation.
	 *
	 * @return void
	 */
	public function flush_all() {
		mai_cache( self::GROUP )->flush();
	}
}
