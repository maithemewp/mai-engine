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

register_activation_hook( dirname( __DIR__ ) . '/mai-engine.php', 'mai_short_circuit_acf' );
/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_short_circuit_acf() {
	deactivate_plugins( '/advanced-custom-fields/acf.php' );
}
