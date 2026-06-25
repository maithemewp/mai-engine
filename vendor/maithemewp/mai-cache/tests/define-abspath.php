<?php
/**
 * Auto-prepend file for the test suite.
 *
 * Defines ABSPATH so that the guard in init.php (`defined('ABSPATH') || exit`)
 * passes when Composer's autoloader runs autoload.files. Without this definition,
 * init.php exits the process before phpunit can load its own config or bootstrap.
 *
 * Wired in via the `composer test-unit` script using PHP's `-d auto_prepend_file`
 * option, which applies this file before vendor/autoload.php is required. It must
 * not be referenced as a phpunit <php><const> entry because that is applied after
 * Composer autoload has already run, which is too late.
 */
defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/' );
