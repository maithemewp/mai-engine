<?php
declare(strict_types=1);

namespace BizBudding\MaiEngine\Tests\Unit;

use BizBudding\MaiEngine\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Covers mai_strip_legacy_block_css() in lib/functions/enqueue.php.
 *
 * The fixtures are copies of WordPress 7.0.2's `block-library/common.css` and
 * `common.min.css`. When a new WordPress changes those files, copy them in again and
 * re-run this test. The integration test of the same name reads the installed core file.
 */
final class LegacyBlockCssTest extends TestCase {

	/**
	 * Core's 15 legacy preset class names.
	 *
	 * @var string[]
	 */
	private const NAMES = [
		'has-vivid-green-cyan-to-vivid-cyan-blue-gradient-background',
		'has-purple-crush-gradient-background',
		'has-hazy-dawn-gradient-background',
		'has-subdued-olive-gradient-background',
		'has-atomic-cream-gradient-background',
		'has-nightshade-gradient-background',
		'has-midnight-gradient-background',
		'has-very-light-gray-color',
		'has-very-dark-gray-color',
		'has-very-light-gray-background-color',
		'has-very-dark-gray-background-color',
		'has-regular-font-size',
		'has-larger-font-size',
		'has-normal-font-size',
		'has-huge-font-size',
	];

	protected function setUp(): void {
		parent::setUp();

		require_once dirname( __DIR__, 3 ) . '/lib/functions/enqueue.php';
	}

	/**
	 * How many of the 15 legacy class names appear in the CSS. The grey colour names are
	 * also the tail of the grey background names, so only whole class names count.
	 */
	private function legacy_count( string $css ): int {
		return (int) preg_match_all( '~\.(?:' . implode( '|', self::NAMES ) . ')(?![\w-])~', $css );
	}

	/**
	 * @return array<string, array{string}>
	 */
	public static function core_files(): array {
		return [
			'common.css'     => [ 'common.css' ],
			'common.min.css' => [ 'common.min.css' ],
		];
	}

	#[DataProvider( 'core_files' )]
	public function test_core_file_loses_all_15_rules_and_keeps_the_rest( string $file ): void {
		$css      = (string) file_get_contents( __DIR__ . '/fixtures/block-library/' . $file );
		$stripped = mai_strip_legacy_block_css( $css );

		$this->assertSame( 15, $this->legacy_count( $css ), 'The fixture holds all 15 rules.' );
		$this->assertSame( 0, $this->legacy_count( $stripped ) );
		$this->assertStringContainsString( '.has-border-color', $stripped );
		$this->assertStringContainsString( '.screen-reader-text', $stripped );
		$this->assertStringContainsString( '.wp-element-button', $stripped );
		$this->assertStringContainsString( '--wp--preset--font-size--huge', $stripped );
		$this->assertSame( substr_count( $stripped, '{' ), substr_count( $stripped, '}' ) );
		$this->assertLessThan( strlen( $css ), strlen( $stripped ) );
	}

	public function test_a_flat_rule_is_removed_and_its_neighbours_stay(): void {
		$this->assertSame(
			'.keep{color:blue}.also{color:red}',
			mai_strip_legacy_block_css( '.keep{color:blue}.has-huge-font-size{font-size:1em}.also{color:red}' )
		);
	}

	public function test_a_nested_rule_is_left_untouched(): void {
		$nested = ':root .has-midnight-gradient-background{background:red;&:hover{opacity:1}}.keep{color:blue}';

		$this->assertSame( $nested, mai_strip_legacy_block_css( $nested ) );
	}

	public function test_a_nested_rule_after_a_sibling_is_removed_and_braces_stay_balanced(): void {
		$this->assertSame(
			'@media (x){.a{b:c}}',
			mai_strip_legacy_block_css( '@media (x){.a{b:c}.has-huge-font-size{font-size:1em}}' )
		);
	}

	public function test_a_plugin_gradient_survives(): void {
		$plugin = ':root .has-acme-gradient-background{background:red}';

		$this->assertSame( $plugin, mai_strip_legacy_block_css( $plugin ) );
	}

	public function test_a_selector_list_is_left_untouched(): void {
		$list = '.a,.has-huge-font-size{font-size:1em}';

		$this->assertSame( $list, mai_strip_legacy_block_css( $list ) );
	}

	public function test_unbalanced_input_is_returned_as_is(): void {
		$broken = '.has-huge-font-size{font-size:1em}.a{';

		$this->assertSame( $broken, mai_strip_legacy_block_css( $broken ) );
	}

	public function test_an_empty_string_stays_empty(): void {
		$this->assertSame( '', mai_strip_legacy_block_css( '' ) );
	}
}
