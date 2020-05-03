<?php

/**
 * Returns data store of setup wizard step objects.
 *
 * @since 1.0.0
 *
 * @param string $id
 * @param object $step
 *
 * @return array
 */
function mai_setup_wizard_steps( $id = '', $step = null ) {
	static $steps = [];

	if ( $id && $step && ! array_key_exists( $id, $steps ) ) {
		$steps[ $id ] = $step;
	}

	return $steps;
}

add_action( 'after_setup_theme', 'mai_add_setup_wizard_steps' );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_add_setup_wizard_steps() {
	$steps = apply_filters( 'mai_setup_wizard_steps', [
		[
			'id'              => 'welcome',
			'title'           => __( 'Welcome', 'mai-engine' ),
			'description'     => __( 'Welcome to the Mai Setup Wizard! Enter your email address in the form below to get started.', 'mai-engine' ),
			'order'           => 10,
			'error_message'   => __( 'Please enter a valid email address.', 'mai-engine' ),
			'success_message' => __( 'Success!', 'mai-engine' ),
			'continue_text'   => __( 'Continue', 'mai-engine' ),
			'fields'          => [
				[
					'element'     => 'input',
					'type'        => 'email',
					'name'        => 'mai-step-email',
					'class'       => 'mai-email-address',
					'placeholder' => __( 'Email address', 'mai-engine' ),
				],
			],
		],
		[
			'id'              => 'style',
			'title'           => __( 'Style', 'mai-engine' ),
			'description'     => __( 'Please select your site style below.', 'mai-engine' ),
			'order'           => 20,
			'error_message'   => __( 'Please select a site style to continue.', 'mai-engine' ),
			'success_message' => __( 'Good choice!', 'mai-engine' ),
			'continue_text'   => __( 'Continue', 'mai-engine' ),
			'fields'          => mai_get_demo_style_choices(),
		],
		[
			'id'              => 'plugins',
			'title'           => __( 'Plugins', 'mai-engine' ),
			'description'     => __( 'The following plugins will be installed:', 'mai-engine' ),
			'order'           => 30,
			'error_message'   => __( 'Plugins could not be installed.', 'mai-engine' ),
			'success_message' => __( 'Plugins successfully installed!', 'mai-engine' ),
			'continue_text'   => __( 'Install Plugins', 'mai-engine' ),
			'fields'          => mai_get_demo_plugin_choices(),
		],
		[
			'id'              => 'content',
			'title'           => __( 'Content', 'mai-engine' ),
			'description'     => __( 'Select which content you would like to import. Please note that this step can take up to 5 minutes.', 'mai-engine' ),
			'order'           => 40,
			'error_message'   => __( 'Content could not be installed.', 'mai-engine' ),
			'success_message' => __( 'Content successfully installed!', 'mai-engine' ),
			'continue_text'   => __( 'Import Content', 'mai-engine' ),
			'fields'          => mai_get_demo_content_choices(),
		],
		[
			'id'            => 'done',
			'title'         => __( 'Done', 'mai-engine' ),
			'description'   => sprintf(
				'%s </p><p class="mai-continue-wrap"><a href="%s" target="_blank" class="button button-primary button-hero">%s →</a>',
				__( 'Your theme has been all set up.', 'mai-engine' ),
				home_url(),
				__( 'View your site', 'mai-engine' )
			),
			'order'         => 50,
			'continue_text' => __( 'View Your Site', 'mai-engine' ),
			'fields'        => [
				[
					'element' => 'input',
					'type'    => 'hidden',
					'name'    => 'mai-step-done',
				],
			],
		],
	] );

	usort( $steps, function ( $a, $b ) {
		return $a['order'] - $b['order'];
	} );

	foreach ( $steps as $step ) {
		mai_add_setup_wizard_step( $step['id'], $step );
	}
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param string $id   Step ID.
 * @param array  $data Step data.
 *
 * @return void
 */
function mai_add_setup_wizard_step( $id, $data = [] ) {
	$step = new stdClass();
	$data = wp_parse_args( $data, [
		'id'              => mai_convert_case( $id, 'kebab' ),
		'title'           => mai_convert_case( $id, 'title' ),
		'description'     => mai_convert_case( $id, 'sentence' ),
		'priority'        => 10,
		'error_message'   => false,
		'success_message' => false,
		'continue_text'   => __( 'Continue', 'mai-engine' ),
		'fields'          => [],
		'ajax_callback'   => "mai_{$id}_step_ajax",
	] );

	foreach ( $data as $key => $value ) {
		$step->{$key} = $value;
	}

	mai_setup_wizard_steps( $step->id, $step );
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param object $step
 *
 * @return stdClass|null
 */
function mai_get_setup_wizard_step( $step ) {
	$steps = mai_setup_wizard_steps();

	return isset( $steps[ $step->id ] ) ? $steps[ $step->id ] : null;
}

add_action( 'mai_before_demo_import', 'mai_before_demo_import' );
/**
 * Runs before demo import (always runs on plugin activation).
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_before_demo_import() {

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
}

add_action( 'mai_after_demo_import', 'mai_after_demo_import' );
/**
 * Set default pages after demo import.
 *
 * Automatically creates and sets the Static Front Page and the Page for Posts
 * upon theme activation, only if these pages don't already exist and only
 * if the site does not already display a static page on the homepage.
 *
 * @since  1.0.0
 *
 * @return void
 */
function mai_after_demo_import() {

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

	// Assign front page and posts page.
	$home = get_page_by_title( apply_filters( 'mai_home_page_title', 'Home' ) );
	$blog = get_page_by_title( apply_filters( 'mai_blog_page_title', 'Blog' ) );
	$shop = get_page_by_title( apply_filters( 'mai_shop_page_title', 'Shop' ) );

	if ( $home ) {
		update_option( 'page_on_front', $home->ID );
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
