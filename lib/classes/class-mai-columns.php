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
	 * @since 2.10.0
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
				'margin_top'             => '',
				'margin_bottom'          => '',
				'arrangements'           => [],
				'preview'                => false,
			]
		);

		$args['class']                  = esc_html( $args['class'] );
		$args['column_gap']             = esc_html( $args['column_gap'] );
		$args['row_gap']                = esc_html( $args['row_gap'] );
		$args['align']                  = sanitize_html_class( $args['align'] );
		$args['align_columns']          = esc_html( $args['align_columns'] );
		$args['align_columns_vertical'] = esc_html( $args['align_columns_vertical'] );
		$args['margin_top']             = sanitize_html_class( $args['margin_top'] );
		$args['margin_bottom']          = sanitize_html_class( $args['margin_bottom'] );
		$args['arrangements']           = mai_array_map_recursive( 'esc_html', $args['arrangements'] );
		$args['preview']                = mai_sanitize_bool( $args['preview'] );

		return $args;
	}

	/**
	 * Renders the columns.
	 *
	 * @since 2.10.0
	 *
	 * @return void
	 */
	function render() {
		$atts = [
			'class'         => 'mai-columns',
			'data-instance' => $this->instance,
			'style'         => '',
		];

		if ( $this->args['class'] ) {
			$atts['class'] = mai_add_classes( $this->args['class'], $atts['class'] );
		}

		if ( in_array( $this->args['align'], [ 'full', 'wide' ] ) ) {
			$atts['class'] = mai_add_classes( 'align' . $this->args['align'], $atts['class'] );
		}

		if ( $this->args['margin_top'] ) {
			$atts['class'] = mai_add_classes( sprintf( 'has-%s-margin-top', $this->args['margin_top'] ), $atts['class'] );
		}

		if ( $this->args['margin_bottom'] ) {
			$atts['class'] = mai_add_classes( sprintf( 'has-%s-margin-bottom', $this->args['margin_bottom'] ), $atts['class'] );
		}

		if ( $this->args['preview'] ) {
			$atts = $this->get_admin_attributes( $atts );
		}

		$atts = $this->get_attributes( $atts );

		genesis_markup(
			[
				'open'    => '<div %s>',
				'context' => 'mai-columns',
				'echo'    => true,
				'atts'    => $atts,
			]
		);

		$wrap_atts = [
			'class' => 'mai-columns-wrap has-columns'
		];

		if ( $this->args['preview'] ) {
			$wrap_atts['class'] = mai_add_classes( 'has-columns-nested', $wrap_atts['class'] ); // Temp workaround for ACF nested block markup.
		}

		genesis_markup(
			[
				'open'    => '<div %s>',
				'close'   => '</div>',
				'context' => 'mai-columns-wrap',
				'content' => $this->get_inner_blocks(),
				'echo'    => true,
				'atts'    => $wrap_atts,
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
		$columns  = absint( $this->args['columns'] ) ?: 1;

		for ( $i = 0 ; $i < $columns; $i++ ) {
			$template[] = [ 'acf/mai-column', [], [] ];
		}

		return sprintf( '<InnerBlocks allowedBlocks="%s" template="%s" />', esc_attr( wp_json_encode( $allowed ) ), esc_attr( wp_json_encode( $template ) ) );
	}

	function get_admin_attributes( $attributes ) {
		foreach ( array_reverse( $this->args['arrangements'] ) as $break => $arrangement ) {
			$elements = $this->get_mapped_admin_elements( $arrangement );

			$index = 0;
			foreach ( $elements as $columns ) {
				$index++;

				// $attributes['style'] .= mai_columns_get_columns( $break, $columns );
				$attributes['style'] .= mai_columns_get_columns( sprintf( '%s-%s', $break, $index ), $columns );
			}

			$index = 0;
			foreach ( $elements as $columns ) {
				$index++;

				// $attributes['style'] .= mai_columns_get_flex( $break, $columns );
				// $attributes['style'] .= mai_columns_get_flex( sprintf( '%s-%s', $break, $index ), $columns );
				if ( in_array( $columns, [ 'auto', 'fill', 'full' ] ) ) {
					$attributes['style'] .= mai_columns_get_flex( sprintf( '%s-%s', $break, $index ), $columns );
				}
			}
		}

		return $attributes;
	}

	function get_attributes( $attributes ) {
		$column_gap = $this->args['column_gap'] ? sprintf( 'var(--spacing-%s)', $this->args['column_gap'] ) : '0px'; // Needs 0px for calc().
		$row_gap    = $this->args['row_gap'] ? sprintf( 'var(--spacing-%s)', $this->args['row_gap'] ) : '0px'; // Needs 0px for calc().

		$attributes['style'] .= sprintf( '--column-gap:%s;', $column_gap  );
		$attributes['style'] .= sprintf( '--row-gap:%s;', $row_gap );
		$attributes['style'] .= sprintf( '--align-columns:%s;', ! empty( $this->args['align_columns'] ) ? mai_get_flex_align( $this->args['align_columns'] ) : 'unset' ); // If wide/full then unset will be used.
		$attributes['style'] .= sprintf( '--align-columns-vertical:%s;', ! empty( $this->args['align_columns_vertical'] ) ? mai_get_flex_align( $this->args['align_columns_vertical'] ) : 'initial' ); // Needs initial for nested columns.

		return $attributes;
	}

	function get_mapped_admin_elements( $arrangement ) {
		$arrangement        = (array) $arrangement;
		$total_arrangements = count( $arrangement );
		$count              = 0;
		$elements           = [];
		for ( $i = 0; $i < 24; $i++ ) {
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
