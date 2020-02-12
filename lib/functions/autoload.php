<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2019 BizBudding
 * @license   GPL-2.0-or-later
 */

/* @noinspection PhpUnhandledExceptionInspection */
spl_autoload_register( 'mai_autoload_register' );
/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @param $class
 *
 * @return void
 */
function mai_autoload_register( $class ) {
	$namespace = 'Mai';

	if ( strpos( $class, $namespace ) === false ) {
		return;
	}

	$class_dir  = mai_get_dir() . 'lib/classes/';
	$class_file = strtolower( str_replace( '_', '-', $class ) );

	/* @noinspection PhpIncludeInspection */
	require_once "{$class_dir}class-{$class_file}.php";
}
