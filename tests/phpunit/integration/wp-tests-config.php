<?php
/**
 * Config for the WordPress-loaded integration suite.
 *
 * WARNING: the WordPress test bootstrap DROPS the WordPress core tables carrying the
 * configured $table_prefix in this database, on every run. It does not drop every table,
 * but pointing this at a real site's database with a matching prefix destroys that site's
 * content. Keep the dedicated database name.
 */

// roots/wordpress-no-content installs here because it declares no dependency on a
// wordpress-core installer plugin, so composer falls back to vendor/<vendor>/<name> and
// ignores extra.wordpress-install-dir entirely. See tests/composer.json.
define( 'ABSPATH', dirname( __DIR__, 2 ) . '/vendor/roots/wordpress-no-content/' );

define( 'DB_NAME', getenv( 'WP_TESTS_DB_NAME' ) ?: 'mai_engine_tests' );
define( 'DB_USER', getenv( 'WP_TESTS_DB_USER' ) ?: 'root' );
define( 'DB_PASSWORD', getenv( 'WP_TESTS_DB_PASS' ) ?: '' );
define( 'DB_HOST', getenv( 'WP_TESTS_DB_HOST' ) ?: '127.0.0.1' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

// Must be a plain local variable, not a constant. The wp-phpunit shim reads it as one.
$table_prefix = 'wptests_';

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Mai Engine Tests' );

// PHP_BINARY, not the string 'php'. The bootstrap shells out to run install.php, and with a
// CI matrix spanning several PHP versions the string form would install WordPress under
// whatever `php` resolves to in PATH rather than the interpreter running the tests.
//
// escapeshellarg() because the bootstrap concatenates this constant into system() unquoted
// (includes/bootstrap.php:261) while escaping only the arguments after it. Herd installs PHP
// at "~/Library/Application Support/Herd/bin/php84", so the bare path splits on the space
// and the install fails with "sh: /Users/you/Library/Application: No such file or
// directory". Pre-quoting here is what the bootstrap's own concatenation expects.
define( 'WP_PHP_BINARY', escapeshellarg( PHP_BINARY ) );

define( 'WP_DEBUG', true );
