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

add_action( 'admin_init', 'mai_plugin_updater' );
/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_plugin_updater() {
	Puc_v4_Factory::buildUpdateChecker(
		'https://github.com/maithemewp/mai-engine',
		__FILE__,
		'mai-engine'
	);
}
