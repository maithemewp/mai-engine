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
			$plugin['host'] = isset( $plugin['host'] ) ? $plugin['host'] : 'WordPress';
			$dependencies[] = $plugin;
		}
	}

	return $dependencies;
}

add_action( 'after_setup_theme', 'mai_deactivate_bundled_plugins' );
/**
 * Deactivate plugins that are bundled as dependencies.
 *
 * @since 2.1.1
 *
 * @return void
 */
function mai_deactivate_bundled_plugins() {
	$plugins = [
		'advanced-custom-fields/acf.php',
		'advanced-custom-fields-master/acf.php',
		'advanced-custom-fields-pro/acf.php',
		'advanced-custom-fields-pro-master/acf.php',
		'kirki/kirki.php',
		'kirki-master/kirki.php',
	];

	$deactivated = [];

	foreach ( $plugins as $plugin ) {
		if ( is_plugin_active( $plugin ) ) {
			deactivate_plugins( $plugin );
			$deactivated[] = $plugin;
		}
	}

	if ( $deactivated && isset( $_GET['activate'] ) && sanitize_text_field( $_GET['activate'] ) ) {
		add_action(
			'admin_notices',
			function () use ( $deactivated ) {
				echo '<style>.acf-deactivated + .updated{display:none}</style>';
				echo '<div class="notice notice-warning acf-deactivated">';
				foreach ( $deactivated as $plugin ) {
					$plugin_dir  = explode( DIRECTORY_SEPARATOR, $plugin );
					$plugin_name = mai_convert_case( $plugin_dir[0], 'title' );

					printf(
						'<p>%s %s</p>',
						$plugin_name,
						__( ' is bundled with Mai Engine and has been deactivated.', 'mai-engine' )
					);
				}
				echo '</div>';
			}
		);
	}
}
