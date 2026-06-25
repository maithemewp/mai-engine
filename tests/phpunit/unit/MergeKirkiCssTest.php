<?php

namespace BizBudding\MaiEngine\Tests\Unit;

use BizBudding\MaiEngine\Tests\TestCase;

require_once dirname( __DIR__, 3 ) . '/lib/customize/css-cache.php';

final class MergeKirkiCssTest extends TestCase {

	/** @return array{0: array, 1: array} */
	private function fixture(): array {
		$base = [
			'global' => [
				'.header-stuck,:root' => [ '--header-stuck-x' => '1' ],
				':root'               => [ '--title-area-padding-mobile' => '10px', '--color-primary' => 'OLD' ],
				'.header-right'       => [ '--header-right-y' => '2' ],
			],
			'@media (min-width: 1000px)' => [ ':root' => [ '--breakpoint-active' => 'lg' ] ],
		];
		$additions = [
			'global' => [
				':root' => [ '--breakpoint-md' => '1000px', '--color-primary' => 'NEW', '--heading-font-family' => 'Inter' ],
				'.is-style-altfont:where(p, span)' => [ 'font-family' => 'var(--alt-font-family)' ],
			],
		];
		return [ $base, $additions ];
	}

	public function test_kirki_root_key_is_preserved_and_first(): void {
		[ $base, $additions ] = $this->fixture();
		$root = mai_merge_kirki_css( $base, $additions )['global'][':root'];
		$this->assertSame( '--title-area-padding-mobile', array_key_first( $root ) );
	}

	public function test_mai_root_keys_all_land_in_one_root_block(): void {
		[ $base, $additions ] = $this->fixture();
		$global = mai_merge_kirki_css( $base, $additions )['global'];
		$root_selectors = array_filter( array_keys( $global ), static fn ( $k ) => ':root' === $k );
		$this->assertCount( 1, $root_selectors );
		$this->assertArrayHasKey( '--breakpoint-md', $global[':root'] );
		$this->assertArrayHasKey( '--heading-font-family', $global[':root'] );
	}

	public function test_mai_wins_on_leaf_conflict(): void {
		[ $base, $additions ] = $this->fixture();
		$root = mai_merge_kirki_css( $base, $additions )['global'][':root'];
		$this->assertSame( 'NEW', $root['--color-primary'] );
	}

	public function test_kirki_siblings_and_media_block_kept_in_place(): void {
		[ $base, $additions ] = $this->fixture();
		$merged = mai_merge_kirki_css( $base, $additions );
		$this->assertSame(
			[ '.header-stuck,:root', ':root', '.header-right' ],
			array_slice( array_keys( $merged['global'] ), 0, 3 )
		);
		$this->assertArrayHasKey( '.is-style-altfont:where(p, span)', $merged['global'] );
		$this->assertSame( [ ':root' => [ '--breakpoint-active' => 'lg' ] ], $merged['@media (min-width: 1000px)'] );
	}
}
