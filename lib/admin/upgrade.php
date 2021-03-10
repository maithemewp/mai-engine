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
 * Run setting upgrades during engine update.
 *
 * @since 0.2.0
 *
 * @return void
 */
function mai_do_upgrade() {
	$plugin_version = mai_get_version();

	// Set first version.
	if ( false === mai_get_option( 'first-version', false ) ) {

		/**
		 * Force 1.0.0 on existing installs prior to 2.0.0, to trigger upgrade.
		 *
		 * @link https://github.com/maithemewp/mai-engine/issues/170#issuecomment-654411831
		 */
		$first_version = false !== mai_get_options() ? '1.0.0' : $plugin_version;
	}

	$db_version = mai_get_option( 'db-version', false );

	// Return early if at latest.
	if ( $plugin_version === $db_version ) {
		return;
	}

	// Only run upgrades if we have an existing version.
	if ( $db_version ) {

		if ( version_compare( $db_version, '0.2.0', '<' ) ) {
			mai_upgrade_0_2_0();
		}

		if ( version_compare( $db_version, '2.0.1', '<' ) ) {
			mai_upgrade_2_0_1();
		}

		if ( version_compare( $db_version, '2.11.0', '<' ) ) {
			$success = mai_upgrade_2_11_0();

			if ( ! $success ) {
				return;
			}
		}
	}

	// Update database version after upgrade.
	mai_update_option( 'db-version', $plugin_version );
}

/**
 * Upgrade function for 2.11.0.
 *
 * @since 2.11.0
 *
 * @return void
 */
function mai_upgrade_2_11_0() {
	$success = true;
	$message = '';
	$posts   = new WP_Query(
		[
			'post_type'              => 'wp_template_part',
			'posts_per_page'         => -1,
			'post_status'            => 'any',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'suppress_filters'       => false, // https://github.com/10up/Engineering-Best-Practices/issues/116
		]
	);

	if ( $posts->have_posts() ) {
		while ( $posts->have_posts() ) : $posts->the_post();
			global $post;
			$post->post_type = 'mai_template_part';
			$post->guid      = str_replace( 'wp_template_part', 'mai_template_part', $post->guid );
			$post_id         = wp_insert_post( $post );

			if ( is_wp_error( $post_id ) ) {
				$success = false;
				$message = $post_id->get_error_message();
				break;
			}
		endwhile;
	}
	wp_reset_postdata();

	$notice = '';

	if ( $success ) {
		delete_transient( 'mai_template_parts' );
		delete_transient( 'mai_demo_template_parts' );
		flush_rewrite_rules( false );
		$text   = __( 'Template Parts successfully updated for compatibility with WP 5.7+.', 'mai-engine' );
		$notice = sprintf( '<div class="notice notice-success is-dismissable"><p>%s</p></div>', $text );

	} else {
		$text   = __( 'Error migrating template parts.', 'mai-engine' );
		$text   = $message ? $text . ' ' . $message : $text;
		$notice = sprintf(
			'<div class="notice notice-error"><p>%s <a target="_blank" href="https://docs.bizbudding.com/support/">%s</a>.</p></div>',
			$text,
			__( 'Please contact BizBudding support.', 'mai-engine' )
		);
	}

	if ( $notice ) {
		add_action( 'admin_notices', function() use ( $notice ) {
			echo $notice;
		});
	}

	return $success;
}

/**
 * Upgrade function for 2.0.1.
 *
 * @since 2.0.1
 *
 * @return void
 */
 function mai_upgrade_2_0_1() {
	$boxed_container = current_theme_supports( 'boxed-container' );
	$site_layouts    = mai_get_option( 'site-layouts' );

	if ( $site_layouts && is_array( $site_layouts ) && isset( $site_layouts['default']['boxed-container'] ) ) {
		$boxed_container = $site_layouts['default']['boxed-container'];
	}

	/**
	 * The very first installs of Mai Engine already have a 'boxed-container' setting saved, but unused.
	 * We need to manually override this one without an isset() check.
	 */
	mai_update_option( 'boxed-container', $boxed_container );

	$colors = mai_get_default_colors();

	$data = [
		'color-background' => mai_get_option( 'lightest', $colors['background'] ),
		'color-alt'        => mai_get_option( 'lighter', $colors['alt'] ),
		'color-body'       => mai_get_option( 'dark', $colors['body'] ),
		'color-heading'    => mai_get_option( 'darkest', $colors['heading'] ),
		'color-link'       => mai_get_option( 'primary', $colors['link'] ),
		'color-primary'    => mai_get_option( 'primary', $colors['primary'] ),
		'color-secondary'  => mai_get_option( 'secondary', $colors['secondary'] ),
	];

	mai_update_data( $data );
}

/**
 * Upgrade function for 0.2.0.
 *
 * @since 0.2.0
 *
 * @return void
 */
function mai_upgrade_0_2_0() {
	$data = [
		'color-darkest'  => mai_get_option( 'color-dark' ),
		'color-dark'     => mai_get_option( 'color-medium' ),
		'color-medium'   => mai_get_option( 'color-muted' ),
		'color-lighter'  => mai_get_option( 'color-light' ),
		'color-lightest' => mai_get_option( 'color-white' ),
	];

	mai_update_data( $data );
}

/**
 * Update data during engine update.
 *
 * @since 2.0.0
 *
 * @param array $data Data array.
 *
 * @return void
 */
function mai_update_data( $data = [] ) {

	// Run the upgrade if data to upgrade.
	$options = mai_get_options();

	if ( $data ) {

		// Add default values for new options.
		foreach ( $data as $new_key => $new_value ) {

			// Must use isset instead of true/false.
			if ( ! isset( $options[ $new_key ] ) ) {

				// Handle nested options.
				if ( is_array( $new_value ) ) {
					$new_value = array_replace_recursive( $new_value, $options[ $new_key ] );
				}

				mai_update_option( $new_key, $new_value );
			}
		}
	}
}
