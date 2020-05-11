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

	$container = new \Pimple\Container();

	$container['file'] = __FILE__;

	$providers = \glob( __DIR__ . '/src/Providers/*.php' );

	foreach ( $providers as $provider ) {
		$key   = \strtolower( \basename( $provider, '.php' ) );
		$class = __NAMESPACE__ . '\\Providers\\' . \basename( $provider, '.php' );

		$container[ $key ] = function () use ( $class ) {
			return new $class();
		};
	}

	foreach ( $container->keys() as $key ) {
		if ( method_exists( $container[ $key ], 'register' ) ) {
			$container[ $key ]->register( $container );
		}

		if ( method_exists( $container[ $key ], 'add_hooks' ) ) {
			$container[ $key ]->add_hooks();
		}
	}
}
