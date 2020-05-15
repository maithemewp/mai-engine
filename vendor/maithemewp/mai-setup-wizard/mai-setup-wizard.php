<?php
/**
 * Mai Setup Wizard
 *
 * Plugin Name: Mai Setup Wizard
 * Plugin URI:  https: //github.com/maithemewp/mai-setup-wizard/
 * Description: Super cool setup wizard for WordPress themes.
 * Version:     0.1.0
 * Author:      Lee Anthony
 * Author URI:  https: //bizbudding.com/
 * Text Domain: mai-setup-wizard
 * License:     GPL-2.0-or-later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path: /resources/lang
 */

namespace MaiSetupWizard;

// Prevent direct file access.
\defined( 'ABSPATH' ) || die();

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return array
 */
function get_instance() {
	static $container = [];

	if ( empty( $container ) ) {
		$container = [
			'plugin' => new Plugin( __FILE__ ),
			'admin'  => new AdminProvider(),
			'ajax'   => new AjaxProvider(),
			'demo'   => new DemoProvider(),
			'field'  => new FieldProvider(),
			'import' => new ImportProvider(),
			'step'   => new StepProvider(),
		];
	}

	return $container;
}

\add_action( 'init', __NAMESPACE__ . '\\init' );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return void
 */
function init() {
	if ( ! \is_admin() ) {
		return;
	}

	\spl_autoload_register( function ( $class ) {
		if ( \strpos( $class, __NAMESPACE__ ) !== false ) {
			require_once __DIR__ . '/src' . \str_replace( '\\', DIRECTORY_SEPARATOR, \substr( $class, \strlen( __NAMESPACE__ ) ) ) . '.php';
		}
	} );

	$providers = get_instance();

	foreach ( $providers as $name => $provider ) {
		if ( \method_exists( $providers[ $name ], 'register' ) ) {
			$providers[ $name ]->register( $providers );
		}

		if ( \method_exists( $providers[ $name ], 'add_hooks' ) ) {
			$providers[ $name ]->add_hooks();
		}
	}
}
