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
 * @param $demos
 *
 * @return array
 */
function mai_setup_wizard_demos( $demos ) {
	$demo_url = 'https://demo.bizbudding.com/reach-podcast/wp-content/uploads/sites/12/mai-engine/';

	$demos[] = [
		'name'       => 'Agency',
		'content'    => $demo_url . 'content.xml',
		'widgets'    => $demo_url . 'widgets.json',
		'customizer' => $demo_url . 'customizer.dat',
		'preview'    => 'https://demo.bizbudding.com/reach-agency/',
		'plugins'    => [
			[
				'name' => 'Genesis Connect for WooCommerce',
				'slug' => 'genesis-connect-woocommerce/genesis-connect-woocommerce.php',
				'uri'  => 'https://wordpress.org/plugins/genesis-connect-woocommerce/',
			],
			[
				'name' => 'Hello Dolly',
				'slug' => 'hello-dolly/hello.php',
				'uri'  => 'https://wordpress.org/plugins/hello-dolly/',
			],
			[
				'name' => 'Simple Social Icons',
				'slug' => 'simple-social-icons/simple-social-icons.php',
				'uri'  => 'https://wordpress.org/plugins/simple-social-icons/',
			],
		],
	];

	$demos[] = [
		'name'       => 'Podcast',
		'content'    => $demo_url . 'content.xml',
		'widgets'    => $demo_url . 'widgets.json',
		'customizer' => $demo_url . 'customizer.dat',
		'preview'    => 'https://demo.bizbudding.com/reach-podcast/',
		'plugins'    => [
			[
				'name' => 'Simple Social Icons',
				'slug' => 'simple-social-icons/simple-social-icons.php',
				'uri'  => 'https://wordpress.org/plugins/simple-social-icons/',
			],
			[
				'name' => 'WP Forms Lite',
				'slug' => 'wpforms-lite/wpforms.php',
				'uri'  => 'https://wordpress.org/plugins/wpforms-lite/',
			],
		],
	];

	$demos[] = [
		'name'       => 'Local',
		'content'    => $demo_url . 'content.xml',
		'widgets'    => $demo_url . 'widgets.json',
		'customizer' => $demo_url . 'customizer.dat',
		'preview'    => 'https://demo.bizbudding.com/reach-local/',
		'plugins'    => [
			[
				'name' => 'Genesis Connect for EDD',
				'slug' => 'genesis-connect-edd/genesis-connect-edd.php',
				'uri'  => 'https://wordpress.org/plugins/genesis-connect-edd/',
			],
			[
				'name' => 'Genesis Widget Column Classes',
				'slug' => 'genesis-widget-column-classes/genesis-widget-column-classes.php',
				'uri'  => 'https://wordpress.org/plugins/genesis-widget-column-classes/',
			],
		],
	];

	return $demos;
}
