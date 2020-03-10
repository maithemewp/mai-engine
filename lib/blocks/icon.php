<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2019 BizBudding
 * @license   GPL-2.0-or-later
 */

add_action( 'acf/init', 'mai_register_icon_block' );
/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_register_icon_block() {
	if ( function_exists( 'acf_register_block_type' ) ) {
		acf_register_block_type(
			[
				'name'            => 'icon',
				'title'           => __( 'Icon', 'mai-engine' ),
				'description'     => __( 'A custom icon block.', 'mai-engine' ),
				'render_callback' => 'mai_render_icon_block',
				'category'        => 'widgets',
				'keywords'        => [ 'icon' ],
				'icon'            => 'star-filled',
			]
		);
	}
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
	$params = '';
	$style  = strtolower( get_field( 'style' ) ) ?: 'regular';
	$icon   = 'regular' === $style ? get_field( 'regular' ) : get_field( 'solid' );
	$icon   = str_replace( [ 'fas', 'far', 'fa-', ' ' ], '', $icon );

	foreach ( mai_icon_shortcode_atts() as $param => $default ) {
		$field = get_field( $param ) ? get_field( $param ) : $default;
		$value = 'icon' === $param ? $icon : $field;

		if ( '' !== $value && 'class' !== $value ) {
			$params .= ' ' . $param . '="' . $value . '"';
		}
	}

	echo do_shortcode( sprintf( '[mai_icon %s]', $params ) );
}


if ( function_exists( 'acf_add_local_field_group' ) ) :

	acf_add_local_field_group(
		[
			'key'                   => 'group_5e3f491031be8',
			'title'                 => 'Icon',
			'fields'                => [
				[
					'key'               => 'field_5e3f492a8c62e',
					'label'             => 'Icon',
					'name'              => '',
					'type'              => 'accordion',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => [
						'width' => '',
						'class' => '',
						'id'    => '',
					],
					'open'              => 0,
					'multi_expand'      => 0,
					'endpoint'          => 0,
				],
				[
					'key'               => 'field_5e3f49758c633',
					'label'             => 'Style',
					'name'              => 'style',
					'type'              => 'button_group',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => [
						'width' => '',
						'class' => '',
						'id'    => '',
					],
					'choices'           => [
						'regular' => 'Regular',
						'solid'   => 'Solid',
					],
					'allow_null'        => 0,
					'default_value'     => '',
					'layout'            => 'horizontal',
					'return_format'     => 'value',
				],
				[
					'key'               => 'field_5e3f4bcd855d9',
					'label'             => 'Icon',
					'name'              => 'regular',
					'type'              => 'font-awesome',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => [
						[
							[
								'field'    => 'field_5e3f49758c633',
								'operator' => '==',
								'value'    => 'regular',
							],
						],
					],
					'wrapper'           => [
						'width' => '',
						'class' => '',
						'id'    => '',
					],
					'icon_sets'         => [
						0 => 'far',
					],
					'custom_icon_set'   => '',
					'default_label'     => '<i class="fas"></i> address-book',
					'default_value'     => 'fas fa-address-book',
					'save_format'       => 'class',
					'allow_null'        => 0,
					'show_preview'      => 1,
					'enqueue_fa'        => 0,
					'fa_live_preview'   => '',
					'choices'           => [],
				],
				[
					'key'               => 'field_5e3f4c7da7011',
					'label'             => 'Icon',
					'name'              => 'solid',
					'type'              => 'font-awesome',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => [
						[
							[
								'field'    => 'field_5e3f49758c633',
								'operator' => '==',
								'value'    => 'solid',
							],
						],
					],
					'wrapper'           => [
						'width' => '',
						'class' => '',
						'id'    => '',
					],
					'icon_sets'         => [
						0 => 'fas',
					],
					'custom_icon_set'   => '',
					'default_label'     => '<i class="fas"></i> address-book',
					'default_value'     => 'fas fa-address-book',
					'save_format'       => 'class',
					'allow_null'        => 0,
					'show_preview'      => 1,
					'enqueue_fa'        => 0,
					'fa_live_preview'   => '',
					'choices'           => [],
				],
				[
					'key'               => 'field_5e3f49c18c634',
					'label'             => 'Display',
					'name'              => 'display',
					'type'              => 'button_group',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => [
						'width' => '',
						'class' => '',
						'id'    => '',
					],
					'choices'           => [
						'inline-block' => 'Inline',
						'block'        => 'Block',
					],
					'allow_null'        => 0,
					'default_value'     => '',
					'layout'            => 'horizontal',
					'return_format'     => 'value',
				],
				[
					'key'               => 'field_5e3f49e68c635',
					'label'             => 'Align',
					'name'              => 'align',
					'type'              => 'button_group',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => [
						'width' => '',
						'class' => '',
						'id'    => '',
					],
					'choices'           => [
						'left'   => 'Left',
						'center' => 'Center',
						'right'  => 'Right',
					],
					'allow_null'        => 0,
					'default_value'     => '',
					'layout'            => 'horizontal',
					'return_format'     => 'value',
				],
				[
					'key'               => 'field_5e3f4a0f8c636',
					'label'             => 'Size',
					'name'              => 'size',
					'type'              => 'range',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => [
						'width' => '',
						'class' => '',
						'id'    => '',
					],
					'default_value'     => '',
					'min'               => '',
					'max'               => '',
					'step'              => '',
					'prepend'           => '',
					'append'            => '',
				],
				[
					'key'               => 'field_5e3f493b8c62f',
					'label'             => 'Color',
					'name'              => '',
					'type'              => 'accordion',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => [
						'width' => '',
						'class' => '',
						'id'    => '',
					],
					'open'              => 0,
					'multi_expand'      => 0,
					'endpoint'          => 0,
				],
				[
					'key'               => 'field_5e3f4a368c637',
					'label'             => 'Icon',
					'name'              => 'color_icon',
					'type'              => 'color_picker',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => [
						'width' => '',
						'class' => '',
						'id'    => '',
					],
					'default_value'     => '',
				],
				[
					'key'               => 'field_5e3f4a4a8c638',
					'label'             => 'Background',
					'name'              => 'color_background',
					'type'              => 'color_picker',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => [
						'width' => '',
						'class' => '',
						'id'    => '',
					],
					'default_value'     => '',
				],
				[
					'key'               => 'field_5e3f4a558c639',
					'label'             => 'Border',
					'name'              => 'color_border',
					'type'              => 'color_picker',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => [
						'width' => '',
						'class' => '',
						'id'    => '',
					],
					'default_value'     => '',
				],
				[
					'key'               => 'field_5e3f791235e3c',
					'label'             => 'Shadow',
					'name'              => 'color_shadow',
					'type'              => 'color_picker',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => [
						'width' => '',
						'class' => '',
						'id'    => '',
					],
					'default_value'     => '',
				],
				[
					'key'               => 'field_5e3f49488c630',
					'label'             => 'Spacing',
					'name'              => '',
					'type'              => 'accordion',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => [
						'width' => '',
						'class' => '',
						'id'    => '',
					],
					'open'              => 0,
					'multi_expand'      => 0,
					'endpoint'          => 0,
				],
				[
					'key'               => 'field_5e3f4a6f8c63a',
					'label'             => 'Margin Top',
					'name'              => 'margin_top',
					'type'              => 'range',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => [
						'width' => '',
						'class' => '',
						'id'    => '',
					],
					'default_value'     => '',
					'min'               => '',
					'max'               => '',
					'step'              => '',
					'prepend'           => '',
					'append'            => '',
				],
				[
					'key'               => 'field_5e3f4a898c63b',
					'label'             => 'Margin Right',
					'name'              => 'margin_right',
					'type'              => 'range',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => [
						'width' => '',
						'class' => '',
						'id'    => '',
					],
					'default_value'     => '',
					'min'               => '',
					'max'               => '',
					'step'              => '',
					'prepend'           => '',
					'append'            => '',
				],
				[
					'key'               => 'field_5e3f4a928c63c',
					'label'             => 'Margin Bottom',
					'name'              => 'margin_bottom',
					'type'              => 'range',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => [
						'width' => '',
						'class' => '',
						'id'    => '',
					],
					'default_value'     => '',
					'min'               => '',
					'max'               => '',
					'step'              => '',
					'prepend'           => '',
					'append'            => '',
				],
				[
					'key'               => 'field_5e3f4a998c63d',
					'label'             => 'Margin Left',
					'name'              => 'margin_left',
					'type'              => 'range',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => [
						'width' => '',
						'class' => '',
						'id'    => '',
					],
					'default_value'     => '',
					'min'               => '',
					'max'               => '',
					'step'              => '',
					'prepend'           => '',
					'append'            => '',
				],
				[
					'key'               => 'field_5e3f4aa38c63e',
					'label'             => 'Padding Top',
					'name'              => 'padding_top',
					'type'              => 'range',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => [
						'width' => '',
						'class' => '',
						'id'    => '',
					],
					'default_value'     => '',
					'min'               => '',
					'max'               => '',
					'step'              => '',
					'prepend'           => '',
					'append'            => '',
				],
				[
					'key'               => 'field_5e3f4aab8c63f',
					'label'             => 'Padding Right',
					'name'              => 'padding_right',
					'type'              => 'range',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => [
						'width' => '',
						'class' => '',
						'id'    => '',
					],
					'default_value'     => '',
					'min'               => '',
					'max'               => '',
					'step'              => '',
					'prepend'           => '',
					'append'            => '',
				],
				[
					'key'               => 'field_5e3f4ab48c640',
					'label'             => 'Padding Bottom',
					'name'              => 'padding_bottom',
					'type'              => 'range',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => [
						'width' => '',
						'class' => '',
						'id'    => '',
					],
					'default_value'     => '',
					'min'               => '',
					'max'               => '',
					'step'              => '',
					'prepend'           => '',
					'append'            => '',
				],
				[
					'key'               => 'field_5e3f4abd8c641',
					'label'             => 'Padding Left',
					'name'              => 'padding_left',
					'type'              => 'range',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => [
						'width' => '',
						'class' => '',
						'id'    => '',
					],
					'default_value'     => '',
					'min'               => '',
					'max'               => '',
					'step'              => '',
					'prepend'           => '',
					'append'            => '',
				],
				[
					'key'               => 'field_5e3f49508c631',
					'label'             => 'Border',
					'name'              => '',
					'type'              => 'accordion',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => [
						'width' => '',
						'class' => '',
						'id'    => '',
					],
					'open'              => 0,
					'multi_expand'      => 0,
					'endpoint'          => 0,
				],
				[
					'key'               => 'field_5e3f4ac78c642',
					'label'             => 'Border Width',
					'name'              => 'border_copy',
					'type'              => 'range',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => [
						'width' => '',
						'class' => '',
						'id'    => '',
					],
					'default_value'     => '',
					'min'               => '',
					'max'               => '',
					'step'              => '',
					'prepend'           => '',
					'append'            => '',
				],
				[
					'key'               => 'field_5e3f4b0a8c643',
					'label'             => 'Border Radius',
					'name'              => 'border_radius',
					'type'              => 'range',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => [
						'width' => '',
						'class' => '',
						'id'    => '',
					],
					'default_value'     => '',
					'min'               => '',
					'max'               => '',
					'step'              => '',
					'prepend'           => '',
					'append'            => '',
				],
				[
					'key'               => 'field_5e3f49608c632',
					'label'             => 'Shadow',
					'name'              => '',
					'type'              => 'accordion',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => [
						'width' => '',
						'class' => '',
						'id'    => '',
					],
					'open'              => 0,
					'multi_expand'      => 0,
					'endpoint'          => 0,
				],
				[
					'key'               => 'field_5e3f4b188c644',
					'label'             => 'X Offset',
					'name'              => 'x_offset',
					'type'              => 'range',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => [
						'width' => '',
						'class' => '',
						'id'    => '',
					],
					'default_value'     => '',
					'min'               => '',
					'max'               => '',
					'step'              => '',
					'prepend'           => '',
					'append'            => '',
				],
				[
					'key'               => 'field_5e3f4b2c8c645',
					'label'             => 'Y Offset',
					'name'              => 'y_offset',
					'type'              => 'range',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => [
						'width' => '',
						'class' => '',
						'id'    => '',
					],
					'default_value'     => '',
					'min'               => '',
					'max'               => '',
					'step'              => '',
					'prepend'           => '',
					'append'            => '',
				],
				[
					'key'               => 'field_5e3f4b3e8c647',
					'label'             => 'Blur',
					'name'              => 'blur',
					'type'              => 'range',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => [
						'width' => '',
						'class' => '',
						'id'    => '',
					],
					'default_value'     => '',
					'min'               => '',
					'max'               => '',
					'step'              => '',
					'prepend'           => '',
					'append'            => '',
				],
			],
			'location'              => [
				[
					[
						'param'    => 'block',
						'operator' => '==',
						'value'    => 'acf/icon',
					],
				],
			],
			'menu_order'            => 0,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'hide_on_screen'        => '',
			'active'                => true,
			'description'           => '',
		]
	);

endif;
