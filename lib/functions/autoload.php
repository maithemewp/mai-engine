<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2020 BizBudding
 * @license   GPL-2.0-or-later
 */

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/* @noinspection PhpUnhandledExceptionInspection */
spl_autoload_register( 'mai_autoload_register' );
/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @param string $class Class to check.
 *
 * @return void
 */
function mai_autoload_register( $class ) {
	$namespace = 'Mai_';

	if ( strpos( $class, $namespace ) === false ) {
		return;
	}

	$dir  = mai_get_dir() . 'lib/classes/';
	$name = strtolower( str_replace( '_', '-', $class ) );
	$file = "{$dir}class-{$name}.php";

	if ( file_exists( $file ) ) {
		/* @noinspection PhpIncludeInspection */
		require_once $file;
	}
}
