<?php

namespace MaiSetupWizard;

abstract class AbstractServiceProvider implements ServiceProviderInterface {
	/**
	 * @var string
	 */
	protected $file;

	/**
	 * @var Providers\Plugin $plugin
	 */
	protected $plugin;

	/**
	 * @var Providers\Demo $demo
	 */
	protected $demo;

	/**
	 * @var Providers\Field $field
	 */
	protected $field;

	/**
	 * @var Providers\Step $step
	 */
	protected $step;

	/**
	 * @var Providers\Admin $admin
	 */
	protected $admin;

	/**
	 * @var Providers\Importer $importer
	 */
	protected $importer;

	/**
	 * @var Providers\Ajax $ajax
	 */
	protected $ajax;

	public function register( \Pimple\Container $container ) {
		$this->file     = isset( $container['file'] ) ? $container['file'] : '';
		$this->plugin   = isset( $container['plugin'] ) ? $container['plugin'] : '';
		$this->demo     = isset( $container['demo'] ) ? $container['demo'] : '';
		$this->field    = isset( $container['field'] ) ? $container['field'] : '';
		$this->step     = isset( $container['step'] ) ? $container['step'] : '';
		$this->admin    = isset( $container['admin'] ) ? $container['admin'] : '';
		$this->importer = isset( $container['importer'] ) ? $container['importer'] : '';
		$this->ajax     = isset( $container['ajax'] ) ? $container['ajax'] : '';
	}
}
