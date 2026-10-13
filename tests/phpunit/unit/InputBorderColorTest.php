<?php
declare(strict_types=1);

namespace BizBudding\MaiEngine\Tests\Unit;

use Brain\Monkey\Functions;
use BizBudding\MaiEngine\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * mai_get_input_border_color(): the body color blended into the background until the
 * border reaches 3:1 contrast with it.
 *
 * Each test runs in its own process, because the color is cached per request.
 */
#[RunTestsInSeparateProcesses]
final class InputBorderColorTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		$root = dirname( __DIR__, 3 );

		require_once $root . '/packages/kirki/lib/class-aricolor.php';
		require_once $root . '/lib/functions/colors.php';

		Functions\when( 'mai_get_color_value' )->returnArg();
		Functions\when( 'sanitize_hex_color' )->returnArg();
		Functions\when( 'maybe_hash_hex_color' )->returnArg();
	}

	/**
	 * Gets the WCAG contrast ratio of two hex colors.
	 *
	 * @param string $a A hex color.
	 * @param string $b A hex color.
	 *
	 * @return float
	 */
	private function ratio( string $a, string $b ): float {
		$lum = function ( string $hex ): float {
			$rgb = array_map( fn( $c ) => hexdec( $c ) / 255, str_split( ltrim( $hex, '#' ), 2 ) );
			$rgb = array_map( fn( $c ) => $c <= 0.03928 ? $c / 12.92 : ( ( $c + 0.055 ) / 1.055 ) ** 2.4, $rgb );

			return 0.2126 * $rgb[0] + 0.7152 * $rgb[1] + 0.0722 * $rgb[2];
		};

		$x = $lum( $a );
		$y = $lum( $b );

		return ( max( $x, $y ) + 0.05 ) / ( min( $x, $y ) + 0.05 );
	}

	/**
	 * Palettes: Mai's defaults, black text, a warm palette, and a dark site.
	 *
	 * @return array
	 */
	public static function palettes(): array {
		return [
			'mai default' => [ '#6c747d', '#ffffff', '#8f959c' ],
			'black body'  => [ '#000000', '#ffffff', '#949494' ],
			'warm'        => [ '#4a3f33', '#ffffff', '#9a938d' ],
			'dark site'   => [ '#e5e5e5', '#111111', '' ],
		];
	}

	#[DataProvider( 'palettes' )]
	public function test_the_border_reaches_three_to_one( string $body, string $background, string $expected ): void {
		Functions\when( 'mai_get_colors' )->justReturn( [ 'body' => $body, 'background' => $background ] );

		$color = mai_get_input_border_color();

		if ( $expected ) {
			$this->assertSame( $expected, $color );
		}

		$this->assertGreaterThanOrEqual( 3, $this->ratio( $color, $background ) );
	}

	public function test_a_low_contrast_body_ends_at_the_body_color(): void {
		Functions\when( 'mai_get_colors' )->justReturn( [ 'body' => '#cccccc', 'background' => '#ffffff' ] );

		$this->assertSame( '#cccccc', mai_get_input_border_color() );
	}

	public function test_missing_colors_give_nothing(): void {
		Functions\when( 'mai_get_colors' )->justReturn( [] );

		$this->assertSame( '', mai_get_input_border_color() );
	}
}
