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
	 * Args.
	 *
	 * @var array $args
	 */
	public $args;

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
	}

	function get_sanitized_args( $args ) {
		$args = wp_parse_args( $args,
			[
				'class'                 => '',
				'align_column_vertical' => 'start',
				'spacing'               => '',
				'background'            => '',
				'first_xs'              => false,
				'first_sm'              => false,
				'first_md'              => false,
				'preview'               => false,
			]
		);

		$args['class']                 = esc_html( $args['class'] );
		$args['align_column_vertical'] = esc_html( $args['align_column_vertical'] );
		$args['spacing']               = esc_html( $args['spacing'] );
		$args['background']            = esc_html( $args['background'] );
		$args['first_xs']              = mai_sanitize_bool( $args['first_xs'] );
		$args['first_sm']              = mai_sanitize_bool( $args['first_sm'] );
		$args['first_md']              = mai_sanitize_bool( $args['first_md'] );
		$args['preview']               = mai_sanitize_bool( $args['preview'] );

		return $args;
	}

	/**
	 * Renders the columns.
	 *
	 * @since 2.10.0
	 *
	 * @return void
	 */
	public function render() {
		$attributes = [
			'class' => 'mai-column',
			'style' => '',
		];

		if ( $this->args['class'] ) {
			$attributes['class'] = mai_add_classes( $this->args['class'], $attributes['class'] );
		}

		if ( $this->args['align_column_vertical'] ) {
			$attributes['style'] .= sprintf( '--justify-content:%s;', mai_get_flex_align( $this->args['align_column_vertical'] ) );
		}

		if ( $this->args['spacing'] ) {
			$attributes['class'] = mai_add_classes( sprintf( 'has-%s-padding', esc_html( $this->args['spacing'] ) ), $attributes['class'] );
		}

		if ( $this->args['background'] ) {
			$attributes['style'] .= sprintf( 'background:%s;', mai_get_color_css( $this->args['background'] ) );

			if ( ! mai_is_light_color( $this->args['background'] ) ) {
				$attributes['class'] = mai_add_classes( 'has-dark-background', $attributes['class'] );
			}
		}

		if ( $this->args['first_xs'] ) {
			$attributes['style'] .= '--order-xs:-1;';
		}

		if ( $this->args['first_sm'] ) {
			$attributes['style'] .= '--order-sm:-1;';
		}

		if ( $this->args['first_md'] ) {
			$attributes['style'] .= '--order-md:-1;';
		}

		genesis_markup(
			[
				'open'    => '<div %s>',
				'close'   => '</div>',
				'context' => 'mai-column',
				'content' => $this->get_inner_blocks(),
				'echo'    => true,
				'atts'    => $attributes,
			]
		);
	}

	function get_inner_blocks() {
		// $template = [
			// [ 'core/paragraph', [], [] ],
		// ];

		// return sprintf( '<InnerBlocks template="%s" />', esc_attr( wp_json_encode( $template ) ) );
		return '<InnerBlocks />';
	}
}
