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
	 * @var
	 */
	protected $name;

	/**
	 * @var
	 */
	protected $slug;

	/**
	 * @var Mai_Setup_Wizard_Demos $demos
	 */
	protected $demos;

	/**
	 * @var Mai_Setup_Wizard_Fields $fields
	 */
	protected $fields;

	/**
	 * @var Mai_Setup_Wizard_Steps $steps
	 */
	protected $steps;

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
		$this->slug   = 'mai-setup-wizard';
		$this->name   = __( 'Mai Setup Wizard', 'mai-engine' );
		$this->demos  = isset( $container['demos'] ) ? $container['demos'] : '';
		$this->fields = isset( $container['fields'] ) ? $container['fields'] : '';
		$this->steps  = isset( $container['steps'] ) ? $container['steps'] : '';
		$this->admin  = isset( $container['admin'] ) ? $container['admin'] : '';
		$this->import = isset( $container['import'] ) ? $container['import'] : '';
		$this->ajax   = isset( $container['ajax'] ) ? $container['ajax'] : '';
	}
}
