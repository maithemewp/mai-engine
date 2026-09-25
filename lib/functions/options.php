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

add_action( 'added_option', 'mai_maybe_reset_options_cache' );
add_action( 'updated_option', 'mai_maybe_reset_options_cache' );
add_action( 'deleted_option', 'mai_maybe_reset_options_cache' );
/**
 * Empties Mai's option caches whenever the Mai option is written.
 *
 * mai_get_options() and mai_get_option() keep what they read for the rest of the
 * request. Without this, anything read after a save in the same request, such as
 * the upgrade routine or a Customizer save, gets the options from before.
 *
 * @since 2.41.0
 *
 * @param string $option The option that changed.
 *
 * @return void
 */
function mai_maybe_reset_options_cache( $option ) {
	if ( mai_get_handle() !== $option ) {
		return;
	}

	mai_reset_options_cache();
}
