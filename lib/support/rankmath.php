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

add_filter( 'rank_math/frontend/breadcrumb/args', 'mai_rank_math_breadcrumb_args' );
/**
 * Adds breadcrumb class to Rank Math breadcrumbs.
 *
 * @since 2.21.0
 *
 * @param $args The existing breadcrumb args.
 *
 * @return array
 */
function mai_rank_math_breadcrumb_args( $args ) {
	$args['wrap_before'] = str_replace( 'rank-math-breadcrumb', 'rank-math-breadcrumb breadcrumb', $args['wrap_before'] );
	return $args;
}
