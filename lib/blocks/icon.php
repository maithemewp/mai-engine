<?php

add_action( 'acf/init', 'mai_register_icon_block' );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_register_icon_block() {
	acf_register_block_type( [
		'name'            => 'icon',
		'title'           => __( 'Icon', 'mai-engine' ),
		'description'     => __( 'A custom icon block.', 'mai-engine' ),
		'render_callback' => 'mai_render_icon_block',
		'category'        => 'mai',
		'icon'            => 'star-filled',
	] );
}

/**
 * Callback function to render the Icon block.
 *
 * @since 2.0.0
 *
 * @param array  $block      The block settings and attributes.
 * @param string $content    The block inner HTML (empty).
 * @param bool   $is_preview True during AJAX preview.
 * @param int    $post_id    The post ID this block is saved to.
 *
 * @return void
 */
function mai_render_icon_block( $block, $content = '', $is_preview = false, $post_id = 0 ) {

	// Create id attribute allowing for custom "anchor" value.
	$id = 'icon-' . $block['id'];
	if ( ! empty( $block['anchor'] ) ) {
		$id = $block['anchor'];
	}

	// Create class attribute allowing for custom "className" and "align" values.
	$className = 'icon';
	if ( ! empty( $block['className'] ) ) {
		$className .= ' ' . $block['className'];
	}
	if ( ! empty( $block['align'] ) ) {
		$className .= ' align' . $block['align'];
	}

	$style   = strtolower( get_field( 'style' ) ) ?: 'regular';
	$regular = get_field( 'regular' ) ?: 'address-book';
	$solid   = get_field( 'solid' ) ?: 'address-book';
	$color   = get_field( 'color_icon' ) ?: mai_default_color( 'primary' );

	if ( $style === 'regular' ) {
		$icon = $regular;
	} else {
		$icon = $solid;
	}

	$icon = str_replace( [ 'fas', 'far', 'fa-', ' ' ], '', $icon );

	echo do_shortcode( sprintf(
		'[mai_icon style="%s" icon="%s" color_icon="%s"]',
		$style,
		$icon,
		$color
	) );
}
