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
	 * Hashes.
	 *
	 * @var array $hashes
	 */
	protected static $hashes = [];

	/**
	 * Hash.
	 *
	 * @var string $hash
	 */
	protected $hash;

	/**
	 * Args.
	 *
	 * @var array $args
	 */
	protected $args;

	/**
	 * Parent args.
	 *
	 * @var array $arrangement
	 */
	public $arrangement;

	/**
	 * Mai_Columns constructor.
	 *
	 * @since 2.10.0
	 *
	 * @return void
	 */
	function __construct( $args ) {
		$this->args = $this->get_sanitized_args( $args );

		if ( $this->args['preview'] ) {
			$this->arrangement = mai_columns_get_arrangement( $this->args );
			$this->hash        = md5( serialize( $this->arrangement ) );
		}
	}

	/**
	 * Gets sanitized args.
	 *
	 * @since 2.10.0
	 *
	 * @param array $args The existing args.
	 *
	 * @return array
	 */
	function get_sanitized_args( $args ) {
		$args = wp_parse_args( $args,
			[
				'class'                  => '',
				'columns'                => 2,
				'columns_responsive'     => false,
				'columns_md'             => 2,
				'columns_sm'             => 1,
				'columns_xs'             => 1,
				'arrangement'            => [ '1/2' ],
				'arrangement_md'         => [ '1/2' ],
				'arrangement_sm'         => [ '1/2' ],
				'arrangement_xs'         => [ 'full' ],
				'column_gap'             => 'xl',
				'row_gap'                => 'xl',
				'align'                  => '',
				'align_columns'          => 'start',
				'align_columns_vertical' => '',
				'margin_top'             => '',
				'margin_bottom'          => '',
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
		echo $this->get();
	}

	/**
	 * Gets the columns.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	function get() {
		$html = '';
		$atts = [
			'class' => 'mai-columns',
			'style' => '',
		];

		if ( $this->args['preview'] ) {
			$html                 .= $this->get_styles();
			$atts['data-instance'] = $this->hash;
		}

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

		$html .= genesis_markup(
			[
				'open'    => '<div %s>',
				'context' => 'mai-columns',
				'echo'    => false,
				'atts'    => $atts,
			]
		);

		$wrap_atts = [
			'class' => 'mai-columns-wrap has-columns',
			'style' => '',
		];

		if ( $this->args['preview'] ) {
			$wrap_atts['class'] = mai_add_classes( 'has-columns-nested', $wrap_atts['class'] ); // Workaround for editor nested block markup.
		}

		$wrap_atts = $this->add_atts( $wrap_atts );

		$html .= genesis_markup(
			[
				'open'    => '<div %s>',
				'close'   => '</div>',
				'context' => 'mai-columns-wrap',
				'content' => $this->get_inner_blocks(),
				'echo'    => false,
				'atts'    => $wrap_atts,
			]
		);

		$html .= genesis_markup(
			[
				'close'   => '</div>',
				'context' => 'mai-columns',
				'echo'    => false,
			]
		);

		/**
		 * Reset index.
		 * Nested blocks are parsed before the parent,
		 * so this needs to be after rendering the parent.
		 */
		$index = mai_column_get_index( $this->hash, true );

		return $html;
	}

	/**
	 * Gets inline CSS for editor styles.
	 * Frustrating to do this, but here we are.
	 * Using `display: contents;` hides the column toolbar.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	function get_styles() {
		// Bail if we've already loaded styles for this arranagment.
		if ( in_array( $this->hash, $this::$hashes ) ) {
			return '';
		}

		// Add hash to hashes.
		$this::$hashes[] = $this->hash;

		$wrap  = sprintf( '.mai-columns[data-instance="%s"] > .mai-columns-wrap > .acf-innerblocks-container', $this->hash );
		$media = [
			'xs' => '@media only screen and (max-width: 599px)',
			'sm' => '@media only screen and (min-width: 600px) and (max-width: 799px)',
			'md' => '@media only screen and (min-width: 800px) and (max-width: 999px)',
			'lg' => '@media only screen and (min-width: 1000px)',
		];

		$html = '<style>';

		foreach ( $this->arrangement as $break => $arrangement ) {
			$count = count( $arrangement );

			// Start media query.
			$html .= $media[ $break ] . ' {';
				if ( 1 === $count ) {
					$html .= $wrap . ' > .wp-block {';
						$html .= mai_columns_get_columns( $break, reset( $arrangement ) );
						$html .= mai_columns_get_flex( $break, reset( $arrangement ) );
					$html .= '}';
				} else {
					foreach ( $arrangement as $index => $column ) {
						$html .= sprintf( '%s > .wp-block:nth-child(%sn+%s) {', $wrap, $count, $index + 1 );
							$html .= mai_columns_get_columns( $break, $column );
							$html .= mai_columns_get_flex( $break, $column );
						$html .= '}';
					}
				}

			// End media query.
			$html .= '}';
		}

		$html .= '</style>';

		return $html;
	}

	/**
	 * Adds attributes from args.
	 *
	 * @since 2.10.0
	 *
	 * @param array $atts The existing attributes.
	 *
	 * @return array
	 */
	function add_atts( $atts ) {
		$column_gap = $this->args['column_gap'] ? sprintf( 'var(--spacing-%s)', $this->args['column_gap'] ) : '0px'; // Needs 0px for calc().
		$row_gap    = $this->args['row_gap'] ? sprintf( 'var(--spacing-%s)', $this->args['row_gap'] ) : '0px'; // Needs 0px for calc().

		$atts['style'] .= sprintf( '--column-gap:%s;', $column_gap  );
		$atts['style'] .= sprintf( '--row-gap:%s;', $row_gap );
		$atts['style'] .= sprintf( '--align-columns:%s;', ! empty( $this->args['align_columns'] ) ? mai_get_flex_align( $this->args['align_columns'] ) : 'unset' ); // If wide/full then unset will be used.
		$atts['style'] .= sprintf( '--align-columns-vertical:%s;', ! empty( $this->args['align_columns_vertical'] ) ? mai_get_flex_align( $this->args['align_columns_vertical'] ) : 'initial' ); // Needs initial for nested columns.

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
		$allowed  = [ 'acf/mai-column' ];
		$template = [];
		$columns  = isset( $this->args['columns'] ) ? absint( $this->args['columns'] ) : 2;

		for ( $i = 0 ; $i < $columns; $i++ ) {
			$template[] = [ 'acf/mai-column', [], [] ];
		}

		return sprintf( '<InnerBlocks allowedBlocks="%s" template="%s" />', esc_attr( wp_json_encode( $allowed ) ), esc_attr( wp_json_encode( $template ) ) );
	}
}
