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
	$atts = [
		'style' => get_field( 'style' ),
		'icon'  => get_field( 'icon' ),
	];

	foreach ( mai_get_icon_default_args() as $param => $default ) {
		$atts[ $param ] = get_field( $param ) ?: $default;
	}

	echo mai_get_icon( $atts );
}

add_filter( 'acf/load_field/key=field_5e3f4bcd867e8', 'mai_load_icon_choices' );
function mai_load_icon_choices( $field ) {
	$field['choices'] = [];
	// Bail if editing the field group.
	if ( 'acf-field-group' === get_post_type() ) {
		return $field;
	}
	$style = mai_get_acf_request( 'style' );
	if ( ! $style ) {
		return $field;
	}
	$dir = mai_get_dir() . sprintf( 'assets/icons/svgs/%s', $style );
	$url = mai_get_url() . sprintf( 'assets/icons/sprites/%s', $style );
	foreach ( glob( $dir . '/*.svg' ) as $file ) {
		$name = basename( $file, '.svg' );
		$field['choices'][ $name ] = sprintf( '<svg class="mai-notice-icon-svg"><use xlink:href="%s.svg#%s"></use></svg><span class="mai-notice-icon-name">%s</span>', $url, $name, $name );
	}
	return $field;
}


if ( function_exists( 'acf_add_local_field_group' ) ) :

	acf_add_local_field_group(
		[
			'key'                   => 'group_5e3f491031be8',
			'title'                 => 'Icon',
			'fields'                => [
				[
					'key'               => 'field_5df14557d58dg',
					'name'              => 'icon_tab',
					'label'             => esc_html__( 'Icon', 'mai-engine' ),
					'type'              => 'tab',
				],
				[
					'key'               => 'field_5e3f49758c633',
					'name'              => 'style',
					'label'             => 'Style',
					'type'              => 'button_group',
					'default'           => 'light',
					'choices'           => [
						'light'   => 'Light',
						'regular' => 'Regular',
						'solid'   => 'Solid',
						'brands'  => 'Brands',
					],
				],
				[	'key'               => 'field_5e3f4bcd867e8',
					'name'              => 'icon',
					'label'             => esc_html__( 'Icon', 'mai-engine' ),
					'type'              => 'select',
						'multiple'      => 0,
						'ui'            => 1,
						'ajax'          => 1,
				],
				[
					'key'               => 'field_5e3f49c18c634',
					'name'              => 'display',
					'label'             => 'Display',
					'type'              => 'button_group',
					'default_value'     => 'block',
					'choices'           => [
						'inline-block' => 'Inline',
						'block'        => 'Block',
					],
					'allow_null'        => 0,
					'layout'            => 'horizontal',
					'return_format'     => 'value',
				],
				[
					'key'               => 'field_5e3f49e68c635',
					'name'              => 'align',
					'label'             => 'Align',
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
					'name'              => 'size',
					'label'             => 'Size',
					'type'              => 'range',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'default_value'     => '',
					'min'               => '',
					'max'               => '',
					'step'              => '',
					'prepend'           => '',
					'append'            => '',
				],
				[
					'key'               => 'field_5df16678e67ef',
					'name'              => 'style_tab',
					'label'             => esc_html__( 'Style', 'mai-engine' ),
					'type'              => 'tab',
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
				],
				[
					'key'               => 'field_5e3f4ac78c642',
					'label'             => 'Border Width',
					'name'              => 'border_width',
					'type'              => 'number',
					'default_value'     => 0,
				],
				[
					'key'               => 'field_5e3f4b0a8c643',
					'label'             => 'Border Radius',
					'name'              => 'border_radius',
					'type'              => 'number',
					'default_value'     => 0,
				],
				[
					'key'               => 'field_5e3f791235e3c',
					'label'             => 'Shadow',
					'name'              => 'color_shadow',
					'type'              => 'color_picker',
				],
				[
					'key'               => 'field_5e3f4b188c644',
					'label'             => 'X Offset',
					'name'              => 'x_offset',
					'type'              => 'number',
					'default_value'     => 0,
				],
				[
					'key'               => 'field_5e3f4b2c8c645',
					'label'             => 'Y Offset',
					'name'              => 'y_offset',
					'type'              => 'number',
					'default_value'     => 0,
				],
				[
					'key'               => 'field_5e3f4b3e8c647',
					'label'             => 'Blur',
					'name'              => 'blur',
					'type'              => 'number',
					'default_value'     => 0,
				],
				[
					'key'               => 'field_5df17786f76fg',
					'name'              => 'layout_tab',
					'label'             => esc_html__( 'Layout', 'mai-engine' ),
					'type'              => 'tab',
				],
				[
					'key'               => 'field_5e3f4a6f8c63a',
					'label'             => 'Margin Top',
					'name'              => 'margin_top',
					'type'              => 'number',
					'default_value'     => 0,
				],
				[
					'key'               => 'field_5e3f4a998c63d',
					'label'             => 'Margin Left',
					'name'              => 'margin_left',
					'type'              => 'number',
					'default_value'     => 0,
				],
				[
					'key'               => 'field_5e3f4a898c63b',
					'label'             => 'Margin Right',
					'name'              => 'margin_right',
					'type'              => 'number',
					'default_value'     => 0,
				],
				[
					'key'               => 'field_5e3f4a928c63c',
					'label'             => 'Margin Bottom',
					'name'              => 'margin_bottom',
					'type'              => 'number',
					'default_value'     => 0,
				],
				[
					'key'               => 'field_5e3f4aa38c63e',
					'label'             => 'Padding Top',
					'name'              => 'padding_top',
					'type'              => 'number',
					'default_value'     => 0,
				],
				[
					'key'               => 'field_5e3f4abd8c641',
					'label'             => 'Padding Left',
					'name'              => 'padding_left',
					'type'              => 'number',
					'default_value'     => 0,
				],
				[
					'key'               => 'field_5e3f4aab8c63f',
					'label'             => 'Padding Right',
					'name'              => 'padding_right',
					'type'              => 'number',
					'default_value'     => 0,
				],
				[
					'key'               => 'field_5e3f4ab48c640',
					'label'             => 'Padding Bottom',
					'name'              => 'padding_bottom',
					'type'              => 'number',
					'default_value'     => 0,
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
