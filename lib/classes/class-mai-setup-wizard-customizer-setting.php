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

/**
 * Class Mai_Setup_Wizard_Customizer_Setting
 */
class Mai_Setup_Wizard_Customizer_Setting extends WP_Customize_Setting {

	/**
	 * Description of expected behavior.
	 *
	 * @since 1.0.0
	 *
	 * @param $value
	 *
	 * @return void
	 */
	public function import( $value ) {
		$this->update( $value );
	}
}
