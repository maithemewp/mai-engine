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

/**
 * Instantiate a grid.
 * Use render() method to display.
 */
class Mai_Grid {

	/**
	 * Type.
	 *
	 * @var $type
	 */
	protected $type;

	/**
	 * Settings.
	 *
	 * @var $settings
	 */
	protected $settings;

	/**
	 * Defaults.
	 *
	 * @var $defaults
	 */
	protected $defaults;

	/**
	 * Args.
	 *
	 * @var $args
	 */
	protected $args;

	// All displayed items incase exclude_displayed is true in any instance of grid.
	public static $existing_post_ids = array();
	public static $existing_term_ids = array();

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
		$args['context'] = 'block'; // Required for Mai_Entry.
		$this->type      = isset( $args['type'] ) ? $args['type'] : 'post';
		$this->settings  = $this->get_settings();
		$this->defaults  = $this->get_defaults();
		$this->args      = $this->get_sanitized_args( $args );
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_settings() {
		$settings = [];
		$config   = mai_get_settings( 'grid-block' );

		foreach ( $config as $key => $setting ) {

			// Skip tabs.
			if ( 'tab' === $setting['type'] ) {
				continue;
			}

			// Skip fields not in this grid type.
			if ( ! in_array( $this->type, $setting['block'], true ) ) {
				continue;
			}

			$settings[ $setting['name'] ] = $setting;
		}

		return $settings;
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_defaults() {
		return wp_list_pluck( $this->settings, 'default' );
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Loop args.
	 *
	 * @return array
	 */
	public function get_sanitized_args( $args ) {

		// Parse args.
		$args = wp_parse_args( $args, $this->defaults );

		// Sanitize.
		foreach ( $args as $name => $value ) {
			// Has sub fields.
			if ( isset( $this->settings[ $name ]['atts']['sub_fields'] ) ) {
				if ( $value ) {
					$sub_values = [];
					foreach ( $value as $index => $group ) {
						foreach ( $group as $sub_name => $sub_value ) {
							$field                             = $this->settings[ $name ]['atts']['sub_fields'][ $sub_name ];
							$sub_values[ $index ][ $sub_name ] = mai_sanitize( $sub_value, $field['sanitize'] );
						}
					}
					$args[ $name ] = $sub_values;
				}
			} else {
				// Standard field check.
				$sanitize      = isset( $this->settings[ $name ] ) ? $this->settings[ $name ]['sanitize'] : 'esc_html';
				$args[ $name ] = mai_sanitize( $value, $sanitize );
			}
		}

		return apply_filters( 'mai_grid_args', $args );
	}

	/**
	 * Description of expected behavior.
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
	 * Description of expected behavior.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function do_grid_entries() {
		switch ( $this->args['type'] ) {
			case 'post':
				$posts = new WP_Query( $this->get_post_query_args() );
				if ( $posts->have_posts() ) {
					while ( $posts->have_posts() ) :
						$posts->the_post();
						global $post;
						mai_do_entry( $post, $this->args );
						// Add this post to the existing post IDs.
						$this::$existing_post_ids[] = get_the_ID();
					endwhile;
					// Clear duplicate IDs.
					$this::$existing_post_ids = array_unique( $this::$existing_post_ids );
				}

				wp_reset_postdata();
				break;
			case 'term':
				$term_query = new WP_Term_Query( $this->get_term_query_args() );
				if ( ! empty( $term_query->terms ) ) {
					foreach ( $term_query->terms as $term ) {
						mai_do_entry( $term, $this->args );
						// Add this term to the existing term IDs.
						$this::$existing_term_ids[] = $term->term_id;
					}
					// Clear duplicate IDs.
					$this::$existing_term_ids = array_unique( $this::$existing_term_ids );
				}
				break;
			case 'user':
				// TODO.
				break;
		}
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_post_query_args() {
		$query_args = [
			'post_type'           => $this->args['post_type'],
			'posts_per_page'      => $this->args['posts_per_page'],
			'post_status'         => 'publish',
			'offset'              => $this->args['offset'],
			'ignore_sticky_posts' => true,
		];

		// Handle query_by.
		switch ( $this->args['query_by'] ) {
			case 'parent':
				$query_args['post_parent__in'] = $this->args['post_parent__in'];
				break;
			case 'id':
				// Empty array returns all posts, so we need to check for values.
				if ( $this->args['post__in'] ) {
					$query_args['post__in'] = $this->args['post__in'];
					$this->args['orderby']  = 'post__in';
				}
				break;
			case 'tax_meta':
				$tax_query = [];
				if ( $this->args['taxonomies'] ) {
					foreach ( $this->args['taxonomies'] as $taxo ) {
						// Skip if we don't have all the tax query args.
						if ( ! ( $taxo['taxonomy'] && $taxo['taxonomy'] && $taxo['taxonomy'] ) ) {
							continue;
						}
						// Set the value.
						$tax_query[] = [
							'taxonomy' => $taxo['taxonomy'],
							'field'    => 'id',
							'terms'    => $taxo['terms'],
							'operator' => $taxo['operator'],
						];
					}

					// If we have tax query values.
					if ( $tax_query ) {

						// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						$query_args['tax_query'] = $tax_query;

						if ( $this->args['taxonomies_relation'] ) {
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
							$query_args['tax_query'][] = [
								'relation' => $this->args['taxonomies_relation'],
							];
						}
					}
				}

				break;
		}

		// Orderby.
		if ( $this->args['orderby'] && 'id' !== $this->args['query_by'] ) {
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

		// Exclude entries.
		if ( ( 'title' !== $this->args['query_by'] ) && $this->args['post__not_in'] ) {
			$query_args['post__not_in'] = $this->args['post__not_in'];
		}

		// Exclude displayed.
		if ( $this->args['excludes'] && in_array( 'exclude_displayed', $this->args['excludes'] ) && ! empty( $this::$existing_post_ids ) ) {
			if ( isset( $query_args['post__not_in'] ) ) {
				$query_args['post__not_in'] = array_push( $query_args['post__not_in'], $this::$existing_post_ids );
			} else {
				$query_args['post__not_in'] = $this::$existing_post_ids;
			}
		}

		// Exclude current.
		if ( is_singular() && $this->args['excludes'] && in_array( 'exclude_current', $this->args['excludes'] ) ) {
			if ( isset( $query_args['post__not_in'] ) ) {
				$query_args['post__not_in'][] = get_the_ID();
			} else {
				$query_args['post__not_in'] = [ get_the_ID() ];
			}
		}

		return apply_filters( 'mai_post_grid_query_args', $query_args );
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_term_query_args() {
		$query_args = [
			'taxonomy' => $this->args['taxonomy'],
			'number'   => $this->args['number'],
			'offset'   => $this->args['offset'],
		];

		// Handle query_by.
		switch ( $this->args['query_by'] ) {
			case 'name':
				// Nothing, this is the default.
				break;
			case 'id':
				$query_args['include'] = $this->args['include'];
				$this->args['orderby'] = 'include';
				break;
			case 'parent':
				$query_args['parent'] = $this->args['parent'];
				break;
		}

		// Orderby.
		if ( $this->args['orderby'] && 'id' !== $this->args['query_by'] ) {
			$query_args['orderby'] = $this->args['orderby'];
		}

		// Order.
		if ( $this->args['order'] ) {
			$query_args['order'] = $this->args['order'];
		}

		// Exclude.
		if ( $this->args['exclude'] && 'id' !== $this->args['query_by'] ) {
			$query_args['exclude'] = $this->args['exclude'];
		}

		// Exclude terms with no posts.
		if ( $this->args['excludes'] && in_array( 'hide_empty', $this->args['excludes'] ) ) {
			$query_args['hide_empty'] = true;
		} else {
			$query_args['hide_empty'] = false;
		}

		// Exclude displayed.
		if ( $this->args['excludes'] && in_array( 'exclude_displayed', $this->args['excludes'] ) && ! empty( $this::$existing_term_ids ) ) {
			if ( isset( $query_args['exclude'] ) ) {
				$query_args['exclude'] = array_push( $query_args['exclude'], $this::$existing_term_ids );
			} else {
				$query_args['exclude'] = $this::$existing_term_ids;
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

		return apply_filters( 'mai_term_grid_query_args', $query_args );
	}

}
