<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2019 BizBudding
 * @license   GPL-2.0-or-later
 */

add_action( 'init', 'mai_header_customizer_settings' );
/**
 * Add header customizer settings.
 *
 * @return  void
 */
function mai_header_customizer_settings() {

	// Bail if no Kirki.
	if ( ! class_exists( 'Kirki' ) ) {
		return;
	}

	$config_id = 'mai_header';

	/**
	 * Kirki Config.
	 */
	Kirki::add_config(
		$config_id,
		[
			'capability'  => 'edit_theme_options',
			'option_type' => 'option',
			'option_name' => $config_id,
		]
	);

	// TODO: Header style: sticky, transparent, conceal, etc.
	// TODO: Mobile menu width/breakpoint.

}
