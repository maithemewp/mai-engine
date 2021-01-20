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

add_action( 'acf/init', 'mai_register_columns_blocks' );
/**
 * Registers the columns blocks.
 *
 * @since TBD
 *
 * @return void
 */
function mai_register_columns_blocks() {
	if ( ! function_exists( 'acf_register_block_type' ) ) {
		return;
	}

	acf_register_block_type(
		[
			'name'            => 'mai-columns',
			'title'           => __( __( 'Mai Columns', 'mai-engine' ), 'mai-engine' ),
			'description'     => __( 'A custom columns block.', 'mai-engine' ),
			'render_callback' => 'mai_do_columns_block',
			'category'        => 'layout',
			'keywords'        => [ 'columns' ],
			'icon'            => mai_get_svg_icon( 'columns', 'light' ),
			'supports'        => [
				'align' => [ 'wide', 'full', 'left', 'center', 'right' ],
				'mode'  => false,
				'jsx'   => true,
			],
			'enqueue_assets'  => function() {
				if ( is_admin() ) {
					wp_enqueue_style( 'mai-columns-admin', mai_get_url() . '/assets/css/mai-columns-admin.min.css', [], '0.1.0' );
				} else {
					wp_enqueue_style( 'mai-columns', mai_get_url() . '/assets/css/mai-columns.min.css', [], '0.1.0' );
				}
			},
		]
	);

	acf_register_block_type(
		[
			'name'            => 'mai-column',
			'title'           => __( __( 'Mai Column', 'mai-engine' ), 'mai-engine' ),
			'description'     => __( 'A custom column block.', 'mai-engine' ),
			'render_callback' => 'mai_do_column_block',
			'category'        => 'layout',
			'keywords'        => [],
			'icon'            => mai_get_svg_icon( 'rectangle-portrait', 'regular' ),
			'parent'          => [ 'acf/mai-columns' ],
			'supports'        => [
				'align' => false,
				'mode'  => false,
				'jsx'   => true,
			],
		]
	);
}

/**
 * Callback function to render the block.
 *
 * @since TBD
 *
 * @param array  $block      The block settings and attributes.
 * @param string $content    The block inner HTML (empty).
 * @param bool   $is_preview True during AJAX preview.
 * @param int    $post_id    The post ID this block is saved to.
 *
 * @return void
 */
function mai_do_columns_block( $block, $content = '', $is_preview = false, $post_id = 0 ) {
	static $instance = 1;

	$args                                        = mai_columns_get_args( $instance );
	$args[ $instance ]['preview']                = $is_preview;
	$args[ $instance ]['class']                  = isset( $block['className'] ) ? $block['className']: '';
	$args[ $instance ]['column_gap']             = get_field( 'column_gap' );
	$args[ $instance ]['row_gap']                = get_field( 'row_gap' );
	$args[ $instance ]['align_columns']          = $block['align'];
	$args[ $instance ]['align_columns_vertical'] = get_field( 'align_columns_vertical' );

	$columns = new Mai_Columns( $instance, $args[ $instance ] );
	$columns->render();

	$instance++;
}

/**
 * Callback function to render the column block.
 *
 * @since TBD
 *
 * @param array  $block      The block settings and attributes.
 * @param string $content    The block inner HTML (empty).
 * @param bool   $is_preview True during AJAX preview.
 * @param int    $post_id    The post ID this block is saved to.
 *
 * @return void
 */
function mai_do_column_block( $block, $content = '', $is_preview = false, $post_id = 0 ) {
	$args = [
		'preview'    => $is_preview,
		'class'      => isset( $block['className'] ) ? $block['className']: '',
		'spacing'    => get_field( 'spacing' ),
		'background' => get_field( 'background' ),
	];

	$columns = new Mai_Column( $args );
	$columns->render();
}

add_filter( 'render_block', 'mai_render_mai_columns_block', 10, 2 );
/**
 * Adds inline custom properties for custom column arrangments.
 *
 * @since TBD
 *
 * @param string $block_content The existing block content.
 * @param object $block         The columns block object.
 *
 * @return string The modified block HTML.
 */
function mai_render_mai_columns_block( $block_content, $block ) {
	if ( ! $block_content ) {
		return $block_content;
	}

	// Bail if not a columns block.
	if ( 'acf/mai-columns' !== $block['blockName'] ) {
		return $block_content;
	}

	$args = mai_columns_get_args();

	if ( ! $args ) {
		return $block_content;
	}

	$dom      = mai_get_dom_document( $block_content );
	$first    = mai_get_dom_first_child( $dom );
	$instance = $first->getAttribute( 'data-instance' );

	if ( ! isset( $args[ $instance ] ) ) {
		return $block_content;
	}

	$args     = $args[ $instance ];
	$xpath    = new DOMXPath( $dom );
	$elements = $xpath->query( '//div[contains(@class, "mai-columns-wrap")]//div[contains(@class, "mai-column")]' );

	if ( ! ( $elements->length ) ) {
		return $block_content;
	}

	if ( 'custom' === $args['columns'] ) {

		foreach ( array_reverse( $args['arrangements'] ) as $break => $arrangement ) {
			$total_arrangements = count( $arrangement );
			$element_i          = 0;

			foreach ( $elements as $element ) {
				$style = $element->getAttribute( 'style' );

				// If only 1 size for this breakpoint, all the columns get the same max width.
				if ( 1 === $total_arrangements ) {
					$arrangement_col = reset( $arrangement );
				}
				// Repeat sizes for total number of elements.
				else {
					$arrangement_col = $arrangement[ $element_i ];
				}

				if ( $flex = mai_columns_get_flex( $arrangement_col ) ) {
					$style .= sprintf( '--flex-%s:%s;', $break, $flex );
				}

				if ( $max_width = mai_columns_get_max_width( $arrangement_col ) ) {
					$style .= sprintf( '--max-width-%s:%s;', $break, $max_width );
				}

				$element->setAttribute( 'style', $style );

				if ( $element_i === ( $total_arrangements - 1 ) ) {
					$element_i = 0;
				} else {
					$element_i++;
				}
			}
		}

	} else {

		foreach ( $elements as $element ) {
			$style = $element->getAttribute( 'style' );

			foreach ( $args['arrangements'] as $break => $column ) {
				if ( $flex = mai_columns_get_flex( $column ) ) {
					$style .= sprintf( '--flex-%s:%s;', $break, $flex );
				}

				if ( $max_width = mai_columns_get_max_width( $column ) ) {
					$style .= sprintf( '--max-width-%s:%s;', $break, $max_width );
				}
			}

			$element->setAttribute( 'style', $style );
		}
	}

	$block_content = $dom->saveHTML();

	return $block_content;
}

add_action( 'acf/init', 'mai_register_columns_field_groups' );
/**
 * Register Mai Columns block field group.
 *
 * @since TBD
 *
 * @return void
 */
function mai_register_columns_field_groups() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	$column_choices = [
		'1/12'  => __( '1/12', 'mai-engine' ),
		'1/8'   => __( '1/8', 'mai-engine' ),
		'1/6'   => __( '1/6', 'mai-engine' ),
		'1/5'   => __( '1/5', 'mai-engine' ),
		'1/4'   => __( '1/4', 'mai-engine' ),
		'1/3'   => __( '1/3', 'mai-engine' ),
		'1/2'   => __( '1/2', 'mai-engine' ),
		'2/3'   => __( '2/3', 'mai-engine' ),
		'2/5'   => __( '2/5', 'mai-engine' ),
		'3/4'   => __( '3/4', 'mai-engine' ),
		'3/5'   => __( '3/5', 'mai-engine' ),
		'3/8'   => __( '3/8', 'mai-engine' ),
		'4/5'   => __( '4/5', 'mai-engine' ),
		'5/6'   => __( '5/6', 'mai-engine' ),
		'5/8'   => __( '5/8', 'mai-engine' ),
		'7/8'   => __( '7/8', 'mai-engine' ),
		'11/12' => __( '11/12', 'mai-engine' ),
		'full'  => __( 'Full Width', 'mai-engine' ),
		'fill'  => __( 'Fill Space', 'mai-engine' ),
		'auto'  => __( 'Auto', 'mai-engine' ),
	];

	acf_add_local_field_group( [
		'key'                 => 'group_6001286f21c03',
		'title'               => __( 'Mai Columns', 'mai-engine' ),
		'fields'              => [
			[
				'key'               => 'field_6001287b052a3',
				'label'             => __( 'Columns', 'mai-engine' ),
				'name'              => 'columns',
				'type'              => 'select',
				'choices'           => [
					1                  => '1',
					2                  => '2',
					3                  => '3',
					4                  => '4',
					5                  => '5',
					6                  => '6',
					0                  => __( 'Auto', 'mai-engine' ),
					'custom'           => __( 'Custom', 'mai-engine' ),
				],
				'default_value'     => 2,
			],
			[
				'key'               => 'field_600128e3052a4',
				'label'             => __( 'Arrangement (desktop)', 'mai-engine' ) . '<br /><em><small>' . sprintf( 'Screens over %spx', mai_get_breakpoint( 'lg' ) ) . '</small></em>',
				'name'              => 'arrangement',
				'type'              => 'repeater',
				'conditional_logic' => [
					[
						[
							'field'          => 'field_6001287b052a3',
							'operator'       => '==',
							'value'          => 'custom',
						],
					],
				],
				'collapsed'         => 'field_60012902052a5',
				'min'               => 1,
				'max'               => 0,
				'layout'            => 'block',
				'button_label'      => __( 'Add Column', 'mai-engine' ),
				'sub_fields'        => [
					[
						'key'             => 'field_60012902052a5',
						'label'           => '',
						'name'            => 'columns',
						'type'            => 'select',
						'choices'         => $column_choices,
						'default_value'   => '1/3',
					],
				],
			],
			[
				'key'               => 'field_60012afc56b15',
				'label'             => __( 'Arrangement (lg tablets)', 'mai-engine' ) . '<br /><em><small>' . sprintf( 'Screens %spx to %spx', mai_get_breakpoint( 'sm' ), mai_get_breakpoint( 'md' ) ) . '</small></em>',
				'name'              => 'arrangement_md',
				'type'              => 'repeater',
				'conditional_logic' => [
					[
						[
							'field'          => 'field_6001287b052a3',
							'operator'       => '==',
							'value'          => 'custom',
						],
					],
				],
				'collapsed'         => 'field_60012902052a5',
				'min'               => 1,
				'max'               => 0,
				'layout'            => 'block',
				'button_label'      => __( 'Add Column', 'mai-engine' ),
				'sub_fields'        => [
					[
						'key'             => 'field_60012afc56b16',
						'label'           => '',
						'name'            => 'columns',
						'type'            => 'select',
						'choices'         => $column_choices,
						'default_value'   => '1/3',
					],
				],
			],
			[
				'key'               => 'field_60012b1f56b17',
				'label'             => __( 'Arrangement (sm tablets)', 'mai-engine' ) . '<br /><em><small>' . sprintf( 'Screens %spx to %spx', mai_get_breakpoint( 'xs' ), ( (int) mai_get_breakpoint( 'sm' ) - 1 ) ) . '</small></em>',
				'name'              => 'arrangement_sm',
				'type'              => 'repeater',
				'conditional_logic' => [
					[
						[
							'field'          => 'field_6001287b052a3',
							'operator'       => '==',
							'value'          => 'custom',
						],
					],
				],
				'collapsed'         => 'field_60012902052a5',
				'min'               => 1,
				'max'               => 0,
				'layout'            => 'block',
				'button_label'      => __( 'Add Column', 'mai-engine' ),
				'sub_fields'        => [
					[
						'key'             => 'field_60012b1f56b18',
						'label'           => '',
						'name'            => 'columns',
						'type'            => 'select',
						'choices'         => $column_choices,
						'default_value'   => '1/2',
					],
				],
			],
			[
				'key'               => 'field_60012b3456b19',
				'label'             => __( 'Arrangement (mobile)', 'mai-engine' ) . '<br /><em><small>' . sprintf( 'Screens up to %spx', ( (int) mai_get_breakpoint( 'xs' ) - 1 ) ) . '</small></em>',
				'name'              => 'arrangement_xs',
				'type'              => 'repeater',
				'conditional_logic' => [
					[
						[
							'field'          => 'field_6001287b052a3',
							'operator'       => '==',
							'value'          => 'custom',
						],
					],
				],
				'collapsed'         => 'field_60012902052a5',
				'min'               => 1,
				'max'               => 0,
				'layout'            => 'block',
				'button_label'      => __( 'Add Column', 'mai-engine' ),
				'sub_fields'        => [
					[
						'key'             => 'field_60012b3456b1a',
						'label'           => '',
						'name'            => 'columns',
						'type'            => 'select',
						'choices'         => $column_choices,
						'default_value'   => 'full',
					],
				],
			],
			[
				'key'               => 'field_6001eae94989e',
				'label'             => __( 'Align Columns', 'mai-engine' ),
				'name'              => 'align_columns',
				'type'              => 'button_group',
				'choices'           => [
					'start'            => __( 'Start', 'mai-engine' ),
					'center'           => __( 'Center', 'mai-engine' ),
					'end'              => __( 'End', 'mai-engine' ),
				],
				'default_value'     => 'start',
			],
			[
				'key'               => 'field_6001eb544989f',
				'label'             => __( 'Align Columns (vertical)', 'mai-engine' ),
				'name'              => 'align_columns_vertical',
				'type'              => 'button_group',
				'choices'           => [
					''                => __( 'Full', 'mai-engine' ),
					'top'              => __( 'Top', 'mai-engine' ),
					'middle'           => __( 'Middle', 'mai-engine' ),
					'bottom'           => __( 'Bottom', 'mai-engine' ),
				],
			],
			[
				'key'               => 'field_6001bf9a58d20',
				'label'             => __( 'Row Gap', 'mai-engine' ),
				'name'              => 'row_gap',
				'type'              => 'button_group',
				'choices'           => [
					''                 => __( 'None', 'mai-engine' ),
					'md'               => __( 'XS', 'mai-engine' ),
					'lg'               => __( 'SM', 'mai-engine' ),
					'xl'               => __( 'MD', 'mai-engine' ),
					'xxl'              => __( 'LG', 'mai-engine' ),
					'xxxl'             => __( 'XL', 'mai-engine' ),
				],
				'default_value'     => 'xl',
			],
			[
				'key'               => 'field_6001c00358d21',
				'label'             => __( 'Column Gap', 'mai-engine' ),
				'name'              => 'column_gap',
				'type'              => 'button_group',
				'choices'           => [
					''                 => __( 'None', 'mai-engine' ),
					'md'               => __( 'XS', 'mai-engine' ),
					'lg'               => __( 'SM', 'mai-engine' ),
					'xl'               => __( 'MD', 'mai-engine' ),
					'xxl'              => __( 'LG', 'mai-engine' ),
					'xxxl'             => __( 'XL', 'mai-engine' ),
				],
				'default_value'     => 'xl',
			],
		],
		'location'              => [
			[
				[
					'param'            => 'block',
					'operator'         => '==',
					'value'            => 'acf/mai-columns',
				],
			],
		],
	]);

	acf_add_local_field_group( [
		'key'         => 'group_5ec95a876824c',
		'title'       => __( 'Mai Column', 'mai-engine' ),
		'fields'      => [
			[
				'key'       => 'field_5efa0fd795476',
				'label'     => __( 'Spacing', 'mai-engine' ),
				'name'      => 'spacing',
				'type'      => 'button_group',
				'choices'   => [
					''         => __( 'None', 'mai-engine' ),
					'xs'       => __( 'XS', 'mai-engine' ),
					'sm'       => __( 'SM', 'mai-engine' ),
					'md'       => __( 'MD', 'mai-engine' ),
					'lg'       => __( 'LG', 'mai-engine' ),
					'xl'       => __( 'XL', 'mai-engine' ),
				],
			],
			[
				'key'       => 'field_6fga14027fbbc',
				'label'     => __( 'Background Color', 'mai-engine' ),
				'name'      => 'background',
				'type'      => 'color_picker',
			],
		],
		'location'    => [
			[
				[
					'param'    => 'block',
					'operator' => '==',
					'value'    => 'acf/mai-column',
				],
			],
		],
		'active'      => true,
	]);
}