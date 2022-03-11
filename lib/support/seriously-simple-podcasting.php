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

add_filter( 'ssp_register_post_type_args', 'mai_ssp_add_settings' );
/**
 * Adds support for mai settings in Seriously Simple Podcasting.
 *
 * @since 2.15.0
 *
 * @param array $args The existing post type args.
 *
 * @return array
 */
function mai_ssp_add_settings( $args ) {
	$args['supports'][] = 'genesis-cpt-archives-settings';
	$args['supports'][] = 'mai-archive-settings';
	$args['supports'][] = 'mai-single-settings';
	$args['supports']   = array_unique( $args['supports'] );

	return $args;
}
