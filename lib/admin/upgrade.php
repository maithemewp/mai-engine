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

add_action( 'admin_init', 'mai_do_upgrade' );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_do_upgrade() {
	$plugin_version = mai_get_version();
	$db_version     = mai_get_option( 'db-version', '0.0.0' );

	// Return early if at latest.
	if ( $plugin_version !== $db_version ) {
		return;
	}

	// Set first version.
	if ( false === mai_get_option( 'first-version', false ) ) {
		mai_update_option( 'first-version', $plugin_version );
	}

	// Run the upgrade if data to upgrade.
	$data    = mai_get_upgrade_data( $plugin_version );
	$options = mai_get_options();

	if ( $data ) {

		// Add default values for new options.
		foreach ( $data as $new_key => $new_value ) {

			// Must use isset instead of true/false.
			if ( ! isset( $options[ $new_key ] ) ) {
				mai_update_option( $new_key, $new_value );
			}

			// Handle nested options.
			if ( is_array( $new_value ) && isset( $options[ $new_key ] ) ) {
				$new_value = array_replace_recursive( $new_value, $options[ $new_key ] );
				mai_update_option( $new_key, $new_value );
			}
		}
	}

	// Update database version after upgrade.
	mai_update_option( 'db-version', $plugin_version );
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param $version
 *
 * @return bool|mixed
 */
function mai_get_upgrade_data( $version ) {
	$data = [
		'0.2.0' => [
			'color-darkest'  => mai_get_option( 'color-dark' ),
			'color-dark'     => mai_get_option( 'color-medium' ),
			'color-medium'   => mai_get_option( 'color-muted' ),
			'color-lighter'  => mai_get_option( 'color-light' ),
			'color-lightest' => mai_get_option( 'color-white' ),
		],
	];

	return isset( $data[ $version ] ) ? $data[ $version ] : false;
}
