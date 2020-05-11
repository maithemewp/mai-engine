<?php

add_filter( 'mai_setup_wizard_menu', 'mai_setup_wizard_menu', 10, 2 );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param array $args
 *
 * @return array
 */
function mai_setup_wizard_menu( $args ) {
	$args['parent_slug'] = mai_get_handle();
	$args['menu_slug']   = 'mai-demo-import';

	return $args;
}

add_filter( 'mai_setup_wizard_demos', 'mai_setup_wizard_demos', 15, 1 );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param $defaults
 *
 * @return array
 */
function mai_setup_wizard_demos( $defaults ) {
	$theme  = mai_get_active_theme();
	$demos  = mai_get_theme_demos();
	$config = mai_get_config( 'required-plugins' );

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
			'widgets'    => $demo_url . 'widgets.json',
			'customizer' => $demo_url . 'customizer.dat',
			'preview'    => "https://demo.bizbudding.com/{$theme}-{$demo}/",
			'plugins'    => $plugins,
		];
	}


	return $defaults;
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return bool|mixed
 */
function mai_get_theme_demos() {
	$transient = mai_get_handle() . '-demos';
	$body      = get_transient( $transient );

	if ( ! $body ) {
		$theme    = mai_get_active_theme();
		$endpoint = 'https://demo.bizbudding.com/sparkle-creative/wp-json/mai-demo-exporter/v2/sites/?theme=' . $theme;
		$request  = wp_remote_get( $endpoint );

		if ( is_wp_error( $request ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $request );
		set_transient( $transient, $body, 28800 );
	}

	return json_decode( $body, true );
}
