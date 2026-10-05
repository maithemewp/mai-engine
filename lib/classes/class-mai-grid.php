<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2020 BizBudding
 * @license   GPL-2.0-or-later
 */

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * Instantiate a grid.
 *
 * Use render() method to display.
 */
class Mai_Grid {

	/**
	 * Index.
	 *
	 * @var int
	 */
	protected $index;

	/**
	 * Type.
	 *
	 * @var $type
	 */
	protected $type;

	/**
	 * Args.
	 *
	 * @var $args
	 */
	protected $args;

	/**
	 * Query Args.
	 *
	 * @var $query_args
	 */
	protected $query_args;

	/**
	 * Query.
	 *
	 * @var $query
	 */
	protected $query;

	/**
	 * Incase exclude_displayed is true in any instance of grid.
	 *
	 * @var array
	 */
	public static $existing_post_ids = [];

	/**
	 * Incase exclude_displayed is true in any instance of grid.
	 *
	 * @var array
	 */
	public static $existing_term_ids = [];

	/**
	 * Post IDs this render contributed through the exclude_displayed and exclude_current
	 * settings, as opposed to the author-set Exclude Entries field. These change with every
	 * page view, so they shatter the result cache key when they reach the SQL. Recorded raw
	 * here, exactly as they were added to post__not_in; get_query() normalizes them and
	 * decides whether to keep them out of the query and apply them in PHP instead.
	 *
	 * @since 2.41.0
	 *
	 * @var array
	 */
	protected $deferred_excludes = [];

	/**
	 * Mai_Grid constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Loop args.
	 *
	 * @return void
	 */
	public function __construct( $args ) {
		$args['context']  = 'block'; // Required for Mai_Entry.
		$this->type       = isset( $args['type'] ) ? $args['type'] : 'post';
		$this->args       = wp_parse_args( $this->get_sanitized_args( $args ), $this->get_defaults() );
		$this->query_args = [];
	}

	/**
	 * Get default settings.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_defaults() {
		static $defaults = null;

		if ( is_array( $defaults ) && isset( $defaults[ $this->type ] ) ) {
			return $defaults[ $this->type ];
		}

		$display                 = mai_get_grid_display_defaults();
		$layout                  = mai_get_grid_layout_defaults();
		$defaults[ $this->type ] = array_merge( $display, $layout );

		switch ( $this->type ) {
			case 'post':
				$defaults[ $this->type ] = array_merge( $defaults[ $this->type ], mai_get_wp_query_defaults() );
			break;
			case 'term':
				$defaults[ $this->type ] = array_merge( $defaults[ $this->type ], mai_get_wp_term_query_defaults() );
			break;
		}

		return $defaults[ $this->type ];
	}

	/**
	 * Get sanitized args.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Loop args.
	 *
	 * @return array
	 */
	public function get_sanitized_args( $args ) {
		$sanitized = mai_get_grid_display_sanitized( $args );
		$sanitized = mai_get_grid_layout_sanitized( $sanitized );

		switch ( $this->type ) {
			case 'post':
				$sanitized = mai_get_wp_query_sanitized( $sanitized );
			break;
			case 'term':
				$sanitized = mai_get_wp_term_query_sanitized( $sanitized );
			break;
		}

		// Filter to add args via custom ACF fields.
		$args = apply_filters( 'mai_grid_args', $sanitized );

		return $args;
	}

	/**
	 * Renders the grid.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function render() {
		// Bail if not showing any elements.
		if ( empty( $this->args['show'] ) ) {
			return;
		}

		// Increment index.
		$this->index = mai_get_index( 'entries' );

		// Add index to args.
		$this->args['index'] = $this->index;

		// do_action( 'mai_before_grid_query', $this->args );
		$this->query = $this->get_query();
		// do_action( 'mai_after_grid_query', $this->args );

		$no_results = false;

		if ( 'post' === $this->type && ( ! $this->query || ! $this->query->have_posts() ) ) {
			$no_results = true;
		}

		if ( 'term' === $this->type && ( ! $this->query || ! $this->query->terms ) ) {
			$no_results = true;
		}

		// No resuilts.
		if ( $no_results ) {
			if ( ! $this->args['no_results'] ) {
				return;
			}

			$class = 'mai-grid-no-results';

			if ( isset( $this->args['margin_top'] ) && $this->args['margin_top'] ) {
				$class = mai_add_classes( sprintf( 'has-%s-margin-top', $this->args['margin_top'] ), $class );
			}

			if ( isset( $this->args['margin_bottom'] ) && $this->args['margin_bottom'] ) {
				$class = mai_add_classes( sprintf( 'has-%s-margin-bottom', $this->args['margin_bottom'] ), $class );
			}

			if ( isset( $this->args['align_text'] ) && $this->args['align_text'] ) {
				$class = mai_add_classes( sprintf( 'has-text-align-%s', mai_get_align_text( $this->args['align_text'] ) ), $class );
			}

			printf( '<div class="%s">%s</div>', $class, mai_get_processed_content( $this->args['no_results'] ) );
			return;
		}

		// Grid specific classes. Didn't use mai_add_classes() because I want mai-grid first.
		$this->args['class'] = isset( $this->args['class'] ) ? $this->args['class'] : '';
		$this->args['class'] = 'mai-grid ' . $this->args['class'];
		$this->args['class'] = trim( $this->args['class'] );

		// Open.
		mai_do_entries_open( $this->args, $this->query );

		// Entries.
		$this->do_grid_entries();

		// Close.
		mai_do_entries_close( $this->args, $this->query );
	}

	/**
	 * Gets the query.
	 *
	 * @since 2.4.3
	 *
	 * @return false|WP_Query|WP_Term_Query
	 */
	public function get_query() {
		$query = false;

		switch ( $this->args['type'] ) {
			case 'post':
				$this->query_args = $this->get_post_query_args();

				if ( $this->query_args['post_type'] ) {
					// Remove any post_types that no longer exist.
					foreach ( (array) $this->query_args['post_type'] as $index => $post_type ) {
						if ( ! post_type_exists( $post_type ) ) {
							unset( $this->query_args['post_type'][ $index ] );
						};
					}

					// Bail if no post types.
					if ( ! $this->query_args['post_type'] ) {
						return;
					}

					// Decide here, not in get_post_query_args(), because every
					// mai_post_grid_query_args filter has now run and the args are final.
					$effective = $this->effective_excludes( $this->query_args );
					$defer     = $this->can_defer_excludes( $this->query_args, $effective );
					$asked     = $this->query_args;
					$keep      = null;

					// Ties in the sort are settled for every grid that does not count rows, in the
					// way add_grid_orderby_tiebreaker() describes. Mai Load More counts them, so it
					// is left out. Its next pages run from the saved args, which never carry the
					// marker, so the first page would break ties by ID and the pages after it would not.
					if ( ! empty( $asked['no_found_rows'] ) ) {
						$this->query_args['mai_grid_tiebreak'] = true;
					}

					if ( $defer ) {
						// Keep the per-view ids out of the SQL so every page sharing this
						// grid's filters shares one cache entry, and ask for enough extra
						// rows that the grid still fills once they are dropped.
						$this->query_args['post__not_in']   = array_values( array_diff( $asked['post__not_in'], $effective ) );
						$this->query_args['posts_per_page'] = $asked['posts_per_page'] + count( $effective );

						// Mai_Query_Cache reads this in posts_pre_query and answers with only
						// the posts that will be shown, so the rest are never loaded.
						$keep = [
							'exclude'       => $effective,
							'count'         => $asked['posts_per_page'],
							'cache_results' => $this->get_query_cache_flags( $asked )['cache_results'],
						];

						// Core would store that kept answer under the padded query's key,
						// where a later full run of this grid would read it back short. The
						// IDs come from an ID-only copy of this query, which uses core's
						// cache as asked, carried above (Mai_Query_Cache::fetch_ids()).
						$this->query_args['cache_results'] = false;
					}

					$query     = new WP_Query();
					$optimizer = Mai_Post_Grid_Query_Optimizer::instance();

					// Lets the grid query optimizer send a faster statement for this grid when it
					// can prove it returns the same posts.
					$optimizer->mark( $query, Mai_Post_Grid_Query_Optimizer::ROLE_GRID );

					// Set on the query itself before it runs, never as a query var. A plugin
					// that answers posts_pre_query by building its own query from this one's
					// args and vars, as The Events Calendar does at priority 100, would carry a
					// query var into that copy. The copy would then come back trimmed, and the
					// result cache would store the trimmed list as the shared padded entry.
					if ( $keep ) {
						$query->mai_grid_keep = $keep;
					}

					try {
						$query->query( $this->query_args );
					} finally {
						// The grid's statement has been sent or never will be. recover() drops the
						// prepared swap at posts_results, which a query that throws never reaches.
						$optimizer->drop( $query );
					}

					// The marker has done its work. Take it off the query and the args for every
					// grid, so nothing that reads them later, such as a plugin that serializes the
					// query's args and runs them again, finds it there.
					unset( $query->query_vars['mai_grid_tiebreak'], $query->query['mai_grid_tiebreak'] );

					$this->query_args = $asked;

					if ( $defer ) {
						// Mai_Query_Cache already dropped the excludes and kept the asked count,
						// before posts_results and the_posts ran. Not set when it could not answer
						// that way, or when another posts_pre_query callback answered instead.
						$already_kept = isset( $query->mai_grid_kept );

						// The answer it gave and how it primed it, so the posts are not primed twice.
						$kept_answer = $query->mai_grid_kept ?? null;
						$kept_primed = $query->mai_grid_kept_primed ?? null;

						unset( $query->mai_grid_kept, $query->mai_grid_kept_primed );

						// Apply the excludes now. The result cache has already stored the
						// unfiltered superset, which is what makes the entry shareable, so this
						// has to happen after the query has run. It runs on the kept path too,
						// because a the_posts callback can put an excluded post back after the
						// excludes were dropped. So can core's sticky handling, when a
						// pre_get_posts callback turned stickies back on after
						// can_defer_excludes() checked.
						$kept = array_values(
							array_filter(
								$query->posts,
								static function ( $post ) use ( $effective ) {
									// A the_posts callback can put anything in this list, so do
									// not assume a WP_Post. The two field modes core answers with
									// ints and stdClass cannot arrive here, because
									// can_defer_excludes() refuses to defer for either.
									$id = is_object( $post ) ? (int) $post->ID : (int) $post;

									return ! in_array( $id, $effective, true );
								}
							)
						);

						// The kept path already has the asked count, so slicing it again would
						// cut a post a the_posts callback added, which a grid that does not defer
						// keeps.
						if ( ! $already_kept ) {
							// Widen the slice by however many rows a the_posts filter added on top of
							// the LIMIT, so a plugin that pins posts into grids still gets its full
							// count through. The slice keeps the first entries, so what is guaranteed
							// is the count, not any particular pinned post: a post pinned to the top
							// survives, one appended to the end can still fall off the slice. The
							// baseline comes off the query rather than our own args because
							// pre_get_posts runs after the args were read: a callback without an
							// is_main_query() check can change posts_per_page, and query_vars is what
							// actually built the LIMIT.
							$injected = max( 0, count( $query->posts ) - $query->query_vars['posts_per_page'] );

							$kept = array_slice( $kept, 0, $asked['posts_per_page'] + $injected );
						}

						$query->posts      = $kept;
						$query->post_count = count( $query->posts );

						// Still exactly the posts Mai_Query_Cache answered with, so it has primed
						// them all. A the_posts callback that added or replaced a post changes the
						// list, and then they are primed here as before.
						$this->prime_shown_posts( $query, $asked, ( $already_kept && $kept === $kept_answer ) ? $kept_primed : null );

						// Put the query back the way it was asked for, before anything reads
						// it. Mai Load More and any custom pagination serialize these and
						// re-run them later: a padded posts_per_page would make them stride
						// past posts, and a missing post__not_in would drop the exclusions.
						$query->query_vars['posts_per_page'] = $asked['posts_per_page'];
						$query->query_vars['post__not_in']   = $asked['post__not_in'];
						$query->query['posts_per_page']      = $asked['posts_per_page'];
						$query->query['post__not_in']        = $asked['post__not_in'];

						// Same for cache_results, switched off above. query_vars gets the value
						// core would have filled in for the asked args, and the raw args copy
						// gets the key back only if it was asked for.
						$query->query_vars['cache_results'] = $keep['cache_results'];

						if ( array_key_exists( 'cache_results', $asked ) ) {
							$query->query['cache_results'] = $asked['cache_results'];
						} else {
							unset( $query->query['cache_results'] );
						}

						unset( $query->mai_grid_keep );

						// Core left $query->post pointing at the unfiltered first post, which
						// with exclude_current is very often the post being excluded.
						$query->rewind_posts();

						if ( ! $query->post_count ) {
							$query->post = null;
						}
					}

					// Cache featured images. After the filter, so only the posts that will be
					// shown prime their thumbnails. Meta and terms are already primed, above
					// for a grid that defers, and while the query ran for any other.
					if ( in_array( 'image', $this->args['show'] ) ) {
						update_post_thumbnail_cache( $query );
					}

					wp_reset_postdata();
				}
				break;

			case 'term':
				$this->query_args = $this->get_term_query_args();

				// Remove any taxonomies that no longer exist.
				if ( $this->query_args['taxonomy'] ) {
					foreach ( (array) $this->query_args['taxonomy'] as $index => $taxonomy ) {
						if ( ! taxonomy_exists( $taxonomy ) ) {
							unset( $this->query_args['taxonomy'][ $index ] );
						};
					}

					// Bail if no taxonomies.
					if ( ! $this->query_args['taxonomy'] ) {
						return;
					}

					$query = new WP_Term_Query( $this->query_args );

					// Cache featured images, mirroring the post branch above. WP_Term_Query primes
					// term meta, so reading the image ID is cheap, but the attachments those IDs
					// point at are in no cache: each entry's wp_get_attachment_image() would then
					// cost a get_post() plus a get_post_meta(). Prime them all in one pass.
					if ( $query->terms && in_array( 'image', $this->args['show'] ) ) {
						$image_ids = [];

						foreach ( $query->terms as $term ) {
							$image_id = mai_get_term_image_id( $term );

							if ( $image_id ) {
								$image_ids[] = $image_id;
							}
						}

						if ( $image_ids ) {
							// Attachments carry no terms we need, but _wp_attachment_metadata is
							// read for every srcset, so prime meta only.
							_prime_post_caches( $image_ids, false, true );
						}
					}
				}
				break;
		}

		return $query;
	}

	/**
	 * Renders the grid entries.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function do_grid_entries() {
		switch ( $this->args['type'] ) {
			case 'post':
				if ( $this->query_args['post_type'] ) {
					$posts = $this->query;
					if ( $posts->have_posts() ) {
						while ( $posts->have_posts() ) {
							$posts->the_post();

							/**
							 * Post object.
							 *
							 * @var WP_Post $post Post object.
							 */
							global $post;

							mai_do_entry( $post, $this->args );

							// Add this post to the existing post IDs.
							// self::$existing_post_ids[] = get_the_ID();

							self::$existing_post_ids[ $post->post_type ][] = $post->ID;
						}

						// Clear duplicate IDs.
						// self::$existing_post_ids = array_unique( self::$existing_post_ids );
						foreach ( self::$existing_post_ids as $post_type => $ids ) {
							self::$existing_post_ids[ $post_type ] = array_unique( $ids );
						}
					}
					wp_reset_postdata();
				}
				break;

			case 'term':
				if ( $this->query_args['taxonomy'] ) {
					$term_query = $this->query;

					if ( ! empty( $term_query->terms ) ) {

						/**
						 * Terms.
						 *
						 * @var WP_Term $term Term object.
						 */
						foreach ( $term_query->terms as $term ) {
							// Set global variable for the term, since WP does not offer this by default.
							global $mai_term;
							$mai_term = $term;

							mai_do_entry( $term, $this->args );

							// Add this term to the existing term IDs.
							self::$existing_term_ids[] = $term->term_id;
						}

						// Unset global var.
						unset ( $GLOBALS['mai_term'] );

						// Clear duplicate IDs.
						self::$existing_term_ids = array_unique( self::$existing_term_ids );
					}
				}
				break;
		}
	}

	/**
	 * Get post query args.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_post_query_args() {
		// get_post_query_args() is public and may be called more than once on one instance.
		// Start clean so a second call cannot accumulate ids from the first.
		$this->deferred_excludes = [];

		$post_status  = is_user_logged_in() && current_user_can( 'edit_posts' ) ? [ 'publish', 'private' ] : 'publish';
		$per_page     = ( 0 === $this->args['posts_per_page'] ) ? -1 : $this->args['posts_per_page'];
		$per_page     = ( 'id' === $this->args['query_by'] ) ? count( (array) $this->args['post__in'] ) : $per_page;

		// "Use 0 to show all" is an advertised setting, but on a large site an unbounded query
		// also means unbounded priming, an unbounded cache entry, and an unbounded render loop.
		// Cap it so a single editor choice cannot take a site down; filterable for the rare
		// legitimate case.
		if ( -1 === $per_page ) {
			/**
			 * Filters the ceiling applied when a post grid is set to show all entries.
			 *
			 * Return 0 or a negative number to opt back into an unbounded query.
			 *
			 * @since 2.41.0
			 *
			 * @param int $max The maximum number of entries. Default 1000.
			 */
			$max      = (int) apply_filters( 'mai_post_grid_max_posts_per_page', 1000 );
			$per_page = $max > 0 ? $max : -1;
		}

		$query_args   = [
			'post_type'           => $this->args['post_type'],
			'posts_per_page'      => $per_page,
			'post_status'         => $post_status,
			'offset'              => $this->args['offset'],
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		];

		// Handle query_by.
		switch ( $this->args['query_by'] ) {
			case 'parent':
				if ( $this->args['current_children'] ) {
					if ( is_singular() ) {
						$post_id = get_the_ID();

					} elseif ( isset( $this->args['preview'] ) && $this->args['preview'] ) {
						$post_id = filter_input( INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT );

						if ( ! $post_id && wp_doing_ajax() && isset( $_REQUEST['post_id'] ) ) {
							$post_id = absint( $_REQUEST['post_id'] );
						}
					}

					if ( isset( $post_id ) && $post_id ) {
						$query_args['post_parent__in'] = [ $post_id ];
					}
				} else {
					$query_args['post_parent__in'] = $this->args['post_parent__in'];
				}
			break;

			case 'id':
				// Empty array returns all posts, array(-1) prevents this.
				$query_args['post__in'] = $this->args['post__in'] ?: [ -1 ];
				$query_args['orderby']  = 'post__in';
			break;
			case 'tax_meta':
			case 'trending': // For Mai Publisher/Trending Posts.
				$tax_query = [];

				if ( $this->args['taxonomies'] ) {
					foreach ( $this->args['taxonomies'] as $taxo ) {
						$taxonomy = mai_isset( $taxo, 'taxonomy', '' );
						$terms    = mai_isset( $taxo, 'terms', [] );
						$current  = mai_isset( $taxo, 'current', false );
						$operator = mai_isset( $taxo, 'operator', '' );

						// Skip if we don't have all the tax query args.
						if ( ! ( $taxonomy && ( $terms || $current ) && $operator ) ) {
							continue;
						}

						// Get current archive or entry terms.
						if ( $current ) {
							if ( ! mai_is_editor() ) {
								if ( is_category() || is_tag() || is_tax() ) {
									$terms[] = get_queried_object_id();
								} elseif ( is_singular() ) {
									// get_the_terms() reads the object term cache the main query already
									// primed for this post. wp_get_post_terms() skips that cache and runs a
									// WP_Term_Query keyed on the terms group's last_changed, which churns
									// constantly on a busy site, so it rarely hits warm. Returns false (not
									// an empty array) when the post has no terms in this taxonomy.
									$entry_terms = get_the_terms( get_the_ID(), $taxonomy );

									if ( $entry_terms && ! is_wp_error( $entry_terms ) ) {
										foreach ( $entry_terms as $entry_term ) {
											$terms[] = $entry_term->term_id;
										}
									}
								}
							}
						}

						// Bail if no terms.
						if ( ! $terms ) {
							continue;
						}

						// Set the value.
						$tax_query[] = [
							'taxonomy' => $taxonomy,
							'field'    => 'id',
							'terms'    => $terms,
							'operator' => $operator,
						];
					}

					// If we have tax query values.
					if ( $tax_query ) {
						// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						$query_args['tax_query'] = $tax_query;

						if ( $this->args['taxonomies_relation'] ) {
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
							$query_args['tax_query']['relation'] = $this->args['taxonomies_relation'];
						}
					}
				}

				$meta_query = [];

				if ( $this->args['meta_keys'] ) {
					foreach ( $this->args['meta_keys'] as $meta ) {
						$key     = mai_isset( $meta, 'meta_key', '' );
						$compare = mai_isset( $meta, 'meta_compare', '' );
						$value   = mai_isset( $meta, 'meta_value', '' );
						$type    = mai_isset( $meta, 'meta_type', '' );

						// Skip if we don't have the meta query args.
						if ( ! ( $key && $compare ) ) {
							continue;
						}

						// Skip if no meta value, only if compare is not exists/not exists.
						if ( ! $value && ! in_array( $compare, [ 'EXISTS', 'NOT EXISTS' ] ) ) {
							continue;
						}

						$meta_query_args = [
							'key'     => $key,
							'compare' => $compare,
						];

						if ( ! in_array( $compare, [ 'EXISTS', 'NOT EXISTS' ] ) ) {
							$meta_query_args['value'] = $value;
						}

						// Add type.
						// TODO: Add field for this in the block.
						// Right now it only works programmatically.
						if ( $type ) {
							$meta_query_args['type'] = $type;
						}

						$meta_query[] = $meta_query_args;
					}

					// If we have meta query values.
					if ( $meta_query ) {

						$query_args['meta_query'] = $meta_query;
					}
				}

			break;
		}

		// Date.
		if ( ( $this->args['date_after'] || $this->args['date_before'] ) && 'id' !== $this->args['query_by'] ) {
			$query_args['date_query'] = [];

			if ( $this->args['date_after'] ) {
				$query_args['date_query']['after'] = $this->args['date_after'];
			}

			if ( $this->args['date_before'] ) {
				$query_args['date_query']['before'] = $this->args['date_before'];
			}
		}

		// Orderby.
		if ( $this->args['orderby'] && ( 'id' !== $this->args['query_by'] ) ) {
			$query_args['orderby'] = $this->args['orderby'];

			if ( 'meta_value_num' === $this->args['orderby'] ) {

				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$query_args['meta_key'] = $this->args['orderby_meta_key'];
			}
		}

		// Order.
		if ( $this->args['order'] ) {
			$query_args['order'] = $this->args['order'];
		}

		// Exclude. If not getting entries by choice.
		if ( 'id' !== $this->args['query_by'] ) {
			// Exclude entries.
			if ( $this->args['post__not_in'] ) {
				$query_args['post__not_in'] = $this->args['post__not_in'];
			}

			// Start with empty array.
			$post__not_ins = [];

			// Make sure existing post IDs are for the post type(s) we are querying.
			foreach ( (array) $this->args['post_type'] as $post_type ) {
				// Add existing post IDs for this post type.
				if ( isset( self::$existing_post_ids[ $post_type ] ) ) {
					$post__not_ins = array_merge( $post__not_ins, self::$existing_post_ids[ $post_type ] );
				}
			}

			// Exclude displayed.
			if ( $this->args['excludes'] && in_array( 'exclude_displayed', $this->args['excludes'] ) && ! empty( $post__not_ins ) ) {
				if ( isset( $query_args['post__not_in'] ) ) {
					$query_args['post__not_in'] = array_merge( $query_args['post__not_in'], $post__not_ins );
				} else {
					$query_args['post__not_in'] = $post__not_ins;
				}

				$this->deferred_excludes = array_merge( $this->deferred_excludes, $post__not_ins );
			}

			// Exclude current.
			if ( is_singular() && $this->args['excludes'] && in_array( 'exclude_current', $this->args['excludes'], true ) ) {
				if ( isset( $query_args['post__not_in'] ) ) {
					$query_args['post__not_in'][] = get_the_ID();
				} else {
					$query_args['post__not_in'] = [ get_the_ID() ];
				}

				$this->deferred_excludes[] = get_the_ID();
			}
		}

		// Opt this grid into the result cache, with a per-grid override that receives the grid
		// args (richer than the query vars). Mai_Query_Cache::pre_query then re-checks query-level
		// cacheability (the `mai_query_cache` filter), so caching is still safe by default.
		$query_args['mai_cache'] = (bool) apply_filters( 'mai_post_grid_cache', true, $this->args );

		return apply_filters( 'mai_post_grid_query_args', $query_args, $this->args );
	}

	/**
	 * The dynamic exclude IDs that are still in play for the final query.
	 *
	 * Reconciles what get_post_query_args() recorded against what actually survived every
	 * mai_post_grid_query_args filter. Two things fall out of that, both deliberate:
	 *
	 * A site that filters an id back out of post__not_in gets its override honored, because
	 * an id that is no longer in the query is no longer ours to apply in PHP either.
	 *
	 * A post__not_in that a filter replaced with something that is not an array (WordPress
	 * also accepts a comma string in places) returns empty here, so the grid falls back to
	 * today's behavior instead of fataling on array_diff().
	 *
	 * @since 2.41.0
	 *
	 * @param array $query_args The final query args.
	 *
	 * @return int[]
	 */
	protected function effective_excludes( $query_args ) {
		if ( ! $this->deferred_excludes ) {
			return [];
		}

		$in_query = $query_args['post__not_in'] ?? null;

		if ( ! is_array( $in_query ) ) {
			return [];
		}

		// array_filter drops 0, which is what get_the_ID() casts to when it returns false.
		$recorded = array_filter( array_map( 'intval', $this->deferred_excludes ) );
		$in_query = array_map( 'intval', $in_query );

		return array_values( array_unique( array_intersect( $recorded, $in_query ) ) );
	}

	/**
	 * Whether this grid may keep its dynamic excludes out of the query and apply them in PHP.
	 *
	 * Called from get_query() with the final args, after every mai_post_grid_query_args filter
	 * has run. Checking the block settings instead would read stale values.
	 *
	 * @since 2.41.0
	 *
	 * @param array $query_args The final query args.
	 * @param int[] $effective  The exclude IDs still in play, from effective_excludes().
	 *
	 * @return bool
	 */
	protected function can_defer_excludes( $query_args, $effective ) {
		$can = (bool) $effective;

		// An offset makes the database skip rows before we can filter, so filtering after
		// returns a different set. Measured on real archives: differs for roughly 1 article
		// in 150. `paged` is the same mechanism, since the LIMIT start is
		// (paged - 1) * posts_per_page and padding the page size multiplies the start row.
		if ( ! empty( $query_args['offset'] ) || ! empty( $query_args['paged'] ) ) {
			$can = false;
		}

		// No LIMIT is emitted at all, so there is nothing to pad and the slice would truncate
		// a query that was deliberately asked to return everything.
		if ( ! empty( $query_args['nopaging'] ) ) {
			$can = false;
		}

		// A non-numeric value has no size to pad and adding to it is a TypeError in PHP 8.
		// Decline and let WP_Query cast it the way it does for every other grid.
		if ( ! isset( $query_args['posts_per_page'] ) || ! is_numeric( $query_args['posts_per_page'] ) || $query_args['posts_per_page'] < 1 ) {
			$can = false;
		}

		// Something wants an accurate total, which means something is paginating this grid.
		// Padding inflates found_posts and skews the page count derived from it. empty()
		// rather than a false check on purpose: WP_Query's own default is false, so an absent
		// key means counting is ON and must be treated the same as an explicit false.
		//
		// This guard also keeps a counting grid from deferring. get_query() gives counting grids
		// no ID tiebreaker, because their later pages run from saved args that never carry it,
		// so a padded LIMIT on one would break ties differently from run to run. Do not drop this
		// guard on the strength of having made the total accurate under padding: the ordering
		// half would still be broken.
		if ( empty( $query_args['no_found_rows'] ) ) {
			$can = false;
		}

		// FacetWP rewrites the query for its own pagination.
		if ( ! empty( $query_args['facetwp'] ) ) {
			$can = false;
		}

		// Both halves of the deal are switched off here. WP_Query runs posts_orderby and
		// the_posts inside `if ( ! $query_vars['suppress_filters'] )`, so the padded LIMIT
		// would get no tiebreaker, which is the tie instability the tiebreaker exists to
		// prevent, and the result cache stores on the_posts, so nothing would be shared.
		if ( ! empty( $query_args['suppress_filters'] ) ) {
			$can = false;
		}

		// With sticky posts on, core fetches the stickies missing from the results and leaves out
		// only those in post__not_in, which no longer holds the deferred excludes. It would put
		// the post being viewed right back. empty() because WP_Query's own default is false,
		// which means stickies are on.
		if ( empty( $query_args['ignore_sticky_posts'] ) ) {
			$can = false;
		}

		// Core returns these straight out of get_posts(), before the_posts and before it sets
		// $this->post. Nothing reaches the cache, and rewind_posts() would leave $query->post
		// as an int or a stdClass where core leaves it null.
		if ( isset( $query_args['fields'] ) && in_array( $query_args['fields'], [ 'ids', 'id=>parent' ], true ) ) {
			$can = false;
		}

		// Never step over the ceiling that exists so one editor setting cannot take a site
		// down. A show-all grid already sits at it, so it simply does not defer.
		$max = (int) apply_filters( 'mai_post_grid_max_posts_per_page', 1000 );

		if ( $max > 0 && is_numeric( $query_args['posts_per_page'] ?? null ) && ( $query_args['posts_per_page'] + count( $effective ) ) > $max ) {
			$can = false;
		}

		// 500 is WordPress core's own threshold, not ours. WP_Query::get_posts() drops
		// $split_the_query once posts_per_page reaches 500, so core selects whole rows
		// instead of IDs then hydrating, and the tax query's temp table has to carry every
		// matching post's full content. Measured on a 54,461-post category: 246ms at 499,
		// 1190ms at 500, flat either side, a shape switch rather than a volume effect. Only
		// bites on large categories, noise below roughly 12MB of category content, and
		// wp_using_ext_object_cache() true skips the branch on core's side too, so this
		// guard costs nothing there. The padded rows are cheap either way, about 0.2ms each.
		if ( is_numeric( $query_args['posts_per_page'] ?? null ) && ( $query_args['posts_per_page'] + count( $effective ) ) >= 500 ) {
			$can = false;
		}

		// No point paying for this on a grid whose result will not be cached: the whole
		// benefit is a shared cache entry. Covers the mai_post_grid_cache opt-out, plus
		// everything Mai_Query_Cache refuses (ElasticPress, random order), plus a store that
		// cannot write at all (SCRIPT_DEBUG, or the mai_can_cache filter). Calling
		// is_cacheable() rather than restating its rules means the two cannot drift apart. It
		// fires the mai_query_cache filter a second time for this query, which is harmless for
		// a filter that only answers a question.
		if ( empty( $query_args['mai_cache'] ) ) {
			$can = false;
		}

		if ( $can && class_exists( 'Mai_Query_Cache' ) ) {
			$cache = Mai_Query_Cache::instance();

			if ( ! $cache->is_cacheable( $query_args ) || ! $cache->can_store() ) {
				$can = false;
			}
		}

		/**
		 * Filters whether a grid keeps exclude_displayed and exclude_current out of the query
		 * and applies them while rendering instead. Off means those IDs go into post__not_in
		 * as before, which gives that grid a separate cache entry per page view.
		 *
		 * This is an opt-out. Returning true cannot turn deferring on for a grid the guards
		 * above ruled out, because those guards protect correctness rather than preference.
		 *
		 * Fires for every post grid, including the ones the guards already declined, and the
		 * default it passes in is the guards' own verdict. That is what makes "which of my
		 * grids are deferring, and which are not" answerable from a small mu-plugin. Without
		 * it, a third-party filter that flips no_found_rows on every query would switch this
		 * off site-wide and look exactly like the feature working.
		 *
		 * @since 2.41.0
		 *
		 * @param bool  $enabled    Whether deferring is allowed, as the guards left it.
		 * @param array $query_args The final query args.
		 * @param array $args       The grid args.
		 */
		$filtered = (bool) apply_filters( 'mai_post_grid_defer_excludes', $can, $query_args, $this->args );

		// Two statements, not `$can && apply_filters( ... )`. PHP short-circuits &&, so writing
		// it that way would skip the filter entirely for every grid a guard already declined,
		// which is the half a site most needs to see.
		return $can && $filtered;
	}

	/**
	 * Primes meta and terms for the posts a deferring grid will show, as its own args asked.
	 *
	 * When Mai_Query_Cache answered with only the kept posts, it already primed them, so this is
	 * only cache reads. It still matters for a post a the_posts callback added, and for the
	 * fallback, where Mai_Query_Cache could not answer that way and core or another
	 * posts_pre_query callback answered the grid's query instead. Core primes the padded rows
	 * there when it splits the query, which it does for an unfiltered statement. It does not
	 * prime a statement it does not split, a rewritten one for example, or another callback's
	 * answer, because the priming core does after the_posts checks cache_results, and
	 * get_query() switched that off.
	 *
	 * The priming is skipped only when Mai_Query_Cache already primed exactly these posts with
	 * the same flags the grid asked for. Priming them again would only read the cache. The term
	 * meta lazy load queue below still runs either way.
	 *
	 * @since 2.41.0
	 * @since 2.41.0 Skips the priming Mai_Query_Cache already did.
	 *
	 * @param WP_Query   $query  The query.
	 * @param array      $asked  The query args as asked, before padding.
	 * @param array|null $primed How Mai_Query_Cache primed exactly these posts, as
	 *                           update_post_term_cache and update_post_meta_cache. Null when it
	 *                           did not, or when the posts are not the ones it answered with.
	 *
	 * @return void
	 */
	protected function prime_shown_posts( $query, $asked, $primed = null ) {
		$flags = $this->get_query_cache_flags( $asked );

		// Not gated on cache_results. Core primes a split query as the update flags ask whatever
		// cache_results says, and every deferring grid's query splits when nothing rewrote it.
		if ( ! $query->posts ) {
			return;
		}

		$ids = [];

		foreach ( $query->posts as $post ) {
			if ( $post instanceof WP_Post ) {
				$ids[] = $post->ID;
			}
		}

		if ( ! $ids ) {
			return;
		}

		$asks = [
			'update_post_term_cache' => (bool) $flags['update_post_term_cache'],
			'update_post_meta_cache' => (bool) $flags['update_post_meta_cache'],
		];

		if ( $asks !== $primed ) {
			_prime_post_caches( $ids, $asks['update_post_term_cache'], $asks['update_post_meta_cache'] );
		}

		// Core queues term meta while it builds the posts, reading only terms already cached. On
		// a fallback core did not prime, the kept posts' terms missed that queue. Queueing twice
		// is harmless.
		if ( $flags['lazy_load_term_meta'] ) {
			wp_queue_posts_for_term_meta_lazyload( $query->posts );
		}
	}

	/**
	 * The cache flags WP_Query fills in for these args.
	 *
	 * Mirrors the defaults at the top of WP_Query::get_posts(): each flag defaults to true,
	 * lazy_load_term_meta follows update_post_term_cache when it is not set, and setting it
	 * turns update_post_term_cache back on, because lazy loading term meta needs term caches.
	 *
	 * @since 2.41.0
	 *
	 * @param array $query_args The query args.
	 *
	 * @return array
	 */
	protected function get_query_cache_flags( $query_args ) {
		$flags = [
			'cache_results'          => $query_args['cache_results'] ?? true,
			'update_post_meta_cache' => $query_args['update_post_meta_cache'] ?? true,
			'update_post_term_cache' => $query_args['update_post_term_cache'] ?? true,
		];

		if ( ! isset( $query_args['lazy_load_term_meta'] ) ) {
			$flags['lazy_load_term_meta'] = $flags['update_post_term_cache'];
		} else {
			$flags['lazy_load_term_meta'] = $query_args['lazy_load_term_meta'];

			if ( $flags['lazy_load_term_meta'] ) {
				$flags['update_post_term_cache'] = true;
			}
		}

		return $flags;
	}

	/**
	 * Appends a post ID tiebreaker to a grid's ORDER BY. A date or author sort breaks ties by ID in
	 * its own direction. Any other sort shows tied posts newest first.
	 *
	 * Every post grid that does not count rows gets this, deferring its excludes or not. Mai Load
	 * More counts rows, so its grids do not. get_query() asks for it with the mai_grid_tiebreak
	 * query var, and removes the var again once the query has run.
	 *
	 * Public only because mai_add_grid_orderby_tiebreaker() calls it. That function is registered
	 * once on posts_orderby, in mai_register_query_cache(), and it stays registered. It only
	 * calls this for a query carrying the mai_grid_tiebreak query var, so Mai_Grid is not loaded
	 * for any other query and this cannot reach into one. Staying registered means an ID-only
	 * copy of a grid's query, run after the page by Mai_Query_Cache, gets the same ORDER BY as
	 * the grid did.
	 *
	 * Why it is needed: when rows tie on the sort column, MySQL is free to answer differently for
	 * different LIMITs, and between two runs of the same statement. A deferring grid asks for
	 * posts_per_page + N rows, so it hits that directly. Measured on larrybrownsports with
	 * comment_count ordering, where 134,207 of 145,646 posts tie at zero: across 120 grid
	 * renders, 20 differed between a LIMIT 6 and a LIMIT 13 read of the same grid, 5 of them
	 * returning genuinely different posts rather than a reshuffle. The faster taxonomy query
	 * (Mai_Post_Grid_Query_Optimizer) also needs an order with no ties before it can promise the
	 * same posts as the statement it replaces.
	 *
	 * The rule, in this order:
	 * 1. An empty ORDER BY, or one that already names the post ID, is returned as it is.
	 * 2. When the last sort key is the post date or the post author, ", ID" follows in that key's
	 *    direction. A key with no direction is ascending, as it is in SQL.
	 * 3. Otherwise, when the post date is already named, ", ID DESC".
	 * 4. Otherwise ", post_date DESC, ID DESC".
	 *
	 * Why the ascending date sort breaks ties ascending: the taxonomy query walks the posts index
	 * in the sort's direction and stops at the LIMIT. A trailing ID DESC on an ascending date sort
	 * does not match that index order, so MySQL sorts every matching row. Measured on eurweb's
	 * biggest category, date ASC with LIMIT 7 took 68 ms with ID ASC and 254 ms with ID DESC.
	 * Beta.5's deferring grids followed the sort direction like this too. Other sorts have no such
	 * index to match, so their tied posts show newest first. A grid sorted by a field nobody filled
	 * in, such as menu order on a site that never used it, then reads like a plain latest posts
	 * list. What this buys is a fixed answer, which can differ from what the database picked
	 * before among tied rows.
	 *
	 * @since 2.41.0
	 * @since 2.41.0 Static, and registered once instead of around each grid's query.
	 * @since 2.41.0 Applied to every grid that does not count rows. Date and author sorts tie by ID in
	 *               their own direction, because ID DESC on an ascending date sort forces a full sort.
	 *               Other sorts tie newest first.
	 *
	 * @param string   $orderby The ORDER BY clause.
	 * @param WP_Query $query   The query.
	 *
	 * @return string
	 */
	public static function add_grid_orderby_tiebreaker( $orderby, $query ) {
		global $wpdb;

		if ( empty( $query->query_vars['mai_grid_tiebreak'] ) ) {
			return $orderby;
		}

		// Already deterministic.
		if ( str_contains( $orderby, "{$wpdb->posts}.ID" ) ) {
			return $orderby;
		}

		// Nothing to append to. An empty ORDER BY means no ordering was requested, and
		// imposing one would change the grid rather than settle a tie.
		if ( ! trim( $orderby ) ) {
			return $orderby;
		}

		$posts = preg_quote( $wpdb->posts, '/' );

		// The last sort key is the date or the author, so the ID follows its direction. The
		// pattern is anchored at the end and starts at the clause or at a comma, so a column
		// inside a function, which a closing bracket follows, or in another table, does not match.
		if ( preg_match( '/(?:^|,)\s*' . $posts . '\.post_(?:date|author)(?:\s+((?i:ASC|DESC)))?\s*$/', $orderby, $last ) ) {
			$direction = 'DESC' === strtoupper( $last[1] ?? '' ) ? 'DESC' : 'ASC';

			return "{$orderby}, {$wpdb->posts}.ID {$direction}";
		}

		// The post date is already one of the sort keys, so a second date key would add nothing.
		// A whole-column match, so the GMT date does not count.
		if ( preg_match( '/(?<!\w)' . $posts . '\.post_date\b/', $orderby ) ) {
			return "{$orderby}, {$wpdb->posts}.ID DESC";
		}

		return "{$orderby}, {$wpdb->posts}.post_date DESC, {$wpdb->posts}.ID DESC";
	}

	/**
	 * Get the term query args.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_term_query_args() {
		$query_args = [
			'taxonomy' => (array) $this->args['taxonomy'],
			'offset'   => $this->args['offset'],
		];

		if ( 'id' !== $this->args['query_by'] ) {
			$number = (int) $this->args['number'];

			// WP_Term_Query treats 0 as unlimited, and "Use 0 to show all" is an advertised
			// setting. Taxonomies run far larger than editors expect (tens of thousands of
			// tags is normal), and term grids get no query caching, so cap it.
			if ( $number <= 0 ) {
				/**
				 * Filters the ceiling applied when a term grid is set to show all entries.
				 *
				 * Return 0 or a negative number to opt back into an unbounded query.
				 *
				 * @since 2.41.0
				 *
				 * @param int $max The maximum number of terms. Default 1000.
				 */
				$max    = (int) apply_filters( 'mai_term_grid_max_number', 1000 );
				$number = $max > 0 ? $max : 0;
			}

			$query_args['number'] = $number;
		}

		// Handle query_by.
		switch ( $this->args['query_by'] ) {
			// "Taxonomy" name is the default in WP_Term_Query.
			case 'name':
				// Top level terms only. Only add if at least one taxonomy is hierarchical. See #597.
				// TODO: Add a setting to show child terms too.
				foreach ( (array) $this->args['taxonomy'] as $taxonomy ) {
					// Skip if taxonomy does not exist.
					if ( is_taxonomy_hierarchical( $taxonomy ) ) {
						$query_args['parent'] = 0;
						break;
					}
				}
			break;
			case 'id':
				// Empty array returns all terms, array(-1) prevents this.
				$query_args['include'] = $this->args['include'] ?: [ -1 ];
				$query_args['orderby'] = 'include';
				$query_args['order']   = 'ASC';
			break;
			case 'parent':
				if ( $this->args['current_children'] ) {
					if ( is_category() || is_tag() || is_tax() ) {
						$term_id = get_queried_object_id();
					}
					if ( isset( $term_id ) && $term_id ) {
						$query_args['parent'] = $term_id;
					}
				} else {
					$query_args['parent'] = $this->args['parent'];
				}
			break;
		}

		// Orderby.
		if ( $this->args['orderby'] && ( 'id' !== $this->args['query_by'] ) ) {
			$query_args['orderby'] = $this->args['orderby'];
		}

		// Order.
		if ( $this->args['order'] && ( 'id' !== $this->args['query_by'] ) ) {
			$query_args['order'] = $this->args['order'];
		}

		// Exclude.
		if ( $this->args['exclude'] && ( 'id' !== $this->args['query_by'] ) ) {
			$query_args['exclude'] = $this->args['exclude'];
		}

		// Exclude terms with no posts.
		if ( $this->args['excludes'] && in_array( 'hide_empty', $this->args['excludes'], true ) ) {
			$query_args['hide_empty'] = true;
		} else {
			$query_args['hide_empty'] = false;
		}

		// Not sure if this is needed. Added this commented out code when we hit the bug in post__not_in in WP_Query above.
		// Make sure existing term IDs are for the taxonomies we are querying.
		// if ( ! empty( self::$existing_term_ids ) ) {
		// 	foreach ( self::$existing_term_ids as $index => $existing_term_id ) {
		// 		// Remove term IDs that are not in any of the taxonomies from the query.
		// 		if ( ! in_array( get_term( $existing_term_id )->taxonomy, (array) $this->args['taxonomy'] ) ) {
		// 			unset( self::$existing_term_ids[ $index ] );
		// 		}
		// 	}
		// }

		// Exclude displayed.
		if ( $this->args['excludes'] && in_array( 'exclude_displayed', $this->args['excludes'], true ) && ! empty( self::$existing_term_ids ) ) {
			if ( isset( $query_args['exclude'] ) ) {
				$query_args['exclude'] = array_merge( $query_args['exclude'], self::$existing_term_ids );
			} else {
				$query_args['exclude'] = self::$existing_term_ids;
			}
		}

		// Exclude current.
		if ( ( is_category() || is_tag() || is_tax() ) && $this->args['excludes'] && in_array( 'exclude_current', $this->args['excludes'], true ) ) {
			if ( isset( $query_args['exclude'] ) ) {
				$query_args['exclude'][] = get_queried_object_id();
			} else {
				$query_args['exclude'] = [ get_queried_object_id() ];
			}
		}

		return apply_filters( 'mai_term_grid_query_args', $query_args, $this->args );
	}
}
