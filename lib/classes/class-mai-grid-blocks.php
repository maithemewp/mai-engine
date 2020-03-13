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
 * Get Mai_Grid_Blocks Running.
 *
 * @since   0.1.0
 * @return  object
 */
final class Mai_Grid_Blocks {

	/**
	 * Instance.
	 *
	 * @var    Mai_Grid_Blocks The one true Mai_Grid_Blocks
	 * @since  0.1.0
	 */
	private static $instance;

	/**
	 * Settings.
	 *
	 * @var object $settings Mai_Entry_Settings
	 */
	private $settings;

	/**
	 * Fields.
	 *
	 * @var $fields
	 */
	private $fields;

	/**
	 * Main Mai_Grid_Blocks Instance.
	 *
	 * Insures that only one instance of Mai_Grid_Blocks exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since   0.1.0
	 *
	 * @static  var array $instance
	 *
	 * @return  object | Mai_Grid_Blocks The one true Mai_Grid_Blocks
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Mai_Grid_Blocks();
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
	 *
	 * @access  protected
	 *
	 * @return  void
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'mai-engine' ), '1.0' );
	}

	/**
	 * Disable unserializing of the class.
	 *
	 * @since   0.1.0
	 *
	 * @access  protected
	 *
	 * @return  void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'mai-engine' ), '1.0' );
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function run() {
		add_action( 'acf/init', [ $this, 'blocks_init' ], 10, 3 );
		add_action( 'acf/init', [ $this, 'register_blocks' ], 10, 3 );
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function blocks_init() {
		$this->settings = new Mai_Entry_Settings( 'block' );
		$this->fields   = $this->settings->fields;
		$this->run_filters();
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_blocks() {
		// Bail if no ACF Pro >= 5.8.
		if ( ! function_exists( 'acf_register_block_type' ) ) {
			return;
		}
		// Mai Post Grid.
		acf_register_block_type(
			[
				'name'            => 'mai-post-grid',
				'title'           => __( 'Mai Post Grid', 'mai-engine' ),
				'description'     => __( 'Display posts/pages/cpts in various layouts.', 'mai-engine' ),
				'icon'            => 'grid-view',
				'category'        => 'widgets',
				'keywords'        => [ 'grid', 'post', 'page' ],
				'mode'            => 'preview',
				'render_callback' => [ $this, 'do_post_grid' ],
				'supports'        => [
					'align'  => [ 'wide' ],
					'ancher' => true,
				],
			]
		);
		// Mai Term Grid.
		acf_register_block_type(
			[
				'name'            => 'mai-term-grid',
				'title'           => __( 'Mai Term Grid', 'mai-engine' ),
				'description'     => __( 'Display posts/pages/cpts in various layouts.', 'mai-engine' ),
				'icon'            => 'grid-view',
				'category'        => 'widgets',
				'keywords'        => [ 'grid', 'category', 'term' ],
				'mode'            => 'preview',
				'render_callback' => [ $this, 'do_term_grid' ],
				'supports'        => [
					'align'  => [ 'wide' ],
					'ancher' => true,
				],
			]
		);
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 0.1.0
	 *
	 * @param array  $block      Block object.
	 * @param string $content    String of content.
	 * @param bool   $is_preview Is preview check.
	 *
	 * @return void
	 */
	public function do_post_grid( $block, $content = '', $is_preview = false ) {
		// TODO: block id?
		$this->do_grid( 'post', $block, $content = '', $is_preview = false );
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 0.1.0
	 *
	 * @param array  $block      Block object.
	 * @param string $content    Content string.
	 * @param bool   $is_preview Is preview check.
	 *
	 * @return void
	 */
	public function do_term_grid( $block, $content = '', $is_preview = false ) {
		// TODO: block id?
		$this->do_grid( 'term', $block, $content = '', $is_preview = false );
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 0.1.0
	 *
	 * @param string $type       Type of grid.
	 * @param array  $block      Block object.
	 * @param string $content    Content string.
	 * @param bool   $is_preview Is preview check.
	 *
	 * @return void
	 */
	public function do_grid( $type, $block, $content = '', $is_preview = false ) {
		$args = [
			'type'    => $type,
			'context' => 'block',
		];

		$args = array_merge( $args, $this->get_fields() );

		if ( ! empty( $block['className'] ) ) {
			$args['class'] = ( isset( $args['class'] ) && ! empty( $args['class'] ) ) ? ' ' . $block['className'] : $block['className'];
		}

		$grid = new Mai_Grid( $args );
		$grid->render();
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_fields() {
		$fields = [];
		foreach ( $this->fields as $name => $field ) {
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

	/**
	 * Description of expected behavior.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Field name.
	 *
	 * @return mixed
	 *
	 * @todo  Can we get defaults better from settings config?
	 * @todo  What if we want null? Will we ever?
	 */
	public function get_field( $name ) {
		$value = get_field( $name );

		return is_null( $value ) ? $this->fields[ $name ]['default'] : $value;
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function run_filters() {

		// Add field wrapper classes.
		add_filter(
			'acf/field_wrapper_attributes',
			function ( $wrapper, $field ) {

				// Conditional Show.
				if ( in_array(
					$field['key'],
					[
						$this->fields['image_orientation']['key'],
						$this->fields['image_size']['key'],
						$this->fields['image_position']['key'],
						$this->fields['header_meta']['key'],
						$this->fields['content_limit']['key'],
						$this->fields['more_link_text']['key'],
						$this->fields['footer_meta']['key'],
					],
					true
				) ) {
					$wrapper['class'] = isset( $wrapper['class'] ) && ! empty( $wrapper['class'] ) ? $wrapper['class'] . ' mai-engine-show-conditional' : 'mai-engine-show-conditional';
				}

				// Button Group.
				if ( in_array(
					$field['key'],
					[
						$this->fields['align_text']['key'],
						$this->fields['align_text_vertical']['key'],
						$this->fields['columns']['key'],
						$this->fields['columns_md']['key'],
						$this->fields['columns_sm']['key'],
						$this->fields['columns_xs']['key'],
						$this->fields['align_columns']['key'],
						$this->fields['align_columns_vertical']['key'],
					],
					true
				) ) {
					$wrapper['class'] = isset( $wrapper['class'] ) && ! empty( $wrapper['class'] ) ? $wrapper['class'] . ' mai-engine-button-group' : 'mai-engine-button-group';
				}

				// Button Group.
				if ( in_array(
					$field['key'],
					[
						$this->fields['align_text']['key'],
						$this->fields['align_text_vertical']['key'],
						$this->fields['columns_md']['key'],
						$this->fields['columns_sm']['key'],
						$this->fields['columns_xs']['key'],
						$this->fields['align_columns']['key'],
						$this->fields['align_columns_vertical']['key'],
					],
					true
				) ) {
					$wrapper['class'] = isset( $wrapper['class'] ) && ! empty( $wrapper['class'] ) ? $wrapper['class'] . ' mai-engine-button-group-clear' : 'mai-engine-button-group-clear';
				}

				// Nested Columns.
				if ( in_array(
					$field['key'],
					[
						$this->fields['columns_md']['key'],
						$this->fields['columns_sm']['key'],
						$this->fields['columns_xs']['key'],
					],
					true
				) ) {
					$wrapper['class'] = isset( $wrapper['class'] ) && ! empty( $wrapper['class'] ) ? $wrapper['class'] . ' mai-engine-nested-columns' : 'mai-engine-nested-columns';
				}

				// Nested Columns First.
				if ( in_array(
					$field['key'],
					[
						$this->fields['columns_md']['key'],
					],
					true
				) ) {
					$wrapper['class'] = isset( $wrapper['class'] ) && ! empty( $wrapper['class'] ) ? $wrapper['class'] . ' mai-engine-nested-columns-first' : 'mai-engine-nested-columns-first';
				}

				// Nested Columns Last.
				if ( in_array(
					$field['key'],
					[
						$this->fields['columns_xs']['key'],
					],
					true
				) ) {
					$wrapper['class'] = isset( $wrapper['class'] ) && ! empty( $wrapper['class'] ) ? $wrapper['class'] . ' mai-engine-nested-columns-last' : 'mai-engine-nested-columns-last';
				}

				return $wrapper;
			},
			10,
			2
		);

		// Add filters.
		foreach ( $this->fields as $name => $values ) {
			// Skip if not an ACF field.
			if ( ! $values['block'] ) {
				continue;
			}
			// Choices.
			if ( method_exists( $this->settings, $name ) ) {
				add_filter(
					"acf/load_field/key={$values['key']}",
					function ( $field ) use( $name) {
						// Set choices from our config function.
						$field['choices'] = $this->settings->get_choices( $field['name'] );
						return $field;
					}
				);
			}
			// Sub fields.
			if ( isset( $values['acf']['sub_fields'] ) ) {
				foreach ( $values['acf']['sub_fields'] as $sub_name => $sub_values ) {
					// Choices.
					if ( method_exists( $this->settings, $sub_name ) ) {
						add_filter(
							"acf/load_field/key={$sub_values['key']}",
							function ( $field ) {
								// Set choices from our config function.
								$field['choices'] = $this->settings->get_choices( $field['name'] );

								return $field;
							}
						);
					}
				}
			}
		}

		// Show.
		add_filter( "acf/load_field/key={$this->fields['show']['key']}", [ $this, 'load_show' ] );

		// Posts.
		add_filter(
			"acf/fields/post_object/query/key={$this->fields['post__in']['key']}",
			[
				$this,
				'get_posts',
			],
			10,
			1
		);
		// Terms.
		add_filter(
			"acf/fields/taxonomy/query/key={$this->fields['taxonomies']['acf']['sub_fields']['terms']['key']}",
			[
				$this,
				'get_terms',
			],
			10,
			1
		);
		// Parent.
		add_filter(
			"acf/fields/post_object/query/key={$this->fields['post_parent__in']['key']}",
			[
				$this,
				'get_parents',
			],
			10,
			1
		);
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 0.1.0
	 *
	 * @param array $field Field array.
	 *
	 * @return mixed
	 */
	public function load_show( $field ) {

		// Default choices, in default order.
		$field['choices'] = $this->settings->get_choices( 'show' );

		// Get existing values, which are sorted correctly, without infinite loop.
		remove_filter( "acf/load_field/key={$this->fields['show']['key']}", [ $this, 'load_show' ] );
		$existing = get_field( 'show' );
		$defaults = $field['choices'];
		add_filter( "acf/load_field/key={$this->fields['show']['key']}", [ $this, 'load_show' ] );

		// If we have existing values, reorder them.
		$field['choices'] = $existing ? array_merge( array_flip( $existing ), $defaults ) : $field['choices'];

		return $field;
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Field args.
	 *
	 * @return mixed
	 */
	public function get_posts( $args ) {

		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'acf_nonce' ) && isset( $_REQUEST['post_type'] ) && ! empty( $_REQUEST['post_type'] ) ) {
			$args['post_type'] = sanitize_text_field( wp_unslash( $_REQUEST['post_type'] ) );
		}

		return $args;
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Field args.
	 *
	 * @return mixed
	 */
	public function get_terms( $args ) {

		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'acf_nonce' ) && isset( $_REQUEST['taxonomy'] ) && ! empty( $_REQUEST['taxonomy'] ) ) {
			$args['taxonomy'] = sanitize_text_field( wp_unslash( $_REQUEST['taxonomy'] ) );
		}

		return $args;
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Field args.
	 *
	 * @return mixed
	 */
	public function get_parents( $args ) {

		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'acf_nonce' ) && isset( $_REQUEST['post_type'] ) && ! empty( $_REQUEST['post_type'] ) ) {
			$args['post_type'] = sanitize_text_field( wp_unslash( $_REQUEST['post_type'] ) );
		}

		return $args;
	}
}
