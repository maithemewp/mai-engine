<?php
/**
 * Bootstrap for the WordPress-loaded integration suite.
 *
 * Run it with: composer test-integration
 */

$tests_root = dirname( __DIR__, 2 );

// Must come first. This autoloader's files-autoload includes wp-phpunit's __loaded.php,
// which is what sets WP_PHPUNIT__DIR.
require_once $tests_root . '/vendor/autoload.php';

putenv( 'WP_PHPUNIT__TESTS_CONFIG=' . __DIR__ . '/wp-tests-config.php' );

$wp_phpunit_dir = getenv( 'WP_PHPUNIT__DIR' );

if ( ! $wp_phpunit_dir ) {
	fwrite( STDERR, "WP_PHPUNIT__DIR is not set. Run: composer test-setup\n" );
	exit( 1 );
}

// Defines tests_add_filter(), which must exist before it is called below. wp-phpunit's own
// bootstrap requires this file again, but with require_once, so there is no conflict.
require_once $wp_phpunit_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function () {
		require_once __DIR__ . '/plugin-loader.php';
	}
);

require $wp_phpunit_dir . '/includes/bootstrap.php';
