<?php


/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param string $style
 *
 * @return array
 */
function mai_get_demo_plugin_choices( $style = '' ) {
	$choices = [];
	$demo    = $style ? $style : mai_get_chosen_demo_style();
	$plugins = mai_get_config( 'required-plugins' );

	foreach ( $plugins as $plugin ) {
		if ( in_array( $demo, $plugin['demos'], true ) ) {
			$choices[] = [
				'element' => 'input',
				'type'    => 'checkbox',
				'name'    => 'mai-step-plugins[]',
				'checked' => true,
				'id'      => $plugin['slug'],
				'value'   => $plugin['slug'],
				'label'   => $plugin['name'],
			];
		}
	}

	return $choices;
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param string $demo
 *
 * @return string
 */
function mai_get_demo_plugin_list_items( $demo ) {
	$choices = mai_get_demo_plugin_choices( $demo );
	$plugins = '';

	foreach ( $choices as $choice ) {
		$plugins .= '<li class="mai-step-field">' . mai_build_step_field( $choice ) . '</li>';
	};

	return $plugins;
}

add_filter( 'mai_plugin_dependencies', 'mai_require_genesis_connect', 10, 1 );
/**
 * Recommend Genesis Connect if WooCommerce or EDD installed.
 *
 * @since 1.0.0
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

	if ( class_exists( 'Easy_Digital_Downloads' ) ) {
		$plugins[] = [
			'name'     => 'Genesis Connect for EDD',
			'host'     => 'wordpress',
			'slug'     => 'easy-digital-downloads/easy-digital-downloads.php',
			'uri'      => 'https://wordpress.org/plugins/easy-digital-downloads/',
			'optional' => true,
		];
	}

	return $plugins;
}

add_filter( 'network_admin_plugin_action_links_mai-engine/mai-engine.php', 'mai_change_plugin_dependency_text', 100 );
add_filter( 'plugin_action_links_mai-engine/mai-engine.php', 'mai_change_plugin_dependency_text', 100 );
/**
 * Change plugin dependency text.
 *
 * @since 1.0.0
 *
 * @param array $actions Plugin action links.
 *
 * @return array
 */
function mai_change_plugin_dependency_text( $actions ) {
	$actions['required-plugin'] = sprintf(
		'<span class="network_active">%s</span>',
		__( 'Theme Dependency', 'mai-engine' )
	);

	return $actions;
}
