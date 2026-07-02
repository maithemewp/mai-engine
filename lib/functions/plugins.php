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

add_action( 'wp_enqueue_scripts', 'mai_remove_simple_social_icons_css', 15 );
/**
 * Remove Simple Social Icons CSS.
 *
 * @since 2.4.0
 *
 * @return void
 */
function mai_remove_simple_social_icons_css() {
	mai_deregister_asset( 'simple-social-icons-font' );
}

add_action( 'after_setup_theme', 'mai_deactivate_bundled_plugins' );
/**
 * Deactivates standalone ACF and Kirki plugins that Mai Engine bundles.
 *
 * Mai bundles ACF Pro and Kirki and loads them on plugins_loaded, but only when
 * no other copy is present. A standalone free ACF (or an older standalone ACF
 * Pro) loads first and defines the ACF class, so the bundled ACF Pro then bails
 * and every Mai ACF block renders with no wrapper. Running here on all requests,
 * not just admin, means a front-end-only visit still clears the conflict and the
 * site self-heals on the next load.
 *
 * @since 2.1.1
 * @since 2.40.0 Moved out of the admin-only dependencies file so it runs everywhere.
 *
 * @return void
 */
function mai_deactivate_bundled_plugins() {
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

	$conflicts = array_merge( mai_needs_mai_acf_pro() ? $acfs : [], $kirkis );

	// Check the stored active plugins first so front-end requests bail before loading plugin.php.
	$active = array_merge(
		(array) get_option( 'active_plugins', [] ),
		array_keys( (array) get_site_option( 'active_sitewide_plugins', [] ) )
	);

	if ( ! array_intersect( $conflicts, $active ) ) {
		return;
	}

	if ( ! function_exists( 'deactivate_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$deactivated = [];

	foreach ( $conflicts as $plugin ) {
		if ( is_plugin_active( $plugin ) ) {
			deactivate_plugins( $plugin );
			$deactivated[] = $plugin;
		}
	}

	if ( ! $deactivated ) {
		return;
	}

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
