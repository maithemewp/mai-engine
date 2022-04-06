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

add_action( 'wp_enqueue_scripts', 'mai_remove_simple_social_icons_css', 15 );
/**
 * Remove Simple Social Icons CSS.
 *
 * @since 2.4.0
 *
 * @return void
 */
function mai_remove_simple_social_icons_css() {
	mai_deregister_asset( 'simple-social-icons-font' );
}
