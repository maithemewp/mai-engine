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

if ( ! apply_filters( 'mai_init_setup_wizard', true ) ) {
	return;
}

/**
 * Returns the setup wizard shared instance, or a service provider instance.
 *
 * @since 1.0.0
 *
 * @param string $service_provider
 *
 * @return array|Mai_Setup_Wizard_Service_Provider
 */
function mai_get_setup_wizard_instance( $service_provider = '' ) {
	static $container = [];

	if ( empty( $container ) ) {
		$container = [
			'admin'  => new Mai_Setup_Wizard_Admin(),
			'ajax'   => new Mai_Setup_Wizard_Ajax(),
			'demos'  => new Mai_Setup_Wizard_Demos(),
			'fields' => new Mai_Setup_Wizard_Fields(),
			'import' => new Mai_Setup_Wizard_Importer(),
			'steps'  => new Mai_Setup_Wizard_Steps(),
		];
	}

	return $service_provider && isset( $container[ $service_provider ] ) ? $container[ $service_provider ] : $container;
}

add_action( 'init', 'mai_setup_wizard_init' );
/**
 * Registers setup wizard service providers and adds hooks.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_setup_wizard_init() {
	if ( ! is_admin() || ! apply_filters( 'mai_init_setup_wizard', true ) ) {
		return;
	}

	$providers = mai_get_setup_wizard_instance();

	foreach ( $providers as $name => $provider ) {
		if ( method_exists( $providers[ $name ], 'register' ) ) {
			$providers[ $name ]->register( $providers );
		}

		if ( method_exists( $providers[ $name ], 'add_hooks' ) ) {
			$providers[ $name ]->add_hooks();
		}
	}
}

add_action( 'mai_setup_wizard_after_import_all_content', 'mai_reset_setup_wizard_options' );
/**
 * Resets setup wizard options after content is imported.
 *
 * @since 2.0.0
 *
 * @return void
 */
function mai_reset_setup_wizard_options() {
	$slug    = 'mai-setup-wizard';
	$options = get_option( $slug );

	if ( isset( $options['demo'] ) ) {
		$options['previous-demo'] = $options['demo'];
		unset( $options['demo'] );
		update_option( $slug, $options );
	}

	if ( isset( $options['theme'] ) ) {
		$options['previous-theme'] = $options['theme'];
		unset( $options['theme'] );
		update_option( $slug, $options );
	}

	// Delete importer transients.
	delete_transient( 'pt_importer_data' );
}

add_filter( 'mai_setup_wizard_menu', 'mai_setup_wizard_menu', 10, 2 );
/**
 * Add setup wizard menu item.
 *
 * @since 0.3.0
 *
 * @param array $args
 *
 * @return array
 */
function mai_setup_wizard_menu( $args ) {
	$args['parent_slug'] = 'mai-theme';
	$args['menu_slug']   = 'mai-setup-wizard';
	$args['menu_title']  = __( 'Setup Wizard', 'mai-engine' );

	return $args;
}

add_filter( 'mai_setup_wizard_demos', 'mai_setup_wizard_demos', 15, 1 );
/**
 * Get available setup wizard demos.
 *
 * @since 0.3.0
 *
 * @param $defaults
 *
 * @return array
 */
function mai_setup_wizard_demos( $defaults ) {
	$theme   = mai_get_active_theme();
	$demos   = mai_get_config( 'demos' );
	$config  = mai_get_config( 'plugins' );
	$plugins = [];

	if ( empty( $demos ) ) {
		return [];
	}

	foreach ( $demos as $demo => $id ) {
		$demo_url = "https://demo.bizbudding.com/{$theme}-{$demo}/wp-content/uploads/sites/{$id}/mai-engine/";

		foreach ( $config as $plugin ) {
			if ( in_array( $demo, $plugin['demos'], true ) ) {
				$plugins[] = $plugin;
			}
		}

		$defaults[] = [
			'name'       => ucwords( $demo ),
			'content'    => $demo_url . 'content.xml',
			'templates'  => $demo_url . 'template-parts.json',
			'customizer' => $demo_url . 'customizer.dat',
			'preview'    => "https://demo.bizbudding.com/{$theme}-{$demo}/",
			'plugins'    => $plugins,
		];
	}

	return $defaults;
}

add_action( 'mai_setup_wizard_before_steps', 'mai_setup_wizard_header_content' );
/**
 * Add wizard logo/icon.
 *
 * @since 0.3.0
 *
 * @return void
 */
function mai_setup_wizard_header_content() {
	printf( '<p class="setup-wizard-logo-wrap"><img class="setup-wizard-logo" src="%sassets/img/wizard-icon.png" alt="Mai Theme logo"></p>', mai_get_url() );
}

add_filter( 'mai_setup_wizard_steps', 'mai_setup_wizard_welcome_step_description' );
/**
 * Add additional description text to the welcome step.
 *
 * @since 0.3.0
 *
 * @return string
 */
function mai_setup_wizard_welcome_step_description( $steps ) {
	$text = __( 'Mai Theme Setup Wizard Page', 'mai-engine' );
	$link = sprintf( '<a target="_blank" rel="noopener nofollow" href="https://bizbudding.com/mai-setup-wizard/">%s</a>', $text );

	$steps['welcome']['description'] = sprintf( '%s %s %s',
		__( 'Welcome to the Mai Setup Wizard! Enter your email address in the form below to receive automatic updates, share your environment information, win free swag, receive the latest news and get special offers. To learn more about providing your email and claiming your free goodies, visit the', 'mai-engine' ),
		$link,
		__( 'on BizBudding for all the details, terms and conditions of this program.', 'mai-engine' )
	);

	return $steps;
}

add_action( 'mai_setup_wizard_email_submit', 'mai_setup_wizard_email_option' );
/**
 * Send email to subscribe user.
 *
 * @since 0.3.0
 *
 * @param string $email_address Email address.
 *
 * @return void
 */
function mai_setup_wizard_email_option( $email_address ) {
	$to          = 'subscribe-af4840f00e125c4e59953f0197daf346@subscription-serv.com';
	$subject     = 'mai setup wizard email optin';
	$message     = $email_address;
	$headers     = [];
	$attachments = [];
	$filter      = function () use ( $email_address ) {
		return $email_address;
	};

	add_filter( 'wp_mail_from', $filter );
	wp_mail( $to, $subject, $message, $headers, $attachments );
	remove_filter( 'wp_mail_from', $filter );
}

add_action( 'mai_setup_wizard_after_import', 'mai_after_setup_wizard_import' );
/**
 * Configures site settings after demo content is imported.
 *
 * @since 2.0.1
 *
 * @param string $demo Chosen demo ID.
 *
 * @return void
 */
function mai_after_setup_wizard_import( $demo ) {

	// Set nav menu locations.
	$menus     = get_theme_support( 'genesis-menus' )[0];
	$locations = [];

	foreach ( $menus as $id => $name ) {
		$name = 'Footer Menu' === $name ? $name : str_replace( ' Menu', '', $name );
		$menu = get_term_by( 'name', $name, 'nav_menu' );

		if ( $menu && isset( $menu->term_id ) ) {
			$locations[ $id ] = $menu->term_id;
		}
	}

	if ( ! empty( $locations ) ) {
		set_theme_mod( 'nav_menu_locations', $locations );
	}

	// Set static front page.
	update_option( 'show_on_front', 'page' );

	// Trash default posts and pages.
	$hello_world    = get_page_by_path( 'hello-world', OBJECT, 'post' );
	$sample_page    = get_page_by_path( 'sample-page', OBJECT, 'page' );
	$privacy_policy = get_page_by_path( 'privacy-policy', OBJECT, 'page' );

	if ( $hello_world && isset( $hello_world->ID ) ) {
		wp_delete_post( $hello_world->ID );
	}

	if ( $sample_page && isset( $sample_page->ID ) ) {
		wp_delete_post( $sample_page->ID );
	}

	if ( $privacy_policy && isset( $privacy_policy->ID ) ) {
		wp_delete_post( $privacy_policy->ID );
	}

	// Get front page title, e.g `Home - Reach Agency`.
	$home = 'Home - ' . mai_convert_case( mai_get_active_theme() . ' ' . $demo, 'title' );

	// Assign front page and posts page.
	$front = get_page_by_title( $home );
	$front = $front ?: get_page_by_title( 'Home' ); // Fallback if no home with demo name.
	$blog  = get_page_by_title( 'Blog' );
	$shop  = get_page_by_title( 'Shop' );

	if ( $front ) {
		update_option( 'page_on_front', $front->ID );
	}

	if ( $blog ) {
		update_option( 'page_for_posts', $blog->ID );
	}

	if ( $shop ) {
		update_option( 'woocommerce_shop_page_id', $shop->ID );
	}

	// Update WP Forms settings.
	$wpforms = get_option( 'wpforms_settings', [] );

	if ( ! isset( $wpforms['disable-css'] ) ) {
		$wpforms['disable-css'] = 2;

		update_option( 'wpforms_settings', $wpforms );
	}

	/**
	 * WP Rewrite object.
	 *
	 * @var WP_Rewrite $wp_rewrite WP Rewrite object.
	 */
	global $wp_rewrite;

	// Update permalink structure.
	$wp_rewrite->set_permalink_structure( '/%postname%/' );
	$wp_rewrite->flush_rules();
}
