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
				'align'           => 'full',
				'supports'        => [
					'align' => [ 'wide', 'full' ],
				],
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
		'align'           => isset( $block['align'] ) ? esc_html( $block['align'] ) : 'full',
	];

	echo mai_get_divider( $atts );
}

/**
 * Description of expected behavior.
 *
 * @since 0.3.0
 *
 * @param $atts
 *
 * @return void
 */
function mai_do_divider( $atts ) {
	echo mai_get_divider( $atts );
}

/**
 * Description of expected behavior.
 *
 * @since 0.3.0
 *
 * @param array $atts
 *
 * @return string
 */
function mai_get_divider( $atts = [] ) {
	$atts = wp_parse_args( $atts, [
		'style'            => 'angle',
		'height'           => 'md',
		'flip_horizontal'  => false,
		'flip_vertical'    => false,
		'background_color' => 'transparent',
		'color'            => mai_get_color( 'primary' ),
		'align'            => 'full',
		'class'            => '',
	] );

	$atts = [
		'style'            => esc_html( $atts['style'] ),
		'height'           => esc_html( $atts['height'] ),
		'flip_horizontal'  => mai_sanitize_bool( $atts['flip_horizontal'] ),
		'flip_vertical'    => mai_sanitize_bool( $atts['flip_vertical'] ),
		'background_color' => esc_html( $atts['background_color'] ),
		'color'            => esc_html( $atts['color'] ),
		'align'            => esc_html( $atts['align'] ),
		'class'            => sanitize_html_class( $atts['class'] ),
	];

	$flipping_horizontal = $atts['flip_horizontal'] && ! in_array( $atts['style'], [ 'point', 'round' ] );
	$flipping_vertical   = $atts['flip_vertical'];
	$file_name           = 'divider-' . $atts['style'];

	$file = mai_get_svg( $file_name, 'mai-divider-svg' );

	if ( ! $file ) {
		return '';
	}

	$atts['background_color'] = trim( $atts['background_color'] );
	$atts['background_color'] = $atts['background_color'] ?: 'transparent';
	$atts['color']            = trim( $atts['color'] );
	$atts['color']            = $atts['color'] ?: 'transparent';

	$colors     = array_flip( mai_get_colors() );
	$attributes = [
		'class' => sprintf( 'mai-divider mai-divider-%s', $atts['style'] ),
		'style' => '',
	];

	if ( $atts['align'] ) {
		$attributes['class'] .= ' align' . $atts['align'];
	}

	if ( isset( $colors[ $atts['color'] ] ) ) {
		$attributes['class'] .= sprintf( ' has-%s-color', $colors[ $atts['color'] ] );
	} else {
		$attributes['style'] .= sprintf( '--divider-color:%s;', $atts['color'] );
	}

	if ( isset( $colors[ $atts['background_color'] ] ) ) {
		$attributes['class'] .= sprintf( ' has-%s-background-color', $colors[ $atts['background_color'] ] );
	} else {
		$attributes['style'] .= sprintf( '--divider-background-color:%s;', $atts['background_color'] );
	}

	if ( $atts['height'] ) {
		switch ( $atts['height'] ) {
			case 'xs':
				$height = '1rem';
				break;
			case 'sm':
				$height = '2rem';
				break;
			case 'md':
				$height = '4rem';
				break;
			case 'lg':
				$height = '6rem';
				break;
			case 'xl':
				$height = '8rem';
				break;
			default:
				$height = '4rem';
		}
		$attributes['style'] .= sprintf( '--divider-height:%s;', $height );
	}

	if ( $flipping_vertical ) {
		$attributes['class'] .= ' flip-vertical';
	}

	if ( $flipping_horizontal ) {
		$attributes['class'] .= ' flip-horizontal';
	}

	if ( ! empty( $atts['class'] ) ) {
		$attributes['class'] .= ' ' . $atts['class'];
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
		'key'         => 'mai_divider',
		'title'       => esc_html__( 'Mai Divider', 'mai-engine' ),
		'fields'      => [
			[
				'key'     => 'mai_divider_style',
				'label'   => esc_html__( 'Style', 'mai-engine' ),
				'name'    => 'style',
				'type'    => 'radio',
				'choices' => [
					'angle' => esc_html__( 'Angle', 'mai-engine' ),
					'curve' => esc_html__( 'Curve', 'mai-engine' ),
					'wave'  => esc_html__( 'Wave', 'mai-engine' ),
					'point' => esc_html__( 'Point', 'mai-engine' ),
					'round' => esc_html__( 'Round', 'mai-engine' ),
				],
			],
			[
				'key'           => 'mai_divider_height',
				'label'         => esc_html__( 'Height', 'mai-engine' ),
				'name'          => 'height',
				'type'          => 'button_group',
				'choices'       => [
					'xs' => esc_html__( 'XS', 'mai-engine' ),
					'sm' => esc_html__( 'SM', 'mai-engine' ),
					'md' => esc_html__( 'MD', 'mai-engine' ),
					'lg' => esc_html__( 'LG', 'mai-engine' ),
					'xl' => esc_html__( 'XL', 'mai-engine' ),
				],
				'default_value' => 'md',
			],
			[
				'key'               => 'mai_divider_flip_horizontal',
				'label'             => esc_html__( 'Flip Horizontally', 'mai-engine' ),
				'name'              => 'flip_horizontal',
				'type'              => 'true_false',
				'ui'                => 1,
				'conditional_logic' => [
					[
						[
							'field'    => 'mai_divider_style',
							'operator' => '!=',
							'value'    => 'point',
						],
						[
							'field'    => 'mai_divider_style',
							'operator' => '!=',
							'value'    => 'round',
						],
					],
				],
			],
			[
				'key'   => 'mai_divider_flip_vertical',
				'label' => esc_html__( 'Flip Vertically', 'mai-engine' ),
				'name'  => 'flip_vertical',
				'type'  => 'true_false',
				'ui'    => 1,
			],
			[
				'key'           => 'mai_divider_color',
				'label'         => esc_html__( 'Color', 'mai-engine' ),
				'name'          => 'color',
				'type'          => 'color_picker',
				'default_value' => '#ffffff',
			],
		],
		'location'    => [
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
