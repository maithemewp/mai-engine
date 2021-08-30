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

add_action( 'acf/init', 'mai_register_columns_blocks' );
/**
 * Registers the columns blocks.
 *
 * @since 2.10.0
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
			'title'           => __( 'Mai Columns', 'mai-engine' ),
			'description'     => __( 'A custom columns block.', 'mai-engine' ),
			'render_callback' => 'mai_do_columns_block',
			'category'        => 'layout',
			'keywords'        => [ 'columns' ],
			'icon'            => '<svg style="margin-bottom:-3px;" width="20" height="20" viewBox="0 0 96 96" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;"><g transform="matrix(0.75,0,0,0.780483,12,10.5366)"><g transform="matrix(1,0,0,0.851775,31,-1.2925)"><g transform="matrix(0.116119,-0.108814,0.238273,0.223283,16.9541,72.8004)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g><g transform="matrix(0.185237,-0.173584,0.238273,0.223283,6.84275,60.9146)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g><g transform="matrix(0.185237,-0.173584,0.238273,0.223283,6.84275,39.5536)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g><g transform="matrix(0.185237,-0.173584,0.238273,0.223283,6.84275,18.5536)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g><g transform="matrix(0.0966534,-0.0905728,0.238273,0.223283,5.13751,-0.987447)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g></g><g transform="matrix(1,0,0,0.851775,-4.26326e-14,-1.2925)"><g transform="matrix(0.116119,-0.108814,0.238273,0.223283,16.9541,72.8004)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g><g transform="matrix(0.185237,-0.173584,0.238273,0.223283,6.84275,60.9146)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g><g transform="matrix(0.185237,-0.173584,0.238273,0.223283,6.84275,39.5536)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g><g transform="matrix(0.185237,-0.173584,0.238273,0.223283,6.84275,18.5536)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g><g transform="matrix(0.0966534,-0.0905728,0.238273,0.223283,5.13751,-0.987447)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g></g><g transform="matrix(1,0,0,0.851775,-31,-1.2925)"><g transform="matrix(0.116119,-0.108814,0.238273,0.223283,16.9541,72.8004)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g><g transform="matrix(0.185237,-0.173584,0.238273,0.223283,6.84275,60.9146)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g><g transform="matrix(0.185237,-0.173584,0.238273,0.223283,6.84275,39.5536)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g><g transform="matrix(0.185237,-0.173584,0.238273,0.223283,6.84275,18.5536)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g><g transform="matrix(0.0966534,-0.0905728,0.238273,0.223283,5.13751,-0.987447)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g></g><g transform="matrix(-0.268797,0,0,0.273288,155.348,7.00041)"><path d="M328.678,18.753L328.678,281.297L243.112,281.297L243.112,18.753M351,-0C351,-0.003 235.671,-0 235.671,-0C225.663,0.022 220.806,3.089 220.79,12.502L220.79,287.548C220.806,293.834 229.385,300.034 235.671,300.05L351,300.05L351,-0Z" style="fill:currentColor;fill-rule:nonzero;"/></g><g transform="matrix(0.291836,0,0,0.273288,-35.4345,7.00041)"><g><path d="M330.441,18.753L330.441,281.297L241.349,281.297L241.349,18.753M351,-0C351,-0.003 220.79,-0 220.79,-0L220.79,300.05L351,300.05L351,-0Z" style="fill:currentColor;fill-rule:nonzero;"/></g></g><g transform="matrix(0.268797,0,0,0.273288,-59.3476,7.00041)"><path d="M328.678,18.753L328.678,281.297L243.112,281.297L243.768,18.753M351,-0C351,-0.003 235.671,-0 235.671,-0C225.663,0.022 220.806,3.089 220.79,12.502L220.79,287.548C220.806,293.834 229.385,300.034 235.671,300.05L351,300.05L351,-0Z" style="fill:currentColor;fill-rule:nonzero;"/></g></g></svg>',
			'supports'        => [
				'align' => [ 'wide', 'full' ],
				'mode'  => false,
				'jsx'   => true,
			],
		]
	);

	acf_register_block_type(
		[
			'name'            => 'mai-column',
			'title'           => __( 'Mai Column', 'mai-engine' ),
			'description'     => __( 'A custom column block.', 'mai-engine' ),
			'render_callback' => 'mai_do_column_block',
			'category'        => 'layout',
			'keywords'        => [],
			'icon'            => '<svg style="margin-bottom:-3px;" width="20" height="20" viewBox="0 0 96 96" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;"><g transform="matrix(0.75,0,0,0.780483,12,10.5366)"><g transform="matrix(1,0,0,0.851775,-31,-1.2925)"><g transform="matrix(0.116119,-0.108814,0.238273,0.223283,16.9541,72.8004)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g><g transform="matrix(0.185237,-0.173584,0.238273,0.223283,6.84275,60.9146)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g><g transform="matrix(0.185237,-0.173584,0.238273,0.223283,6.84275,39.5536)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g><g transform="matrix(0.185237,-0.173584,0.238273,0.223283,6.84275,18.5536)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g><g transform="matrix(0.0966534,-0.0905728,0.238273,0.223283,5.13751,-0.987447)"><rect x="-19.25" y="116.35" width="165.54" height="14" style="fill:currentColor;"/></g></g><g transform="matrix(-0.268797,0,0,0.273288,155.348,7.00041)"><path d="M328.678,18.753L328.678,281.297L243.112,281.297L243.112,18.753M351,-0C351,-0.003 235.671,-0 235.671,-0C225.663,0.022 220.806,3.089 220.79,12.502L220.79,287.548C220.806,293.834 229.385,300.034 235.671,300.05L351,300.05L351,-0Z" style="fill:currentColor;fill-rule:nonzero;"/></g><g transform="matrix(0.291836,0,0,0.273288,-35.4345,7.00041)"><g><path d="M330.441,18.753L330.441,281.297L241.349,281.297L241.349,18.753M351,-0C351,-0.003 220.79,-0 220.79,-0L220.79,300.05L351,300.05L351,-0Z" style="fill:currentColor;fill-rule:nonzero;"/></g></g><g transform="matrix(0.268797,0,0,0.273288,-59.3476,7.00041)"><path d="M328.678,18.753L328.678,281.297L243.112,281.297L243.768,18.753M351,-0C351,-0.003 235.671,-0 235.671,-0C225.663,0.022 220.806,3.089 220.79,12.502L220.79,287.548C220.806,293.834 229.385,300.034 235.671,300.05L351,300.05L351,-0Z" style="fill:currentColor;fill-rule:nonzero;"/></g></g></svg>',
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
 * @since 2.10.0
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
	$args[ $instance ]['align']                  = $block['align'];
	$args[ $instance ]['align_columns']          = get_field( 'align_columns' );
	$args[ $instance ]['align_columns_vertical'] = get_field( 'align_columns_vertical' );
	$args[ $instance ]['margin_top']             = get_field( 'margin_top' );
	$args[ $instance ]['margin_bottom']          = get_field( 'margin_bottom' );

	$columns = new Mai_Columns( $instance, $args[ $instance ] );
	$columns->render();

	$instance++;
}

/**
 * Callback function to render the column block.
 *
 * @since 2.10.0
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
		'preview'               => $is_preview,
		'class'                 => isset( $block['className'] ) ? $block['className']: '',
		'align_column_vertical' => get_field( 'align_column_vertical' ),
		'spacing'               => get_field( 'spacing' ),
		'background'            => get_field( 'background' ),
		'first_xs'              => get_field( 'first_xs' ),
		'first_sm'              => get_field( 'first_sm' ),
		'first_md'              => get_field( 'first_md' ),
	];

	$columns = new Mai_Column( $args );
	$columns->render();
}

add_filter( 'render_block', 'mai_render_mai_columns_block', 10, 2 );
/**
 * Adds inline custom properties for custom column arrangments.
 *
 * @since 2.10.0
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

	$args = $args[ $instance ];

	if ( ! isset( $args['arrangements'] ) ) {
		return $block_content;
	}

	$xpath    = new DOMXPath( $dom );
	$elements = $xpath->query( 'div[contains(concat(" ", normalize-space(@class), " "), " mai-columns-wrap ")]/div[contains(concat(" ", normalize-space(@class), " "), " mai-column ")]' );

	if ( ! $elements->length ) {
		return $block_content;
	}

	if ( 'custom' === $args['columns'] ) {

		foreach ( array_reverse( $args['arrangements'] ) as $break => $arrangement ) {
			$total_arrangements = count( $arrangement );
			$element_i          = 0;

			foreach ( $elements as $element ) {
				$style = $element->getAttribute( 'style' );

				$element->setAttribute( 'data-instance', $instance );

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

				if ( $style ) {
					$element->setAttribute( 'style', $style );
				} else {
					$element->removeAttribute( 'style' );
				}

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

			if ( $style ) {
				$element->setAttribute( 'style', $style );
			} else {
				$element->removeAttribute( 'style' );
			}
		}
	}

	$block_content = $dom->saveHTML();

	return $block_content;
}

add_action( 'acf/init', 'mai_register_columns_field_groups' );
/**
 * Register Mai Columns block field group.
 *
 * @since 2.10.0
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
		'3/8'   => __( '3/8', 'mai-engine' ),
		'2/5'   => __( '2/5', 'mai-engine' ),
		'1/2'   => __( '1/2', 'mai-engine' ),
		'3/5'   => __( '3/5', 'mai-engine' ),
		'5/8'   => __( '5/8', 'mai-engine' ),
		'2/3'   => __( '2/3', 'mai-engine' ),
		'3/4'   => __( '3/4', 'mai-engine' ),
		'4/5'   => __( '4/5', 'mai-engine' ),
		'5/6'   => __( '5/6', 'mai-engine' ),
		'7/8'   => __( '7/8', 'mai-engine' ),
		'11/12' => __( '11/12', 'mai-engine' ),
		'full'  => __( 'Full Width', 'mai-engine' ),
		'fill'  => __( 'Fill Space', 'mai-engine' ),
		'auto'  => __( 'Auto', 'mai-engine' ),
	];

	acf_add_local_field_group( [
		'key'                 => 'mai_columns_field_group',
		'title'               => __( 'Mai Columns', 'mai-engine' ),
		'fields'              => [
			[
				'key'               => 'mai_columns_columns',
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
				'key'               => 'mai_columns_arrangement_message',
				'label'             => __( 'Responsive Arrangements', 'mai-engine' ),
				'type'              => 'message',
				'conditional_logic' => [
					[
						[
							'field'          => 'mai_columns_columns',
							'operator'       => '==',
							'value'          => 'custom',
						],
					],
				],
			],
			[
				'key'               => 'mai_columns_arrangement_tab',
				'label'             => __( 'LG', 'mai-engine' ),
				'type'              => 'tab',
				'conditional_logic' => [
					[
						[
							'field'          => 'mai_columns_columns',
							'operator'       => '==',
							'value'          => 'custom',
						],
					],
				],
			],
			[
				'key'               => 'mai_columns_arrangement',
				'label'             => __( 'Arrangement (desktop)', 'mai-engine' ),
				'name'              => 'arrangement',
				'type'              => 'repeater',
				'conditional_logic' => [
					[
						[
							'field'          => 'mai_columns_columns',
							'operator'       => '==',
							'value'          => 'custom',
						],
					],
				],
				'min'               => 1,
				'max'               => 0,
				'layout'            => 'block',
				'button_label'      => __( 'Add Column', 'mai-engine' ),
				'sub_fields'        => [
					[
						'key'             => 'mai_columns_arrangement_columns',
						'label'           => '',
						'name'            => 'columns',
						'type'            => 'select',
						'choices'         => $column_choices,
						'default_value'   => '1/3',
					],
				],
			],
			[
				'key'               => 'mai_columns_arrangement_md_tab',
				'label'             => __( 'MD', 'mai-engine' ),
				'type'              => 'tab',
				'conditional_logic' => [
					[
						[
							'field'          => 'mai_columns_columns',
							'operator'       => '==',
							'value'          => 'custom',
						],
					],
				],
			],
			[
				'key'               => 'mai_columns_md_arrangement',
				'label'             => __( 'Arrangement (lg tablets)', 'mai-engine' ),
				'name'              => 'arrangement_md',
				'type'              => 'repeater',
				'conditional_logic' => [
					[
						[
							'field'          => 'mai_columns_columns',
							'operator'       => '==',
							'value'          => 'custom',
						],
					],
				],
				'min'               => 1,
				'max'               => 0,
				'layout'            => 'block',
				'button_label'      => __( 'Add Column', 'mai-engine' ),
				'sub_fields'        => [
					[
						'key'             => 'mai_columns_md_arrangement_columns',
						'label'           => '',
						'name'            => 'columns',
						'type'            => 'select',
						'choices'         => $column_choices,
						'default_value'   => '1/3',
					],
				],
			],
			[
				'key'               => 'mai_columns_arrangement_sm_tab',
				'label'             => __( 'SM', 'mai-engine' ),
				'type'              => 'tab',
				'conditional_logic' => [
					[
						[
							'field'          => 'mai_columns_columns',
							'operator'       => '==',
							'value'          => 'custom',
						],
					],
				],
			],
			[
				'key'               => 'mai_columns_sm_arrangement',
				'label'             => __( 'Arrangement (sm tablets)', 'mai-engine' ),
				'name'              => 'arrangement_sm',
				'type'              => 'repeater',
				'conditional_logic' => [
					[
						[
							'field'          => 'mai_columns_columns',
							'operator'       => '==',
							'value'          => 'custom',
						],
					],
				],
				'min'               => 1,
				'max'               => 0,
				'layout'            => 'block',
				'button_label'      => __( 'Add Column', 'mai-engine' ),
				'sub_fields'        => [
					[
						'key'             => 'mai_columns_sm_arrangement_columns',
						'label'           => '',
						'name'            => 'columns',
						'type'            => 'select',
						'choices'         => $column_choices,
						'default_value'   => '1/2',
					],
				],
			],
			[
				'key'               => 'mai_columns_arrangement_xs_tab',
				'label'             => __( 'XS', 'mai-engine' ),
				'type'              => 'tab',
				'conditional_logic' => [
					[
						[
							'field'          => 'mai_columns_columns',
							'operator'       => '==',
							'value'          => 'custom',
						],
					],
				],
			],
			[
				'key'               => 'mai_columns_xs_arrangement',
				'label'             => __( 'Arrangement (mobile)', 'mai-engine' ),
				'name'              => 'arrangement_xs',
				'type'              => 'repeater',
				'conditional_logic' => [
					[
						[
							'field'          => 'mai_columns_columns',
							'operator'       => '==',
							'value'          => 'custom',
						],
					],
				],
				'min'               => 1,
				'max'               => 0,
				'layout'            => 'block',
				'button_label'      => __( 'Add Column', 'mai-engine' ),
				'sub_fields'        => [
					[
						'key'             => 'mai_columns_xs_arrangement_columns',
						'label'           => '',
						'name'            => 'columns',
						'type'            => 'select',
						'choices'         => $column_choices,
						'default_value'   => 'full',
					],
				],
			],
			[
				'key'               => 'mai_columns_arrangement_closing_tab',
				'type'              => 'tab',
				'endpoint'          => 1,
				'wrapper'           => [
					'class'            => 'mai-columns-closing-tab',
				],
			],
			[
				'key'               => 'mai_columns_align_columns',
				'label'             => __( 'Align Columns', 'mai-engine' ),
				'name'              => 'align_columns',
				'type'              => 'button_group',
				'choices'           => [
					'start'            => __( 'Start', 'mai-engine' ),
					'center'           => __( 'Center', 'mai-engine' ),
					'end'              => __( 'End', 'mai-engine' ),
				],
				'default_value'     => 'start',
				'wrapper'           => [
					'class'            => 'mai-acf-button-group',
				],
			],
			[
				'key'               => 'mai_columns_align_columns_vertical',
				'label'             => __( 'Align Columns (vertical)', 'mai-engine' ),
				'name'              => 'align_columns_vertical',
				'type'              => 'button_group',
				'choices'           => [
					''                 => __( 'Full', 'mai-engine' ),
					'top'              => __( 'Top', 'mai-engine' ),
					'middle'           => __( 'Middle', 'mai-engine' ),
					'bottom'           => __( 'Bottom', 'mai-engine' ),
				],
				'wrapper'           => [
					'class'            => 'mai-acf-button-group',
				],
			],
			[
				'key'               => 'mai_columns_column_gap',
				'label'             => __( 'Column Gap', 'mai-engine' ),
				'name'              => 'column_gap',
				'type'              => 'button_group',
				'choices'           => [
					''                 => __( 'None', 'mai-engine' ),
					'md'               => __( 'XS', 'mai-engine' ),
					'lg'               => __( 'S', 'mai-engine' ),
					'xl'               => __( 'M', 'mai-engine' ),
					'xxl'              => __( 'L', 'mai-engine' ),
					'xxxl'             => __( 'XL', 'mai-engine' ),
				],
				'default_value'     => 'xl',
				'wrapper'           => [
					'class'            => 'mai-acf-button-group mai-acf-button-group-small',
				],
			],
			[
				'key'               => 'mai_columns_row_gap',
				'label'             => __( 'Row Gap', 'mai-engine' ),
				'name'              => 'row_gap',
				'type'              => 'button_group',
				'choices'           => [
					''                 => __( 'None', 'mai-engine' ),
					'md'               => __( 'XS', 'mai-engine' ),
					'lg'               => __( 'S', 'mai-engine' ),
					'xl'               => __( 'M', 'mai-engine' ),
					'xxl'              => __( 'L', 'mai-engine' ),
					'xxxl'             => __( 'XL', 'mai-engine' ),
				],
				'default_value'     => 'xl',
				'wrapper'           => [
					'class'            => 'mai-acf-button-group mai-acf-button-group-small',
				],
			],
			[
				'key'               => 'mai_columns_margin_top',
				'label'             => __( 'Top Margin', 'mai-engine' ),
				'name'              => 'margin_top',
				'type'              => 'button_group',
				'choices'           => [
					''                 => __( 'None', 'mai-engine' ),
					'md'               => __( 'XS', 'mai-engine' ),
					'lg'               => __( 'S', 'mai-engine' ),
					'xl'               => __( 'M', 'mai-engine' ),
					'xxl'              => __( 'L', 'mai-engine' ),
					'xxxl'             => __( 'XL', 'mai-engine' ),
					'xxxxl'            => __( 'XXL', 'mai-engine' ),
				],
				'default_value'     => '',
				'wrapper'           => [
					'class'            => 'mai-acf-button-group mai-acf-button-group-small',
				],
			],
			[
				'key'               => 'mai_columns_margin_bottom',
				'label'             => __( 'Bottom Margin', 'mai-engine' ),
				'name'              => 'margin_bottom',
				'type'              => 'button_group',
				'choices'           => [
					''                 => __( 'None', 'mai-engine' ),
					'md'               => __( 'XS', 'mai-engine' ),
					'lg'               => __( 'S', 'mai-engine' ),
					'xl'               => __( 'M', 'mai-engine' ),
					'xxl'              => __( 'L', 'mai-engine' ),
					'xxxl'             => __( 'XL', 'mai-engine' ),
					'xxxxl'            => __( 'XXL', 'mai-engine' ),
				],
				'default_value'     => '',
				'wrapper'           => [
					'class'            => 'mai-acf-button-group mai-acf-button-group-small',
				],
			],
		],
		'location'            => [
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
		'key'         => 'mai_column_field_group',
		'title'       => __( 'Mai Column', 'mai-engine' ),
		'fields'      => [
			[
				'key'               => 'mai_column_align_column_vertical',
				'label'             => __( 'Align Content (vertical)', 'mai-engine' ),
				'name'              => 'align_column_vertical',
				'type'              => 'button_group',
				'choices'           => [
					'start'            => __( 'Top', 'mai-engine' ),
					'middle'           => __( 'Middle', 'mai-engine' ),
					'end'              => __( 'Bottom', 'mai-engine' ),
				],
				'default_value'     => 'start',
				'wrapper'           => [
					'class'            => 'mai-acf-button-group',
				],
			],
			[
				'key'       => 'mai_column_spacing',
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
				'wrapper'   => [
					'class'    => 'mai-acf-button-group',
				],
			],
			[
				'key'       => 'mai_column_background',
				'label'     => __( 'Background Color', 'mai-engine' ),
				'name'      => 'background',
				'type'      => 'color_picker',
			],
			[
				'key'               => 'mai_columns_first_xs',
				'name'              => 'first_xs',
				'label'             => '',
				'message'           => esc_html__( 'Show first on mobile', 'mai-engine' ),
				'type'              => 'true_false',
			],
			[
				'key'               => 'mai_columns_first_sm',
				'name'              => 'first_sm',
				'label'             => '',
				'message'           => esc_html__( 'Show first on small tablets', 'mai-engine' ),
				'type'              => 'true_false',
			],
			[
				'key'               => 'mai_columns_first_md',
				'name'              => 'first_md',
				'label'             => '',
				'message'           => esc_html__( 'Show first on large tablets', 'mai-engine' ),
				'type'              => 'true_false',
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
