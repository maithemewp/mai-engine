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
	 * @var
	 */
	protected $class;

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
	 * Register service providers and properties.
	 *
	 * @param array $container Container
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function register( $container = [] ) {
		$this->slug   = 'mai-setup-wizard';
		$this->name   = __( 'Mai Setup Wizard', 'mai-engine' );
		$this->class  = get_class( $this );
		$this->demos  = is_a( $container['demos'], $this->class ) ? null : $container['demos'];
		$this->fields = is_a( $container['fields'], $this->class ) ? null : $container['fields'];
		$this->steps  = is_a( $container['steps'], $this->class ) ? null : $container['steps'];
		$this->admin  = is_a( $container['admin'], $this->class ) ? null : $container['admin'];
		$this->import = is_a( $container['import'], $this->class ) ? null : $container['import'];
		$this->ajax   = is_a( $container['ajax'], $this->class ) ? null : $container['ajax'];
	}
}
