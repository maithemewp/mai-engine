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
				'icon'            => mai_get_svg_icon( 'wave-sine', 'regular' ),
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
		'height'          => get_field( 'height' ),
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

	$atts = wp_parse_args( $atts, [
		'style'           => 'angle',
		'height'          => 'md',
		'flip_vertical'   => false,
		'flip_horizontal' => false,
		'color'           => mai_get_color( 'primary' ),
		'class'           => '',
	] );

	$atts = [
		'style'           => esc_html( $atts['style'] ),
		'height'          => esc_html( $atts['height'] ),
		'flip_vertical'   => mai_sanitize_bool( $atts['flip_vertical'] ),
		'flip_horizontal' => mai_sanitize_bool( $atts['flip_horizontal'] ),
		'color'           => esc_html( $atts['color'] ),
		'class'           => sanitize_html_class( $atts['class'] ),
	];

	$is_palette = false;
	$colors     = array_flip( mai_get_colors() );
	$attributes = [
		'class' => sprintf( 'mai-divider mai-divider-%s alignfull', $atts['style'] ),
		'style' => '',
	];

	if ( isset( $colors[ $atts['color'] ] ) ) {
		$is_palette = true;
		$attributes['class'] .= sprintf( ' has-%s-color', $colors[ $atts['color'] ] );
	} else {
		$attributes['style'] .= sprintf( '--divider-color:%s;', $atts['color'] );
	}

	if ( $atts['height'] ) {
		switch ( $atts['height'] ) {
			case 'xs':
				$height = '2rem';
			break;
			case 'sm':
				$height = 'calc(2rem + 2vw)';
			break;
			case 'md':
				$height = 'calc(2rem + 6vw)';
			break;
			case 'lg':
				$height = 'calc(2rem + 12vw)';
			break;
			case 'xl':
				$height = 'calc(2rem + 16vw)';
			break;
			default:
				$height = 'calc(2rem + 4vw)';
		}
		$attributes['style'] .= sprintf( '--divider-height:%s;', $height );
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

add_action( 'acf/init', 'mai_register_divider_field_groups' );
/**
 * Register Mai Divider block field group.
 *
 * @since 0.2.0
 *
 * @return void
 */
function mai_register_divider_field_groups() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group( [
		'key'    => 'group_5eb199873d4ff',
		'title'  => esc_html__( 'Mai Divider', 'mai-engine' ),
		'fields' => [
			[
				'key'     => 'field_5eb19996e23b9',
				'label'   => esc_html__( 'Style', 'mai-engine' ),
				'name'    => 'style',
				'type'    => 'radio',
				'choices' => [
					'angle' => esc_html__( 'Angle', 'mai-engine' ),
					'curve' => esc_html__( 'Curve', 'mai-engine' ),
					'point' => esc_html__( 'Point', 'mai-engine' ),
					'round' => esc_html__( 'Round', 'mai-engine' ),
					'wave'  => esc_html__( 'Wave', 'mai-engine' ),
				],
			],
			[
				'key'     => 'field_5eb211f885ac8',
				'label'   => esc_html__( 'Height', 'mai-engine' ),
				'name'    => 'height',
				'type'    => 'button_group',
				'choices' => [
					'xs' => esc_html__( 'XS', 'mai-engine' ),
					'sm' => esc_html__( 'SM', 'mai-engine' ),
					'md' => esc_html__( 'MD', 'mai-engine' ),
					'lg' => esc_html__( 'LG', 'mai-engine' ),
					'xl' => esc_html__( 'XL', 'mai-engine' ),
				],
				'default_value' => 'md',
			],
			[
				'key'   => 'field_5eb19a01e23ba',
				'label' => esc_html__( 'Flip Vertical', 'mai-engine' ),
				'name'  => 'flip_vertical',
				'type'  => 'true_false',
				'ui'    => 1,
			],
			[
				'key'   => 'field_5eb19a28e23bb',
				'label' => esc_html__( 'Flip Horizontal', 'mai-engine' ),
				'name'  => 'flip_horizontal',
				'type'  => 'true_false',
				'ui'    => 1,
			],
			[
				'key'           => 'field_5eb1a70c7f083',
				'label'         => esc_html__( 'Color', 'mai-engine' ),
				'name'          => 'color',
				'type'          => 'color_picker',
				'default_value' => '#ffffff',
			],
		],
		'location' => [
			[
				[
					'param'    => 'block',
					'operator' => '==',
					'value'    => 'acf/mai-divider',
				],
			],
		],
		'description' => '',
	] );

}
