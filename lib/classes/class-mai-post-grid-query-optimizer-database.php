<?php
declare(strict_types=1);

/**
 * Mai Post Grid query optimizer: which databases can take the faster query.
 *
 * The faster query is only proven safe on some database servers and through some database
 * layers. This class answers both questions from the values and objects it is given. It never
 * reads the global $wpdb and sends no statement, so it runs in unit tests as is.
 *
 * @package BizBudding\MaiEngine
 */

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

final class Mai_Post_Grid_Query_Optimizer_Database {

	/**
	 * The oldest MySQL that may take the faster query. Before 8.0.16 MySQL cannot plan an EXISTS
	 * like a join, so the swap would be slower.
	 */
	public const MYSQL_MIN = '8.0.16';

	/**
	 * The oldest MariaDB that may take the faster query, as X.Y.Z. Empty, or anything that is not
	 * X.Y.Z, means MariaDB is off.
	 *
	 * Off until the speed and same-posts runs on each MariaDB version show where the swap meets
	 * the bar. MariaDB has its own query planner, so MySQL's results do not carry over to it.
	 *
	 * @internal Set by the test suite and the Docker runs, never by a site.
	 *
	 * @var string
	 */
	public static string $mariadb_min = '';

	/**
	 * Reads the engine and its version from a database server string.
	 *
	 * Returns null when the value is not a non-empty string, names a server that only speaks
	 * the MySQL protocol (SQLite, Vitess, TiDB), or carries no version.
	 *
	 * @param mixed $server_info What $wpdb->db_server_info() returned, as is.
	 *
	 * @return array{engine:string,version:string}|null
	 */
	public static function engine( mixed $server_info ): ?array {
		if ( ! is_string( $server_info ) || '' === $server_info ) {
			return null;
		}

		if ( preg_match( '/sqlite|vitess|tidb/i', $server_info ) ) {
			return null;
		}

		if ( false !== stripos( $server_info, 'mariadb' ) ) {
			// Older PHP versions report a fake 5.5.5- in front of the real MariaDB version.
			$string = preg_replace( '/^5\.5\.5-/', '', $server_info );

			if ( is_string( $string ) && preg_match( '/\d+\.\d+\.\d+/', $string, $matches ) ) {
				return [
					'engine'  => 'mariadb',
					'version' => $matches[0],
				];
			}

			return null;
		}

		if ( preg_match( '/^\d+\.\d+\.\d+/', $server_info, $matches ) ) {
			return [
				'engine'  => 'mysql',
				'version' => $matches[0],
			];
		}

		return null;
	}

	/**
	 * Whether the database server may take the faster query.
	 *
	 * @param mixed       $server_info What $wpdb->db_server_info() returned, as is.
	 * @param string|null $mariadb_min The oldest MariaDB allowed, as X.Y.Z. Null uses
	 *                                 self::$mariadb_min. Anything else that is not X.Y.Z keeps
	 *                                 MariaDB off, since version_compare() reads junk such as
	 *                                 '0' or 'abc' as a low version.
	 *
	 * @return bool
	 */
	public static function allows( mixed $server_info, ?string $mariadb_min = null ): bool {
		$engine = self::engine( $server_info );

		if ( null === $engine ) {
			return false;
		}

		$mariadb_min ??= self::$mariadb_min;

		return match ( $engine['engine'] ) {
			'mysql'   => version_compare( $engine['version'], self::MYSQL_MIN, '>=' ),
			'mariadb' => 1 === preg_match( '/\A\d+\.\d+\.\d+\z/', $mariadb_min ) && version_compare( $engine['version'], $mariadb_min, '>=' ),
			default   => false,
		};
	}

	/**
	 * Whether the query optimizer hint may be written. Only MySQL reads it.
	 *
	 * @param mixed $server_info What $wpdb->db_server_info() returned, as is.
	 *
	 * @return bool
	 */
	public static function hint( mixed $server_info ): bool {
		$engine = self::engine( $server_info );

		return null !== $engine && 'mysql' === $engine['engine'];
	}

	/**
	 * Whether the database layer is one that is known to leave the query alone.
	 *
	 * Only the class that WordPress makes and the one Query Monitor swaps in. A subclass of
	 * either, such as a layer that rewrites SQL, does not pass.
	 *
	 * @param object $db The $wpdb object.
	 *
	 * @return bool
	 */
	public static function layer_allows( object $db ): bool {
		return in_array( get_class( $db ), [ 'wpdb', 'QM_DB' ], true );
	}

	/**
	 * Reads the database server string from the database layer.
	 *
	 * Returns an empty string when the layer throws, returns false or returns anything that is
	 * not a string, so the caller never has to catch.
	 *
	 * @param object $db The $wpdb object.
	 *
	 * @return string
	 */
	public static function server_info( object $db ): string {
		try {
			$info = $db->db_server_info();
		} catch ( Throwable ) {
			return '';
		}

		return is_string( $info ) ? $info : '';
	}
}
