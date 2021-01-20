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
 * Class Mai_Columns
 */
class Mai_Columns {

	/**
	 * Instance.
	 *
	 * @var int $instance
	 */
	protected $instance;

	/**
	 * Args.
	 *
	 * @var array $args
	 */
	protected $args;

	/**
	 * Mai_Columns constructor.
	 *
	 * @since TBD
	 *
	 * @param array $columns_block.
	 * @param bool  $columns_preview If in admin.
	 *
	 * @return void
	 */
	function __construct( $instance, $args ) {
		$this->instance = $instance;
		$this->args     = $this->get_sanitized_args( $args );
	}

	function get_sanitized_args( $args ) {
		$args = wp_parse_args( $args,
			[
				'class'                  => '',
				'column_gap'             => 'xl',
				'row_gap'                => 'xl',
				'align'                  => '',
				'align_columns'          => 'start',
				'align_columns_vertical' => '',
				'arrangements'           => [],
				'preview'                => false,
			]
		);

		$args['class']                  = esc_html( $args['class'] );
		$args['column_gap']             = esc_html( $args['column_gap'] );
		$args['row_gap']                = esc_html( $args['row_gap'] );
		$args['align']                  = esc_html( $args['align'] );
		$args['align_columns']          = esc_html( $args['align_columns'] );
		$args['align_columns_vertical'] = esc_html( $args['align_columns_vertical'] );
		$args['arrangements']           = mai_array_map_recursive( 'esc_html', $args['arrangements'] );
		$args['preview']                = mai_sanitize_bool( $args['preview'] );

		return $args;
	}

	/**
	 * Renders the columns.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	function render() {
		$attributes = [
			'class'         => 'mai-columns',
			'data-instance' => $this->instance,
			'style'         => '',
		];

		if ( $this->args['class'] ) {
			$attributes['class'] = mai_add_classes( $this->args['class'], $attributes['class'] );
		}

		if ( in_array( $this->args['align'], [ 'full', 'wide' ] ) ) {
			$attributes['class'] .= ' align' . $this->args['align'];
		}

		if ( $this->args['preview'] ) {
			$attributes = $this->get_admin_attributes( $attributes );
		}

		$attributes = $this->get_attributes( $attributes );

		genesis_markup(
			[
				'open'    => '<div %s>',
				'context' => 'mai-columns',
				'echo'    => true,
				'atts'    => $attributes,
			]
		);

		genesis_markup(
			[
				'open'    => '<div %s>',
				'close'   => '</div>',
				'context' => 'mai-columns-wrap',
				'content' => $this->get_inner_blocks(),
				'echo'    => true,
			]
		);

		genesis_markup(
			[
				'close'   => '</div>',
				'context' => 'mai-columns',
				'echo'    => true,
			]
		);
	}

	function get_inner_blocks() {
		$allowed  = [ 'acf/mai-column' ];
		$template = [];

		for ( $i = 0 ; $i < $this->args['columns']; $i++ ) {
			$template[] = [ 'acf/mai-column', [], [] ];
		}

		return sprintf( '<InnerBlocks allowedBlocks="%s" template="%s" />', esc_attr( wp_json_encode( $allowed ) ), esc_attr( wp_json_encode( $template ) ) );
	}

	function get_admin_attributes( $attributes ) {
		if ( 'custom' === $this->args['columns'] ) {

			foreach ( array_reverse( $this->args['arrangements'] ) as $break => $arrangement ) {
				$index    = 0;
				$elements = $this->get_mapped_admin_elements( $arrangement );

				foreach ( $elements as $index => $columns ) {
					$index++;

					if ( $flex = mai_columns_get_flex( $columns ) ) {
						$attributes['style'] .= sprintf( '--flex-%s-%s:%s;', $break, $index, $flex );
					}

					if ( $max_width = mai_columns_get_max_width( $columns ) ) {
						$attributes['style'] .= sprintf( '--max-width-%s-%s:%s;', $break, $index, $max_width );
					}
				}
			}

		} else {

			foreach ( array_reverse( $this->args['arrangements'] ) as $break => $columns ) {
				if ( $flex = mai_columns_get_flex( $columns ) ) {
					$attributes['style'] .= sprintf( '--flex-%s:%s;', $break, $flex );
				}

				if ( $max_width = mai_columns_get_max_width( $columns ) ) {
					$attributes['style'] .= sprintf( '--max-width-%s:%s;', $break, $max_width );
				}
			}
		}

		return $attributes;
	}

	function get_attributes( $attributes ) {
		$column_gap = $this->args['column_gap'] ? sprintf( 'var(--spacing-%s)', $this->args['column_gap'] ) : 0;
		$row_gap    = $this->args['row_gap'] ? sprintf( 'var(--spacing-%s)', $this->args['row_gap'] ) : 0;

		$attributes['style'] .= sprintf( '--column-gap:%s;', $column_gap  );
		$attributes['style'] .= sprintf( '--row-gap:%s;', $row_gap );
		$attributes['style'] .= sprintf( '--align-columns:%s;', ! empty( $this->args['align_columns'] ) ? mai_get_flex_align( $this->args['align_columns'] ) : 'unset' ); // If wide/full then unset will be used.
		$attributes['style'] .= sprintf( '--align-columns-vertical:%s;', ! empty( $this->args['align_columns_vertical'] ) ? mai_get_flex_align( $this->args['align_columns_vertical'] ) : 'unset' );

		return $attributes;
	}

	function get_mapped_admin_elements( $arrangement ) {
		$total_arrangements = count( $arrangement );
		$count              = 0;
		$elements           = [];
		for ( $i = 0; $i < 12; $i++ ) {
			$elements[ $i ] = $arrangement[ $count ];

			if ( $count === ( $total_arrangements - 1 ) ) {
				$count = 0;
			} else {
				$count++;
			}
		}

		return $elements;
	}
}
