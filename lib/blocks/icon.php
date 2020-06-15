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

add_action( 'acf/init', 'mai_register_icon_block' );
/**
 * Register Mai Icon block.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_register_icon_block() {
	if ( function_exists( 'acf_register_block_type' ) ) {
		acf_register_block_type(
			[
				'name'            => 'mai-icon',
				'title'           => __( 'Mai Icon', 'mai-engine' ),
				'description'     => __( 'A custom icon block.', 'mai-engine' ),
				'render_callback' => 'mai_do_icon_block',
				'category'        => 'widgets',
				'keywords'        => [ 'icon' ],
				'icon'            => 'heart',
				'mode'            => 'preview',
				'supports'        => [
					'align' => false,
				],
			]
		);
	}
}

/**
 * Callback function to render the Icon block.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_do_icon_block() {
	$args     = [];
	$settings = array_keys( mai_get_icon_default_args() );

	foreach ( $settings as $setting ) {
		$args[ $setting ] = get_field( $setting );
	}

	echo mai_get_icon( $args );
}

add_filter( 'acf/load_field/key=field_5e3f4bcd978f9', 'mai_load_icon_choices' );
add_filter( 'acf/load_field/key=field_5e3f4bcd867e8', 'mai_load_icon_brand_choices' );
/**
 * Load the icon field, getting choices from our icons directory.
 * Uses sprite for performance of loading choices in the field.
 *
 * @since 0.1.0
 *
 * @param array $field The ACF field.
 *
 * @return array
 */
function mai_load_icon_choices( $field ) {
	// Bail if editing the field group.
	if ( 'acf-field-group' === get_post_type() ) {
		return $field;
	}

	$field['choices']       = mai_get_icon_choices( 'light' );
	$field['default_value'] = 'heart';

	return $field;
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param array $field Field args.
 *
 * @return mixed
 */
function mai_load_icon_brand_choices( $field ) {
	// Bail if editing the field group.
	if ( 'acf-field-group' === get_post_type() ) {
		return $field;
	}

	$field['choices']       = mai_get_icon_choices( 'brands' );
	$field['default_value'] = 'WordPress';

	return $field;
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param string $style Icon style.
 *
 * @return array
 */
function mai_get_icon_choices( $style ) {
	$choices = [];
	$dir     = mai_get_dir() . sprintf( 'assets/icons/svgs/%s', $style );
	$url     = mai_get_url() . sprintf( 'assets/icons/sprites/%s', $style );

	foreach ( glob( $dir . '/*.svg' ) as $file ) {
		$name             = basename( $file, '.svg' );
		$choices[ $name ] = sprintf( '<svg class="mai-icon-svg"><use xlink:href="%s.svg#%s"></use></svg><span class="mai-icon-name">%s</span>', $url, $name, $name );
	}

	return $choices;
}

add_action( 'acf/init', 'mai_register_icon_field_groups' );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_register_icon_field_groups() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		[
			'key'         => 'group_5e3f491031be8',
			'title'       => esc_html__( 'Icon', 'mai-engine' ),
			'fields'      => [
				[
					'key'   => 'field_5df14557d58dg',
					'name'  => 'icon_tab',
					'label' => esc_html__( 'Icon', 'mai-engine' ),
					'type'  => 'tab',
				],
				[
					'key'     => 'field_5e3f49758c633',
					'name'    => 'style',
					'label'   => esc_html__( 'Style', 'mai-engine' ),
					'type'    => 'button_group',
					'default' => 'light',
					'choices' => [
						'light'   => esc_html__( 'Light', 'mai-engine' ),
						'regular' => esc_html__( 'Regular', 'mai-engine' ),
						'solid'   => esc_html__( 'Solid', 'mai-engine' ),
						'brands'  => esc_html__( 'Brands', 'mai-engine' ),
					],
				],
				[
					'key'               => 'field_5e3f4bcd978f9',
					'name'              => 'icon',
					'label'             => esc_html__( 'Icon', 'mai-engine' ),
					'type'              => 'select',
					'default'           => 'heart',
					'multiple'          => 0,
					'ui'                => 1,
					'ajax'              => 1,
					'conditional_logic' => [
						[
							'field'    => 'field_5e3f49758c633', // Style.
							'operator' => '!=',
							'value'    => 'brands',
						],
					],
					'wrapper'           => [
						'class' => 'mai-icon-select',
					],
				],
				[
					'key'               => 'field_5e3f4bcd867e8',
					'name'              => 'icon',
					'label'             => esc_html__( 'Icon (Brands)', 'mai-engine' ),
					'type'              => 'select',
					'multiple'          => 0,
					'ui'                => 1,
					'ajax'              => 1,
					'conditional_logic' => [
						[
							'field'    => 'field_5e3f49758c633', // Style.
							'operator' => '==',
							'value'    => 'brands',
						],
					],
					'wrapper'           => [
						'class' => 'mai-icon-select',
					],
				],
				[
					'key'           => 'field_5e3f49c18c634',
					'name'          => 'display',
					'label'         => esc_html__( 'Display', 'mai-engine' ),
					'type'          => 'button_group',
					'default_value' => 'block',
					'choices'       => [
						'block'        => esc_html__( 'Block', 'mai-engine' ),
						'inline-block' => esc_html__( 'Inline', 'mai-engine' ),
					],
					'allow_null'    => 0,
					'layout'        => 'horizontal',
					'return_format' => 'value',
				],
				[
					'key'               => 'field_5e3f49e68c635',
					'name'              => 'align',
					'label'             => esc_html__( 'Align', 'mai-engine' ),
					'type'              => 'button_group',
					'choices'           => [
						'left'   => esc_html__( 'Left', 'mai-engine' ),
						'center' => esc_html__( 'Center', 'mai-engine' ),
						'right'  => esc_html__( 'Right', 'mai-engine' ),
					],
					'allow_null'        => 0,
					'default_value'     => '',
					'layout'            => 'horizontal',
					'return_format'     => 'value',
					'conditional_logic' => [
						[
							'field'    => 'field_5e3f49c18c634', // Display.
							'operator' => '==',
							'value'    => 'block',
						],
					],
				],
				[
					'key'           => 'field_5e3f4a0f8c636',
					'name'          => 'size',
					'label'         => esc_html__( 'Size', 'mai-engine' ),
					'instructions'  => esc_html__( 'Use 0 for theme default', 'mai-engine' ),
					'type'          => 'number',
					'default_value' => 0,
					'append'        => 'px',
				],
				[
					'key'   => 'field_5df16678e67ef',
					'name'  => 'style_tab',
					'label' => esc_html__( 'Styles', 'mai-engine' ),
					'type'  => 'tab',
				],
				[
					'key'   => 'field_5e3f4a368c637',
					'label' => esc_html__( 'Icon Color', 'mai-engine' ),
					'name'  => 'color_icon',
					'type'  => 'color_picker',
				],
				[
					'key'   => 'field_5e3f4a4a8c638',
					'label' => esc_html__( 'Background', 'mai-engine' ),
					'name'  => 'color_background',
					'type'  => 'color_picker',
				],
				[
					'key'   => 'field_5e3f4a558c639',
					'label' => esc_html__( 'Border Color', 'mai-engine' ),
					'name'  => 'color_border',
					'type'  => 'color_picker',
				],
				[
					'key'               => 'field_5e3f4ac78c642',
					'label'             => esc_html__( 'Border Width', 'mai-engine' ),
					'name'              => 'border_width',
					'type'              => 'number',
					'default_value'     => 0,
					'append'            => 'px',
					'conditional_logic' => [
						[
							'field'    => 'field_5e3f4a558c639', // Border Color.
							'operator' => '!=empty',
						],
					],
				],

				/*
				 * Box Shadow.
				 */

				[
					'key'   => 'field_5e3f791235e3c',
					'label' => esc_html__( 'Box Shadow', 'mai-engine' ),
					'name'  => 'color_shadow',
					'type'  => 'color_picker',
				],
				[
					'key'               => 'field_5e3f4b188c644',
					'label'             => esc_html__( 'X Offset', 'mai-engine' ),
					'name'              => 'x_offset',
					'type'              => 'number',
					'default_value'     => 0,
					'conditional_logic' => [
						[
							'field'    => 'field_5e3f791235e3c', // Shadow.
							'operator' => '!=empty',
						],
					],
				],
				[
					'key'               => 'field_5e3f4b2c8c645',
					'label'             => esc_html__( 'Y Offset', 'mai-engine' ),
					'name'              => 'y_offset',
					'type'              => 'number',
					'default_value'     => 0,
					'conditional_logic' => [
						[
							'field'    => 'field_5e3f791235e3c', // Shadow.
							'operator' => '!=empty',
						],
					],
				],
				[
					'key'               => 'field_5e3f4b3e8c647',
					'label'             => esc_html__( 'Blur', 'mai-engine' ),
					'name'              => 'blur',
					'type'              => 'number',
					'default_value'     => 0,
					'conditional_logic' => [
						[
							'field'    => 'field_5e3f791235e3c', // Shadow.
							'operator' => '!=empty',
						],
					],
				],

				/*
				 * Text Shadow.
				 */

				[
					'key'   => 'color_text_shadow',
					'label' => esc_html__( 'Text Shadow', 'mai-engine' ),
					'name'  => 'color_text_shadow',
					'type'  => 'color_picker',
				],
				[
					'key'               => 'text_shadow_x_offset',
					'label'             => esc_html__( 'X Offset', 'mai-engine' ),
					'name'              => 'text_shadow_x_offset',
					'type'              => 'number',
					'default_value'     => 0,
					'conditional_logic' => [
						[
							'field'    => 'color_text_shadow', // Shadow.
							'operator' => '!=empty',
						],
					],
				],
				[
					'key'               => 'text_shadow_y_offset',
					'label'             => esc_html__( 'Y Offset', 'mai-engine' ),
					'name'              => 'text_shadow_y_offset',
					'type'              => 'number',
					'default_value'     => 0,
					'conditional_logic' => [
						[
							'field'    => 'color_text_shadow', // Shadow.
							'operator' => '!=empty',
						],
					],
				],
				[
					'key'               => 'text_shadow_blur',
					'label'             => esc_html__( 'Blur', 'mai-engine' ),
					'name'              => 'text_shadow_blur',
					'type'              => 'number',
					'default_value'     => 0,
					'conditional_logic' => [
						[
							'field'    => 'color_text_shadow', // Shadow.
							'operator' => '!=empty',
						],
					],
				],

				/*
				 * Padding.
				 */

				[
					'key'           => 'field_5e3f4bb49d74f',
					'label'         => esc_html__( 'Padding', 'mai-engine' ),
					'name'          => 'padding',
					'type'          => 'number',
					'default_value' => 0,
					'append'        => 'px',
				],
				[
					'key'   => 'field_5e3f5b7g9d74b',
					'label' => esc_html__( 'Margin', 'mai-engine' ),
					'name'  => 'margin_message',
					'type'  => 'message',
				],
				[
					'key'           => 'field_5e3f4a6f8c63a',
					'label'         => esc_html__( 'Top', 'mai-engine' ),
					'name'          => 'margin_top',
					'type'          => 'number',
					'default_value' => 0,
					'append'        => 'px',
				],
				[
					'key'           => 'field_5e3f4a928c63c',
					'label'         => esc_html__( 'Bottom', 'mai-engine' ),
					'name'          => 'margin_bottom',
					'type'          => 'number',
					'default_value' => 0,
					'append'        => 'px',
				],
				[
					'key'           => 'field_5e3f4a998c63d',
					'label'         => esc_html__( 'Left', 'mai-engine' ),
					'name'          => 'margin_left',
					'type'          => 'number',
					'default_value' => 0,
					'append'        => 'px',
				],
				[
					'key'           => 'field_5e3f4a898c63b',
					'label'         => esc_html__( 'Right', 'mai-engine' ),
					'name'          => 'margin_right',
					'type'          => 'number',
					'default_value' => 0,
					'append'        => 'px',
				],
				[
					'key'           => 'field_5e3f4b0a8c643',
					'label'         => esc_html__( 'Round Corners', 'mai-engine' ),
					'instructions'  => esc_html__( 'Accepts any unit value (%, px, etc.) and shorthand (0 16 0 16). Use 0px for square.', 'mai-theme' ),
					'name'          => 'border_radius',
					'type'          => 'text',
					'default_value' => '50%',
					'placeholder'   => esc_html__( '50%', 'mai-engine' ),
				],
			],
			'location'    => [
				[
					[
						'param'    => 'block',
						'operator' => '==',
						'value'    => 'acf/mai-icon',
					],
				],
			],
			'active'      => true,
			'description' => '',
		]
	);

}
