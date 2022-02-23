<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2022 BizBudding
 * @license   GPL-2.0-or-later
 */

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

add_action( 'acf/init', 'mai_register_gallery_block' );
/**
 * Register Mai Icon block.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_register_gallery_block() {
	if ( ! function_exists( 'acf_register_block_type' ) ) {
		return;
	}

	acf_register_block_type(
		[
			'name'            => 'mai-gallery',
			'title'           => __( 'Mai Gallery', 'mai-engine' ),
			'description'     => __( 'A custom gallery block.', 'mai-engine' ),
			'render_callback' => 'mai_do_gallery_block',
			'category'        => 'widgets',
			'keywords'        => [ 'gallery' ],
			'icon'            => 'format-gallery',
			'mode'            => 'preview',
			'supports'        => [
				'align' => false,
			],
		]
	);
}

/**
 * Callback function to render the Gallery block.
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
function mai_do_gallery_block( $block, $content = '', $is_preview = false, $post_id = 0 ) {
	$args = [
		'preview'                => $is_preview,
		'class'                  => isset( $block['className'] ) && ! empty( $block['className'] ) ? mai_add_classes( $block['className'] ) : '',
		'images'                 => get_field( 'images' ),
		'image_orientation'      => get_field( 'image_orientation' ),
		'image_size'             => get_field( 'image_size' ),
		'shadow'                 => get_field( 'shadow' ),
		'columns'                => get_field( 'columns' ),
		'columns_responsive'     => get_field( 'columns_responsive' ),
		'columns_md'             => get_field( 'columns_md' ),
		'columns_sm'             => get_field( 'columns_sm' ),
		'columns_xs'             => get_field( 'columns_xs' ),
		'align_columns'          => get_field( 'align_columns' ),
		'align_columns_vertical' => get_field( 'align_columns_vertical' ),
		'column_gap'             => get_field( 'column_gap' ),
		'row_gap'                => get_field( 'row_gap' ),
		'margin_top'             => get_field( 'margin_top' ),
		'margin_bottom'          => get_field( 'margin_bottom' ),
	];

	echo mai_get_gallery( $args );
}

/**
 * Gets the gallery markup.
 *
 * @since TBD
 *
 * @param array $args The gallery args.
 *
 * @return string
 */
function mai_get_gallery( $args ) {
	$gallery = new Mai_Gallery( $args );
	return $gallery->get();
}

add_action( 'acf/init', 'mai_register_gallery_group' );
/**
 * Register Mai Divider block field group.
 *
 * @since 0.2.0
 *
 * @return void
 */
function mai_register_gallery_group() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		[
			'key'         => 'mai_gallery_field_group',
			'title'       => esc_html__( 'Mai Divider', 'mai-engine' ),
			'fields'      => [
				[
					'key'       => 'mai_gallery_images_tab',
					'label'     => __( 'Images', 'mai-engine' ),
					'type'      => 'tab',
					'placement' => 'top',
				],
				[
					'key'        => 'mai_gallery_image_orientation',
					'name'       => 'image_orientation',
					'label'      => esc_html__( 'Image Orientation', 'mai-engine' ),
					'type'       => 'select',
					'default'    => 'landscape',
					'choices'    => mai_get_image_orientation_choices(),
				],
				[
					'key'        => 'mai_gallery_image_size',
					'name'       => 'image_size',
					'label'      => esc_html__( 'Image Size', 'mai-engine' ),
					'type'       => 'select',
					'sanitize'   => 'esc_html',
					'default'    => 'landscape-md',
					'choices'    => mai_get_image_size_choices(),
					'conditions' => [
						[
							'field'    => 'mai_gallery_image_orientation',
							'operator' => '==',
							'value'    => 'custom',
						],
					],
				],
				[
					'key'           => 'mai_gallery_images',
					'label'         => __( 'Images', 'mai-engine'),
					'name'          => 'images',
					'type'          => 'gallery',
					'return_format' => 'id',
					'preview_size'  => 'medium',
					'insert'        => 'append',
					'library'       => 'all',
					'min'           => 1,
				],
				[
					'key'     => 'mai_gallery_shadow',
					'name'    => 'shadow',
					'label'   => '',
					'message' => esc_html__( 'Add image shadow', 'mai-engine' ),
					'type'    => 'true_false',
				],
				[
					'key'   => 'mai_gallery_layout_tab',__(  'mai-engine' ),
					'label' => __( 'Layout', 'mai-engine' ),
					'type'  => 'tab',
				],
				[
					'key'           => 'mai_gallery_columns',
					'label'         => 'Columns',
					'name'          => 'columns',
					'type'          => 'button_group',
					'default_value' => 3,
					'choices'       => [
						1 => 1,
						2 => 2,
						3 => 3,
						4 => 4,
						5 => 5,
						6 => 6,
						7 => 7,
						8 => 8,
					],
					'wrapper'       => [
						'class' => 'mai-acf-button-group',
					]
				],
				[
					'key'     => 'mai_gallery_columns_responsive',
					'name'    => 'columns_responsive',
					'type'    => 'true_false',
					'message' => __( 'Custom responsive columns', 'mai-engine' ),
				],
				[
					'key'               => 'mai_gallery_columns_md',
					'label'             => __( 'Columns (lg tablets)', 'mai-engine' ),
					'name'              => 'columns_md',
					'type'              => 'button_group',
					'choices'           => [
						1 => 1,
						2 => 2,
						3 => 3,
						4 => 4,
						5 => 5,
						6 => 6,
						7 => 7,
						8 => 8,
					],
					'wrapper'           => [
						'class' => 'mai-acf-button-group mai-grid-nested-columns mai-grid-nested-columns-first',
					],
					'conditional_logic' => [
						[
							[
								'field'    => 'mai_gallery_columns_responsive',
								'operator' => '==',
								'value'    => 1,
							],
						],
					],
				],
				[
					'key'               => 'mai_gallery_columns_sm',
					'label'             => __( 'Columns (md tablets)', 'mai-engine' ),
					'name'              => 'columns_sm',
					'type'              => 'button_group',
					'choices'           => [
						1 => 1,
						2 => 2,
						3 => 3,
						4 => 4,
						5 => 5,
						6 => 6,
						7 => 7,
						8 => 8,
					],
					'wrapper'           => [
						'class' => 'mai-acf-button-group mai-grid-nested-columns',
					],
					'conditional_logic' => [
						[
							[
								'field'    => 'mai_gallery_columns_responsive',
								'operator' => '==',
								'value'    => 1,
							],
						],
					],
				],
				[
					'key'               => 'mai_gallery_columns_xs',
					'label'             => __( 'Columns (mobile)', 'mai-engine' ),
					'name'              => 'columns_xs',
					'type'              => 'button_group',
					'choices'           => [
						1 => 1,
						2 => 2,
						3 => 3,
						4 => 4,
						5 => 5,
						6 => 6,
						7 => 7,
						8 => 8,
					],
					'wrapper'           => [
						'class' => 'mai-acf-button-group mai-grid-nested-columns mai-grid-nested-columns-last',
					],
					'conditional_logic' => [
						[
							[
								'field'    => 'mai_gallery_columns_responsive',
								'operator' => '==',
								'value'    => 1,
							],
						],
					],
				],
				[
					'key'               => 'mai_gallery_align_columns',
					'label'             => __( 'Align Columns', 'mai-engine' ),
					'name'              => 'align_columns',
					'type'              => 'button_group',
					'default_value'     => 'center',
					'choices'           => [
						'start'  => 'Start',
						'center' => 'Center',
						'end'    => 'End',
					],
					'wrapper'           => [
						'class' => 'mai-acf-button-group',
					],
					'conditional_logic' => [
						[
							[
								'field'    => 'mai_gallery_columns',
								'operator' => '!=',
								'value'    => '1',
							],
						],
					],
				],
				[
					'key'               => 'mai_gallery_align_columns_vertical',
					'label'             => __( 'Align Columns (vertical)', 'mai-engine' ),
					'name'              => 'align_columns_vertical',
					'type'              => 'button_group',
					'choices'           => [
						'full'   => __( 'Full', 'mai-engine' ),
						'top'    => __( 'Top', 'mai-engine' ),
						'middle' => __( 'Middle', 'mai-engine' ),
						'bottom' => __( 'Bottom', 'mai-engine' ),
					],
					'wrapper'           => [
						'class' => 'mai-acf-button-group',
					],
					'conditional_logic' => [
						[
							[
								'field'    => 'mai_gallery_columns',
								'operator' => '!=',
								'value'    => '1',
							],
						],
					],
				],
				[
					'key'           => 'mai_gallery_column_gap',
					'label'         => __( 'Column Gap', 'mai-engine' ),
					'name'          => 'column_gap',
					'type'          => 'button_group',
					'default_value' => 'md',
					'choices'       => [
						''      => __( 'None', 'mai-engine' ),
						'md'    => __( 'XS', 'mai-engine' ),
						'lg'    => __( 'S', 'mai-engine' ),
						'xl'    => __( 'M', 'mai-engine' ),
						'xxl'   => __( 'L', 'mai-engine' ),
						'xxxl'  => __( 'XL', 'mai-engine' ),
						'xxxxl' => __( 'XXL', 'mai-engine' ),
					],
					'wrapper'       => [
						'class' => 'mai-acf-button-group mai-acf-button-group-small',
					],
				],
				[
					'key'           => 'mai_gallery_row_gap',
					'label'         => __( 'Row Gap', 'mai-engine' ),
					'name'          => 'row_gap',
					'type'          => 'button_group',
					'default_value' => 'md',
					'choices'       => [
						''      => __( 'None', 'mai-engine' ),
						'md'    => __( 'XS', 'mai-engine' ),
						'lg'    => __( 'S', 'mai-engine' ),
						'xl'    => __( 'M', 'mai-engine' ),
						'xxl'   => __( 'L', 'mai-engine' ),
						'xxxl'  => __( 'XL', 'mai-engine' ),
						'xxxxl' => __( 'XXL', 'mai-engine' ),
					],
					'wrapper'       => [
						'class' => 'mai-acf-button-group mai-acf-button-group-small',
					],
				],
				[
					'key'           => 'mai_gallery_margin_top',
					'label'         => __( 'Top Margin', 'mai-engine' ),
					'name'          => 'margin_top',
					'type'          => 'button_group',
					'default_value' => '',
					'choices'       => [
						''      => __( 'None', 'mai-engine' ),
						'md'    => __( 'XS', 'mai-engine' ),
						'lg'    => __( 'S', 'mai-engine' ),
						'xl'    => __( 'M', 'mai-engine' ),
						'xxl'   => __( 'L', 'mai-engine' ),
						'xxxl'  => __( 'XL', 'mai-engine' ),
						'xxxxl' => __( 'XXL', 'mai-engine' ),
					],
					'wrapper' => [
						'class' => 'mai-acf-button-group mai-acf-button-group-small',
					],
				],
				[
					'key'           => 'mai_gallery_margin_bottom',
					'label'         => __( 'Bottom Margin', 'mai-engine' ),
					'name'          => 'margin_bottom',
					'type'          => 'button_group',
					'default_value' => '',
					'choices'       => [
						''      => __( 'None', 'mai-engine' ),
						'md'    => __( 'XS', 'mai-engine' ),
						'lg'    => __( 'S', 'mai-engine' ),
						'xl'    => __( 'M', 'mai-engine' ),
						'xxl'   => __( 'L', 'mai-engine' ),
						'xxxl'  => __( 'XL', 'mai-engine' ),
						'xxxxl' => __( 'XXL', 'mai-engine' ),
					],
					'wrapper' => [
						'class' => 'mai-acf-button-group mai-acf-button-group-small',
					],
				],
			],
			'location'    => [
				[
					[
						'param'    => 'block',
						'operator' => '==',
						'value'    => 'acf/mai-gallery',
					],
				],
			],
			'description' => '',
		]
	);
}
