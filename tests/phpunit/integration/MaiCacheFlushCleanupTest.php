<?php
declare(strict_types=1);

namespace BizBudding\MaiEngine\Tests\Integration;

use Mai\Cache\Cache;
use Mai_Query_Cache;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * A flush deletes the rows its old token left behind, and nothing else, on MySQL.
 *
 * Without a persistent object cache, mai-cache keeps its entries in wp_options as transients.
 * A flush rotates a token, which leaves every row named with the old one unreachable, so
 * mai-cache 0.5.0 deletes them, 1000 rows at a time. Two shapes reach it from Mai Engine:
 *
 * - group: Mai_Query_Cache::flush_all(), which `wp cache flush` runs, rotates the grid token.
 *   The old prefix is mai_s1_<root token>_grid_<old grid token>_.
 * - root: mai_cache()->flush(), which a Mai Engine update and `wp mai flush` run, rotates the
 *   root token. The old prefix is mai_s1_<old root token>_, which covers every group.
 *
 * The cleanup matches rows with LIKE, so most of this checks the rows it must leave alone.
 */
final class MaiCacheFlushCleanupTest extends MaiIntegrationTestCase {

	private const ENTRIES = 2500;

	private const BATCH = 1000;

	private const TOKEN_ROWS = [ '_transient_mai__token', '_transient_mai_grid__token', '_transient_mai_css__token' ];

	public function set_up(): void {
		parent::set_up();

		// Read tokens as stored, not as an earlier test left them in memory.
		Cache::reset_runtime();
	}

	public function tear_down(): void {
		parent::tear_down();

		// The rows this test wrote were rolled back, so forget the tokens it read or minted.
		Cache::reset_runtime();
	}

	/** @return array<string,array{0:string}> */
	public static function shapes(): array {
		return [
			'group flush, from wp cache flush'          => [ 'group' ],
			'root flush, from an update or wp mai flush' => [ 'root' ],
		];
	}

	// ---- Helpers ----

	/** Runs a shape's flush the way Mai Engine runs it. */
	private function flush( string $shape ): void {
		if ( 'group' === $shape ) {
			( new Mai_Query_Cache() )->flush_all();
		} else {
			mai_cache()->flush();
		}
	}

	/** The key prefix a shape's flush retires. Read it before the flush. */
	private function old_prefix( string $shape ): string {
		return 'group' === $shape ? mai_cache( 'grid' )->key( '' ) : mai_cache()->key( '' );
	}

	/** The option that holds the token a shape's flush rotates. */
	private function token_row( string $shape ): string {
		return 'group' === $shape ? '_transient_mai_grid__token' : '_transient_mai__token';
	}

	/**
	 * Every row in the options table, name => value, sorted by name.
	 *
	 * @return array<string,string>
	 */
	private function options(): array {
		global $wpdb;

		$options = array_column( $wpdb->get_results( "SELECT option_name, option_value FROM {$wpdb->options}" ), 'option_value', 'option_name' );

		ksort( $options );

		return $options;
	}

	/** Whether a row is named under the old prefix, as a transient's value or its timeout. */
	private static function is_old( string $name, string $old ): bool {
		return str_starts_with( $name, '_transient_' . $old ) || str_starts_with( $name, '_transient_timeout_' . $old );
	}

	/**
	 * The names of the rows under the old prefix.
	 *
	 * @param array<string,string> $options Rows, name => value.
	 *
	 * @return string[]
	 */
	private static function old_rows( array $options, string $old ): array {
		return array_values( array_filter( array_map( 'strval', array_keys( $options ) ), static fn( $name ) => self::is_old( $name, $old ) ) );
	}

	/**
	 * Runs the callback and returns the DELETE statements it ran against the options table.
	 *
	 * @return string[]
	 */
	private function capture_deletes( callable $callback ): array {
		global $wpdb;

		$deletes = [];
		$capture = static function ( $sql ) use ( &$deletes, $wpdb ) {
			if ( str_starts_with( ltrim( $sql ), "DELETE FROM {$wpdb->options} " ) ) {
				$deletes[] = $sql;
			}

			return $sql;
		};

		add_filter( 'query', $capture );

		try {
			$callback();
		} finally {
			remove_filter( 'query', $capture );
		}

		return $deletes;
	}

	/** Writes $count grid entries and a grid version row, the way the grid cache writes them. */
	private function write_grid_entries( int $count ): void {
		$grid    = mai_cache( 'grid' );
		$version = $grid->version( [ 'post' ] );

		for ( $i = 0; $i < $count; $i++ ) {
			$grid->write_swr( "entry{$i}", [ 'ids' => [ $i + 1 ], 'found' => 1 ], $version, HOUR_IN_SECONDS );
		}
	}

	/** Writes a few entries and a version row in another group. */
	private function write_css_entries(): void {
		$css = mai_cache( 'css' );
		$css->version( [ 'main' ] );

		for ( $i = 0; $i < 5; $i++ ) {
			$css->set( "css{$i}", 'body{}', HOUR_IN_SECONDS );
		}
	}

	/**
	 * Seeds rows that a cleanup matching too much would delete, and returns their names.
	 *
	 * @return string[]
	 */
	private function seed_lookalikes( string $old ): array {
		// Another plugin's transient: its value row and its timeout row.
		set_transient( 'someplugin_cache', 'another plugin', HOUR_IN_SECONDS );

		$names = [ '_transient_someplugin_cache', '_transient_timeout_someplugin_cache' ];

		// Mai rows under another 12-hex token: each token in the old prefix swapped in turn.
		preg_match_all( '/[0-9a-f]{12}/', $old, $tokens );

		foreach ( $tokens[0] as $token ) {
			$other = '0123456789ab' === $token ? 'ba9876543210' : '0123456789ab';

			$names[] = $this->add_row( '_transient_' . str_replace( $token, $other, $old ) . 'entry0' );
			$names[] = $this->add_row( '_transient_timeout_' . str_replace( $token, $other, $old ) . 'entry0' );
		}

		// One character off: each _ in the exact text swapped for x. A bare _ is a LIKE
		// wildcard, so a pattern that left any of them unescaped would match these.
		foreach ( [ '_transient_' . $old, '_transient_timeout_' . $old ] as $literal ) {
			foreach ( array_keys( str_split( $literal ), '_', true ) as $position ) {
				$names[] = $this->add_row( substr_replace( $literal, 'x', $position, 1 ) . 'entry0' );
			}
		}

		$names[] = $this->add_row( 'xtransientx' . $old . 'entry0' );
		$names[] = $this->add_row( 'xtransientxtimeoutx' . $old . 'entry0' );

		// A plain option named like the old prefix and a real key, without _transient_.
		$names[] = $this->add_row( $old . 'entry0' );

		return array_values( array_unique( $names ) );
	}

	private function add_row( string $name ): string {
		add_option( $name, 'look-alike', '', false );

		return $name;
	}

	// ---- Tests ----

	#[DataProvider( 'shapes' )]
	public function test_flush_deletes_the_old_rows_and_nothing_else( string $shape ): void {
		$this->assertFalse( (bool) wp_using_ext_object_cache(), 'transients live in the options table here' );

		$this->write_grid_entries( self::ENTRIES );
		$this->write_css_entries();

		$old        = $this->old_prefix( $shape );
		$lookalikes = $this->seed_lookalikes( $old );
		$version    = '_transient_' . mai_cache( 'grid' )->key( '__v_post' );
		$css_row    = '_transient_' . mai_cache( 'css' )->key( 'css0' );
		$rotated    = $this->token_row( $shape );

		$before   = $this->options();
		$old_rows = self::old_rows( $before, $old );

		// The setup is what it should be.
		$this->assertGreaterThanOrEqual( self::ENTRIES * 2 + 1, count( $old_rows ), 'two rows per entry, plus the version row' );
		$this->assertContains( $version, $old_rows );
		$this->assertArrayHasKey( $version, wp_load_alloptions(), 'the version row is autoloaded' );

		foreach ( $lookalikes as $name ) {
			$this->assertArrayHasKey( $name, $before, "{$name} was seeded" );
			$this->assertFalse( self::is_old( $name, $old ), "{$name} is not under the old prefix" );
		}

		foreach ( self::TOKEN_ROWS as $name ) {
			$this->assertArrayHasKey( $name, $before, "{$name} exists" );
		}

		$deletes = $this->capture_deletes( fn() => $this->flush( $shape ) );
		$after   = $this->options();

		// Every old row is gone, in batches of 1000 and one last pass that deletes nothing.
		$this->assertSame( [], self::old_rows( $after, $old ), 'no _transient_ or _transient_timeout_ row is left under the old prefix' );
		$this->assertGreaterThan( 1, count( $deletes ), 'more than one pass ran' );
		$this->assertCount( intdiv( count( $old_rows ), self::BATCH ) + ( count( $old_rows ) % self::BATCH ? 1 : 0 ) + 1, $deletes );

		// Every other row is still there with the same value, except the token the flush rotated.
		$kept = array_diff_key( $before, array_flip( $old_rows ) );

		$this->assertMatchesRegularExpression( '/^[0-9a-f]{12}$/', $after[ $rotated ] );
		$this->assertNotSame( $kept[ $rotated ], $after[ $rotated ], 'the token rotated' );

		unset( $kept[ $rotated ], $after[ $rotated ] );

		$this->assertSame( $kept, $after, 'nothing outside the old prefix changed' );

		// The same, spelled out for the rows that matter most.
		foreach ( $lookalikes as $name ) {
			$this->assertArrayHasKey( $name, $after, "{$name} survives" );
		}

		foreach ( self::TOKEN_ROWS as $name ) {
			$this->assertArrayHasKey( $name, $this->options(), "{$name} survives" );
		}

		if ( 'group' === $shape ) {
			$this->assertArrayHasKey( $css_row, $after, "another group's rows are untouched" );
		} else {
			$this->assertArrayNotHasKey( $css_row, $after, 'a root flush covers every group' );
		}

		$this->assertArrayNotHasKey( $version, wp_load_alloptions(), 'alloptions no longer holds the old version row' );

		// A live entry written after the flush is stored under the new token and reads back.
		$grid = mai_cache( 'grid' );
		$grid->write_swr( 'live', [ 'ids' => [ 1 ], 'found' => 1 ], $grid->version( [ 'post' ] ), HOUR_IN_SECONDS );

		$live = '_transient_' . $grid->key( 'live' );

		$this->assertFalse( self::is_old( $live, $old ), 'the live entry is under the new token' );
		$this->assertArrayHasKey( $live, $this->options() );
		$this->assertSame( [ 1 ], $grid->read_swr( 'live', $grid->version( [ 'post' ] ) )['value']['ids'] );
	}

	/**
	 * A stored token of any other shape could stand for a broader prefix than one retired
	 * token, so the cleanup is skipped. The token still rotates, and the old rows wait for
	 * WordPress's own expiry. Here the token row is written the way mai-cache writes it, and
	 * the token cached in memory is dropped so it is read back from the row.
	 */
	#[DataProvider( 'shapes' )]
	public function test_cleanup_is_skipped_when_a_token_is_not_12_lowercase_hex( string $shape ): void {
		set_transient( substr( $this->token_row( $shape ), strlen( '_transient_' ) ), 'Not_Hex_Tokn', 0 );
		Cache::reset_runtime();

		$this->write_grid_entries( 5 );

		$old     = $this->old_prefix( $shape );
		$rotated = $this->token_row( $shape );
		$before  = $this->options();

		$this->assertStringContainsString( '_Not_Hex_Tokn_', $old );
		$this->assertGreaterThanOrEqual( 11, count( self::old_rows( $before, $old ) ) );

		$deletes = $this->capture_deletes( fn() => $this->flush( $shape ) );
		$after   = $this->options();

		$this->assertSame( [], $deletes, 'no DELETE ran' );
		$this->assertMatchesRegularExpression( '/^[0-9a-f]{12}$/', $after[ $rotated ], 'the token still rotated' );

		unset( $before[ $rotated ], $after[ $rotated ] );

		$this->assertSame( $before, $after, 'nothing was deleted' );
	}
}
