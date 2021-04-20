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

add_action( 'admin_notices', 'mai_maybe_display_admin_notice', 20 );
/**
 * Displays notices from a query args.
 *
 * @since 2.6.0
 *
 * @return void
 */
function mai_maybe_display_admin_notice() {
	$notice = filter_input( INPUT_GET, 'mai_notice', FILTER_SANITIZE_STRING );

	if ( ! $notice ) {
		return;
	}

	$type = filter_input( INPUT_GET, 'mai_type', FILTER_SANITIZE_STRING );
	$type = $type ?: 'success';

	printf( '<div class="notice notice-%s">%s</div>', sanitize_html_class( $type ), wpautop( $notice ) );
}
