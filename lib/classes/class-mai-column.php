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
 * Class Mai_Column
 */
class Mai_Column {
	/**
	 * Index.
	 *
	 * @var int $index
	 */
	protected $index;

	/**
	 * Args.
	 *
	 * @var array $args
	 */
	public $args;

	/**
	 * Hash.
	 *
	 * @var string $hash
	 */
	public $hash;

	/**
	 * Parent args.
	 *
	 * @var array $parent
	 */
	public $parent;

	/**
	 * Parent args.
	 *
	 * @var array $arrangement
	 */
	public $arrangement;

	/**
	 * Mai_Column constructor.
	 *
	 * @since 2.10.0
	 *
	 * @param array $block
	 * @param bool  $preview
	 *
	 * @return void
	 */
	public function __construct( $args ) {
		$this->args = $this->get_sanitized_args( $args );

		if ( ! $this->args['preview'] ) {
			$this->hash        = $this->get_hash();
			$this->index       = mai_column_get_index( $this->hash );
			$this->parent      = $this->get_parent_args();
			$this->arrangement = mai_columns_get_arrangement( $this->parent );
		}
	}

	/**
	 * Gets the column.
	 *
	 * @since 2.10.0
	 *
	 * @return void
	 */
	public function render() {
		echo $this->get();
	}

	/**
	 * Gets the column.
	 *
	 * @since 2.10.0
	 *
	 * @return string
	 */
	public function get() {
		$atts = [
			'class' => 'mai-column is-column',
			'style' => '',
		];

		if ( ! $this->args['preview'] ) {
			$atts = $this->add_columns_atts( $atts );
		}

		if ( $this->args['class'] ) {
			$atts['class'] = mai_add_classes( $this->args['class'], $atts['class'] );
		}

		if ( $this->args['align_column_vertical'] ) {
			$atts['style'] .= sprintf( '--justify-content:%s;', mai_get_flex_align( $this->args['align_column_vertical'] ) );
		}

		if ( $this->args['spacing'] ) {
			$atts['class'] = mai_add_classes( sprintf( 'has-%s-padding', esc_html( $this->args['spacing'] ) ), $atts['class'] );
		}

		if ( $this->args['background'] ) {
			$atts['class']  = mai_add_classes( 'has-background', $atts['class'] );
			$atts['style'] .= sprintf( 'background:%s;', mai_get_color_css( $this->args['background'] ) );

			if ( mai_is_light_color( $this->args['background'] ) ) {
				$atts['class'] = mai_add_classes( 'has-light-background', $atts['class'] );
			} else {
				$atts['class'] = mai_add_classes( 'has-dark-background', $atts['class'] );
			}
		}

		if ( $this->args['shadow'] ) {
			$atts['class'] = mai_add_classes( 'has-shadow', $atts['class'] );
		}

		if ( $this->args['border'] ) {
			$atts['class'] = mai_add_classes( 'has-border', $atts['class'] );
		}

		if ( $this->args['radius'] ) {
			$atts['class'] = mai_add_classes( 'has-border-radius', $atts['class'] );
		}

		if ( $this->args['first_xs'] ) {
			$atts['style'] .= '--order-xs:-1;';
		}

		if ( $this->args['first_sm'] ) {
			$atts['style'] .= '--order-sm:-1;';
		}

		if ( $this->args['first_md'] ) {
			$atts['style'] .= '--order-md:-1;';
		}

		return genesis_markup(
			[
				'open'    => '<div %s>',
				'close'   => '</div>',
				'context' => 'mai-column',
				'content' => $this->get_inner_blocks(),
				'echo'    => false,
				'atts'    => $atts,
			]
		);
	}

	/**
	 * Gets sanitized args.
	 *
	 * @since 2.10.0
	 *
	 * @return array
	 */
	function get_sanitized_args( $args ) {
		$args = wp_parse_args( $args,
			[
				'class'                 => '',
				'align_column_vertical' => 'start',
				'spacing'               => '',
				'background'            => '',
				'shadow'                => false,
				'border'                => false,
				'radius'                => false,
				'first_xs'              => false,
				'first_sm'              => false,
				'first_md'              => false,
				'preview'               => false,
				'fields'                => [],
			]
		);

		$args['class']                 = esc_html( $args['class'] );
		$args['align_column_vertical'] = esc_html( $args['align_column_vertical'] );
		$args['spacing']               = esc_html( $args['spacing'] );
		$args['background']            = esc_html( $args['background'] );
		$args['shadow']                = mai_sanitize_bool( $args['shadow'] );
		$args['border']                = mai_sanitize_bool( $args['border'] );
		$args['radius']                = mai_sanitize_bool( $args['radius'] );
		$args['first_xs']              = mai_sanitize_bool( $args['first_xs'] );
		$args['first_sm']              = mai_sanitize_bool( $args['first_sm'] );
		$args['first_md']              = mai_sanitize_bool( $args['first_md'] );
		$args['preview']               = mai_sanitize_bool( $args['preview'] );

		return $args;
	}

	/**
	 * Gets hash from args.
	 * These will be identical for all columns within the same parent,
	 * and possibly other instances with the same settings.
	 *
	 * @since 2.25.0
	 *
	 * @return string
	 */
	function get_hash() {
		return md5( serialize( $this->args['fields'] ) );
	}

	/**
	 * Gets parsed args with only the keys we want.
	 *
	 * @since 2.25.0
	 *
	 * @return void
	 */
	function get_parent_args() {
		static $cache = [];

		// Return if already cached.
		if ( isset( $cache[ $this->hash ] ) ) {
			return $cache[ $this->hash ];
		}

		$args     = [];
		$defaults = [
			'columns'            => 2,
			'columns_responsive' => false,
			'columns_md'         => 2,
			'columns_sm'         => 1,
			'columns_xs'         => 1,
		];

		// Gets field values.
		foreach ( $defaults as $name => $default ) {
			$args[ $name ] = isset( $this->args['fields'][ $name ] ) ? $this->args['fields'][ $name ] : $default;
		}

		if ( 'custom' === $args['columns'] ) {
			/**
			 * We can't get repeater data via ACF, since it's the raw values.
			 *
			 * @link https://github.com/AdvancedCustomFields/acf/issues/710
			 */
			$this->args['fields'] = acf_setup_meta( $this->args['fields'], 'block_context' );
			$repeaters            = [
				'arrangement_xs' => 'full',
				'arrangement_md' => '1/2',
				'arrangement_sm' => '1/2',
				'arrangement'    => '1/2',
			];

			foreach ( $repeaters as $name => $default ) {
				$args[ $name ] = [];

				$value = isset( $this->args['fields'][ $name ] ) ? $this->args['fields'][ $name ] : null;

				if ( is_null( $value ) ) {
					$args[ $name ][] = [
						'columns' => $default,
					];

					continue;
				}

				// Manually get our repeater row keys/values.
				for ( $i = 0; $i < (int) $value; $i++ ) {
					$sub_key       = sprintf( '%s_%s_columns', $name, $i );
					$sub_value     = isset( $this->args['fields'][ $sub_key ] ) ? $this->args['fields'][ $sub_key ] : $default;

					$args[ $name ][] = [
						'columns' => $sub_value,
					];
				}
			}
		}

		// Store in cache.
		$cache[ $this->hash ] = $args;

		return $cache[ $this->hash ];
	}

	/**
	 * Adds columns attributes from parent block args.
	 *
	 * @since 2.10.0
	 *
	 * @param array $atts The existing attributes.
	 *
	 * @return array
	 */
	function add_columns_atts( $atts ) {
		// Make sure style is set.
		$atts['style'] = isset( $atts['style'] ) ? $atts['style'] : '';

		foreach ( $this->arrangement as $break => $column ) {
			$col            = is_array( $column ) ? mai_get_index_value_from_array( $this->index, $column ) : $column;
			$atts['style'] .= mai_columns_get_columns( $break, $col );
			$atts['style'] .= mai_columns_get_flex( $break, $col );
		}

		return $atts;
	}

	/**
	 * Gets inner blocks element.
	 *
	 * @since 2.10.0
	 *
	 * @return string
	 */
	function get_inner_blocks() {
		return '<InnerBlocks />';
	}
}
