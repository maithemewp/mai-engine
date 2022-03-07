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

/**
 * Gets field defaults.
 * TODO: Move these to config.php?
 *
 * @access private
 *
 * @since TBD
 *
 * @return array
 */
function mai_get_grid_display_defaults() {
	static $defaults = null;

	if ( ! is_null( $defaults ) ) {
		return $defaults;
	}

	$defaults = [
		'show'                => [ 'image', 'title' ],
		'title_size'          => 'lg',
		'image_orientation'   => 'landscape',
		'image_size'          => 'landscape-md',
		'image_position'      => 'full',
		'image_alternate'     => '',
		'image_width'         => 'third',
		'header_meta'         => '[post_date] [post_author_posts_link before="by "]', // TODO: this should be different, or empty depending on the post type?
		'custom_content'      => '',
		'content_limit'       => 0,
		'more_link_text'      => '',
		'footer_meta'         => '[post_categories]', // TODO:                                 this should be different, or empty depending on the post type?
		'align_text'          => 'start',
		'align_text_vertical' => '',
		'image_stack'         => 1,
		'boxed'               => 1,
		'border_radius'       => '',
		'disable_entry_link'  => 0,
	];

	return $defaults;
}

/**
 * Gets sanitized field values.
 *
 * @access private
 *
 * @since TBD
 *
 * @return array
 */
function mai_get_grid_display_sanitized( $args ) {
	$array = [
		'show'                => 'esc_html',
		'title_size'          => 'esc_html',
		'image_orientation'   => 'esc_html',
		'image_size'          => 'esc_html',
		'image_position'      => 'esc_html',
		'image_alternate'     => 'mai_sanitize_bool',
		'image_width'         => 'esc_html',
		'header_meta'         => 'wp_kses_post',
		'custom_content'      => 'wp_kses_post',
		'content_limit'       => 'absint',
		'more_link_text'      => 'wp_kses_post', // We may want to add icons/spans and HTML in here.
		'footer_meta'         => 'wp_kses_post',
		'align_text'          => 'esc_html',
		'align_text_vertical' => 'esc_html',
		'image_stack'         => 'mai_sanitize_bool',
		'boxed'               => 'mai_sanitize_bool',
		'border_radius'       => 'esc_html',
		'disable_entry_link'  => 'mai_sanitize_bool',
	];

	foreach ( $array as $key => $function ) {
		if ( ! isset( $args[ $key ] ) ) {
			continue;
		}

		$args[ $key ] = mai_sanitize( $args[ $key ], $function );
	}

	return $args;
}

/**
 * Gets fields for acf field group.
 *
 * @access private
 *
 * @since TBD
 *
 * @return array
 */
function mai_get_grid_display_fields() {
	static $fields = null;

	if ( ! is_null( $fields ) ) {
		return $fields;
	}

	$defaults  = mai_get_grid_display_defaults();
	$date_info = mai_get_block_setting_info_link( 'https://help.bizbudding.com/article/176-mai-grid-blocks' );
	$fields    = [
		[
			'key'           => 'mai_grid_block_show',
			'name'          => 'show',
			'label'         => esc_html__( 'Show', 'mai-engine' ),
			'desc'          => esc_html__( 'Show/hide and re-order elements.', 'mai-engine' ),
			'type'          => 'checkbox',
			'default_value' => $defaults['show'],
			'choices'       => [
				'image'          => esc_html__( 'Image', 'mai-engine' ),
				'title'          => esc_html__( 'Title', 'mai-engine' ),
				'header_meta'    => esc_html__( 'Header Meta', 'mai-engine' ),
				'excerpt'        => esc_html__( 'Excerpt', 'mai-engine' ),
				'content'        => esc_html__( 'Content', 'mai-engine' ),
				'custom_content' => esc_html__( 'Custom Content', 'mai-engine' ),
				'more_link'      => esc_html__( 'Read More link', 'mai-engine' ),
				'footer_meta'    => esc_html__( 'Footer Meta', 'mai-engine' ),
			],
			'wrapper'       => [
				'class' => 'mai-sortable',
			],
		],
		[
			'key'               => 'mai_grid_block_title_size',
			'name'              => 'title_size',
			'label'             => esc_html__( 'Title Size', 'mai-engine' ),
			'type'              => 'button_group',
			'default_value'     => $defaults['title_size'],
			'choices'           => [
				'sm'  => esc_html__( 'XS', 'mai-engine' ),
				'md'  => esc_html__( 'S', 'mai-engine' ),
				'lg'  => esc_html__( 'M', 'mai-engine' ),
				'xl'  => esc_html__( 'L', 'mai-engine' ),
				'xxl' => esc_html__( 'XL', 'mai-engine' ),
			],
			'wrapper'           => [
				'class' => 'mai-acf-button-group',
			],
			'conditional_logic' => [
				[
					'field'    => 'mai_grid_block_show',
					'operator' => '==',
					'value'    => 'title',
				],
			],
		],
		[
			'key'               => 'mai_grid_block_image_orientation',
			'name'              => 'image_orientation',
			'label'             => esc_html__( 'Image Orientation', 'mai-engine' ),
			'type'              => 'select',
			'default_value'     => $defaults['image_orientation'],
			'choices'           => mai_get_image_orientation_choices(),
			'conditional_logic' => [
				[
					'field'    => 'mai_grid_block_show',
					'operator' => '==',
					'value'    => 'image',
				],
			],
		],
		[
			'key'               => 'mai_grid_block_image_size',
			'name'              => 'image_size',
			'label'             => esc_html__( 'Image Size', 'mai-engine' ),
			'type'              => 'select',
			'default_value'     => $defaults['image_size'],
			'choices'           => mai_get_image_size_choices(),
			'conditional_logic' => [
				[
					'field'    => 'mai_grid_block_show',
					'operator' => '==',
					'value'    => 'image',
				],
				[
					'field'    => 'mai_grid_block_image_orientation',
					'operator' => '==',
					'value'    => 'custom',
				],
			],
		],
		[
			'key'               => 'mai_grid_block_image_position',
			'name'              => 'image_position',
			'label'             => esc_html__( 'Image Position', 'mai-engine' ),
			'type'              => 'select',
			'default_value'     => $defaults['image_position'],
			'choices'           => [
				'full'         => esc_html__( 'Full', 'mai-engine' ),
				'center'       => esc_html__( 'Center', 'mai-engine' ),
				'left-top'     => esc_html__( 'Left Top', 'mai-engine' ),
				'left-middle'  => esc_html__( 'Left Middle', 'mai-engine' ),
				'left-full'    => esc_html__( 'Left Full', 'mai-engine' ),
				'right-top'    => esc_html__( 'Right Top', 'mai-engine' ),
				'right-middle' => esc_html__( 'Right Middle', 'mai-engine' ),
				'right-full'   => esc_html__( 'Right Full', 'mai-engine' ),
				'background'   => esc_html__( 'Background', 'mai-engine' ),
			],
			'conditional_logic' => [
				[
					'field'    => 'mai_grid_block_show',
					'operator' => '==',
					'value'    => 'image',
				],
			],
		],
		[
			'key'               => 'mai_grid_block_image_alternate',
			'name'              => 'image_alternate',
			'label'             => '',
			'type'              => 'true_false',
			'default_value'     => $defaults['image_alternate'],
			'message'           => esc_html__( 'Display images alternating', 'mai-engine' ),
			'conditional_logic' => [
				[
					[
						'field'    => 'mai_grid_block_show',
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'mai_grid_block_image_position',
						'operator' => '==',
						'value'    => 'left-top',
					],
				],
				[
					[
						'field'    => 'mai_grid_block_show',
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'mai_grid_block_image_position',
						'operator' => '==',
						'value'    => 'left-middle',
					],
				],
				[
					[
						'field'    => 'mai_grid_block_show',
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'mai_grid_block_image_position',
						'operator' => '==',
						'value'    => 'left-full',
					],
				],
				[
					[
						'field'    => 'mai_grid_block_show',
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'mai_grid_block_image_position',
						'operator' => '==',
						'value'    => 'right-top',
					],
				],
				[
					[
						'field'    => 'mai_grid_block_show',
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'mai_grid_block_image_position',
						'operator' => '==',
						'value'    => 'right-middle',
					],
				],
				[
					[
						'field'    => 'mai_grid_block_show',
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'mai_grid_block_image_position',
						'operator' => '==',
						'value'    => 'right-full',
					],
				],
			],
		],
		[
			'key'               => 'mai_grid_block_image_width',
			'name'              => 'image_width',
			'label'             => esc_html__( 'Image Width', 'mai-engine' ),
			'type'              => 'button_group',
			'default_value'     => $defaults['image_width'],
			'choices'           => [
				'fourth' => esc_html__( '¼', 'mai-engine' ),
				// 'third'  => esc_html__( '⅓', 'mai-engine' ),
				'third'  => esc_html__( '1/3', 'mai-engine' ),
				// 'half'   => esc_html__( '½', 'mai-engine' ),
				'half'   => esc_html__( 'Half', 'mai-engine' ),
			],
			'wrapper'           => [
				'class' => 'mai-acf-button-group',
			],
			'conditional_logic' => [
				[
					[
						'field'    => 'mai_grid_block_show',
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'mai_grid_block_image_orientation',
						'operator' => '!=',
						'value'    => 'custom',
					],
					[
						'field'    => 'mai_grid_block_image_position',
						'operator' => '==',
						'value'    => 'left-top',
					],
				],
				[
					[
						'field'    => 'mai_grid_block_show',
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'mai_grid_block_image_orientation',
						'operator' => '!=',
						'value'    => 'custom',
					],
					[
						'field'    => 'mai_grid_block_image_position',
						'operator' => '==',
						'value'    => 'left-middle',
					],
				],
				[
					[
						'field'    => 'mai_grid_block_show',
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'mai_grid_block_image_orientation',
						'operator' => '!=',
						'value'    => 'custom',
					],
					[
						'field'    => 'mai_grid_block_image_position',
						'operator' => '==',
						'value'    => 'left-full',
					],
				],
				[
					[
						'field'    => 'mai_grid_block_show',
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'mai_grid_block_image_orientation',
						'operator' => '!=',
						'value'    => 'custom',
					],
					[
						'field'    => 'mai_grid_block_image_position',
						'operator' => '==',
						'value'    => 'right-top',
					],
				],
				[
					[
						'field'    => 'mai_grid_block_show',
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'mai_grid_block_image_orientation',
						'operator' => '!=',
						'value'    => 'custom',
					],
					[
						'field'    => 'mai_grid_block_image_position',
						'operator' => '==',
						'value'    => 'right-middle',
					],
				],
				[
					[
						'field'    => 'mai_grid_block_show',
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'mai_grid_block_image_orientation',
						'operator' => '!=',
						'value'    => 'custom',
					],
					[
						'field'    => 'mai_grid_block_image_position',
						'operator' => '==',
						'value'    => 'right-full',
					],
				],
			],
		],
		[
			'key'               => 'mai_grid_block_header_meta',
			'name'              => 'header_meta',
			'label'             => esc_html__( 'Header Meta', 'mai-engine' ),
			'type'              => 'textarea',
			'default_value'     => $defaults['header_meta'],
			'rows'              => 3,
			'conditional_logic' => [
				[
					'field'    => 'mai_grid_block_show',
					'operator' => '==',
					'value'    => 'header_meta',
				],
			],
		],
		[
			'key'               => 'mai_grid_block_custom_content',
			'name'              => 'custom_content',
			'label'             => esc_html__( 'Custom Content', 'mai-engine' ),
			'type'              => 'textarea',
			'default_value'     => $defaults['custom_content'],
			'rows'              => 3,
			'conditional_logic' => [
				[
					'field'    => 'mai_grid_block_show',
					'operator' => '==',
					'value'    => 'custom_content',
				],
			],
		],
		[
			'key'               => 'mai_grid_block_content_limit',
			'name'              => 'content_limit',
			'label'             => esc_html__( 'Content Limit', 'mai-engine' ),
			'desc'              => esc_html__( 'Limit the number of characters shown for the content or excerpt. Use 0 for no limit.', 'mai-engine' ),
			'type'              => 'text',
			'default_value'     => $defaults['content_limit'],
			'conditional_logic' => [
				[
					[
						'field'    => 'mai_grid_block_show',
						'operator' => '==',
						'value'    => 'excerpt',
					],
				],
				[
					[
						'field'    => 'mai_grid_block_show',
						'operator' => '==',
						'value'    => 'content',
					],
				],
			],
		],
		[
			'key'               => 'mai_grid_block_more_link_text',
			'name'              => 'more_link_text',
			'label'             => esc_html__( 'More Link Text', 'mai-engine' ),
			'type'              => 'text',
			'default_value'     => $defaults['more_link_text'],
			'placeholder'       => mai_get_read_more_text(),
			'conditional_logic' => [
				[
					'field'    => 'mai_grid_block_show',
					'operator' => '==',
					'value'    => 'more_link',
				],
			],
		],
		[
			'key'               => 'mai_grid_block_footer_meta',
			'name'              => 'footer_meta',
			'label'             => esc_html__( 'Footer Meta', 'mai-engine' ),
			'type'              => 'textarea',
			'default_value'     => $defaults['footer_meta'],
			'rows'              => 3,
			'conditional_logic' => [
				[
					'field'    => 'mai_grid_block_show',
					'operator' => '==',
					'value'    => 'footer_meta',
				],
			],
		],
		[
			'key'           => 'mai_grid_block_align_text',
			'name'          => 'align_text',
			'label'         => esc_html__( 'Align Text', 'mai-engine' ),
			'type'          => 'button_group',
			'default_value' => $defaults['align_text'],
			'choices'       => [
				'start'  => esc_html__( 'Start', 'mai-engine' ),
				'center' => esc_html__( 'Center', 'mai-engine' ),
				'end'    => esc_html__( 'End', 'mai-engine' ),
			],
			'wrapper'       => [
				'class' => 'mai-acf-button-group',
			],
		],
		[
			'key'               => 'mai_grid_block_align_text_vertical',
			'name'              => 'align_text_vertical',
			'label'             => esc_html__( 'Align Text (vertical)', 'mai-engine' ),
			'type'              => 'button_group',
			'default_value'     => $defaults['align_text_vertical'],
			'choices'           => [
				''       => esc_html__( 'Default', 'mai-engine' ),
				'top'    => esc_html__( 'Top', 'mai-engine' ),
				'middle' => esc_html__( 'Middle', 'mai-engine' ),
				'bottom' => esc_html__( 'Bottom', 'mai-engine' ),
			],
			'conditional_logic' => [
				[
					[
						'field'    => 'mai_grid_block_show',
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'mai_grid_block_image_position',
						'operator' => '==',
						'value'    => 'left-top',
					],
				],
				[
					[
						'field'    => 'mai_grid_block_show',
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'mai_grid_block_image_position',
						'operator' => '==',
						'value'    => 'left-middle',
					],
				],
				[
					[
						'field'    => 'mai_grid_block_show',
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'mai_grid_block_image_position',
						'operator' => '==',
						'value'    => 'left-full',
					],
				],
				[
					[
						'field'    => 'mai_grid_block_show',
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'mai_grid_block_image_position',
						'operator' => '==',
						'value'    => 'right-top',
					],
				],
				[
					[
						'field'    => 'mai_grid_block_show',
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'mai_grid_block_image_position',
						'operator' => '==',
						'value'    => 'right-middle',
					],
				],
				[
					[
						'field'    => 'mai_grid_block_show',
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'mai_grid_block_image_position',
						'operator' => '==',
						'value'    => 'right-full',
					],
				],
				[
					[
						'field'    => 'mai_grid_block_show',
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'mai_grid_block_image_position',
						'operator' => '==',
						'value'    => 'background',
					],
				],
			],
			'wrapper' => [
				'class' => 'mai-acf-button-group',
			],
		],
		[
			'key'               => 'mai_grid_block_image_stack',
			'name'              => 'image_stack',
			'label'             => esc_html__( 'Stack Image', 'mai-engine' ),
			'type'              => 'true_false',
			'default_value'     => $defaults['image_stack'],
			'message'           => esc_html__( 'Stack image and content on mobile', 'mai-engine' ),
			'conditional_logic' => [
				[
					[
						'field'    => 'mai_grid_block_show',
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'mai_grid_block_image_position',
						'operator' => '==',
						'value'    => 'left-top',
					],
				],
				[
					[
						'field'    => 'mai_grid_block_show',
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'mai_grid_block_image_position',
						'operator' => '==',
						'value'    => 'left-middle',
					],
				],
				[
					[
						'field'    => 'mai_grid_block_show',
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'mai_grid_block_image_position',
						'operator' => '==',
						'value'    => 'left-full',
					],
				],
				[
					[
						'field'    => 'mai_grid_block_show',
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'mai_grid_block_image_position',
						'operator' => '==',
						'value'    => 'right-top',
					],
				],
				[
					[
						'field'    => 'mai_grid_block_show',
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'mai_grid_block_image_position',
						'operator' => '==',
						'value'    => 'right-middle',
					],
				],
				[
					[
						'field'    => 'mai_grid_block_show',
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'mai_grid_block_image_position',
						'operator' => '==',
						'value'    => 'right-full',
					],
				],
			],
		],
		[
			'key'               => 'mai_grid_block_boxed',
			'name'              => 'boxed',
			'label'             => esc_html__( 'Boxed', 'mai-engine' ),
			'type'              => 'true_false',
			'default_value'     => $defaults['boxed'],
			'message'           => esc_html__( 'Display boxed styling', 'mai-engine' ),
			'conditional_logic' => [
				[
					[
						'field'    => 'mai_grid_block_show',
						'operator' => '!=',
						'value'    => 'image',
					],
				],
				[
					[
						'field'    => 'mai_grid_block_show',
						'operator' => '==',
						'value'    => 'image',
					],
					[
						'field'    => 'mai_grid_block_image_position',
						'operator' => '!=',
						'value'    => 'background',
					],
				],
			],
		],
		[
			'key'               => 'mai_grid_block_border_radius',
			'name'              => 'border_radius',
			'label'             => esc_html__( 'Border Radius', 'mai-engine' ),
			'desc'              => esc_html__( 'Leave empty for theme default. Accepts all unit values (px, rem, em, vw, etc).', 'mai-engine' ),
			'type'              => 'text',
			'default_value'     => $defaults['border_radius'],
			'input_attrs'       => [
				'placeholder' => isset( mai_get_global_styles( 'extra' )['border-radius'] ) ? mai_get_global_styles( 'extra' )['border-radius']: '4px',
			],
			'conditional_logic' => [
				[
					[
						'field'    => 'mai_grid_block_image_position',
						'operator' => '==',
						'value'    => 'background',
					],
				],
				[
					[
						'field'    => 'mai_grid_block_boxed',
						'operator' => '==',
						'value'    => 1,
					],
				],
			],
		],
		// This is added in Entries tab but it's cleaner to keep it here
		// since entries is separate files for post/term grid.
		[
			'key'           => 'mai_grid_block_disable_entry_link',
			'name'          => 'disable_entry_link',
			'label'         => esc_html__( 'Disable', 'mai-engine' ),
			'type'          => 'true_false',
			'default_value' => $defaults['disable_entry_link'],
			'message'       => esc_html__( 'Disable entry links', 'mai-engine' ),
		],
	];

	return $fields;
}
