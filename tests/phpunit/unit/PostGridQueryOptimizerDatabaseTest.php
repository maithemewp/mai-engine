<?php
// tests/phpunit/unit/PostGridQueryOptimizerDatabaseTest.php
namespace BizBudding\MaiEngine\Tests\Unit;

use BizBudding\MaiEngine\Tests\TestCase;
use Mai_Post_Grid_Query_Optimizer_Database;
use PHPUnit\Framework\Attributes\DataProvider;

final class PostGridQueryOptimizerDatabaseTest extends TestCase {
	/** @var string The MariaDB minimum as it was before the test. */
	private string $saved_mariadb_min = '';

	protected function setUp(): void {
		parent::setUp();
		$this->saved_mariadb_min = Mai_Post_Grid_Query_Optimizer_Database::$mariadb_min;
	}

	protected function tearDown(): void {
		Mai_Post_Grid_Query_Optimizer_Database::$mariadb_min = $this->saved_mariadb_min;

		parent::tearDown();
	}

	/**
	 * @return array<string,array{0:mixed,1:bool}>
	 */
	public static function servers(): array {
		return [
			'MySQL one patch below the minimum' => [ '8.0.15', false ],
			'MySQL at the minimum'              => [ '8.0.16', true ],
			'MySQL on Ubuntu'                   => [ '8.0.46-0ubuntu0.22.04.4', true ],
			'Percona 8.4'                       => [ '8.4.11-11', true ],
			'MySQL 9'                           => [ '9.7.1', true ],
			'MySQL 5.7'                         => [ '5.7.44', false ],
			'SQLite on top of WordPress'        => [ '8.0.38-mysql-on-sqlite-3.0.2', false ],
			'Vitess'                            => [ '8.0.40-Vitess', false ],
			'TiDB'                              => [ '8.0.11-TiDB-v7.5.0', false ],
			// Off on purpose: no X.Y.Z at the start, so no MySQL version Mai can compare. Aurora
			// plans like the MySQL it is built on, which is unmeasured here.
			'Aurora'                            => [ '8.0.mysql_aurora.3.04.0', false ],
			'MariaDB with the 5.5.5 prefix'     => [ '5.5.5-10.11.6-MariaDB', true ],
			'MariaDB with a suffix'             => [ '10.11.6-MariaDB-log', true ],
			'MariaDB below the minimum'         => [ '10.6.18-MariaDB', false ],
			'MariaDB 11'                        => [ '11.8.2-MariaDB', true ],
			'false from a failed connection'    => [ false, false ],
			'an empty string'                   => [ '', false ],
			'null'                              => [ null, false ],
		];
	}

	#[DataProvider( 'servers' )]
	public function test_allows( mixed $info, bool $expected ): void {
		$this->assertSame( $expected, Mai_Post_Grid_Query_Optimizer_Database::allows( $info, '10.11.0' ) );
	}

	public function test_mariadb_is_off_by_default(): void {
		Mai_Post_Grid_Query_Optimizer_Database::$mariadb_min = '';

		$this->assertFalse( Mai_Post_Grid_Query_Optimizer_Database::allows( '11.8.2-MariaDB' ) );
		$this->assertTrue( Mai_Post_Grid_Query_Optimizer_Database::allows( '8.0.46' ), 'MySQL does not need a MariaDB minimum' );
	}

	public function test_mariadb_uses_the_shipped_minimum_when_none_is_passed(): void {
		Mai_Post_Grid_Query_Optimizer_Database::$mariadb_min = '10.11.0';

		$this->assertTrue( Mai_Post_Grid_Query_Optimizer_Database::allows( '11.8.2-MariaDB' ) );
		$this->assertFalse( Mai_Post_Grid_Query_Optimizer_Database::allows( '10.6.18-MariaDB' ) );
	}

	/**
	 * @return array<string,array{0:string}>
	 */
	public static function junk_minimums(): array {
		return [
			'zero'           => [ '0' ],
			'letters'        => [ 'abc' ],
			'a space'        => [ ' ' ],
			'two parts'      => [ '10.11' ],
			'a suffix'       => [ '10.11.0-MariaDB' ],
			'a leading v'    => [ 'v10.11.0' ],
			'a trailing dot' => [ '10.11.0.' ],
		];
	}

	#[DataProvider( 'junk_minimums' )]
	public function test_a_junk_mariadb_minimum_keeps_mariadb_off( string $minimum ): void {
		Mai_Post_Grid_Query_Optimizer_Database::$mariadb_min = $minimum;

		$this->assertFalse( Mai_Post_Grid_Query_Optimizer_Database::allows( '11.8.2-MariaDB' ), 'the shipped minimum' );
		$this->assertFalse( Mai_Post_Grid_Query_Optimizer_Database::allows( '11.8.2-MariaDB', $minimum ), 'a minimum passed in' );
		$this->assertTrue( Mai_Post_Grid_Query_Optimizer_Database::allows( '11.8.2-MariaDB', '10.11.0' ), 'a well-formed one turns it on' );
	}

	public function test_engine(): void {
		$this->assertSame(
			[ 'engine' => 'mariadb', 'version' => '10.11.6' ],
			Mai_Post_Grid_Query_Optimizer_Database::engine( '5.5.5-10.11.6-MariaDB' )
		);
		$this->assertSame(
			[ 'engine' => 'mariadb', 'version' => '10.11.6' ],
			Mai_Post_Grid_Query_Optimizer_Database::engine( '10.11.6-MariaDB-log' )
		);
		$this->assertSame(
			[ 'engine' => 'mysql', 'version' => '8.0.46' ],
			Mai_Post_Grid_Query_Optimizer_Database::engine( '8.0.46-0ubuntu0.22.04.4' )
		);
		$this->assertNull( Mai_Post_Grid_Query_Optimizer_Database::engine( '8.0.40-VITESS' ) );
		$this->assertNull( Mai_Post_Grid_Query_Optimizer_Database::engine( 'MariaDB' ) );
		$this->assertNull( Mai_Post_Grid_Query_Optimizer_Database::engine( 8.0 ) );
	}

	public function test_hint(): void {
		$this->assertTrue( Mai_Post_Grid_Query_Optimizer_Database::hint( '8.0.46' ) );
		$this->assertFalse( Mai_Post_Grid_Query_Optimizer_Database::hint( '10.11.6-MariaDB' ) );
		$this->assertFalse( Mai_Post_Grid_Query_Optimizer_Database::hint( '8.0.38-mysql-on-sqlite-3.0.2' ) );
		$this->assertFalse( Mai_Post_Grid_Query_Optimizer_Database::hint( false ) );
	}

	public function test_layer_allows(): void {
		// Declared globally, because this file is in a namespace and the real names are global.
		if ( ! class_exists( 'wpdb', false ) ) {
			eval( 'class wpdb {}' );
		}

		if ( ! class_exists( 'QM_DB', false ) ) {
			eval( 'class QM_DB extends wpdb {}' );
		}

		$this->assertTrue( Mai_Post_Grid_Query_Optimizer_Database::layer_allows( new \wpdb() ) );
		$this->assertTrue( Mai_Post_Grid_Query_Optimizer_Database::layer_allows( new \QM_DB() ) );
		$this->assertFalse( Mai_Post_Grid_Query_Optimizer_Database::layer_allows( new \stdClass() ) );
		$this->assertFalse( Mai_Post_Grid_Query_Optimizer_Database::layer_allows( new class() extends \wpdb {} ), 'any other subclass of wpdb' );
	}

	public function test_server_info(): void {
		$returns = new class() {
			public function db_server_info() {
				return '8.0.46';
			}
		};
		$false = new class() {
			public function db_server_info() {
				return false;
			}
		};
		$throws = new class() {
			public function db_server_info() {
				throw new \TypeError( 'mysqli_get_server_info(): Argument #1 must be of type mysqli' );
			}
		};

		$this->assertSame( '8.0.46', Mai_Post_Grid_Query_Optimizer_Database::server_info( $returns ) );
		$this->assertSame( '', Mai_Post_Grid_Query_Optimizer_Database::server_info( $false ) );
		$this->assertSame( '', Mai_Post_Grid_Query_Optimizer_Database::server_info( $throws ) );
		$this->assertSame( '', Mai_Post_Grid_Query_Optimizer_Database::server_info( new \stdClass() ), 'no such method' );
	}
}
