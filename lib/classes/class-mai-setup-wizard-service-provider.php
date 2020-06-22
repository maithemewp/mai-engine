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
 * Class Mai_Setup_Wizard_Service_Provider
 */
abstract class Mai_Setup_Wizard_Service_Provider {

	/**
	 * @var Mai_Setup_Wizard $plugin
	 */
	protected $plugin;

	/**
	 * @var Mai_Setup_Wizard_Demos $demo
	 */
	protected $demo;

	/**
	 * @var Mai_Setup_Wizard_Fields $field
	 */
	protected $field;

	/**
	 * @var Mai_Setup_Wizard_Steps $step
	 */
	protected $step;

	/**
	 * @var Mai_Setup_Wizard_Admin $admin
	 */
	protected $admin;

	/**
	 * @var Mai_Setup_Wizard_Importer $import
	 */
	protected $import;

	/**
	 * @var Mai_Setup_Wizard_Ajax $ajax
	 */
	protected $ajax;

	/**
	 * Description of expected behavior.
	 *
	 * @since 1.0.0
	 *
	 * @param array $container
	 *
	 * @return void
	 */
	public function register( $container = [] ) {
		$this->plugin = isset( $container['plugin'] ) ? $container['plugin'] : '';
		$this->demo   = isset( $container['demo'] ) ? $container['demo'] : '';
		$this->field  = isset( $container['field'] ) ? $container['field'] : '';
		$this->step   = isset( $container['step'] ) ? $container['step'] : '';
		$this->admin  = isset( $container['admin'] ) ? $container['admin'] : '';
		$this->import = isset( $container['import'] ) ? $container['import'] : '';
		$this->ajax   = isset( $container['ajax'] ) ? $container['ajax'] : '';
	}
}
