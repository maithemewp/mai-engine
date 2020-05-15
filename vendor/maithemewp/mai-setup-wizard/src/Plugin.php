<?php

namespace MaiSetupWizard;

class Plugin {
	public $base;
	public $slug;
	public $dir;
	public $url;
	public $name;
	public $data;
	public $version;

	public function __construct( $file ) {
		$data     = \get_plugin_data( $file );
		$defaults = \apply_filters(
			'mai_setup_wizard_plugin',
			[
				'file'    => $file,
				'base'    => \plugin_basename( $file ),
				'slug'    => \basename( $file, '.php' ),
				'dir'     => \trailingslashit( \dirname( $file ) ),
				'url'     => \trailingslashit( \plugin_dir_url( $file ) ),
				'name'    => $data['Name'],
				'version' => $data['Version'],
			]
		);

		foreach ( $defaults as $property => $value ) {
			$this->{$property} = $value;
		}
	}
}
