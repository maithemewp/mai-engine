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
 * Renders a post grid.
 *
 * @since 0.1.0
 *
 * @param array $args Grid args.
 *
 * @return void
 */
function mai_do_post_grid( $args ) {
	mai_do_grid( 'post', $args );
}

/**
 * Renders a term grid.
 *
 * @since 0.1.0
 *
 * @param array $args Grid args.
 *
 * @return void
 */
function mai_do_term_grid( $args ) {
	mai_do_grid( 'term', $args );
}

/**
 * Renders a user grid.
 *
 * @since 0.1.0
 *
 * @param array $args Grid args.
 *
 * @return void
 */
function mai_do_user_grid( $args ) {
	mai_do_grid( 'user', $args );
}

/**
 * Renders a grid.
 *
 * @since 0.1.0
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
 * Get the text align value from a setting value.
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
		case 'left':
		case 'top':
			$value = 'start';
		case 'center':
		case 'middle':
			$value = 'center';
		break;
		case 'end':
		case 'right':
		case 'bottom':
			$value = 'end';
		break;
		default:
			$value = 'unset';
	}

	return $value;
}

/**
 * Get the flexbox property value from a setting value.
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
		case 'left':
		case 'top':
			$return = 'start';
		break;
		case 'center':
		case 'middle':
			$return = 'center';
		break;
		case 'end':
		case 'right':
		case 'bottom':
			$return = 'end';
		break;
		case 'between':
			$return = 'space-between';
		break;
		default:
			$return = 'initial'; // Needs initial for nested columns.
	}

	return $return;
}
