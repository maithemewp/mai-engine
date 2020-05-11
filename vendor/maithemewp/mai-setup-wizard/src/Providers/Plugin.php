<?php

namespace MaiSetupWizard\Providers;

use MaiSetupWizard\AbstractServiceProvider;

class Plugin extends AbstractServiceProvider {
	public $base;
	public $slug;
	public $dir;
	public $url;
	public $name;
	public $data;
	public $version;

	public function add_hooks() {
		$data     = \get_plugin_data( $this->file );
		$defaults = \apply_filters(
			'mai_setup_wizard_plugin',
			[
				'file'    => $this->file,
				'base'    => \plugin_basename( $this->file ),
				'slug'    => \basename( $this->file, '.php' ),
				'dir'     => \trailingslashit( \dirname( $this->file ) ),
				'url'     => \trailingslashit( \plugin_dir_url( $this->file ) ),
				'name'    => $data['Name'],
				'version' => $data['Version'],
			]
		);

		foreach ( $defaults as $property => $value ) {
			$this->{$property} = $value;
		}
	}
}
