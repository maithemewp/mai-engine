<?php

namespace BizBudding\MaiEngine\Tests\Unit;

use BizBudding\MaiEngine\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once dirname( __DIR__, 3 ) . '/lib/functions/utilities.php';

/**
 * Characterization of mai_get_dom_document() + mai_get_dom_html().
 *
 * This does NOT assert correct behavior. It asserts CURRENT behavior, defects included, so
 * that changing the encoding round trip becomes a measured comparison instead of a third
 * guess. Two prior attempts were reverted after non-English content broke.
 *
 * Several goldens record behavior that is actively wrong: escaped markup becoming live
 * markup (g3) and JSON data attributes broken out of their own quotes (g7). Those are
 * pinned so a fix shows up as a deliberate diff, not endorsed. See the security finding in
 * docs/superpowers/specs/2026-07-30-wordpress-phpunit-suite-design.md.
 *
 * Regenerate goldens with: php tests/phpunit/unit/fixtures/generate.php
 */
final class DomEncodingTest extends TestCase {

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function encodingCases(): array {
		$cases = [];

		foreach ( require __DIR__ . '/fixtures/encoding.php' as $key => $case ) {
			$cases[ $key ] = [ $case['in'], $case['out'] ];
		}

		return $cases;
	}

	#[DataProvider( 'encodingCases' )]
	public function test_round_trip_matches_golden( string $in, string $expected ): void {
		$this->assertSame( $expected, mai_get_dom_html( mai_get_dom_document( $in ) ) );
	}

	/**
	 * Guards against a group being dropped during a future edit. g2 in particular is the
	 * entire divergence surface between the current implementation and candidate A, so a
	 * fixture without it would report "no change" for a migration that does change things.
	 */
	public function test_fixture_covers_every_group(): void {
		$keys = array_keys( require __DIR__ . '/fixtures/encoding.php' );

		foreach ( [ 'g1_', 'g2_', 'g3_', 'g4_', 'g5_', 'g6_', 'g7_' ] as $group ) {
			$this->assertNotEmpty(
				array_filter( $keys, static fn ( $key ) => str_starts_with( $key, $group ) ),
				sprintf( 'Fixture group %s is empty.', $group )
			);
		}
	}
}
