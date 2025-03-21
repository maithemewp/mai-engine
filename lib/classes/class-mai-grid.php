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
	static protected $index = 0;

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
		$this::$index++;

		// Add index to args.
		$this->args['index'] = $this::$index;

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
		mai_do_entries_open( $this->args );

		// Entries.
		$this->do_grid_entries();

		// Close.
		mai_do_entries_close( $this->args );
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

					$query = new WP_Query( $this->query_args );

					// Cache featured images.
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
		$post_status  = is_user_logged_in() && current_user_can( 'edit_posts' ) ? [ 'publish', 'private' ] : 'publish';
		$per_page     = ( 0 === $this->args['posts_per_page'] ) ? -1 : $this->args['posts_per_page'];
		$per_page     = ( 'id' === $this->args['query_by'] ) ? count( (array) $this->args['post__in'] ) : $per_page;
		$query_args   = [
			'post_type'           => $this->args['post_type'],
			'posts_per_page'      => $per_page,
			'post_status'         => $post_status,
			'offset'              => $this->args['offset'],
			'ignore_sticky_posts' => true,
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
							if ( ! is_admin() ) {
								if ( is_category() || is_tag() || is_tax() ) {
									$terms[] = get_queried_object_id();
								} elseif ( is_singular() ) {
									$entry_terms = wp_get_post_terms( get_the_ID(), $taxonomy );

									if ( ! is_wp_error( $entry_terms ) ) {
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
			}

			// Exclude current.
			if ( is_singular() && $this->args['excludes'] && in_array( 'exclude_current', $this->args['excludes'], true ) ) {
				if ( isset( $query_args['post__not_in'] ) ) {
					$query_args['post__not_in'][] = get_the_ID();
				} else {
					$query_args['post__not_in'] = [ get_the_ID() ];
				}
			}
		}

		return apply_filters( 'mai_post_grid_query_args', $query_args, $this->args );
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
			$query_args['number'] = $this->args['number'];
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
				$query_args['exclude'] = array_push( $query_args['exclude'], self::$existing_term_ids );
			} else {
				$query_args['exclude'] = self::$existing_term_ids;
			}
		}

		// Exclude current.
		if ( ( is_category() || is_tag() || is_tax() ) && $this->args['excludes'] && in_array( 'exclude_current', $this->args['excludes'] ) ) {
			if ( isset( $query_args['post__not_in'] ) ) {
				$query_args['post__not_in'] = array_push( $query_args['post__not_in'], get_queried_object_id() );
			} else {
				$query_args['post__not_in'] = [ get_queried_object_id() ];
			}
		}

		return apply_filters( 'mai_term_grid_query_args', $query_args, $this->args );
	}
}
