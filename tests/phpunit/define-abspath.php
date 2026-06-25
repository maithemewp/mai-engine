<?php
/**
 * Define ABSPATH before Composer's autoloader runs.
 *
 * Vendored WordPress drop-ins guard their entrypoints with `defined( 'ABSPATH' ) || exit`
 * (mai-cache's init.php, loaded via autoload.files, is one). PHPUnit's Composer bin proxy
 * requires vendor/autoload.php before it reads phpunit.xml.dist or our unit bootstrap, so
 * without this the process would exit silently before any test runs. Loaded via
 * `php -d auto_prepend_file=tests/phpunit/define-abspath.php` in the composer test-unit script.
 *
 * Unit suite only: brain/monkey mocks WordPress, so a throwaway temp-dir ABSPATH is fine.
 * Do not reuse this for an integration suite that boots real WordPress.
 */
defined( 'ABSPATH' ) || define( 'ABSPATH', sys_get_temp_dir() . '/' );
