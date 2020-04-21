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

//add_action( 'admin_init', 'mai_deactivate_plugin' );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_deactivate_plugin() {
	if ( ! mai_get_active_theme() ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
	}
}

add_action( 'admin_notices', 'mai_deactivate_admin_notices' );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_deactivate_admin_notices() {
	if ( get_stylesheet() !== get_template() ) {
		return;
	}

	printf(
		'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
		__( 'Your theme does not support the Mai Engine plugin. As a result, Mai Engine has been deactivated.', 'mai-engine' )
	);

	if ( isset( $_GET['activate'] ) ) {
		unset( $_GET['activate'] );
	}
}
