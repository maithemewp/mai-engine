<?php
// Define ABSPATH before composer autoload runs init.php (its files guard on it).
// In normal test runs this is set via auto_prepend_file; this define is a fallback.
defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/' );

require dirname( __DIR__ ) . '/vendor/autoload.php';
