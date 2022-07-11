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
 * Removes default meta support for bbPress post types.
 *
 * @since 2.21.3
 *
 * @return void
 */
add_action( 'genesis_meta', 'mai_bbpress_remove_meta_support' );
function mai_bbpress_remove_meta_support() {
	$types = [
		'forum',
		'topic',
		'reply',
	];

	foreach ( $types as $type ) {
		remove_post_type_support( $type, 'genesis-entry-meta-before-content' );
		remove_post_type_support( $type, 'genesis-entry-meta-after-content' );
	}


}

add_action( 'genesis_meta', 'mai_bbpress_search_remove_loop' );
/**
 * Removes custom loop for search results.
 *
 * @since 2.21.3
 *
 * @return void
 */
function mai_bbpress_search_remove_loop() {
	if ( ! function_exists( 'bbp_is_search' ) ) {
		return;
	}

	if ( ! bbp_is_search() ) {
		return;
	}

	remove_action( 'genesis_before_loop', 'mai_setup_loop' );
}
