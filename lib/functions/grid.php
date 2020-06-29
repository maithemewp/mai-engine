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

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param array $args Grid args.
 *
 * @return void
 */
function mai_do_post_grid( $args ) {
	mai_do_grid( 'post', $args );
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param array $args Grid args.
 *
 * @return void
 */
function mai_do_term_grid( $args ) {
	mai_do_grid( 'term', $args );
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param array $args Grid args.
 *
 * @return void
 */
function mai_do_user_grid( $args ) {
	mai_do_grid( 'user', $args );
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param string $type Grid type.
 * @param array  $args Grid args.
 *
 * @return void
 */
function mai_do_grid( $type, $args = [] ) {
	$args = array_merge( [ 'type' => $type ], $args );
	$grid = new Mai_Grid( $args );
	$grid->render();
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return array
 */
function mai_get_grid_show_choices() {
	static $choices = null;

	if ( is_null( $choices ) ) {
		$choices = [
			'image'       => esc_html__( 'Image', 'mai-engine' ),
			'title'       => esc_html__( 'Title', 'mai-engine' ),
			'header_meta' => esc_html__( 'Header Meta', 'mai-engine' ),
			'excerpt'     => esc_html__( 'Excerpt', 'mai-engine' ),
			'content'     => esc_html__( 'Content', 'mai-engine' ),
			'more_link'   => esc_html__( 'Read More link', 'mai-engine' ),
			'footer_meta' => esc_html__( 'Footer Meta', 'mai-engine' ),
		];
	}

	return $choices;
}

/**
 * Get the columns at different breakpoints.
 *
 * @since 0.1.0
 *
 * @param array $args Column args.
 *
 * @return array
 */
function mai_get_breakpoint_columns( $args ) {

	$args = wp_parse_args( $args, [
		'columns_responsive' => false,
		'columns'            => 3,
		'columns_md'         => 1,
		'columns_sm'         => 1,
		'columns_xs'         => 1,
	] );

	$columns = [
		'lg' => $args['columns'],
	];

	if ( $args['columns_responsive'] ) {
		$columns['md'] = $args['columns_md'];
		$columns['sm'] = $args['columns_sm'];
		$columns['xs'] = $args['columns_xs'];
	} else {
		switch ( $args['columns'] ) {
			case 6:
				$columns['md'] = 4;
				$columns['sm'] = 3;
				$columns['xs'] = 2;
				break;
			case 5:
				$columns['md'] = 3;
				$columns['sm'] = 2;
				$columns['xs'] = 2;
				break;
			case 4:
				$columns['md'] = 4;
				$columns['sm'] = 2;
				$columns['xs'] = 1;
				break;
			case 3:
				$columns['md'] = 3;
				$columns['sm'] = 1;
				$columns['xs'] = 1;
				break;
			case 2:
				$columns['md'] = 2;
				$columns['sm'] = 2;
				$columns['xs'] = 1;
				break;
			case 1:
				$columns['md'] = 1;
				$columns['sm'] = 1;
				$columns['xs'] = 1;
				break;
			case 0: // Auto.
				$columns['md'] = 0;
				$columns['sm'] = 0;
				$columns['xs'] = 0;
				break;
		}
	}

	return $columns;
}

/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @param string $alignment Text alignment.
 *
 * @return string
 */
function mai_get_align_text( $alignment ) {
	switch ( $alignment ) {
		case 'start':
			$value = 'start';
		break;
		case 'top':
			$value = 'flex-start';
		break;
		case 'center':
		case 'middle':
			$value = 'center';
		break;
		case 'end':
			$value = 'end';
		break;
		case 'bottom':
			$value = 'flex-end';
		break;
		default:
			$value = 'unset';
	}

	return $value;
}

/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @param string $value Gets flex align rule.
 *
 * @return string
 */
function mai_get_flex_align( $value ) {
	switch ( $value ) {
		case 'start':
		case 'top':
			$return = 'flex-start';
			break;
		case 'center':
		case 'middle':
			$return = 'center';
			break;
		case 'right':
		case 'bottom':
			$return = 'flex-end';
			break;
		default:
			$return = 'unset';
	}

	return $return;
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return array
 */
function mai_get_columns_choices() {
	$choices = [];

	if ( ! ( is_admin() || is_customize_preview() ) ) {
		return $choices;
	}

	return [
		'1' => esc_html__( '1', 'mai-engine' ),
		'2' => esc_html__( '2', 'mai-engine' ),
		'3' => esc_html__( '3', 'mai-engine' ),
		'4' => esc_html__( '4', 'mai-engine' ),
		'5' => esc_html__( '5', 'mai-engine' ),
		'6' => esc_html__( '6', 'mai-engine' ),
		'0' => esc_html__( 'Auto', 'mai-engine' ),
	];
}
