<?php

namespace MaiSetupWizard;

abstract class AbstractServiceProvider implements ServiceProviderInterface {

	/**
	 * @var Plugin $plugin
	 */
	protected $plugin;

	/**
	 * @var DemoProvider $demo
	 */
	protected $demo;

	/**
	 * @var FieldProvider $field
	 */
	protected $field;

	/**
	 * @var StepProvider $step
	 */
	protected $step;

	/**
	 * @var AdminProvider $admin
	 */
	protected $admin;

	/**
	 * @var ImportProvider $import
	 */
	protected $import;

	/**
	 * @var AjaxProvider $ajax
	 */
	protected $ajax;

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
