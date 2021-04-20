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
 * Class Mai_Setup_Wizard_Customizer_Setting
 */
class Mai_Setup_Wizard_Customizer_Setting extends WP_Customize_Setting {

	/**
	 * Import setting used by Mai_Setup_Wizard_Importer.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value to update.
	 *
	 * @return void
	 */
	public function import( $value ) {
		$this->update( $value );
	}
}
