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

add_filter( 'mai_plugin_dependencies', 'mai_engine_plugin_dependencies' );
/**
 * Show recommended plugins, in case setup wizard wasn't run.
 *
 * Note: Currently no way to only recommend plugin by chosen demo, since
 * we need to run this function even if the setup wizard was not run.
 * Workaround is to only recommend plugins required by all demos.
 * Uses the WP Dependency Installer filter in the child theme.
 *
 * @since 1.0.0
 *
 * @param array $dependencies Plugin dependencies.
 *
 * @return array
 */
function mai_engine_plugin_dependencies( $dependencies ) {
	$setup_wizard_options = get_option( 'mai-setup-wizard', [] );

	// Return early if setup wizard was run.
	if ( isset( $setup_wizard_options['demo'] ) ) {
		return $dependencies;
	}

	$plugins     = mai_get_config( 'plugins' );
	$total_demos = count( mai_get_config( 'demos' ) );

	foreach ( $plugins as $plugin ) {
		$plugin_demos = count( $plugin['demos'] );

		if ( $total_demos === $plugin_demos && ! is_plugin_active( $plugin['slug'] ) ) {
			$plugin['host'] = isset( $plugin['host'] ) ? $plugin['host'] : 'wordpress';
			$dependencies[] = $plugin;
		}
	}

	return $dependencies;
}
