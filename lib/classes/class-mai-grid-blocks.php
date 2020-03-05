<?php

/**
 * Get Mai_Grid_Blocks Running.
 *
 * @since   0.1.0
 * @return  object
 */
// add_action( 'plugins_loaded', function() {
// 	return Mai_Grid_Blocks::instance();
// });

final class Mai_Grid_Blocks  {

	/**
	 * @var    Mai_Grid_Blocks The one true Mai_Grid_Blocks
	 * @since  0.1.0
	 */
	private static $instance;

	private $helper;
	private $config;
	private $fields;

	/**
	 * Main Mai_Grid_Blocks Instance.
	 *
	 * Insures that only one instance of Mai_Grid_Blocks exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since   0.1.0
	 * @static  var array $instance
	 * @return  object | Mai_Grid_Blocks The one true Mai_Grid_Blocks
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			// Setup the setup.
			self::$instance = new Mai_Grid_Blocks;
			// Methods.
			self::$instance->run();
		}
		return self::$instance;
	}

	/**
	 * Throw error on object clone.
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since   0.1.0
	 * @access  protected
	 * @return  void
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'mai-grid' ), '1.0' );
	}

	/**
	 * Disable unserializing of the class.
	 *
	 * @since   0.1.0
	 * @access  protected
	 * @return  void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'mai-grid' ), '1.0' );
	}

	function run() {
		add_action( 'acf/init', array( $this, 'blocks_init' ), 10, 3 );
		add_action( 'acf/init', array( $this, 'register_blocks' ), 10, 3 );
	}

	function blocks_init() {
		$this->config = new Mai_Entry_Settings( 'block' );
		$this->fields = $this->config->fields;
		$this->run_filters();
	}

	function register_blocks() {
		// Bail if no ACF Pro >= 5.8.
		if ( ! function_exists( 'acf_register_block_type' ) ) {
			return;
		}
		// Mai Post Grid.
		acf_register_block_type( array(
			'name'            => 'mai-post-grid',
			'title'           => __( 'Mai Post Grid', 'mai-grid' ),
			'description'     => __( 'Display posts/pages/cpts in various layouts.', 'mai-grid' ),
			'icon'            => 'grid-view',
			'category'        => 'widgets',
			'keywords'        => array( 'grid', 'post', 'page' ),
			// 'mode'            => 'auto',
			// 'mode'            => 'edit',
			'mode'            => 'preview',
			'enqueue_assets'  => array( $this, 'enqueue_assets'),
			'render_callback' => array( $this, 'do_post_grid' ),
			'supports'        => array(
				'align'  => array( 'wide' ),
				'ancher' => true,
			),
		) );
		// Mai Term Grid.
		acf_register_block_type( array(
			'name'            => 'mai-term-grid',
			'title'           => __( 'Mai Term Grid', 'mai-grid' ),
			'description'     => __( 'Display posts/pages/cpts in various layouts.', 'mai-grid' ),
			'icon'            => 'grid-view',
			'category'        => 'widgets',
			'keywords'        => array( 'grid', 'category', 'term' ),
			// 'mode'            => 'auto',
			// 'mode'            => 'edit',
			'mode'            => 'preview',
			'enqueue_assets'  => array( $this, 'enqueue_assets'),
			'render_callback' => array( $this, 'do_term_grid' ),
			'supports'        => array(
				'align'  => array( 'wide' ),
				'ancher' => true,
			),
		) );
	}

	function enqueue_assets() {
		if ( ! is_admin() ) {
			return;
		}
		// mai_enqueue_asset( 'fields', 'css' );
		mai_enqueue_asset( 'mai-grid-sortable', 'sortable', 'js' );
	}

	function do_post_grid( $block, $content = '', $is_preview = false ) {
		// TODO: block id?
		$this->do_grid( 'post', $block, $content = '', $is_preview = false );
	}

	function do_term_grid( $block, $content = '', $is_preview = false ) {
		// TODO: block id?
		$this->do_grid( 'term', $block, $content = '', $is_preview = false );
	}

	function do_grid( $type, $block, $content = '', $is_preview = false ) {
		$args = [
			'type'    => $type,
			'context' => 'block',
		];
		$args = array_merge( $args, $this->get_fields() );
		if ( ! empty( $block['className'] ) ) {
			$args['class'] = ( isset( $args['class'] ) && ! empty( $args['class'] ) ) ? ' ' . $block['className'] : $block['className'];
		}
		$grid = new Mai_Grid_Base( $args );
		$grid->render();
	}

	function get_fields() {
		$fields = [];
		foreach( $this->fields as $name => $field ) {
			// Skip if not a block field.
			if ( ! $field['block'] ) {
				continue;
			}
			// Skip tabs.
			if ( 'tab' === $field['type'] ) {
				continue;
			}
			$fields[ $name ] = $this->get_field( $name );
		}
		return $fields;
	}

	// TODO: Can we get defaults better from settings config?
	// TODO: What if we want null? Will we ever?
	function get_field( $name ) {
		$value = get_field( $name );
		return is_null( $value ) ? $this->fields[ $name ]['default'] : $value;
	}

	function run_filters() {

		// Add field wrapper classes.
		add_filter( 'acf/field_wrapper_attributes', function( $wrapper, $field ) {

			// Conditional Show.
			if ( in_array( $field['key'], array(
				$this->fields['image_orientation']['key'],
				$this->fields['image_size']['key'],
				$this->fields['image_position']['key'],
				$this->fields['header_meta']['key'],
				$this->fields['content_limit']['key'],
				$this->fields['more_link_text']['key'],
				$this->fields['footer_meta']['key'],
			) ) ) {
				$wrapper['class'] = isset( $wrapper['class'] ) && ! empty( $wrapper['class'] ) ? $wrapper['class'] . ' mai-grid-show-conditional' : 'mai-grid-show-conditional';
			}

			// Button Group.
			if ( in_array( $field['key'], array(
				$this->fields['align_text']['key'],
				$this->fields['align_text_vertical']['key'],
				$this->fields['columns']['key'],
				$this->fields['columns_md']['key'],
				$this->fields['columns_sm']['key'],
				$this->fields['columns_xs']['key'],
				$this->fields['align_columns']['key'],
				$this->fields['align_columns_vertical']['key'],
			) ) ) {
				$wrapper['class'] = isset( $wrapper['class'] ) && ! empty( $wrapper['class'] ) ? $wrapper['class'] . ' mai-grid-button-group' : 'mai-grid-button-group';
			}

			// Button Group.
			if ( in_array( $field['key'], array(
				$this->fields['align_text']['key'],
				$this->fields['align_text_vertical']['key'],
				$this->fields['columns_md']['key'],
				$this->fields['columns_sm']['key'],
				$this->fields['columns_xs']['key'],
				$this->fields['align_columns']['key'],
				$this->fields['align_columns_vertical']['key'],
			) ) ) {
				$wrapper['class'] = isset( $wrapper['class'] ) && ! empty( $wrapper['class'] ) ? $wrapper['class'] . ' mai-grid-button-group-clear' : 'mai-grid-button-group-clear';
			}

			// Nested Columns.
			if ( in_array( $field['key'], array(
				$this->fields['columns_md']['key'],
				$this->fields['columns_sm']['key'],
				$this->fields['columns_xs']['key'],
			) ) ) {
				$wrapper['class'] = isset( $wrapper['class'] ) && ! empty( $wrapper['class'] ) ? $wrapper['class'] . ' mai-grid-nested-columns' : 'mai-grid-nested-columns';
			}

			// Nested Columns First.
			if ( in_array( $field['key'], array(
				$this->fields['columns_md']['key'],
			) ) ) {
				$wrapper['class'] = isset( $wrapper['class'] ) && ! empty( $wrapper['class'] ) ? $wrapper['class'] . ' mai-grid-nested-columns-first' : 'mai-grid-nested-columns-first';
			}

			// Nested Columns Last.
			if ( in_array( $field['key'], array(
				$this->fields['columns_xs']['key'],
			) ) ) {
				$wrapper['class'] = isset( $wrapper['class'] ) && ! empty( $wrapper['class'] ) ? $wrapper['class'] . ' mai-grid-nested-columns-last' : 'mai-grid-nested-columns-last';
			}

			return $wrapper;

		}, 10, 2 );

		// Add filters.
		foreach( $this->fields as $name => $values ) {
			// Skip if not an ACF field.
			if ( ! $values['block'] ) {
				return;
			}
			// Choices.
			if ( method_exists( $this->config, $name ) ) {
				add_filter( "acf/load_field/key={$values['key']}", function( $field ) {
					// Set choices from our config function.
					$field['choices'] = $this->config->get_choices( $field['name'] );
					return $field;
				});
			}
			// Sub fields.
			if ( isset( $values['acf']['sub_fields'] ) ) {
				foreach( $values['acf']['sub_fields'] as $sub_name => $sub_values ) {
					// Choices.
					if ( method_exists( $this->config, $sub_name ) ) {
						add_filter( "acf/load_field/key={$sub_values['key']}", function( $field ) {
							// Set choices from our config function.
							$field['choices'] = $this->config->get_choices( $field['name'] );
							return $field;
						});
					}
					// // Defaults.
					// add_filter( "acf/load_field/key={$values['key']}", function( $field ) use ( $name ) {
					// 	// Set default from our config function.
					// 	$field['default'] = $this->fields[ $field['name'] ]['default'];
					// 	return $field;
					// });
				}
			}
			// // Standard fields.
			// else {
			// 	// Defaults.
			// 	add_filter( "acf/load_field/key={$values['key']}", function( $field ) use ( $name ) {
			// 		// Set default from our config function.
			// 		$field['default'] = $this->fields[ $field['name'] ]['default'];
			// 		return $field;
			// 	});
			// }
		}

		// Show.
		add_filter( "acf/load_field/key={$this->fields['show']['key']}",                                                array( $this, 'load_show' ) );
		// More Link Text.
		// add_filter( "acf/load_field/key={$this->fields['more_link_text']['key']}",                                      array( $this, 'load_more_link_text' ) );
		// Posts.
		add_filter( "acf/fields/post_object/query/key={$this->fields['post__in']['key']}",                              array( $this, 'get_posts' ), 10, 1 );
		// Terms.
		add_filter( "acf/fields/taxonomy/query/key={$this->fields['taxonomies']['acf']['sub_fields']['terms']['key']}", array( $this, 'get_terms' ), 10, 1 );
		// Parent.
		add_filter( "acf/fields/post_object/query/key={$this->fields['post_parent__in']['key']}",                       array( $this, 'get_parents' ), 10, 1 );
	}

	function load_show( $field ) {

		// Default choices, in default order.
		$field['choices'] = $this->config->get_choices( 'show' );

		// Get existing values, which are sorted correctly, without infinite loop.
		remove_filter( "acf/load_field/key={$this->fields['show']['key']}", array( $this, 'load_show' ) );
		$existing = get_field( 'show' );
		$defaults = $field['choices'];
		add_filter( "acf/load_field/key={$this->fields['show']['key']}", array( $this, 'load_show' ) );

		// If we have existing values, reorder them.
		$field['choices'] = $existing ? array_merge( array_flip( $existing ), $defaults ) : $field['choices'];

		return $field;
	}

	/**
	 * KEEEP THIS FOR PLACEHOLDER, BUT USE THE DEFAULT!
	 */
	// function load_more_link_text( $field ) {
	// 	$field['placeholder'] = __( 'Read More', 'mai-grid' );
	// 	return $field;
	// }

	function get_posts( $args ) {
		if ( isset( $_REQUEST['post_type'] ) && ! empty( $_REQUEST['post_type'] ) ) {
			$args['post_type'] = $_REQUEST['post_type'];
		}
		return $args;
	}

	function get_terms( $args ) {
		if ( isset( $_REQUEST['taxonomy'] ) && ! empty( $_REQUEST['taxonomy'] ) ) {
			$args['taxonomy'] = $_REQUEST['taxonomy'];
		}
		return $args;
	}

	function get_parents( $args ) {
		if ( isset( $_REQUEST['post_type'] ) && ! empty( $_REQUEST['post_type'] ) ) {
			$args['post_type'] = $_REQUEST['post_type'];
		}
		return $args;
	}

}
