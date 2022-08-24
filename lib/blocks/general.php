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

add_filter( 'acf/register_block_type_args', 'mai_acf_register_block_type_args' );
/**
 * Makes sure all mai blocks are using the v2 API.
 * This insures blocks registered via Mai plugins use the current API.
 *
 * @since TBD
 *
 * @param array $args The block args.
 *
 * @return array
 */
function mai_acf_register_block_type_args( $args ) {
	if ( mai_has_string( 'acf/mai-', $args['name'] ) ) {
		$args['acf_block_version'] = 2;
	}

	return $args;
}

add_filter( 'acf/blocks/wrap_frontend_innerblocks', 'mai_acf_remove_wrap_frontend_innerblocks', 10, 2 );
/**
 * Removes innerblocks wrap from ACF.
 * This allows us to update Mai blocks from other plugins to the v2 API from here.
 *
 * @since TBD
 *
 * @param bool   $wrap Whether to include the wrapping element on the front end.
 * @param string $name The registered block name.
 *
 * @return bool
 */
function mai_acf_remove_wrap_frontend_innerblocks( $wrap, $name ) {
	if ( mai_has_string( 'acf/mai-', $name ) ) {
		return false;
	}

	return $wrap;
}
