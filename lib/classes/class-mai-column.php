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
	 * Parent args.
	 *
	 * @var array $parent
	 */
	public $parent;

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
		$this->index  = mai_column_get_index();
		$this->args   = $this->get_sanitized_args( $args ); // Must be before parent.
		$this->parent = $this->get_parent_args();
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
	 * @since TBD
	 *
	 * @return string
	 */
	public function get() {
		$atts = [
			'class' => 'mai-column is-column',
			'style' => '',
		];

		$atts = $this->add_columns_atts( $atts );

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
	 * Gets parsed args with only the keys we want.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	function get_parent_args() {
		static $cache = [];

		// Get hash from args.
		$hash = md5( serialize( $this->args ) );

		// Return if already cached.
		if ( isset( $cache[ $hash ] ) ) {
			// return $cache[ $hash ];
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
			$this->args['fields'] = acf_setup_meta( $this->args['fields'], 'block_context' );
			$repeaters            = [
				'arrangement'    => '1/4',
				'arrangement_md' => '1/3',
				'arrangement_sm' => '1/2',
				'arrangement_xs' => '1/1',
			];

			foreach ( $repeaters as $name => $default ) {
				$args[ $name ] = [];
				// $values        = get_field( $name, 'block_context' );

				// foreach ( (array) $values as $value ) {
				// 	$args[ $name ][] = isset( $value['columns'] ) ? $value['columns'] : $default;
				// }

				$value = isset( $this->args['fields'][ $name ] ) ? $this->args['fields'][ $name ] : null;

				if ( is_null( $value ) ) {
					$args[ $name ][] = $default;
					continue;
				}

				// Array starts with 0 but rows count starts with 1.
				for ( $i = 0; $i < (int) $value; $i++ ) {
					$sub_key         = sprintf( '%s_%s_columns', $name, $i );
					$sub_value       = isset( $this->args['fields'][ $sub_key ] ) ? $this->args['fields'][ $sub_key ] : $default;
					$args[ $name ][] = $sub_value;
				}
			}
		}

		// Store in cache.
		$cache[ $hash ] = $args;

		return $cache[ $hash ];
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

		if ( 'custom' === $this->parent['columns'] ) {
			$arrangements = [
				'xs' => $this->parent['arrangement_xs'],
				'sm' => $this->parent['arrangement_sm'],
				'md' => $this->parent['arrangement_md'],
				'lg' => $this->parent['arrangement'],
			];

			foreach ( $arrangements as $break => $arrangement ) {
				// foreach ( (array) $arrangement as $column ) {
					$arrangement_col = $this->get_value_from_pattern( $this->index, $arrangement );

					// if ( 'lg' === $break ) {
						// ray( $arrangement_col );
					// }

					$atts['style']  .= mai_columns_get_columns( $break, $arrangement_col );
					$atts['style']  .= mai_columns_get_flex( $break, $arrangement_col );
				// }
			}

		} else {

			$columns = mai_get_breakpoint_columns(
				[
					'columns_responsive' => $this->parent['columns_responsive'],
					'columns'            => $this->parent['columns'],
				]
			);

			foreach ( $columns as $break => $column ) {
				$atts['style'] .= mai_columns_get_columns( $break, $column );
				$atts['style'] .= mai_columns_get_flex( $break, $column );
			}
		}

		return $atts;
	}

	/**
	 * Gets the correct column value from the repeated arrangement array.
	 * Originally used for loop like below, until switching to array_fill()
	 * because it's more readable and apparently slightly more performant.
	 *
	 * $array = [];
	 * for ( $i = 0; $i < ( $index + 1) / count( $pattern ); $i++ ) {
	 * 	$array = array_merge( $array, $pattern );
	 * }
	 *
	 * @since TBD
	 *
	 * @param int $index The current item index to get the value for.
	 * @param array
	 *
	 * @return mixed
	 */
	function get_value_from_pattern( $index, $pattern, $default = '' ) {
		// ray( $index, $pattern );
		// If index is already available, return it.
		if ( isset( $pattern[ $index ] ) ) {
			return $pattern[ $index ];
		}

		// If only 1 item in array, return the first.
		if ( 1 === count( $pattern ) ) {
			return reset( $pattern );
		}

		// Duplicate array the amount of times we need.
		// $array = array_merge(...array_fill( 0, $index, $pattern ));

		// return $array[ $index ] ?? $default;

		$return = $pattern[ $index % count( $pattern ) ] ?? $default;

		// ray( $return );
		return $return;
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
