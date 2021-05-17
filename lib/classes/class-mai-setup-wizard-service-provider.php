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
 * Class Mai_Setup_Wizard_Service_Provider
 */
abstract class Mai_Setup_Wizard_Service_Provider {

	/**
	 * Name.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Slug.
	 *
	 * @var string
	 */
	protected $slug;

	/**
	 * Demos.
	 *
	 * @var Mai_Setup_Wizard_Demos $demos
	 */
	protected $demos;

	/**
	 * Fields.
	 *
	 * @var Mai_Setup_Wizard_Fields $fields
	 */
	protected $fields;

	/**
	 * Steps.
	 *
	 * @var Mai_Setup_Wizard_Steps $steps
	 */
	protected $steps;

	/**
	 * Admin.
	 *
	 * @var Mai_Setup_Wizard_Admin $admin
	 */
	protected $admin;

	/**
	 * Importer.
	 *
	 * @var Mai_Setup_Wizard_Importer $import
	 */
	protected $import;

	/**
	 * Ajax.
	 *
	 * @var Mai_Setup_Wizard_Ajax $ajax
	 */
	protected $ajax;

	/**
	 * Register service providers and properties.
	 *
	 * @since 1.0.0
	 *
	 * @param array $container Container.
	 *
	 * @return void
	 */
	public function register( $container = [] ) {
		$this->slug   = 'mai-setup-wizard';
		$this->name   = __( 'Mai Setup Wizard', 'mai-engine' );
		$this->demos  = $container['demos'];
		$this->fields = $container['fields'];
		$this->steps  = $container['steps'];
		$this->admin  = $container['admin'];
		$this->import = $container['import'];
		$this->ajax   = $container['ajax'];
	}
}
