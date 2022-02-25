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

add_action( 'acf/init', 'mai_register_icon_block' );
/**
 * Register Mai Icon block.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_register_icon_block() {
	if ( ! function_exists( 'acf_register_block_type' ) ) {
		return;
	}

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

/**
 * Callback function to render the Icon block.
 *
 * @since 0.1.0
 *
 * @param array  $block      The block settings and attributes.
 * @param string $content    The block inner HTML (empty).
 * @param bool   $is_preview True during AJAX preview.
 * @param int    $post_id    The post ID this block is saved to.
 *
 * @return void
 */
function mai_do_icon_block( $block, $content = '', $is_preview = false, $post_id = 0 ) {
	$args     = [];
	$defaults = mai_get_icon_default_args();

	foreach ( array_keys( $defaults ) as $setting ) {
		$args[ $setting ] = get_field( $setting );
	}

	$args['class'] = isset( $block['className'] ) && ! empty( $block['className'] ) ? mai_add_classes( $block['className'] ) : '';

	// Swap for brand.
	if ( 'brands' === $args['style'] ) {
		$args['icon'] = $args['icon_brand'];
		unset( $args['icon_brand'] );
	}

	// Remove empty args so defaults are used.
	foreach ( $args as $key => $value ) {
		if ( '' !== $value ) {
			continue;
		}
		unset( $args[ $key ] );
	}

	echo mai_get_icon( $args );
}

add_filter( 'acf/load_field/key=mai_icon_choices', 'mai_load_icon_choices' );
add_filter( 'acf/load_field/key=mai_icon_brand_choices', 'mai_load_icon_brand_choices' );
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

	$field['choices'] = mai_get_icon_choices( 'light' );

	return $field;
}

/**
 * Add icon brand choices.
 *
 * @since 0.1.0
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

	$field['choices'] = mai_get_icon_choices( 'brands' );

	return $field;
}

/**
 * Get icon svg choices.
 *
 * @since 1.0.0
 *
 * @link https://css-tricks.com/on-xlinkhref-being-deprecated-in-svg/
 *
 * @param string $style Icon style.
 *
 * @return array
 */
function mai_get_icon_choices( $style ) {
	$choices = [];
	$dir     = mai_get_icons_dir();
	$url     = mai_get_icons_url();

	if ( ! ( $dir && $url ) ) {
		return $choices;
	}

	$dir .= sprintf( '/svgs/%s', $style );
	$url .= sprintf( '/sprites/%s', $style );

	foreach ( glob( $dir . '/*.svg' ) as $file ) {
		$name             = basename( $file, '.svg' );
		$choices[ $name ] = sprintf(
			'<svg class="mai-icon-svg" width="32" height="32"><use href="%s.svg#%s"></use></svg><span class="mai-icon-name">%s</span>',
			$url,
			$name,
			$name
		);
	}

	return $choices;
}

add_action( 'acf/init', 'mai_register_icon_field_group' );
/**
 * Register icon block field group.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_register_icon_field_group() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	if ( ! class_exists( 'Mai_Icons_Plugin' ) ) {
		$link   = sprintf( '<a target="_blank" href="https://bizbudding.com/mai-theme/plugins/mai-icons/">%s</a>', __( 'Mai Icons', 'mai-engine' ) );
		$text   = sprintf( __( '%s plugin required.', 'mai-engine' ), $link );
		$fields = [
			[
				'key'       => 'mai_icon_missing',
				'label'     => '',
				'type'      => 'message',
				'message'   => $text,
				'new_lines' => 'wpautop',
			],
		];
	} else {
		$defaults      = mai_get_icon_default_args();
		$color_choices = mai_get_radio_color_choices();
		$fields        = [
			[
				'key'   => 'mai_icon_tab',
				'name'  => 'icon_tab',
				'label' => esc_html__( 'Icon', 'mai-engine' ),
				'type'  => 'tab',
			],
			[
				'key'           => 'mai_icon_style',
				'name'          => 'style',
				'label'         => esc_html__( 'Style', 'mai-engine' ),
				'type'          => 'button_group',
				'default_value' => $defaults['style'],
				'choices'       => [
					'light'   => esc_html__( 'Light', 'mai-engine' ),
					'regular' => esc_html__( 'Regular', 'mai-engine' ),
					'solid'   => esc_html__( 'Solid', 'mai-engine' ),
					'brands'  => esc_html__( 'Brands', 'mai-engine' ),
				],
			],
			[
				'key'               => 'mai_icon_choices',
				'name'              => 'icon',
				'label'             => esc_html__( 'Icon', 'mai-engine' ) . sprintf( ' (%s <a href="https://fontawesome.com/v5/search/">Font Awesome</a>)', __( 'full search via', 'mai-engine' ) ),
				'type'              => 'select',
				'default_value'     => $defaults['icon'],
				'multiple'          => 0,
				'ui'                => 1,
				'ajax'              => 1,
				'conditional_logic' => [
					[
						'field'    => 'mai_icon_style',
						'operator' => '!=',
						'value'    => 'brands',
					],
				],
				'wrapper'           => [
					'class' => 'mai-icon-select',
				],
			],
			[
				'key'               => 'mai_icon_brand_choices',
				'name'              => 'icon_brand',
				'label'             => esc_html__( 'Icon (Brands)', 'mai-engine' ),
				'type'              => 'select',
				'default_value'     => $defaults['icon_brand'],
				'multiple'          => 0,
				'ui'                => 1,
				'ajax'              => 1,
				'conditional_logic' => [
					[
						'field'    => 'mai_icon_style',
						'operator' => '==',
						'value'    => 'brands',
					],
				],
				'wrapper'           => [
					'class' => 'mai-icon-select',
				],
			],
			[
				'key'           => 'mai_icon_display',
				'name'          => 'display',
				'label'         => esc_html__( 'Display', 'mai-engine' ),
				'type'          => 'button_group',
				'default_value' => $defaults['display'],
				'choices'       => [
					'block'        => esc_html__( 'Block', 'mai-engine' ),
					'inline-block' => esc_html__( 'Inline', 'mai-engine' ),
				],
				'allow_null'    => 0,
				'layout'        => 'horizontal',
				'return_format' => 'value',
			],
			[
				'key'               => 'mai_icon_align',
				'name'              => 'align',
				'label'             => esc_html__( 'Align', 'mai-engine' ),
				'type'              => 'button_group',
				'choices'           => [
					'left'   => esc_html__( 'Left', 'mai-engine' ),
					'center' => esc_html__( 'Center', 'mai-engine' ),
					'right'  => esc_html__( 'Right', 'mai-engine' ),
				],
				'allow_null'        => 0,
				'default_value'     => $defaults['align'],
				'layout'            => 'horizontal',
				'return_format'     => 'value',
				'conditional_logic' => [
					[
						'field'    => 'mai_icon_display',
						'operator' => '==',
						'value'    => 'block',
					],
				],
			],
			[
				'key'           => 'mai_icon_size',
				'name'          => 'size',
				'label'         => esc_html__( 'Size', 'mai-engine' ),
				'instructions'  => esc_html__( 'Accepts all unit values (px, rem, em, vw, etc). Leave empty for theme default.', 'mai-engine' ),
				'type'          => 'text',
				'default_value' => '',
			],
			[
				'key'           => 'mai_icon_link',
				'name'          => 'link',
				'label'         => esc_html__( 'Link', 'mai-engine' ),
				'type'          => 'url',
				'default_value' => '',
			],
			[
				'key'               => 'mai_icon_link_title',
				'name'              => 'link_title',
				'label'             => esc_html__( 'Link Title', 'mai-engine' ),
				'instructions'      => esc_html__( 'Add a title for accessibility. Will not be displayed visually.', 'mai-engine' ),
				'type'              => 'text',
				'conditional_logic' => [
					[
						'field'    => 'mai_icon_link',
						'operator' => '!=empty',
					],
				],
			],
			[
				'key'               => 'mai_icon_link_target',
				'name'              => 'link_target',
				'label'             => '',
				'message'           => esc_html__( 'Open link in new window', 'mai-engine' ),
				'type'              => 'true_false',
				'conditional_logic' => [
					[
						'field'    => 'mai_icon_link',
						'operator' => '!=empty',
					],
				],
			],
			[
				'key'   => 'mai_icon_style_tab',
				'name'  => 'style_tab',
				'label' => esc_html__( 'Styles', 'mai-engine' ),
				'type'  => 'tab',
			],
			[
				'key'     => 'mai_icon_color',
				'label'   => esc_html__( 'Icon Color', 'mai-engine' ),
				'name'    => 'color_icon',
				'type'    => 'radio',
				'choices' => $color_choices,
				'wrapper' => [
					'class' => 'mai-block-colors',
				],
			],
			[
				'key'               => 'mai_icon_color_custom',
				'name'              => 'color_icon_custom',
				'type'              => 'color_picker',
				'conditional_logic' => [
					[
						'field'    => 'mai_icon_color',
						'operator' => '==',
						'value'    => 'custom',
					],
				],
			],
			[
				'key'     => 'mai_icon_background',
				'label'   => esc_html__( 'Background Color', 'mai-engine' ),
				'name'    => 'color_background',
				'type'    => 'radio',
				'choices' => $color_choices,
				'wrapper' => [
					'class' => 'mai-block-colors',
				],
			],
			[
				'key'               => 'mai_icon_background_custom',
				'name'              => 'color_background_custom',
				'type'              => 'color_picker',
				'conditional_logic' => [
					[
						'field'    => 'mai_icon_background',
						'operator' => '==',
						'value'    => 'custom',
					],
				],
			],
			[
				'key'   => 'mai_icon_border_color',
				'label' => esc_html__( 'Border Color', 'mai-engine' ),
				'name'  => 'color_border',
				'type'  => 'color_picker',
			],
			[
				'key'               => 'mai_icon_border_width',
				'label'             => esc_html__( 'Border Width', 'mai-engine' ),
				'name'              => 'border_width',
				'type'              => 'number',
				'default_value'     => 0,
				'append'            => 'px',
				'conditional_logic' => [
					[
						'field'    => 'mai_icon_border_color',
						'operator' => '!=empty',
					],
				],
			],

			/*
			 * Box Shadow.
			 */

			[
				'key'   => 'mai_icon_shadow',
				'label' => esc_html__( 'Box Shadow', 'mai-engine' ),
				'name'  => 'color_shadow',
				'type'  => 'color_picker',
			],
			[
				'key'               => 'mai_icon_x_offset',
				'label'             => esc_html__( 'X Offset', 'mai-engine' ),
				'name'              => 'x_offset',
				'type'              => 'number',
				'default_value'     => 0,
				'conditional_logic' => [
					[
						'field'    => 'mai_icon_shadow',
						'operator' => '!=empty',
					],
				],
			],
			[
				'key'               => 'mai_icon_y_offset',
				'label'             => esc_html__( 'Y Offset', 'mai-engine' ),
				'name'              => 'y_offset',
				'type'              => 'number',
				'default_value'     => 0,
				'conditional_logic' => [
					[
						'field'    => 'mai_icon_shadow',
						'operator' => '!=empty',
					],
				],
			],
			[
				'key'               => 'mai_icon_blur',
				'label'             => esc_html__( 'Blur', 'mai-engine' ),
				'name'              => 'blur',
				'type'              => 'number',
				'default_value'     => 0,
				'conditional_logic' => [
					[
						'field'    => 'mai_icon_shadow',
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
						'field'    => 'color_text_shadow',
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
						'field'    => 'color_text_shadow',
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
						'field'    => 'color_text_shadow',
						'operator' => '!=empty',
					],
				],
			],

			/*
			 * Spacing.
			 */

			[
				'key'           => 'mai_icon_padding',
				'label'         => esc_html__( 'Padding', 'mai-engine' ),
				'name'          => 'padding',
				'type'          => 'number',
				'default_value' => 0,
				'append'        => 'px',
			],
			[
				'key'   => 'mai_icon_margin',
				'label' => esc_html__( 'Margin', 'mai-engine' ),
				'name'  => 'margin_message',
				'type'  => 'message',
			],
			[
				'key'           => 'mai_icon_margin_top',
				'label'         => esc_html__( 'Top', 'mai-engine' ),
				'name'          => 'margin_top',
				'type'          => 'number',
				'default_value' => 0,
				'append'        => 'px',
			],
			[
				'key'           => 'mai_icon_margin_bottom',
				'label'         => esc_html__( 'Bottom', 'mai-engine' ),
				'name'          => 'margin_bottom',
				'type'          => 'number',
				'default_value' => 0,
				'append'        => 'px',
			],
			[
				'key'           => 'mai_icon_margin_left',
				'label'         => esc_html__( 'Left', 'mai-engine' ),
				'name'          => 'margin_left',
				'type'          => 'number',
				'default_value' => 0,
				'append'        => 'px',
			],
			[
				'key'           => 'mai_icon_margin_right',
				'label'         => esc_html__( 'Right', 'mai-engine' ),
				'name'          => 'margin_right',
				'type'          => 'number',
				'default_value' => 0,
				'append'        => 'px',
			],
			[
				'key'           => 'mai_icon_round_corners',
				'label'         => esc_html__( 'Border Radius', 'mai-engine' ),
				'instructions'  => esc_html__( 'Accepts any unit value (%, px, etc.) and shorthand (0 16 0 16). Use 0px for square. Leave empty for theme default.', 'mai-theme' ),
				'name'          => 'border_radius',
				'type'          => 'text',
				'default_value' => '',
				'placeholder'   => esc_html__( '50%', 'mai-engine' ),
			],
		];
	}

	acf_add_local_field_group(
		[
			'key'         => 'mai_icon_field_group',
			'title'       => esc_html__( 'Icon', 'mai-engine' ),
			'fields'      => $fields,
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
