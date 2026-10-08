<?php
declare(strict_types=1);

namespace BizBudding\MaiEngine\Tests\Unit;

use Brain\Monkey\Functions;
use BizBudding\MaiEngine\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

/**
 * The 2.41.0 upgrade steps wait for the next admin page when the code they call is not loaded.
 *
 * A live site once ran the new lib/admin/upgrade.php next to an older lib/functions/widgets.php
 * and fataled in wp-admin. Only lib/admin/upgrade.php is loaded here, so the functions the
 * steps call are really undefined unless a test stubs them.
 *
 * Every test runs in its own process. Stubbing a function defines it for the rest of the
 * process, which would hide the missing function from the next test. Each test also asserts
 * that the functions start undefined, so a test can't pass by accident if another file
 * defines one.
 */
final class UpgradeWaitsForCodeTest extends TestCase {

	/** Functions the 2.41.0 steps call that live outside lib/admin/upgrade.php. */
	private const NEEDED = [
		'mai_get_saved_widgets_block_editor',
		'mai_get_widgets_block_editor_default',
		'mai_typography_flush_local_fonts',
	];

	/** Saved options of an upgrade from 2.40.0. */
	private const FROM_2_40 = [ 'first-version' => '2.30.0', 'db-version' => '2.40.0' ];

	/** Saved options of a new install, which has no db-version yet. */
	private const NEW_INSTALL = [ 'first-version' => '2.41.0' ];

	/** Every mai_update_option() call, as key => value. */
	private array $saved = [];

	/** How many times the font flush ran. */
	private int $flushes = 0;

	// ---- Helpers ----

	/**
	 * Runs mai_do_upgrade() with Mai's option store stubbed.
	 *
	 * @param array    $options Saved options. Leave out `db-version` for a new install.
	 * @param string   $version The plugin version.
	 * @param string[] $loaded  The NEEDED functions that are loaded. The rest stay undefined.
	 *
	 * @return void
	 */
	private function upgrade( array $options, string $version, array $loaded ): void {
		// Loaded here, after Brain Monkey is up, because the file calls add_action() as it loads.
		require_once dirname( __DIR__, 3 ) . '/lib/admin/upgrade.php';

		foreach ( self::NEEDED as $function ) {
			$this->assertFalse( function_exists( $function ), "{$function} starts undefined" );
		}

		$stubs = [
			'mai_get_saved_widgets_block_editor'   => static fn() => null,
			'mai_get_widgets_block_editor_default' => static fn() => true,
			'mai_typography_flush_local_fonts'     => function (): void {
				++$this->flushes;
			},
		];

		foreach ( $loaded as $function ) {
			Functions\when( $function )->alias( $stubs[ $function ] );
		}

		Functions\when( 'mai_get_version' )->justReturn( $version );
		Functions\when( 'mai_get_option' )->alias( static fn( $key, $default = false ) => $options[ $key ] ?? $default );
		Functions\when( 'mai_update_option' )->alias( function ( $key, $value ): void {
			$this->saved[ $key ] = $value;
		} );

		mai_do_upgrade();
	}

	/** No 2.41.0 step ran, and the version stayed, so the whole upgrade runs again next time. */
	private function assertUpgradeWaits(): void {
		$this->assertArrayNotHasKey( 'db-version', $this->saved );
		$this->assertArrayNotHasKey( 'widgets-block-editor', $this->saved );
		$this->assertSame( 0, $this->flushes );
	}

	// ---- Tests ----

	/** @return array<string, array{0: array}> */
	public static function sites(): array {
		return [
			'an upgrade from 2.40.0' => [ self::FROM_2_40 ],
			'a new install'          => [ self::NEW_INSTALL ],
		];
	}

	#[RunInSeparateProcess]
	#[DataProvider( 'sites' )]
	public function test_nothing_runs_while_all_the_needed_code_is_missing( array $options ): void {
		$this->upgrade( $options, '2.41.0', [] );

		$this->assertUpgradeWaits();
	}

	/** @return array<string, array{0: array, 1: string}> */
	public static function sites_missing_one_function(): array {
		$cases = [];

		foreach ( self::sites() as $site => [ $options ] ) {
			foreach ( self::NEEDED as $missing ) {
				$cases[ "{$site}, without {$missing}" ] = [ $options, $missing ];
			}
		}

		return $cases;
	}

	#[RunInSeparateProcess]
	#[DataProvider( 'sites_missing_one_function' )]
	public function test_nothing_runs_while_any_one_needed_function_is_missing( array $options, string $missing ): void {
		$this->upgrade( $options, '2.41.0', array_values( array_diff( self::NEEDED, [ $missing ] ) ) );

		$this->assertUpgradeWaits();
	}

	#[RunInSeparateProcess]
	public function test_an_upgrade_runs_every_step_once_the_code_is_loaded(): void {
		$this->upgrade( self::FROM_2_40, '2.41.0', self::NEEDED );

		$this->assertSame( 1, $this->flushes );
		$this->assertTrue( $this->saved['widgets-block-editor'] );
		$this->assertSame( '2.41.0', $this->saved['db-version'] );
	}

	#[RunInSeparateProcess]
	public function test_a_new_install_saves_its_widget_choice_once_the_code_is_loaded(): void {
		$this->upgrade( self::NEW_INSTALL, '2.41.0', self::NEEDED );

		$this->assertSame( 0, $this->flushes );
		$this->assertTrue( $this->saved['widgets-block-editor'] );
		$this->assertSame( '2.41.0', $this->saved['db-version'] );
	}

	/** A site already past 2.41.0 runs none of its steps, so it never waits on their code. */
	#[RunInSeparateProcess]
	public function test_a_site_past_2_41_0_is_not_held_back(): void {
		$this->upgrade( [ 'first-version' => '2.30.0', 'db-version' => '2.41.0' ], '2.41.1', [] );

		$this->assertSame( '2.41.1', $this->saved['db-version'] );
		$this->assertArrayNotHasKey( 'widgets-block-editor', $this->saved );
		$this->assertSame( 0, $this->flushes );
	}
}
