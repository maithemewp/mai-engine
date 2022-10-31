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
 * Shows theme recommended plugins, in case setup wizard wasn't run.
 *
 * Note: Currently no way to only recommend plugin by chosen demo, since
 * we need to run this function even if the setup wizard was not run.
 * Workaround is to only recommend plugins required by all demos.
 *
 * @since 2.14.0
 * @since 2.19.0 Registered config via PHP.
 *
 * @return void
 */
function mai_load_dependencies() {
	if ( ! class_exists( 'WP_Dependency_Installer' ) ) {
		return;
	}

	if ( ! ( is_admin() && current_user_can( 'install_plugins' ) ) ) {
		return;
	}

	$dependencies = mai_get_plugin_dependencies();

	if ( ! $dependencies ) {
		return;
	}

	WP_Dependency_Installer::instance( dirname( dirname( __DIR__ ) ) )->register( $dependencies )->run();
}

/**
 * Gets engine plugin dependencies.
 *
 * @since 2.19.0
 *
 * @return array
 */
function mai_get_plugin_dependencies() {
	$dependencies         = [];
	$plugins              = mai_get_config_plugins();
	$setup_wizard_options = get_option( 'mai-setup-wizard', [] );

	// Only if setup wizard was not run.
	if ( ! ( $setup_wizard_options && isset( $setup_wizard_options['demo'] ) ) ) {

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}

		$total_demos = count( mai_get_config( 'demos' ) );

		foreach ( $plugins as $plugin ) {
			$demos        = isset( $plugin['demos'] ) ? $plugin['demos'] : [];
			$plugin_demos = count( $demos );

			if ( $total_demos === $plugin_demos && ! is_plugin_active( $plugin['slug'] ) ) {
				$plugin['host'] = isset( $plugin['host'] ) ? $plugin['host'] : 'WordPress';
				$dependencies[] = $plugin;
			}
		}
	}

	// Gets required plugins.
	foreach( $plugins as $plugin ) {
		if ( ! ( isset( $plugin['required'] ) || isset( $plugin['optional'] ) ) ) {
			continue;
		}

		if ( isset( $plugin['required'] ) && ! $plugin['required'] ) {
			continue;
		}

		if ( isset( $plugin['optional'] ) && $plugin['optional'] ) {
			continue;
		}

		$dependencies[] = $plugin;
	}

	// Handles WooCommerce dependencies.
	if ( class_exists( 'WooCommerce' ) || isset( $dependencies['slug']['woocommerce/woocommerce.php'] ) ) {
		$dependencies[] = [
			'name'     => 'Genesis Connect for WooCommerce',
			'host'     => 'wordpress',
			'slug'     => 'genesis-connect-woocommerce/genesis-connect-woocommerce.php',
			'uri'      => 'https://wordpress.org/plugins/genesis-connect-woocommerce/',
			'optional' => true,
		];
	}

	$dependencies = apply_filters( 'mai_plugin_dependencies', $dependencies );

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
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
	}

	$acfs = [
		'advanced-custom-fields/acf.php',
		'advanced-custom-fields-master/acf.php',
		'advanced-custom-fields-pro/acf.php',
		'advanced-custom-fields-pro-master/acf.php',
	];

	$kirkis = [
		'kirki/kirki.php',
		'kirki-master/kirki.php',
	];

	$deactivated = [];

	if ( mai_needs_mai_acf_pro() ) {
		foreach ( $acfs as $plugin ) {
			if ( is_plugin_active( $plugin ) ) {
				deactivate_plugins( $plugin );
				$deactivated[] = $plugin;
			}
		}
	}

	foreach ( $kirkis as $plugin ) {
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

/**
 * Modifies dependency installer labels.
 *
 * @since 0.1.0
 *
 * @param string $label  The label text.
 * @param string $source The dependency manager source.
 *
 * @return string
 */
add_filter( 'wp_dependency_dismiss_label', 'mai_dependencey_dismiss_label', 10, 2 );
function mai_dependencey_dismiss_label( $label, $source ) {
	if ( basename( __DIR__ ) !== $source ) {
		return $label;
	}

	return mai_get_name();
}

/**
 * Disables dependency manager "Required by" text.
 *
 * @since 0.1.0
 *
 * @return bool
 */
add_filter( 'wp_dependency_required_row_meta', '__return_false' );

add_filter( 'network_admin_plugin_action_links_mai-engine/mai-engine.php', 'mai_change_plugin_dependency_text', 100 );
add_filter( 'plugin_action_links_mai-engine/mai-engine.php', 'mai_change_plugin_dependency_text', 100 );
/**
 * Changes plugin dependency text.
 *
 * @since 0.1.0
 *
 * @param array $actions Plugin action links.
 *
 * @return array
 */
function mai_change_plugin_dependency_text( $actions ) {
	$actions['required-plugin'] = sprintf(
		'<span class="network_active">%s</span>',
		__( 'Mai Theme Dependency', 'mai-engine' )
	);

	return $actions;
}
