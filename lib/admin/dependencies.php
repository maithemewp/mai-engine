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

add_action( 'after_setup_theme', 'mai_load_dependencies' );
/**
 * Loads engine plugin dependencies.
 * This can't be added via `mai_plugin_dependencies` filter
 * because the `wp_dependency_dismiss_label` doesn't work correctly that way.
 *
 * @since 2.14.0
 * @since TBD Registered config via PHP.
 *
 * @return void
 */
function mai_load_dependencies() {
	if ( ! class_exists( 'WP_Dependency_Installer' ) ) {
		return;
	}

	$config = [
		[
			'name'     => 'Mai Icons',
			'host'     => 'github',
			'slug'     => 'mai-icons/mai-icons.php',
			'uri'      => 'maithemewp/mai-icons',
			'branch'   => 'master',
			'required' => false,
		]
	];

	WP_Dependency_Installer::instance( dirname( dirname( __DIR__ ) ) )->register( $config )->run();
}

add_filter( 'mai_plugin_dependencies', 'mai_engine_plugin_dependencies' );
/**
 * Show theme recommended plugins, in case setup wizard wasn't run.
 *
 * Note: Currently no way to only recommend plugin by chosen demo, since
 * we need to run this function even if the setup wizard was not run.
 * Workaround is to only recommend plugins required by all demos.
 * Uses the WP Dependency Installer filter in the child theme.
 *
 * @since 0.1.0
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

	$plugins     = mai_get_config_plugins();
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

add_filter( 'mai_plugin_dependencies', 'mai_require_genesis_connect', 10, 1 );
/**
 * Recommend Genesis Connect if WooCommerce is installed.
 *
 * @since 0.1.0
 *
 * @param array $plugins List of plugin dependencies.
 *
 * @return array
 */
function mai_require_genesis_connect( $plugins ) {
	if ( class_exists( 'WooCommerce' ) ) {
		$plugins[] = [
			'name'     => 'Genesis Connect for WooCommerce',
			'host'     => 'wordpress',
			'slug'     => 'genesis-connect-woocommerce/genesis-connect-woocommerce.php',
			'uri'      => 'https://wordpress.org/plugins/genesis-connect-woocommerce/',
			'optional' => true,
		];
	}

	return $plugins;
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

	if ( $deactivated ) {
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
