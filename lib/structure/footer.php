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

// Disable Genesis Footer Widgets toggle setting.
add_filter( 'genesis_footer_widgets_toggle_enabled', '__return_false' );

// Remove Genesis footer output.
remove_action( 'genesis_footer', 'genesis_do_footer' );
