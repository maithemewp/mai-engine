<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2019 BizBudding
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
