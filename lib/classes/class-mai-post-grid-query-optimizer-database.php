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
	 * like a join, so the swap would be slower. 8.0.16, 8.0.28, 8.0.46 and 8.4.11 met the speed
	 * bar on every timed pair and returned the same posts, apart from a post with an invalid date
	 * (Docker runs, 2026-10-05, spec "Results" and "Risks").
	 */
	public const MYSQL_MIN = '8.0.16';

	/**
	 * The oldest MariaDB that may take the faster query, as X.Y.Z. Empty, or anything that is not
	 * X.Y.Z, means MariaDB is off.
	 *
	 * Off. In the Docker runs of 2026-10-05, MariaDB 10.6, 10.11, 11.4 and 11.8 returned the same
	 * posts, but a grid sorted by ID on eurweb's biggest category took 49 to 63 ms swapped against
	 * 1 to 5 ms today, because MariaDB reads every matching term row before it walks the posts.
	 * MariaDB has its own query planner, so MySQL's results do not carry over to it. Without ID
	 * sorts (none are swapped since 2026-10-06) those pairs met the bar, but the hardened replay
	 * (full-form show-all pairs, row-count checks, the rerun rule) never ran on MariaDB, and its
	 * plan reads every matching term row first, the shape that made local MySQL 9.7.1 miss on the
	 * full form. Before turning it on, rerun the hardened replay on MariaDB 10.6, 10.11, 11.4 and
	 * 11.8.
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
