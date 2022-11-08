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
 * @since 2.25.0 Converted to block.json via `register_block_type()`.
 *
 * @return void
 */
function mai_register_icon_block() {
	register_block_type( __DIR__ . '/block.json' );
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
function mai_do_icon_block( $block, $content, $is_preview, $post_id ) {
	$args     = [];
	$defaults = mai_get_icon_default_args();

	// Get values. Checks for null or empty string so defaults are used.
	foreach ( array_keys( $defaults ) as $setting ) {
		$value = get_field( $setting );

		if ( ! is_null( $value ) && '' !== $value ) {
			$args[ $setting ] = $value;
		}
	}

	// Swap for brand.
	if ( isset( $args['style'] ) && 'brands' === $args['style'] ) {
		$args['icon'] = $args['icon_brand'];
	}

	// Remove brand.
	unset( $args['icon_brand'] );

	// Add class.
	$args['class'] = isset( $block['className'] ) && ! empty( $block['className'] ) ? mai_add_classes( $block['className'] ) : '';

	echo mai_get_icon( $args );
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
		$link   = sprintf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=mai-theme' ), __( 'Mai Icons', 'mai-engine' ) );
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
				'key'               => 'mai_icon_clone',
				'label'             => __( 'Icon', 'mai-engine' ),
				'name'              => 'icon_clone',
				'type'              => 'clone',
				'display'           => 'group', // 'group' or 'seamless'. 'group' allows direct return of actual field names via get_field( 'style' ).
				'clone'             => [ 'mai_icon_style', 'mai_icon_choices', 'mai_icon_brand_choices' ],
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
				'key'               => 'mai_icon_color_clone',
				'label'             => __( 'Icon Color', 'mai-engine' ),
				'name'              => 'icon_color_clone',
				'type'              => 'clone',
				'display'           => 'group', // 'group' or 'seamless'. 'group' allows direct return of actual field names via get_field( 'style' ).
				'clone'             => [ 'mai_icon_color', 'mai_icon_color_custom', 'mai_icon_background', 'mai_icon_background_custom' ],
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
