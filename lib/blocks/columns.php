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
			'icon'            => '<svg version="1.0" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" style="margin-bottom:-2px;" width="20" height="20" viewBox="0 0 900 900" preserveAspectRatio="xMidYMid meet"><g transform="translate(0,900) rotate(-360.00) scale(0.09,-0.09)" fill="currentColor" stroke="none"><path d="M4724 9450 l-4572 0 -45 -23 c-27 -13 -57 -40 -74 -64 l-28 -43 -2 -4583 -3 -4583 22 -46 c14 -28 39 -58 65 -75 l43 -28 4595 0 4595 0 37 26 c21 13 49 41 62 62 l26 37 0 4595 0 4595 -28 43 c-17 26 -47 51 -75 65 l-46 22 -4572 0z m-3957 -399 l357 0 -229 -223 c-125 -123 -286 -279 -357 -349 l-128 -125 0 348 0 349 357 0z m1599 0 l670 0 -4 -57 -3 -56 -1254 -1221 c-691 -671 -1280 -1244 -1310 -1273 l-55 -54 0 704 0 703 643 627 643 626 670 1z m1414 0 l336 0 -228 -223 c-126 -123 -277 -270 -336 -328 l-108 -104 0 328 0 327 336 0z m1578 0 l670 0 -3 -57 -3 -56 -1251 -1217 c-688 -669 -1268 -1233 -1289 -1252 l-38 -37 0 703 0 703 622 606 622 606 670 1z m1451 0 l331 0 -223 -218 c-123 -120 -272 -265 -331 -322 l-107 -104 0 322 0 322 330 0z m1568 0 l665 0 -4 -62 -3 -61 -1245 -1212 c-685 -666 -1260 -1225 -1279 -1242 l-32 -31 0 703 0 703 616 600 617 601 665 1z m-5345 -1373 l3 -702 -192 -189 c-105 -104 -283 -277 -395 -383 -112 -106 -457 -439 -767 -740 -309 -300 -649 -628 -755 -728 -106 -101 -198 -191 -205 -200 -7 -10 -80 -83 -162 -163 l-149 -145 0 708 0 707 247 237 c135 129 724 700 1309 1268 l1063 1032 3 -702z m2992 0 l3 -702 -192 -189 c-105 -104 -283 -277 -395 -383 -112 -106 -457 -439 -766 -740 -310 -300 -650 -628 -755 -728 -106 -101 -198 -191 -205 -200 -7 -10 -71 -74 -141 -142 l-129 -123 1 707 0 707 223 213 c122 117 702 679 1288 1248 l1066 1034 2 -702z m3014 -9 l3 -702 -124 -124 c-68 -68 -243 -238 -390 -378 -147 -140 -520 -500 -830 -801 -309 -300 -649 -628 -754 -728 -106 -101 -198 -191 -205 -200 -7 -10 -68 -71 -136 -136 l-123 -119 0 707 1 708 217 207 c120 115 694 671 1278 1238 l1060 1029 3 -701z m-6006 -1957 l3 -692 -224 -220 c-122 -120 -712 -695 -1309 -1276 l-1087 -1058 -3 702 -3 701 124 121 c68 66 291 283 496 482 205 200 708 689 1118 1089 410 399 776 752 814 784 l68 58 3 -691z m2992 0 l3 -692 -228 -225 c-126 -123 -706 -689 -1289 -1256 l-1061 -1032 -3 702 -3 702 103 99 c57 55 270 263 476 462 205 200 708 689 1118 1089 410 399 776 752 814 784 l68 58 2 -691z m3014 -10 l3 -692 -229 -225 c-125 -124 -701 -684 -1278 -1246 l-1050 -1022 -3 703 -3 702 97 94 c54 51 265 257 471 456 205 200 708 689 1118 1089 410 399 774 750 808 779 l63 54 3 -692z m-6006 -1951 l3 -702 -192 -189 c-105 -104 -283 -277 -395 -383 -112 -106 -457 -439 -767 -740 -309 -300 -649 -628 -755 -728 -106 -101 -198 -191 -205 -200 -7 -10 -80 -83 -162 -163 l-149 -145 0 708 0 707 247 237 c135 129 724 700 1309 1268 l1063 1032 3 -702z m2992 0 l3 -702 -192 -189 c-105 -104 -283 -277 -395 -383 -112 -106 -457 -439 -766 -740 -310 -300 -650 -628 -755 -728 -106 -101 -198 -191 -205 -200 -7 -10 -71 -74 -141 -142 l-129 -123 1 707 0 707 223 213 c122 117 702 679 1288 1248 l1066 1034 2 -702z m3014 -9 l3 -702 -124 -124 c-68 -68 -243 -238 -390 -378 -147 -140 -520 -500 -830 -801 -309 -300 -649 -628 -754 -728 -106 -101 -198 -191 -205 -200 -7 -10 -68 -71 -136 -136 l-123 -119 0 707 1 708 217 207 c120 115 694 671 1278 1238 l1060 1029 3 -701z m-6006 -1957 l3 -692 -250 -244 c-137 -135 -316 -310 -397 -390 l-147 -144 -723 3 -722 2 298 289 c164 159 636 618 1048 1020 413 402 781 757 819 789 l68 58 3 -691z m2992 0 l3 -692 -244 -239 c-134 -132 -294 -288 -355 -348 l-111 -107 -721 3 -721 2 255 247 c140 136 592 576 1005 978 413 402 781 757 819 789 l68 58 2 -691z m3014 -10 l3 -692 -239 -234 c-132 -129 -289 -283 -350 -343 l-111 -107 -721 3 -721 2 255 247 c140 136 592 576 1005 978 413 402 779 755 813 784 l63 54 3 -692z m-6006 -1251 c1 1 3 -46 3 -104 l0 -105 -113 0 -112 0 110 104 c60 58 110 105 112 105z m2992 1 c2 0 3 -28 3 -63 l0 -63 -65 0 -66 0 63 63 c34 35 63 63 65 63z m3014 -10 c1 0 3 -26 3 -58 l0 -58 -61 0 -60 0 58 58 c31 32 58 58 60 58z"/></g></svg>',
			// 'icon'            => '<svg version="1.0" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" style="margin-bottom:-2px;" width="20" height="20" viewBox="0 0 900 900" preserveAspectRatio="xMidYMid meet"><g transform="translate(0,900) rotate(-360.00) scale(0.09,-0.09)" fill="currentColor" stroke="none"><path d="M4724 9450 l-4572 0 -45 -23 c-27 -13 -57 -40 -74 -64 l-28 -43 0 -4595 0 -4595 26 -37 c13 -21 41 -49 62 -62 l37 -26 4595 0 4595 0 37 26 c21 13 49 41 62 62 l26 37 0 4595 0 4595 -28 43 c-17 26 -47 51 -75 65 l-46 22 -4572 0z m-3033 -399 l1281 0 0 -4326 0 -4326 -1281 0 -1281 0 0 4326 0 4326 1281 0z m3034 0 l1344 0 0 -4326 0 -4326 -1344 0 -1344 0 0 4326 0 4326 1344 0z m3035 0 l1281 0 0 -4326 0 -4326 -1281 0 -1281 0 0 4326 0 4326 1281 0z"/></g></svg>',
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
			// 'icon'            => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 80" x="0px" y="0px"><path fill="currentColor" d="M180,778 L240,778 C241.104569,778 242,778.895431 242,780 L242,840 C242,841.104569 241.104569,842 240,842 L180,842 C178.895431,842 178,841.104569 178,840 L178,780 C178,778.895431 178.895431,778 180,778 Z M212,782 L212,838 L238,838 L238,782 L212,782 Z M208,823.171573 L208,808.828427 L182,834.828427 L182,838 L193.171573,838 L208,823.171573 Z M208,838 L208,828.828427 L198.828427,838 L208,838 Z M182,829.171573 L208,803.171573 L208,788.828427 L182,814.828427 L182,829.171573 Z M194.828427,782 L182,794.828427 L182,809.171573 L208,783.171573 L208,782 L194.828427,782 Z M189.171573,782 L182,782 L182,789.171573 L189.171573,782 Z" transform="translate(-178 -778)"/></svg>',
			'icon'            => '<svg version="1.0" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" style="margin-bottom:-2px;" width="20" height="20" viewBox="0 0 900 900" preserveAspectRatio="xMidYMid meet"><g transform="translate(0,900) rotate(-360.00) scale(0.09,-0.09)" fill="currentColor" stroke="none"><path d="M107 9427 c-27 -13 -57 -40 -74 -64 l-28 -43 -2 -4583 -3 -4583 22 -46 c14 -28 39 -58 65 -75 l43 -28 4595 0 4595 0 37 26 c21 13 49 41 62 62 l26 37 0 4595 0 4595 -28 43 c-17 26 -47 51 -75 65 l-46 22 -4572 0 -4572 0 -45 -23z m788 -599 c-125 -123 -286 -279 -357 -349 l-128 -125 0 348 0 349 357 0 357 0 -229 -223z m2137 166 l-3 -56 -1254 -1221 c-691 -671 -1280 -1244 -1310 -1273 l-55 -54 0 704 0 703 643 627 643 626 670 1 670 0 -4 -57z m3037 -4269 l0 -4326 -1312 0 -1313 0 0 4326 0 4326 1312 0 1313 0 0 -4326z m2972 0 l0 -4326 -1281 0 -1281 0 0 4326 0 4326 1281 0 1281 0 0 -4326z m-6198 2062 c-105 -104 -283 -277 -395 -383 -112 -106 -457 -439 -767 -740 -309 -300 -649 -628 -755 -728 -106 -101 -198 -191 -205 -200 -7 -10 -80 -83 -162 -163 l-149 -145 0 708 0 707 247 237 c135 129 724 700 1309 1268 l1063 1032 3 -702 3 -702 -192 -189z m-32 -1987 c-122 -120 -712 -695 -1309 -1276 l-1087 -1058 -3 702 -3 701 124 121 c68 66 291 283 496 482 205 200 708 689 1118 1089 410 399 776 752 814 784 l68 58 3 -691 3 -692 -224 -220z m32 -1940 c-105 -104 -283 -277 -395 -383 -112 -106 -457 -439 -767 -740 -309 -300 -649 -628 -755 -728 -106 -101 -198 -191 -205 -200 -7 -10 -80 -83 -162 -163 l-149 -145 0 708 0 707 247 237 c135 129 724 700 1309 1268 l1063 1032 3 -702 3 -702 -192 -189z m-58 -2011 c-137 -135 -316 -310 -397 -390 l-147 -144 -723 3 -722 2 298 289 c164 159 636 618 1048 1020 413 402 781 757 819 789 l68 58 3 -691 3 -692 -250 -244z m250 -429 l0 -105 -113 0 -112 0 110 104 c60 58 110 105 112 105 1 1 3 -46 3 -104z"/></g></svg>',
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
