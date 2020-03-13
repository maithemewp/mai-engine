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
 * Class Mai_Entry_Settings
 */
class Mai_Entry_Settings {

	/**
	 * Context - archive, single, block.
	 *
	 * @var $context
	 */
	public $context;

	/**
	 * Type - post, taxonomy, user.
	 *
	 * @var $type
	 */
	public $type;

	/**
	 * Fields.
	 *
	 * @var $fields
	 */
	public $fields;

	/**
	 * Defaults.
	 *
	 * @var $defaults
	 */
	public $defaults;

	/**
	 * Keys.
	 *
	 * @var $keys
	 */
	public $keys;

	/**
	 * Mai_Entry_Settings constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param string $context Entry context.
	 * @param string $type    Entry type.
	 *
	 * @return void
	 */
	public function __construct( $context, $type = 'post' ) {
		$this->context  = $context;
		$this->type     = $type;
		$this->fields   = $this->get_fields();
		$this->defaults = $this->get_defaults();
		$this->keys     = $this->get_keys();
	}

	/**
	 * Get field configs.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_fields() {
		static $fields = [];

		if ( isset( $fields[ $this->context ] ) ) {
			return $fields[ $this->context ];
		}

		$all_fields = [
			'display_tab'            => [
				'label'   => esc_html__( 'Display', 'mai-engine' ),
				'block'   => true,
				'archive' => false,
				'single'  => false,
				'type'    => 'tab',
				'key'     => 'field_5bd51cac98282',
				'group'   => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
				'default' => '',
			],
			'site_layout'            => [
				'label'    => esc_html__( 'Layout', 'mai-engine' ),
				'desc'     => esc_html__( '"Site Default" uses the setting in Customizer > Theme Settings > Site Layout.', 'mai-engine' ),
				'block'    => false,
				'archive'  => true,
				'single'   => true,
				'sanitize' => 'esc_html',
				'type'     => 'select',
				'default'  => ( 'archive' === $this->context ) ? 'wide-content' : 'standard-content',
			],
			'show'                   => [
				'label'    => esc_html__( 'Show', 'mai-engine' ),
				'desc'     => ( 'archive' === $this->context ) ? esc_html__( 'Show/hide and re-order entry elements. Click "Toggle Hooks" to show Genesis hooks.', 'mai-engine' ) : '',
				'block'    => true,
				'archive'  => true,
				'single'   => true,
				'sanitize' => 'esc_html',
				'type'     => ( 'block' === $this->context ) ? 'checkbox' : 'sortable',
				'key'      => 'field_5e441d93d6236',
				'group'    => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
				'default'  => $this->get_show_default(),
				'acf'      => [
					'wrapper' => [
						'width' => '',
						'class' => 'mai-sortable',
						'id'    => '',
					],
				],
			],
			'image_orientation'      => [
				'label'      => esc_html__( 'Image Orientation', 'mai-engine' ),
				'block'      => true,
				'archive'    => true,
				'single'     => true,
				'sanitize'   => 'esc_html',
				'type'       => 'select',
				'key'        => 'field_5e4d4efe99279',
				'group'      => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
				'default'    => 'landscape',
				'choices'    => [
					'landscape' => esc_html__( 'Landscape', 'mai-engine' ),
					'portrait'  => esc_html__( 'Portrait', 'mai-engine' ),
					'square'    => esc_html__( 'Square', 'mai-engine' ),
					'custom'    => esc_html__( 'Custom', 'mai-engine' ),
				],
				'conditions' => [
					[
						'setting'  => 'show',
						'operator' => ( 'block' === $this->context ) ? '==' : 'contains',
						'value'    => 'image',
					],
				],
			],
			'image_size'             => [
				'label'      => esc_html__( 'Image Size', 'mai-engine' ),
				'block'      => true,
				'archive'    => true,
				'single'     => true,
				'sanitize'   => 'esc_html',
				'type'       => 'select',
				'key'        => 'field_5bd50e580d1e9',
				'group'      => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
				'default'    => 'landscape-md',
				'conditions' => [
					[
						'setting'  => 'show',
						'operator' => ( 'block' === $this->context ) ? '==' : 'contains',
						'value'    => 'image',
					],
					[
						'setting'  => 'image_orientation',
						'operator' => '==',
						'value'    => 'custom',
					],
				],
			],
			'image_position'         => [
				'label'      => esc_html__( 'Image Position', 'mai-engine' ),
				'block'      => true,
				'archive'    => true,
				'single'     => false,
				'sanitize'   => 'esc_html',
				'type'       => 'select',
				'key'        => 'field_5e2f3adf82130',
				'group'      => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
				'default'    => 'full',
				'choices'    => [
					'full'       => esc_html__( 'Full', 'mai-engine' ),
					'left'       => esc_html__( 'Left', 'mai-engine' ),
					'center'     => esc_html__( 'Center', 'mai-engine' ),
					'right'      => esc_html__( 'Right', 'mai-engine' ),
					'background' => esc_html__( 'Background', 'mai-engine' ),
				],
				'conditions' => [
					[
						'setting'  => 'show',
						'operator' => ( 'block' === $this->context ) ? '==' : 'contains',
						'value'    => 'image',
					],
				],
			],
			'header_meta'            => [
				'label'      => esc_html__( 'Header Meta', 'mai-engine' ),
				'block'      => true,
				'archive'    => true,
				'single'     => true,
				'sanitize'   => 'wp_kses_post',
				'type'       => 'text',
				'key'        => 'field_5e2b563a7c6cf',
				'group'      => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
				// TODO: this should be different, or empty depending on the post type?
				'default'    => '[post_date] [post_author_posts_link before="by "]',
				'conditions' => [
					[
						'setting'  => 'show',
						'operator' => ( 'block' === $this->context ) ? '==' : 'contains',
						'value'    => 'header_meta',
					],
				],
			],
			'content_limit'          => [
				'label'      => esc_html__( 'Content Limit', 'mai-engine' ),
				'desc'       => esc_html__( 'Limit the number of characters shown for the content or excerpt. Use 0 for no limit.', 'mai-engine' ),
				'block'      => true,
				'archive'    => true,
				'single'     => false,
				'sanitize'   => 'absint',
				'type'       => 'text',
				'key'        => 'field_5bd51ac107244',
				'group'      => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
				'default'    => 0,
				'conditions' => [
					[
						[
							'setting'  => 'show',
							'operator' => ( 'block' === $this->context ) ? '==' : 'contains',
							'value'    => 'excerpt',
						],
					],
					[
						[
							'setting'  => 'show',
							'operator' => ( 'block' === $this->context ) ? '==' : 'contains',
							'value'    => 'content',
						],
					],
				],
			],
			'more_link_text'         => [
				'label'      => esc_html__( 'More Link Text', 'mai-engine' ),
				'block'      => true,
				'archive'    => true,
				'single'     => false,
				'sanitize'   => 'esc_attr', // We may want to add icons/spans and HTML in here.
				'type'       => 'text',
				'key'        => 'field_5c85465018395',
				'group'      => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
				'default'    => '',
				'conditions' => [
					[
						'setting'  => 'show',
						'operator' => ( 'block' === $this->context ) ? '==' : 'contains',
						'value'    => 'more_link',
					],
				],
				// TODO: These text should be filtered, same as the template that outputs it.
				'acf'        => [
					'placeholder' => esc_html__( 'Read More', 'mai-engine' ),
				],
				'kirki'      => [
					'input_attrs' => [
						'placeholder' => esc_html__( 'Read More', 'mai-engine' ),
					],
				],
			],
			'footer_meta'            => [
				'label'      => esc_html__( 'Footer Meta', 'mai-engine' ),
				'block'      => true,
				'archive'    => true,
				'single'     => true,
				'sanitize'   => 'wp_kses_post',
				'type'       => 'text',
				'key'        => 'field_5e2b567e7c6d0',
				'group'      => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
				// TODO: this should be different, or empty depending on the post type?
				'default'    => '[post_categories]',
				'conditions' => [
					[
						'setting'  => 'show',
						'operator' => ( 'block' === $this->context ) ? '==' : 'contains',
						'value'    => 'footer_meta',
					],
				],
			],
			'boxed'                  => [
				'label'    => esc_html__( 'Boxed', 'mai-engine' ),
				'block'    => true,
				'archive'  => true,
				'single'   => false,
				'sanitize' => 'esc_html',
				'type'     => ( 'block' === $this->context ) ? 'true_false' : 'checkbox',
				// Could try 'switch' in Kirki.
				'key'      => 'field_5e2a08a182c2c',
				'group'    => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
				'default'  => true,
				// ACF has 1.
				'acf'      => [
					'message' => __( 'Display boxed', 'mai-engine' ),
				],
			],
			'align_text'             => [
				'label'    => esc_html__( 'Align Text', 'mai-engine' ),
				'block'    => true,
				'archive'  => true,
				'single'   => false,
				'sanitize' => 'esc_html',
				'type'     => ( 'block' === $this->context ) ? 'button_group' : 'radio-buttonset',
				'key'      => 'field_5c853f84eacd6',
				'group'    => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
				'default'  => '',
				'choices'  => [
					''       => esc_html__( 'Clear', 'mai-engine' ),
					'start'  => esc_html__( 'Start', 'mai-engine' ),
					'center' => esc_html__( 'Center', 'mai-engine' ),
					'end'    => esc_html__( 'End', 'mai-engine' ),
				],
			],
			'align_text_vertical'    => [
				'label'      => esc_html__( 'Align Text (vertical)', 'mai-engine' ),
				'block'      => true,
				'archive'    => true,
				'single'     => false,
				'sanitize'   => 'esc_html',
				'type'       => ( 'block' === $this->context ) ? 'button_group' : 'radio-buttonset',
				'key'        => 'field_5e2f519edc912',
				'group'      => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
				'default'    => '',
				'choices'    => [
					''       => esc_html__( 'Clear', 'mai-engine' ),
					'top'    => esc_html__( 'Top', 'mai-engine' ),
					'middle' => esc_html__( 'Middle', 'mai-engine' ),
					'bottom' => esc_html__( 'Bottom', 'mai-engine' ),
				],
				'conditions' => [
					[
						[
							'setting'  => 'image_position',
							'operator' => '==',
							'value'    => 'left',
						],
					],
					[
						[
							'setting'  => 'image_position',
							'operator' => '==',
							'value'    => 'background',
						],
					],
				],
			],
			/**********
			 * Layout *
			 */
			'layout_tab'             => [
				'label'   => esc_html__( 'Layout', 'mai-engine' ),
				'block'   => true,
				'archive' => false,
				'single'  => false,
				'type'    => 'tab',
				'key'     => 'field_5c8549172e6c7',
				'group'   => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
				'default' => '',
			],
			'columns'                => [
				'label'    => esc_html__( 'Columns (desktop)', 'mai-engine' ),
				'block'    => true,
				'archive'  => true,
				'single'   => false,
				'sanitize' => 'absint',
				'type'     => ( 'block' === $this->context ) ? 'button_group' : 'radio-buttonset',
				'key'      => 'field_5c854069d358c',
				'group'    => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
				'default'  => 3,
			],
			'columns_responsive'     => [
				'label'    => ( 'block' !== $this->context ) ? esc_html__( 'Custom responsive columns', 'mai-engine' ) : '',
				'block'    => true,
				'archive'  => true,
				'single'   => false,
				'sanitize' => 'esc_html',
				'type'     => ( 'block' === $this->context ) ? 'true_false' : 'checkbox',
				// Could try 'switch' in Kirki.
				'key'      => 'field_5e334124b905d',
				'group'    => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
				'default'  => '',
				'acf'      => [
					'message' => esc_html__( 'Custom responsive columns', 'mai-engine' ),
				],
			],
			'columns_md'             => [
				'label'      => esc_html__( 'Columns (lg tablets)', 'mai-engine' ),
				'block'      => true,
				'archive'    => true,
				'single'     => false,
				'sanitize'   => 'absint',
				'type'       => ( 'block' === $this->context ) ? 'button_group' : 'radio-buttonset',
				'key'        => 'field_5e3305dff9d8b',
				'group'      => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
				'default'    => 1,
				'conditions' => [
					[
						'setting'  => 'columns_responsive',
						'operator' => '==',
						'value'    => 1,
					],
				],
			],
			'columns_sm'             => [
				'label'      => esc_html__( 'Columns (sm tablets)', 'mai-engine' ),
				'block'      => true,
				'archive'    => true,
				'single'     => false,
				'sanitize'   => 'absint',
				'type'       => ( 'block' === $this->context ) ? 'button_group' : 'radio-buttonset',
				'key'        => 'field_5e3305f1f9d8c',
				'group'      => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
				'default'    => 1,
				'conditions' => [
					[
						'setting'  => 'columns_responsive',
						'operator' => '==',
						'value'    => 1,
					],
				],
			],
			'columns_xs'             => [
				'label'      => esc_html__( 'Columns (mobile)', 'mai-engine' ),
				'block'      => true,
				'archive'    => true,
				'single'     => false,
				'sanitize'   => 'absint',
				'type'       => ( 'block' === $this->context ) ? 'button_group' : 'radio-buttonset',
				'key'        => 'field_5e332a5f7fe08',
				'group'      => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
				'default'    => 1,
				'conditions' => [
					[
						'setting'  => 'columns_responsive',
						'operator' => '==',
						'value'    => 1,
					],
				],
			],
			'align_columns'          => [
				'label'      => esc_html__( 'Align Columns', 'mai-engine' ),
				'block'      => true,
				'archive'    => true,
				'single'     => false,
				'sanitize'   => 'esc_html',
				'type'       => ( 'block' === $this->context ) ? 'button_group' : 'radio-buttonset',
				'key'        => 'field_5c853e6672972',
				'group'      => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
				'default'    => '',
				'choices'    => [
					''       => esc_html__( 'Clear', 'mai-engine' ),
					'left'   => esc_html__( 'Left', 'mai-engine' ),
					'center' => esc_html__( 'Center', 'mai-engine' ),
					'right'  => esc_html__( 'Right', 'mai-engine' ),
				],
				'conditions' => [
					[
						'setting'  => 'columns',
						'operator' => '!=',
						'value'    => 1,
					],
				],
			],
			'align_columns_vertical' => [
				'label'      => esc_html__( 'Align Columns (vertical)', 'mai-engine' ),
				'block'      => true,
				'archive'    => true,
				'single'     => false,
				'sanitize'   => 'esc_html',
				'type'       => ( 'block' === $this->context ) ? 'button_group' : 'radio-buttonset',
				'key'        => 'field_5e31d5f0e2867',
				'group'      => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
				'default'    => '',
				'choices'    => [
					''       => esc_html__( 'Clear', 'mai-engine' ),
					'top'    => esc_html__( 'Top', 'mai-engine' ),
					'middle' => esc_html__( 'Middle', 'mai-engine' ),
					'bottom' => esc_html__( 'Bottom', 'mai-engine' ),
				],
				'conditions' => [
					[
						'setting'  => 'columns',
						'operator' => '!=',
						'value'    => 1,
					],
				],
			],
			'column_gap'             => [
				'label'    => esc_html__( 'Column Gap', 'mai-engine' ),
				'block'    => true,
				'archive'  => true,
				'single'   => false,
				'sanitize' => 'esc_html',
				'type'     => 'text',
				'key'      => 'field_5c8542d6a67c5',
				'group'    => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
				'default'  => ( 'block' === $this->context ) ? '24px' : '36px',
			],
			'row_gap'                => [
				'label'    => esc_html__( 'Row Gap', 'mai-engine' ),
				'block'    => true,
				'archive'  => true,
				'single'   => false,
				'sanitize' => 'esc_html',
				'type'     => 'text',
				'key'      => 'field_5e29f1785bcb6',
				'group'    => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
				'default'  => ( 'block' === $this->context ) ? '24px' : '64px',
			],
			// TODO: This should save to the direct posts_per_page option (same as main Settings > Reading option) only on Post Archives.
			'posts_per_page'         => [
				'label'    => esc_html__( 'Posts Per Page', 'mai-engine' ),
				'desc'     => esc_html__( 'Sticky posts are not included in count.', 'mai-engine' ),
				'block'    => false,
				'archive'  => true,
				'single'   => false,
				'sanitize' => 'esc_html', // Can't absint cause empty string means to use default.
				'type'     => 'text',
				'default'  => '',
				'kirki'    => [
					'input_attrs' => [
						'placeholder' => get_option( 'posts_per_page' ),
					],
				],
			],
			/***********
			 * Entries *
			 */
			'entries_tab'            => [
				'label'   => esc_html__( 'Entries', 'mai-engine' ),
				'block'   => true,
				'archive' => false,
				'single'  => false,
				'type'    => 'tab',
				'key'     => 'field_5df13446c49cf',
				'group'   => [ 'mai_post_grid', 'mai_term_grid', 'mai_user_grid' ],
				'default' => '',
			],
		];

		if ( 'block' === $this->context ) {

			if ( 'post' === $this->type ) {

				/************
				 * WP_Query *
				 */
				$all_fields = $all_fields + [
					'post_type'              => [
						'label'    => esc_html__( 'Post Type', 'mai-engine' ),
						'block'    => true,
						'archive'  => false,
						'single'   => false,
						'sanitize' => 'esc_html',
						'type'     => 'select',
						'key'      => 'field_5df1053632ca2',
						'group'    => [ 'mai_post_grid' ],
						'default'  => [ 'post' ],
						'acf'      => [
							'multiple' => 1,
							'ui'       => 1,
							'ajax'     => 0,
						],
					],
					'number'               => [
						'label'      => esc_html__( 'Number of Entries', 'mai-engine' ),
						'desc'       => esc_html__( 'Use 0 to show all.', 'mai-engine' ),
						'block'      => true,
						'archive'    => false,
						'single'     => false,
						'sanitize'   => 'absint',
						'type'       => 'number',
						'key'        => 'field_5df1053632ca8',
						'group'      => [ 'mai_post_grid' ],
						'default'    => 12,
						'conditions' => [
							[
								'setting'  => 'post_type',
								'operator' => '!=empty',
							],
							[
								'setting'  => 'query_by',
								'operator' => '!=',
								'value'    => 'title',
							],
						],
						'acf'        => [
							'placeholder' => 12,
							'min'         => 0,
						],
					],
					'query_by'               => [
						'label'      => esc_html__( 'Get Entries By', 'mai-engine' ),
						'block'      => true,
						'archive'    => false,
						'single'     => false,
						'sanitize'   => 'esc_html',
						'type'       => 'select',
						'key'        => 'field_5df1053632cad',
						'group'      => [ 'mai_post_grid' ],
						'default'    => 'date',
						'choices'    => [
							'date'     => esc_html__( 'Date', 'mai-engine' ),
							'title'    => esc_html__( 'Title', 'mai-engine' ),
							'tax_meta' => esc_html__( 'Taxonomy/Meta', 'mai-engine' ),
							'parent'   => esc_html__( 'Parent', 'mai-engine' ),
						],
						'conditions' => [
							[
								'setting'  => 'post_type',
								'operator' => '!=empty',
							],
						],
					],
					'post__in'               => [
						'label'      => esc_html__( 'Entries', 'mai-engine' ),
						'desc'       => esc_html__( 'Show specific entries. Choose all that apply. If empty, Grid will get entries by date.', 'mai-engine' ),
						'block'      => true,
						'archive'    => false,
						'single'     => false,
						'sanitize'   => 'absint',
						'type'       => 'post_object',
						'key'        => 'field_5df1053632cbc',
						'group'      => [ 'mai_post_grid' ],
						'default'    => '', // Can't be empty array.
						'conditions' => [
							[
								'setting'  => 'post_type',
								'operator' => '!=empty',
							],
							[
								'setting'  => 'query_by',
								'operator' => '==',
								'value'    => 'title',
							],
						],
						'acf'        => [
							'multiple'      => 1,
							'return_format' => 'id',
							'ui'            => 1,
						],
					],
					'taxonomies'             => [
						'label'      => esc_html__( 'Taxonomies', 'mai-engine' ),
						'block'      => true,
						'archive'    => false,
						'single'     => false,
						'type'       => 'repeater',
						'key'        => 'field_5df1397316270',
						'group'      => [ 'mai_post_grid' ],
						'default'    => '',
						'conditions' => [
							[
								'setting'  => 'post_type',
								'operator' => '!=empty',
							],
							[
								'setting'  => 'query_by',
								'operator' => '==',
								'value'    => 'tax_meta',
							],
						],
						'acf'        => [
							'collapsed'    => 'field_5df1398916271',
							'layout'       => 'block',
							'button_label' => esc_html__( 'Add Condition', 'mai-engine' ),
							'sub_fields'   => [
								'taxonomy' => [
									'label'    => esc_html__( 'Taxonomy', 'mai-engine' ),
									'sanitize' => 'esc_html',
									'type'     => 'select',
									'key'      => 'field_5df1398916271',
									'default'  => '',
									'acf'      => [
										'ui'   => 1,
										'ajax' => 1,

									],
								],
								'terms'    => [
									'label'    => esc_html__( 'Terms', 'mai-engine' ),
									'sanitize' => 'absint',
									'type'     => 'taxonomy',
									'key'      => 'field_5df139a216272',
									'default'  => [],
									'acf'      => [
										'field_type' => 'multi_select',
										'taxonomy'   => 'category',
										'add_term'   => 0,
										'save_terms' => 0,
										'load_terms' => 0,
										'multiple'   => 0,
										'conditions' => [
											[
												'setting'  => 'taxonomy',
												'operator' => '!=empty',
											],
										],
									],
								],
								'operator' => [
									'key'        => 'field_5df18f2305c2c',
									'label'      => esc_html__( 'Operator', 'mai-engine' ),
									'sanitize'   => 'esc_html',
									'type'       => 'select',
									'default'    => '',
									'conditions' => [
										[
											'setting'  => 'taxonomy',
											'operator' => '!=empty',
										],
									],
								],
							],
						],
					],
					'taxonomies_relation'    => [
						'label'      => esc_html__( 'Taxonomies Relation', 'mai-engine' ),
						'block'      => true,
						'archive'    => false,
						'single'     => false,
						'sanitize'   => 'esc_html',
						'type'       => 'select',
						'key'        => 'field_5df139281626f',
						'group'      => [ 'mai_post_grid' ],
						'default'    => 'AND',
						'conditions' => [
							[
								'setting'  => 'post_type',
								'operator' => '!=empty',
							],
							[
								'setting'  => 'query_by',
								'operator' => '==',
								'value'    => 'tax_meta',
							],
							[
								'setting'  => 'taxonomies',
								'operator' => '>',
								'value'    => '1', // More than 1 row.
							],
						],
					],
					'meta_keys'              => [
						'label'      => esc_html__( 'Meta Keys', 'mai-engine' ),
						'block'      => true,
						'archive'    => false,
						'single'     => false,
						'type'       => 'repeater',
						'key'        => 'field_5df2053632dg5',
						'group'      => [ 'mai_post_grid' ],
						'default'    => '',
						'conditions' => [
							[
								'setting'  => 'post_type',
								'operator' => '!=empty',
							],
							[
								'setting'  => 'query_by',
								'operator' => '==',
								'value'    => 'tax_meta',
							],
						],
						'acf'        => [
							'collapsed'    => 'field_5df3398916382',
							'layout'       => 'block',
							'button_label' => esc_html__( 'Add Condition', 'mai-engine' ),
							'sub_fields'   => [
								// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
								'meta_key'     => [
									'label'    => esc_html__( 'Meta Key', 'mai-engine' ),
									'sanitize' => 'esc_html',
									'type'     => 'text',
									'key'      => 'field_5df3398916382',
									'default'  => '',
								],
								'meta_compare' => [
									'label'      => esc_html__( 'Compare', 'mai-engine' ),
									'sanitize'   => 'esc_html',
									'type'       => 'select',
									'key'        => 'field_5df29f2315d3d',
									'default'    => '',
									'conditions' => [
										[
											'setting'  => 'meta_key',
											'operator' => '!=empty',
										],
									],
								],
								// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
								'meta_value'   => [
									'label'      => esc_html__( 'Meta Value', 'mai-engine' ),
									'sanitize'   => 'esc_html',
									'type'       => 'text',
									'key'        => 'field_5df239a217383',
									'default'    => '',
									'conditions' => [
										[
											'setting'  => 'meta_key',
											'operator' => '!=empty',
										],
										[
											'setting'  => 'meta_compare',
											'operator' => '!=',
											'value'    => 'EXISTS',
										],
									],
								],
							],
						],
					],
					'meta_keys_relation'     => [
						'label'      => esc_html__( 'Meta Keys Relation', 'mai-engine' ),
						'block'      => true,
						'archive'    => false,
						'single'     => false,
						'sanitize'   => 'esc_html',
						'type'       => 'select',
						'key'        => 'field_5df239282737g',
						'group'      => [ 'mai_post_grid' ],
						'default'    => 'AND',
						'conditions' => [
							[
								'setting'  => 'post_type',
								'operator' => '!=empty',
							],
							[
								'setting'  => 'query_by',
								'operator' => '==',
								'value'    => 'tax_meta',
							],
							[
								'setting'  => 'meta_keys',
								'operator' => '>',
								'value'    => '1', // More than 1 row.
							],
						],
					],
					'post_parent__in'        => [
						'label'      => esc_html__( 'Parent', 'mai-engine' ),
						'block'      => true,
						'archive'    => false,
						'single'     => false,
						'sanitize'   => 'absint',
						'type'       => 'post_object',
						'key'        => 'field_5df1053632ce4',
						'group'      => [ 'mai_post_grid' ],
						'default'    => '',
						'conditions' => [
							[
								'setting'  => 'post_type',
								'operator' => '!=empty',
							],
							[
								'setting'  => 'query_by',
								'operator' => '==',
								'value'    => 'parent',
							],
						],
						'acf'        => [
							'multiple' => 1,
							'ui'       => 1,
							'ajax'     => 1,
						],
					],
					'offset'                 => [
						'label'      => esc_html__( 'Offset', 'mai-engine' ),
						'desc'       => esc_html__( 'Skip this number of entries.', 'mai-engine' ),
						'block'      => true,
						'archive'    => false,
						'single'     => false,
						'sanitize'   => 'absint',
						'type'       => 'number',
						'key'        => 'field_5df1bf01ea1de',
						'group'      => [ 'mai_post_grid' ],
						'default'    => 0,
						'conditions' => [
							[
								'setting'  => 'post_type',
								'operator' => '!=empty',
							],
							[
								'setting'  => 'query_by',
								'operator' => '!=',
								'value'    => 'title',
							],
						],
						'acf'        => [
							'placeholder' => 0,
							'min'         => 0,
						],
					],
					'orderby'                => [
						'label'      => esc_html__( 'Order By', 'mai-engine' ),
						'block'      => true,
						'archive'    => false,
						'single'     => false,
						'sanitize'   => 'esc_html',
						'type'       => 'select',
						'key'        => 'field_5df1053632cec',
						'group'      => [ 'mai_post_grid' ],
						'default'    => 'date',
						'choices'    => [
							'title'          => esc_html__( 'Title', 'mai-engine' ),
							'name'           => esc_html__( 'Slug', 'mai-engine' ),
							'date'           => esc_html__( 'Date', 'mai-engine' ),
							'modified'       => esc_html__( 'Modified', 'mai-engine' ),
							'rand'           => esc_html__( 'Random', 'mai-engine' ),
							'comment_count'  => esc_html__( 'Comment Count', 'mai-engine' ),
							'menu_order'     => esc_html__( 'Menu Order', 'mai-engine' ),
							'post__in'       => esc_html__( 'Entries Order', 'mai-engine' ),
							'meta_value_num' => esc_html__( 'Meta Value Number', 'mai-engine' ),
						],
						'conditions' => [
							[
								'setting'  => 'post_type',
								'operator' => '!=empty',
							],
						],
						'acf'        => [
							'ui'   => 1,
							'ajax' => 1,
						],
					],
					'orderby_meta_key'       => [
						'label'      => esc_html__( 'Meta key', 'mai-engine' ),
						'block'      => true,
						'archive'    => false,
						'single'     => false,
						'sanitize'   => 'esc_html',
						'type'       => 'text',
						'key'        => 'field_5df1053632cf4',
						'group'      => [ 'mai_post_grid' ],
						'default'    => '',
						'conditions' => [
							[
								'setting'  => 'post_type',
								'operator' => '!=empty',
							],
							[
								'setting'  => 'orderby',
								'operator' => '==',
								'value'    => 'meta_value_num',
							],
						],
					],
					'order'                  => [
						'label'      => esc_html__( 'Order', 'mai-engine' ),
						'block'      => true,
						'archive'    => false,
						'single'     => false,
						'sanitize'   => 'esc_html',
						'type'       => 'select',
						'key'        => 'field_5df1053632cfb',
						'group'      => [ 'mai_post_grid' ],
						'default'    => '',
						'conditions' => [
							[
								'setting'  => 'post_type',
								'operator' => '!=empty',
							],
						],
					],
					'post__not_in'           => [
						'label'      => esc_html__( 'Exclude Entries', 'mai-engine' ),
						'desc'       => esc_html__( 'Hide specific entries. Choose all that apply.', 'mai-engine' ),
						'block'      => true,
						'archive'    => false,
						'single'     => false,
						'sanitize'   => 'absint',
						'type'       => 'post_object',
						'key'        => 'field_5e349237e1c01',
						'group'      => [ 'mai_post_grid' ],
						'default'    => '',
						'conditions' => [
							[
								'setting'  => 'post_type',
								'operator' => '!=empty',
							],
							[
								'setting'  => 'query_by',
								'operator' => '!=',
								'value'    => 'title',
							],
						],
						'acf'        => [
							'multiple'      => 1,
							'return_format' => 'id',
							'ui'            => 1,
						],
					],
				];
			}

			/*****************
			 * WP_Term_Query *
			 */
			if ( 'term' === $this->type ) {

				$all_fields = $all_fields + [
					'taxonomy'            => [
						'label'    => esc_html__( 'Taxonomy', 'mai-engine' ),
						'block'    => true,
						'archive'  => false,
						'single'   => false,
						'sanitize' => 'esc_html',
						'type'     => 'select',
						'key'      => 'field_5df2063632ca2',
						'group'    => [ 'mai_term_grid' ],
						'default'  => [ 'post' ],
						'acf'      => [
							'multiple' => 1,
							'ui'       => 1,
							'ajax'     => 0,
						],
					],
					'number'               => [
						'label'      => esc_html__( 'Number of Entries', 'mai-engine' ),
						'desc'       => esc_html__( 'Use 0 to show all.', 'mai-engine' ),
						'block'      => true,
						'archive'    => false,
						'single'     => false,
						'sanitize'   => 'absint',
						'type'       => 'number',
						'key'        => 'field_5df2064632ca8',
						'group'      => [ 'mai_term_grid' ],
						'default'    => 12,
						'conditions' => [
							[
								'setting'  => 'taxonomy',
								'operator' => '!=empty',
							],
							[
								'setting'  => 'query_by',
								'operator' => '!=',
								'value'    => 'title',
							],
						],
						'acf'        => [
							'placeholder' => 12,
							'min'         => 0,
						],
					],
					'query_by'               => [
						'label'      => esc_html__( 'Get Entries By', 'mai-engine' ),
						'block'      => true,
						'archive'    => false,
						'single'     => false,
						'sanitize'   => 'esc_html',
						'type'       => 'select',
						'key'        => 'field_5df1054642cad',
						'group'      => [ 'mai_term_grid' ],
						'default'    => 'date',
						'choices'    => [
							'date'     => esc_html__( 'Date', 'mai-engine' ),
							'title'    => esc_html__( 'Title', 'mai-engine' ),
							// 'tax_meta' => esc_html__( 'Taxonomy/Meta', 'mai-engine' ),
							'parent'   => esc_html__( 'Parent', 'mai-engine' ),
						],
						'conditions' => [
							[
								'setting'  => 'post_type',
								'operator' => '!=empty',
							],
						],
					],
				];
			}

			// Add to all grid, after the existing fields.
			$all_fields = $all_fields + [

				// TODO: These shoud be separate fields. We can then have desc text and easier to check when building query.
				'exclude'                => [
					'label'      => esc_html__( 'Exclude', 'mai-engine' ),
					'block'      => true,
					'archive'    => false,
					'single'     => false,
					'sanitize'   => 'esc_html',
					'type'       => 'checkbox',
					'key'        => 'field_5df1053632d03',
					'group'      => [ 'mai_post_grid' ],
					'default'    => '',
					'conditions' => [
						[
							'setting'  => 'post_type',
							'operator' => '!=empty',
						],
					],
				],
			];

		}

		// Get fields by context.
		foreach ( $all_fields as $name => $values ) {
			// Skip if not the right context.
			if ( ! $values[ $this->context ] ) {
				continue;
			}
			$fields[ $this->context ][ $name ] = $values;
		}


		return $fields[ $this->context ];
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 1.0.0
	 *
	 * @return mixed
	 */
	public function get_defaults() {
		static $defaults = [];
		if ( isset( $defaults[ $this->context ] ) ) {
			return $defaults[ $this->context ];
		}
		$defaults[ $this->context ] = [
			'context' => $this->context,
			'type'    => $this->type,
		];
		foreach( $this->fields as $name => $values ) {
			if ( ! $values[ $this->context ] ) {
				continue;
			}
			if ( 'tab' === $values['type'] ) {
				continue;
			}
			$defaults[ $this->context ][ $name ] = $values['default'];
		}
		return $defaults[ $this->context ];
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 1.0.0
	 *
	 * @return null
	 */
	public function get_keys() {
		static $keys = null;
		if ( ! is_null( $keys ) ) {
			return $keys;
		}
		foreach ( $this->fields as $name => $field ) {
			// Skip if no key.
			if ( ! isset( $field['key'] ) ) {
				continue;
			}
			// Set key.
			$keys[ $name ] = $field['key'];
			// Skip if no sub fields.
			if ( ! isset( $field['acf']['sub_fields'] ) ) {
				continue;
			}
			// Add sub fields to top level keys array.
			foreach ( $field['acf']['sub_fields'] as $sub_name => $sub_field ) {
				// Set key.
				$keys[ $sub_name ] = $sub_field['key'];
			}
		}

		return $keys;
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 *
	 * @todo  Check post type supports? Needs to match the choices too.
	 */
	public function get_show_default() {
		switch ( $this->context ) {
			case 'block':
				$default = [ 'image', 'title' ];
				break;
			case 'archive':
				$default = [
					'image',
					'genesis_entry_header',
					'title',
					'header_meta',
					'genesis_before_entry_content',
					'excerpt',
					'genesis_entry_content',
					'more_link',
					'genesis_after_entry_content',
					'genesis_entry_footer',
				];
				break;
			case 'single':
				$default = [
					'genesis_entry_header',
					'title',
					'image',
					'header_meta',
					'genesis_before_entry_content',
					// 'excerpt',
					'content',
					'genesis_entry_content',
					'more_link',
					'genesis_after_entry_content',
					'footer_meta',
					'genesis_entry_footer',
				];
				break;
			default:
				$default = [];
		}

		return $default;
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 0.1.0
	 *
	 * @param string $field Field name.
	 *
	 * @return mixed
	 */
	public function get_choices( $field ) {
		return $this->$field();
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since  0.1.0
	 *
	 * @param  string  $field Field name.
	 *
	 * @return array   The layout choices.
	 */
	public function site_layout() {
		return [ '' => esc_html__( 'Site Default', 'mai-engine' ) ] + genesis_get_layouts_for_customizer();
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 *
	 * @todo  Check post type supports? Needs to match the choices too.
	 */
	public function show() {

		// All elements.
		$show = [
			'image'                        => esc_html__( 'Image', 'mai-engine' ),
			'genesis_entry_header'         => 'genesis_entry_header',
			'title'                        => esc_html__( 'Title', 'mai-engine' ),
			'header_meta'                  => esc_html__( 'Header Meta', 'mai-engine' ),
			'genesis_before_entry_content' => 'genesis_before_entry_content',
			'excerpt'                      => esc_html__( 'Excerpt', 'mai-engine' ),
			'content'                      => esc_html__( 'Content', 'mai-engine' ),
			'genesis_entry_content'        => 'genesis_entry_content',
			'more_link'                    => esc_html__( 'Read More link', 'mai-engine' ),
			'genesis_after_entry_content'  => 'genesis_after_entry_content',
			'footer_meta'                  => esc_html__( 'Footer Meta', 'mai-engine' ),
			'genesis_entry_footer'         => 'genesis_entry_footer',
		];

		// Remove hooks if block.
		if ( 'block' === $this->context ) {
			unset( $show['genesis_entry_header'] );
			unset( $show['genesis_before_entry_content'] );
			unset( $show['genesis_entry_content'] );
			unset( $show['genesis_after_entry_content'] );
			unset( $show['genesis_entry_footer'] );
		}

		// Add single only elements.
		if ( 'single' === $this->context ) {
			$show['author_box']         = 'author_box';
			$show['after_entry']        = 'after_entry';
			$show['adjacent_entry_nav'] = 'adjacent_entry_nav';
		}

		return $show;
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function image_size() {
		$choices = [];
		$sizes   = mai_get_available_image_sizes();
		foreach ( $sizes as $index => $value ) {
			$choices[ $index ] = sprintf( '%s (%s x %s)', $index, $value['width'], $value['height'] );
		}

		return $choices;
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function columns() {
		return $this->get_columns_choices();
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function columns_md() {
		return $this->get_columns_choices();
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function columns_sm() {
		return $this->get_columns_choices();
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function columns_xs() {
		return $this->get_columns_choices();
	}

	/**
	 * Get the column choices.
	 */
	public function get_columns_choices() {
		$choices = [
			1 => esc_html__( '1', 'mai-engine' ),
			2 => esc_html__( '2', 'mai-engine' ),
			3 => esc_html__( '3', 'mai-engine' ),
			4 => esc_html__( '4', 'mai-engine' ),
			5 => esc_html__( '5', 'mai-engine' ),
			6 => esc_html__( '6', 'mai-engine' ),
			0 => esc_html__( 'Auto', 'mai-engine' ),
		];

		return $choices;
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function post_type() {
		$choices    = [];
		$post_types = get_post_types(
			[
				'public'             => true,
				'publicly_queryable' => true,
			],
			'objects',
			'or'
		);
		unset( $post_types['attachment'] );
		if ( $post_types ) {
			foreach ( $post_types as $name => $post_type ) {
				$choices[ $name ] = $post_type->label;
			}
		}

		return $choices;
	}

	/**
	 * Get object taxonomies. This is a subfield of taxonomy.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function taxonomy() {
		$choices = [];

		if ( ! ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'acf_nonce' ) && isset( $_REQUEST['post_type'] ) && ! empty( $_REQUEST['post_type'] ) ) ) {
			return $choices;
		}

		foreach ( $_REQUEST['post_type'] as $post_type ) {
			$taxonomies = get_object_taxonomies( sanitize_text_field( wp_unslash( $post_type ) ), 'objects' );
			if ( $taxonomies ) {
				foreach ( $taxonomies as $name => $taxo ) {
					$choices[ $name ] = $taxo->label;
				}
			}
		}


		return $choices;
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function operator() {
		return [
			'IN'     => esc_html__( 'In', 'mai-engine' ),
			'NOT IN' => esc_html__( 'Not In', 'mai-engine' ),
		];
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function taxonomies_relation() {
		return [
			'AND' => esc_html__( 'And', 'mai-engine' ),
			'OR'  => esc_html__( 'Or', 'mai-engine' ),
		];
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function meta_compare() {
		return [
			'='          => __( 'Is equal to', 'mai-engine' ),
			'!='         => __( 'Is not equal to', 'mai-engine' ),
			'>'          => __( 'Is greater than', 'mai-engine' ),
			'>='         => __( 'Is great than or equal to', 'mai-engine' ),
			'<'          => __( 'Is less than', 'mai-engine' ),
			'<='         => __( 'Is less than or equal to', 'mai-engine' ),
			'EXISTS'     => __( 'Exists', 'mai-engine' ),
			'NOT EXISTS' => __( 'Does not exist', 'mai-engine' ),
		];
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function post_parent__in() {
		$choices = [];

		if ( ! ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'acf_nonce' ) && isset( $_REQUEST['post_type'] ) && ! empty( $_REQUEST['post_type'] ) ) ) {
			return $choices;
		}

		$posts = acf_get_grouped_posts(
			[
				'post_type'   => sanitize_text_field( wp_unslash( $_REQUEST['post_type'] ) ),
				'post_status' => 'publish',
			]
		);

		if ( $posts ) {
			$choices = $posts;
		}

		return $choices;
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function exclude() {
		// TODO: These should be separate fields.
		return [
			'exclude_current'   => esc_html__( 'Exclude current', 'mai-engine' ),
			'exclude_displayed' => esc_html__( 'Exclude displayed', 'mai-engine' ),
		];
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function order() {
		return [
			'ASC'  => esc_html__( 'Ascending', 'mai-engine' ),
			'DESC' => esc_html__( 'Descending', 'mai-engine' ),
		];
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 0.1.0
	 *
	 * @param string $name       Field name.
	 * @param array  $field      Field data.
	 * @param string $section_id Section ID.
	 *
	 * @return array
	 */
	public function get_data( $name, $field, $section_id = '' ) {

		// If an ACF field.
		if ( 'block' === $this->context ) {

			// Build ACF data.
			$data = [
				'name'  => $name,
				'key'   => $field['key'],
				'label' => $field['label'],
				'type'  => $field['type'],
			];

			// Maybe add description.
			if ( isset( $field['desc'] ) ) {
				$data['instructions'] = $field['desc'];
			}

			// ACF-specific fields.
			if ( isset( $field['acf'] ) ) {
				foreach ( $field['acf'] as $key => $value ) {
					// Sub fields.
					if ( 'sub_fields' === $key ) {
						$data['sub_fields'] = [];
						foreach ( $value as $sub_key => $sub_value ) {
							$data['sub_fields'][] = $this->get_data( $sub_key, $sub_value );
						}
					} else {

						// Standard field data.
						$data[ $key ] = $value;
					}
				}
			}

			// Maybe add conditional logic.
			if ( isset( $field['conditions'] ) ) {
				$data['conditional_logic'] = $this->get_conditions( $field );
			}

			// Maybe add default.
			if ( isset( $field['default'] ) ) {
				/**
				 * This needs default_value instead of default.
				 *
				 * @link  https://www.advancedcustomfields.com/resources/register-fields-via-php/
				 */
				$data['default_value'] = $field['default'];
			}

		} else {

			// Kirki.
			$data = [
				'type'     => $field['type'],
				'label'    => $field['label'],
				'settings' => $name,
				'section'  => $section_id,
				'priority' => 10,
			];

			// Maybe add description.
			if ( isset( $field['desc'] ) ) {
				$data['description'] = $field['desc'];
			}

			// Kirki-specific fields.
			if ( isset( $field['kirki'] ) ) {
				foreach ( $field['kirki'] as $key => $value ) {
					$data[ $key ] = $value;
				}
			}

			// Maybe add conditional logic.
			if ( isset( $field['conditions'] ) ) {
				$data['active_callback'] = $this->get_conditions( $field );
			}

			// Maybe add default.
			if ( isset( $field['default'] ) ) {
				// Force radio buttonsets to strings, for some reason integers don't work with Kirki.
				if ( 'radio-buttonset' === $field['type'] && is_integer( $field['default'] ) ) {
					$field['default'] = (string) $field['default'];
				}

				$data['default'] = $field['default'];
			}

		}

		// Maybe add choices.
		if ( isset( $field['choices'] ) ) {
			$data['choices'] = $field['choices'];
		} elseif ( method_exists( $this, $name ) ) {
			$data['choices'] = $this->get_choices( $name );
		}

		// TODO: Handle message/description for checkbox field (Boxed).
		return $data;
	}

	/**
	 * Get conditions for ACF from settings.
	 *
	 * ACF uses field => {key} and kirki uses setting => {name}.
	 * ACF uses == for checkbox, and kirki uses 'contains'.
	 *
	 * @since 0.1.0
	 *
	 * @param array $field Field data.
	 *
	 * @return array
	 */
	public function get_conditions( $field ) {
		if ( is_array( $field['conditions'] ) ) {
			$count      = 0; // Kirki's nesting is different than ACF, so we need this.
			$conditions = [];

			foreach ( $field['conditions'] as $index => $condition ) {

				// If 'AND' relation.
				if ( isset( $condition['setting'] ) ) {
					$conditions[] = $this->get_condition( $condition, $field );
					$count++; // For Kirki's nesting.
				} else {

					// 'OR' relation - nested one level further.
					if ( 'block' === $this->context ) {
						foreach ( $condition as $child_condition ) {
							$conditions[ $index ][] = $this->get_condition( $child_condition, $field );
						}
					} else {
						foreach ( $condition as $child_condition ) {
							$conditions[ $count ][] = $this->get_condition( $child_condition, $field );
						}
					}
				}
			}

			return $conditions;
		}

		return $field['conditions'];
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 0.1.0
	 *
	 * @param array $condition Condition to check.
	 * @param array $field     Field array.
	 *
	 * @return array
	 */
	public function get_condition( $condition, $field ) {
		if ( 'block' === $this->context ) {
			$array = [
				'field'    => $this->keys[ $condition['setting'] ],
				'operator' => $condition['operator'],
			];

			// ACF doesn't have a value for operators like '!=empty'.
			if ( isset( $condition['value'] ) ) {
				$array['value'] = $condition['value'];
			}
		} else {
			$array = [
				'setting'  => $condition['setting'],
				'operator' => $condition['operator'],
				'value'    => $condition['value'],
			];
		}

		return $array;
	}
}
