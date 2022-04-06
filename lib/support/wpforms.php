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

add_filter( 'wpforms_settings_defaults', 'mai_wpforms_default_css' );
/**
 * Set the default WP Forms styling to "Base styling only".
 * This still requires the user to actually save the settings before it applies.
 *
 * @since 0.1.0
 *
 * @param array $defaults The settings defaults.
 *
 * @return array
 */
function mai_wpforms_default_css( $defaults ) {
	if ( isset( $defaults['general']['disable-css']['default'] ) ) {
		$defaults['general']['disable-css']['default'] = 2;
	}

	return $defaults;
}

add_filter( 'wpforms_frontend_form_data', 'mai_wpforms_default_button_class' );
/**
 * Add default button class to WP Forms.
 *
 * @since 0.1.0
 *
 * @param array $data The form data.
 *
 * @return array
 */
function mai_wpforms_default_button_class( $data ) {
	if ( isset( $data['settings']['submit_class'] ) && ! mai_has_string( 'button', $data['settings']['submit_class'] ) ) {
		$data['settings']['submit_class'] .= ' button';
		$data['settings']['submit_class']  = trim( $data['settings']['submit_class'] );
	}

	return $data;
}
