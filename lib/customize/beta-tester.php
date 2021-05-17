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

add_filter( 'genesis_customizer_theme_settings_config', 'mai_customizer_theme_settings_beta_tester' );
/**
 * Filter the Genesis Theme Settings customizer panel settings and add our new beta update setting.
 *
 * Allows all Mai plugins to be updated to beta releases.
 *
 * @since  0.1.0
 *
 * @param array $config The existing customizer config.
 *
 * @return array
 */
function mai_customizer_theme_settings_beta_tester( $config ) {
	if ( ! isset( $config['genesis']['sections']['genesis_updates']['controls'] ) ) {
		return $config;
	}

	$config['genesis']['sections']['genesis_updates']['controls']['mai_tester'] = [
		'label'       => __( 'Mai Beta Tester', 'mai-engine' ),
		'description' => __( 'Check this box to enable beta updates of all Mai plugins and add-ons.', 'mai-engine' ),
		'section'     => 'genesis_updates',
		'type'        => 'checkbox',
		'settings'    => [
			'default' => 0,
		],
	];

	return $config;
}
