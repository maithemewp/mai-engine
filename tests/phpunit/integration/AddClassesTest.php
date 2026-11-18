<?php

namespace BizBudding\MaiEngine\Tests\Integration;

/**
 * mai_add_classes() merges class lists without repeats or stray whitespace.
 */
class AddClassesTest extends MaiIntegrationTestCase {

	public function test_appends(): void {
		$this->assertSame( 'a b c', mai_add_classes( 'b c', 'a' ) );
	}

	public function test_array_input(): void {
		$this->assertSame( 'a b c d', mai_add_classes( [ 'b', 'c d', '' ], 'a' ) );
	}

	public function test_dedupes(): void {
		$this->assertSame( 'a b button', mai_add_classes( 'a button', 'a b' ) );
	}

	public function test_normalizes_whitespace(): void {
		$this->assertSame( 'a b c d', mai_add_classes( "c\td", " a  b\n" ) );
	}

	public function test_empty_new_returns_existing_untouched(): void {
		$this->assertSame( ' a  b ', mai_add_classes( '', ' a  b ' ) );
		$this->assertSame( ' a  b ', mai_add_classes( [], ' a  b ' ) );
		$this->assertSame( ' a  b ', mai_add_classes( null, ' a  b ' ) );
	}

	public function test_empty_existing(): void {
		$this->assertSame( 'a', mai_add_classes( 'a a', '' ) );
	}
}
