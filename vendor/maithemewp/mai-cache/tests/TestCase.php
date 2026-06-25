<?php
namespace Mai\Cache\Tests;

use Brain\Monkey;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase {
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		if ( method_exists( \Mai\Cache\Cache::class, 'reset_runtime' ) ) {
			\Mai\Cache\Cache::reset_runtime();
		}
		Monkey\tearDown();
		parent::tearDown();
	}
}
