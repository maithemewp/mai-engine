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

add_action( 'acf/init', 'mai_register_divider_block' );
/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_register_divider_block() {
	if ( function_exists( 'acf_register_block_type' ) ) {
		acf_register_block_type(
			[
				'name'            => 'mai-divider',
				'title'           => __( 'Mai Divider', 'mai-engine' ),
				'description'     => __( 'A custom divider block.', 'mai-engine' ),
				'render_callback' => 'mai_do_divider_block',
				'category'        => 'widgets',
				'keywords'        => [ 'divider' ],
				'icon'            => 'line',
				'mode'            => 'preview',
			]
		);
	}
}

/**
 * Callback function to render the Divider block.
 *
 * @since 0.2.0
 *
 * @param array  $block      The block settings and attributes.
 * @param string $content    The block inner HTML (empty).
 * @param bool   $is_preview True during AJAX preview.
 * @param int    $post_id    The post ID this block is saved to.
 *
 * @return void
 */
function mai_do_divider_block( $block, $content = '', $is_preview = false, $post_id = 0 ) {
	$atts = [
		'style'           => get_field( 'style' ),
		'flip_vertical'   => get_field( 'flip_vertical' ),
		'flip_horizontal' => get_field( 'flip_horizontal' ),
		'color'           => get_field( 'color' ),
		'class'           => isset( $block['className'] ) && ! empty( $block['className'] ) ? sanitize_html_class( $block['className'] ) : '',
	];

	echo mai_do_divider( $atts );
}

function mai_do_divider( $atts ) {
	echo mai_get_divider( $atts );
}

function mai_get_divider( $atts ) {
	$file = mai_get_svg( 'divider-' . $atts['style'], 'mai-divider-svg' );

	if ( ! $file ) {
		return;
	}

	$is_palette = false;
	$colors     = array_flip( mai_get_colors() );
	$attributes = [
		'class' => 'mai-divider alignfull',
	];

	if ( isset( $colors[ $atts['color'] ] ) ) {
		$is_palette = true;
		$attributes['class'] .= sprintf( ' has-%s-color', $colors[ $atts['color'] ] );
	} else {
		$attributes['style'] = sprintf( '--divider-color:%s;', $atts['color'] );
	}

	if ( $atts['flip_vertical'] ) {
		$attributes['class'] .= ' flip-vertical';
	}

	if ( $atts['flip_horizontal'] ) {
		$attributes['class'] .= ' flip-horizontal';
	}

	if ( ! empty( $atts['class'] ) ) {
		$class .= ' ' . $atts['class'];
	}

	return genesis_markup(
		[
			'open'    => "<div %s>",
			'close'   => "</div>",
			'content' => $file,
			'context' => 'mai-divider',
			'echo'    => false,
			'atts'    => $attributes,
			'params'  => $atts,
		]
	);
}
