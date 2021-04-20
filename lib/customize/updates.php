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

add_filter( 'genesis_customizer_theme_settings_config', 'mai_customizer_update_child_theme_settings', 5 );
/**
 * Filter the Genesis Theme Settings customizer panel settings and add our new beta update setting.
 *
 * Allows all Mai plugins to be updated to beta releases.
 *
 * @since 2.0.0
 *
 * @param array $config The existing customizer config.
 *
 * @return array The modified config.
 */
function mai_customizer_update_child_theme_settings( $config ) {
	if ( isset( $config['genesis']['sections']['genesis_updates']['controls'] ) ) {
		$config['genesis']['sections']['genesis_updates']['controls']['mai_child_theme_updates'] = [
			'label'       => __( 'Check For Mai Theme Updates', 'mai-engine' ),
			'description' => __( 'By checking this box, you allow Mai Engine to periodically check for Mai Theme updates. Update requests send information about your site including software and theme data, as well as the site’s URL and locale. See the privacy policy for more details. Please note that updating your child theme may override any customizations that you have made inside the theme files. Customizer settings will not be affected.', 'mai-engine' ),
			'section'     => 'genesis_updates',
			'type'        => 'checkbox',
			'settings'    => [
				'default' => 0,
			],
		];
	}

	return $config;
}
