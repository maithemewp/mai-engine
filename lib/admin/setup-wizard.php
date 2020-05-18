<?php

add_filter( 'mai_setup_wizard_menu', 'mai_setup_wizard_menu', 10, 2 );
/**
 * Description of expected behavior.
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
 * Description of expected behavior.
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
			'widgets'    => $demo_url . 'widgets.json',
			'customizer' => $demo_url . 'customizer.dat',
			'preview'    => "https://demo.bizbudding.com/{$theme}-{$demo}/",
			'plugins'    => $plugins,
		];
	}

	return $defaults;
}
