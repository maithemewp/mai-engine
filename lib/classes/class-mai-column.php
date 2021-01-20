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
	 * @since TBD
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
				'class'      => '',
				'spacing'    => '',
				'background' => '',
				'preview'    => false,
			]
		);

		$args['class']      = esc_html( $args['class'] );
		$args['spacing']    = esc_html( $args['spacing'] );
		$args['background'] = esc_html( $args['background'] );
		$args['preview']    = mai_sanitize_bool( $args['preview'] );

		return $args;
	}

	/**
	 * Renders the columns.
	 *
	 * @since TBD
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

		if ( $this->args['spacing'] ) {
			$attributes['class'] = mai_add_classes( sprintf( 'has-%s-padding', esc_html( $this->args['spacing'] ) ), $attributes['class'] );
		}

		if ( $this->args['background'] ) {
			$colors = array_flip( mai_get_colors() );

			if ( isset( $colors[ $this->args['background'] ] ) ) {
				$attributes['class'] .= sprintf( ' has-%s-background-color', $colors[ $this->args['background'] ] );
			} else {
				$attributes['style'] .= sprintf( 'background:%s !important;', $this->args['background'] );
			}
		}

		genesis_markup(
			[
				'open'    => '<div %s>',
				'close'   => '</div>',
				'context' => 'mai-column',
				'content' => '<InnerBlocks />',
				'echo'    => true,
				'atts'    => $attributes,
			]
		);
	}
}