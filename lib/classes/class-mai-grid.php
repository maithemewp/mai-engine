<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2019 BizBudding
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
		$this->type      = isset( $args['type'] ) ?: 'post';
		$this->settings  = $this->get_settings();
		$this->defaults  = $this->get_defaults();
		$this->args      = $this->get_sanitized_args( $args );
	}

	public function get_settings() {
		$settings = [];
		$config   = mai_get_config( 'grid-settings' );
		foreach( $config as $key => $setting ) {
			// Skip tabs.
			if ( 'tab' === $setting['type'] ) {
				continue;
			}
			// Skip fields not in this grid type.
			if ( ! in_array( $this->type, $setting['block'] ) ) {
				continue;
			}
			$settings[ $setting['name'] ] = $setting;
		}
		return $settings;
	}

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
							$sub_values[ $index ][ $sub_name ] = $this->sanitize( $sub_value, $field['sanitize'] );
						}
					}
					$args[ $name ] = $sub_values;
				}
			} else {
				// Standard field. Check
				$sanitize      = isset( $this->settings[ $name ] ) ? $this->settings[ $name ]['sanitize'] : 'esc_html';
				$args[ $name ] = $this->sanitize( $value, $sanitize );
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

		// Grid specific classes.
		$this->args['class'] = isset( $this->args['class'] ) ? $this->args['class'] : '';
		$this->args['class'] = 'mai-engine ' . $this->args['class'];
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

					endwhile;
				}

				wp_reset_postdata();
				break;
			case 'term':
				$term_query = new WP_Term_Query( $this->get_term_query_args() );
				foreach ( $term_query->terms as $term ) {
					mai_do_entry( $term, $this->args );
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
			'offset'              => absint( $this->args['offset'] ),
			'ignore_sticky_posts' => true,
		];

		// Handle query_by.
		switch ( $this->args['query_by'] ) {
			case 'parent':
				$query_args['post_parent__in'] = $this->args['post_parent__in'];
				break;
			case 'title':
				// Empty array returns all posts, so we need to check for values.
				if ( $this->args['post__in'] ) {
					$query_args['post__in'] = $this->args['post__in'];
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

		// Exclude entries.
		if ( ( 'title' !== $this->args['query_by'] ) && $this->args['post__not_in'] ) {
			$query_args['post__not_in'] = $this->args['post__not_in'];
		}

		// Orderby.
		if ( $this->args['orderby'] ) {
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

		return apply_filters( 'mai_post_grid_query_args', $query_args );
	}

	public function get_term_query_args() {
		$query_args = [
			'taxonomy' => $this->args['taxonomy'],
			'number'   => $this->args['number'],
			'offset'   => absint( $this->args['offset'] ),
		];

		return apply_filters( 'mai_term_grid_query_args', $query_args );
	}

	/**
	 * Sanitize a value. Checks for null/array.
	 *
	 * @param   string $value      The value to sanitize.
	 * @param   string $function   The function to use for escaping.
	 * @param   bool   $allow_null Wether to return or escape if the value is.
	 *
	 * @return  mixed
	 */
	public function sanitize( $value, $function = 'esc_html', $allow_null = false ) {

		// Return null if allowing null.
		if ( is_null( $value ) && $allow_null ) {
			return $value;
		}

		// If array, escape and return it.
		if ( is_array( $value ) ) {
			$escaped = [];
			foreach ( $value as $index => $item ) {
				if ( is_array( $item ) ) {
					$escaped[ $index ] = $this->sanitize( $item, $function );
				} else {
					$item              = trim( $item );
					$escaped[ $index ] = $function( $item );
				}
			}

			return $escaped;
		}

		// Return single value.
		$value   = trim( $value );
		$escaped = $function( $value );

		return $escaped;
	}
}
