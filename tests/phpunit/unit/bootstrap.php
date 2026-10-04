<?php
// tests/phpunit/unit/bootstrap.php
// Unit suite: no WordPress, no DB. brain/monkey mocks WP functions.

// ABSPATH must be defined before any lib/classes file loads. Every one of them starts with
// `defined( 'ABSPATH' ) || die`, and without ABSPATH the process exits with status 0 and no
// output at all, which reads as "no tests ran" rather than as an error. The root autoloader
// required below no longer loads a guarded file: its files-autoload is mai-package-loader's
// init.php and plugin-update-checker, and mai-cache 0.6 dropped the guard from its classes.
// tests/vendor/autoload.php has its own files-autoloader, but nothing in it is
// ABSPATH-guarded, which is why the phpunit bin proxy can load safely.
defined( 'ABSPATH' ) || define( 'ABSPATH', sys_get_temp_dir() . '/' );

require_once dirname( __DIR__, 3 ) . '/vendor/autoload.php';
require_once dirname( __DIR__, 2 ) . '/TestCase.php';

// Mirror mai-engine's runtime autoloader so Mai_* classes load on demand from
// lib/classes/ without booting WordPress. On-demand means this bootstrap does
// not depend on any one class file existing yet (so the harness runs before the
// optimizer class is written).
spl_autoload_register( function ( string $class ): void {
	if ( ! str_starts_with( $class, 'Mai_' ) ) {
		return;
	}
	$name = strtolower( str_replace( '_', '-', $class ) );
	$file = dirname( __DIR__, 3 ) . "/lib/classes/class-{$name}.php";
	if ( is_readable( $file ) ) {
		require_once $file;
	}
} );
