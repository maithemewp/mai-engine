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
 * Class Mai_Setup_Wizard
 */
class Mai_Setup_Wizard {

	/**
	 * @var
	 */
	public $base;

	/**
	 * @var
	 */
	public $slug;

	/**
	 * @var
	 */
	public $dir;

	/**
	 * @var
	 */
	public $url;

	/**
	 * @var
	 */
	public $name;

	/**
	 * @var
	 */
	public $data;

	/**
	 * @var
	 */
	public $version;

	/**
	 * Mai_Setup_Wizard constructor.
	 *
	 * @param $file
	 */
	public function __construct( $file ) {
		$data     = get_plugin_data( $file );
		$defaults = apply_filters(
			'mai_setup_wizard_plugin',
			[
				'file'    => $file,
				'base'    => plugin_basename( $file ),
				'slug'    => basename( $file, '.php' ),
				'dir'     => trailingslashit( dirname( $file ) ),
				'url'     => trailingslashit( plugin_dir_url( $file ) ),
				'name'    => $data['Name'],
				'version' => $data['Version'],
			]
		);

		foreach ( $defaults as $property => $value ) {
			$this->{$property} = $value;
		}
	}
}
