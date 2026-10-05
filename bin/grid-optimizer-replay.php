<?php
declare(strict_types=1);

/**
 * Mai grid optimizer replay (dev tool, not shipped).
 *
 * Runs the statement pairs from bin/grid-optimizer-pairs.php against a MySQL or MariaDB server,
 * and compares today's statement with the swapped EXISTS form. For each pair:
 *
 *   1. The IDs of both forms must match, in the same order. A pair whose IDs differ is a
 *      correctness bug and the run exits with code 2.
 *   2. Both forms alternate in one session, 10 runs each, timed on the server with NOW(6). The
 *      time runs from one NOW(6) to the next, around the statement and the transfer of its rows,
 *      so it includes two short round trips. Both forms carry the same extra. The medians are
 *      compared.
 *   3. EXPLAIN FORMAT=TREE on MySQL, plain EXPLAIN on MariaDB. A swapped MySQL plan containing
 *      "weedout" or "Remove duplicates" is flagged.
 *   4. The bar passes when the swapped median is no more than today's median plus the larger of
 *      0.5 ms and 10%.
 *
 * It prints one summary line per pair and a total, and writes every number as JSON. Exit code:
 * 0 when every pair passes, 1 when a pair misses the bar, is flagged or fails to run, 2 when
 * the IDs of a pair differ.
 *
 * Usage:
 *   php bin/grid-optimizer-replay.php --pairs=FILE --db=NAME --engine=mysql|mariadb \
 *     [--host=127.0.0.1] [--port=3306] [--user=root] [--pass=] [--out=FILE] [--runs=10]
 *
 *   --pairs   The JSON lines file bin/grid-optimizer-pairs.php wrote.
 *   --engine  mysql uses "swapped_mysql" (with the hint). mariadb uses "swapped_mariadb". The
 *             server must be the engine named, or the run stops.
 *   --user    Defaults to root, with no password, as in a throwaway Docker container.
 *   --out     Defaults to /tmp/mai-optimizer-replay-<engine>-<db>-<port>.json.
 *   --runs    Timed runs of each form, 10 unless set.
 *
 * Check uptime first and wait until the 1-minute load is under 20. The load at the start and at
 * the end is printed and written to the JSON.
 *
 * Only SELECT statements run. A statement that takes over 30 seconds is stopped and counts as a
 * failed pair.
 *
 * @package BizBudding\MaiEngine
 */

namespace BizBudding\MaiEngine\Tools\GridOptimizerReplay;

use mysqli;
use mysqli_result;
use mysqli_sql_exception;
use RuntimeException;
use Throwable;

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

/**
 * Prints a message to stderr and stops.
 *
 * @param string $message The message.
 *
 * @return never
 */
function fail( string $message ): never {
	fwrite( STDERR, "Error: {$message}\n" );
	exit( 1 );
}

/**
 * The median of some numbers.
 *
 * @param list<float> $values The numbers, not empty.
 *
 * @return float
 */
function median( array $values ): float {
	sort( $values );

	$count  = count( $values );
	$middle = intdiv( $count, 2 );

	return 0 === $count % 2 ? ( $values[ $middle - 1 ] + $values[ $middle ] ) / 2 : $values[ $middle ];
}

/**
 * The server's clock, in microseconds. Taken with NOW(6), which is the time the statement began.
 *
 * @param mysqli $db The connection.
 *
 * @return int
 */
function server_micros( mysqli $db ): int {
	$result = $db->query( 'SELECT UNIX_TIMESTAMP( NOW( 6 ) )' );
	$row    = $result instanceof mysqli_result ? $result->fetch_row() : null;

	if ( ! is_array( $row ) || ! is_string( $row[0] ?? null ) ) {
		fail( 'The server did not answer NOW( 6 ).' );
	}

	[ $seconds, $fraction ] = array_pad( explode( '.', $row[0], 2 ), 2, '0' );

	return (int) $seconds * 1000000 + (int) str_pad( substr( $fraction, 0, 6 ), 6, '0' );
}

/**
 * Runs a statement and returns the milliseconds it took, rows transferred included.
 *
 * @param mysqli $db  The connection.
 * @param string $sql The statement.
 *
 * @return float
 */
function timed_ms( mysqli $db, string $sql ): float {
	$start  = server_micros( $db );
	$result = $db->query( $sql );

	if ( $result instanceof mysqli_result ) {
		$result->free();
	}

	return ( server_micros( $db ) - $start ) / 1000;
}

/**
 * Runs a statement and returns its IDs, in order.
 *
 * @param mysqli $db  The connection.
 * @param string $sql The statement. It selects the ID column, alone or with the whole row.
 *
 * @return list<int>
 */
function fetch_ids( mysqli $db, string $sql ): array {
	$result = $db->query( $sql );

	if ( ! $result instanceof mysqli_result ) {
		throw new RuntimeException( 'The statement returned no result set.' );
	}

	$ids = [];

	foreach ( $result as $row ) {
		if ( ! isset( $row['ID'] ) ) {
			throw new RuntimeException( 'The statement has no ID column.' );
		}

		$ids[] = (int) $row['ID'];
	}

	$result->free();

	return $ids;
}

/**
 * The plan of a statement as text.
 *
 * @param mysqli $db     The connection.
 * @param string $sql    The statement.
 * @param string $engine mysql or mariadb.
 *
 * @return string
 */
function plan_of( mysqli $db, string $sql, string $engine ): string {
	$result = $db->query( ( 'mysql' === $engine ? 'EXPLAIN FORMAT=TREE ' : 'EXPLAIN ' ) . $sql );

	if ( ! $result instanceof mysqli_result ) {
		return '';
	}

	$lines = [];

	foreach ( $result as $row ) {
		$lines[] = 'mysql' === $engine ? implode( "\n", array_map( 'strval', $row ) ) : implode( ' | ', array_map( 'strval', $row ) );
	}

	$result->free();

	return implode( "\n", $lines );
}

/**
 * A load average line, for the log and the JSON.
 *
 * @return list<float>
 */
function load_average(): array {
	$load = function_exists( 'sys_getloadavg' ) ? sys_getloadavg() : false;

	return is_array( $load ) ? array_map( static fn( float $value ): float => round( $value, 2 ), $load ) : [];
}

$options = getopt( '', [ 'pairs:', 'db:', 'engine:', 'host:', 'port:', 'user:', 'pass:', 'out:', 'runs:' ] );
$options = is_array( $options ) ? $options : [];
$option  = static fn( string $name, string $default = '' ): string => is_string( $options[ $name ] ?? null ) ? $options[ $name ] : $default;

$pairs_file = $option( 'pairs' );
$database   = $option( 'db' );
$engine     = $option( 'engine' );
$host       = $option( 'host', '127.0.0.1' );
$port       = (int) $option( 'port', '3306' );
$user       = $option( 'user', 'root' );
$password   = $option( 'pass' );
$runs       = max( 1, (int) $option( 'runs', '10' ) );

if ( '' === $pairs_file || '' === $database || ! in_array( $engine, [ 'mysql', 'mariadb' ], true ) ) {
	fail( 'Usage: php bin/grid-optimizer-replay.php --pairs=FILE --db=NAME --engine=mysql|mariadb [--host=127.0.0.1] [--port=3306] [--user=root] [--pass=] [--out=FILE] [--runs=10]' );
}

if ( ! is_readable( $pairs_file ) ) {
	fail( "Cannot read {$pairs_file}." );
}

$out = $option( 'out', "/tmp/mai-optimizer-replay-{$engine}-{$database}-{$port}.json" );

mysqli_report( MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );

try {
	$db = new mysqli( $host, $user, $password, $database, $port );
} catch ( mysqli_sql_exception $exception ) {
	fail( 'Cannot connect: ' . $exception->getMessage() );
}

$version = (string) $db->server_info;

if ( ( false !== stripos( $version, 'mariadb' ) ) !== ( 'mariadb' === $engine ) ) {
	fail( "The server is {$version}, which is not {$engine}." );
}

// Stop a runaway statement. MySQL counts milliseconds, MariaDB seconds.
$db->query( 'mysql' === $engine ? 'SET SESSION max_execution_time = 30000' : 'SET SESSION max_statement_time = 30' );

$key     = 'mysql' === $engine ? 'swapped_mysql' : 'swapped_mariadb';
$results = [];
$totals  = [
	'pairs'      => 0,
	'ids_match'  => 0,
	'ids_differ' => 0,
	'bar_met'    => 0,
	'bar_missed' => 0,
	'weedout'    => 0,
	'errors'     => 0,
	'skipped'    => 0,
];

$load_start = load_average();

echo "Server {$version}, database {$database}, {$runs} runs of each form. Load at start: " . implode( ' ', $load_start ) . "\n";

if ( isset( $load_start[0] ) && $load_start[0] >= 20 ) {
	echo "Warning: the 1-minute load is 20 or more. Wait for it to drop before trusting these times.\n";
}

foreach ( (array) file( $pairs_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) as $number => $row ) {
	$pair = json_decode( (string) $row, true );
	$name = is_array( $pair ) && is_string( $pair['name'] ?? null ) ? $pair['name'] : 'line ' . ( $number + 1 );

	if ( ! is_array( $pair ) || ! is_string( $pair['original'] ?? null ) ) {
		echo "SKIP  {$name}: not a pair line\n";
		++$totals['skipped'];
		continue;
	}

	$today   = $pair['original'];
	$swapped = $pair[ $key ] ?? null;

	if ( ! is_string( $swapped ) || '' === $swapped ) {
		echo "SKIP  {$name}: no {$key} text\n";
		++$totals['skipped'];
		continue;
	}

	if ( ! str_starts_with( ltrim( $today ), 'SELECT' ) || ! str_starts_with( ltrim( $swapped ), 'SELECT' ) ) {
		echo "SKIP  {$name}: not a SELECT\n";
		++$totals['skipped'];
		continue;
	}

	++$totals['pairs'];

	$result = [
		'name'  => $name,
		'error' => null,
	];

	try {
		// The first run of each form checks the IDs and warms the buffer pool. It is not timed.
		$ids_today   = fetch_ids( $db, $today );
		$ids_swapped = fetch_ids( $db, $swapped );
		$match       = $ids_today === $ids_swapped;
		$first_diff  = null;

		if ( ! $match ) {
			foreach ( $ids_today as $index => $id ) {
				if ( ( $ids_swapped[ $index ] ?? null ) !== $id ) {
					$first_diff = $index;
					break;
				}
			}

			$first_diff ??= count( $ids_today );
		}

		$today_ms   = [];
		$swapped_ms = [];

		for ( $run = 0; $run < $runs; $run++ ) {
			$today_ms[]   = timed_ms( $db, $today );
			$swapped_ms[] = timed_ms( $db, $swapped );
		}

		$plan_today   = plan_of( $db, $today, $engine );
		$plan_swapped = plan_of( $db, $swapped, $engine );

		$today_median   = median( $today_ms );
		$swapped_median = median( $swapped_ms );
		$allowed        = $today_median + max( 0.5, 0.10 * $today_median );
		$weedout        = 'mysql' === $engine && 1 === preg_match( '/weedout|Remove duplicates/i', $plan_swapped );

		$result += [
			'ids_match'         => $match,
			'rows'              => count( $ids_today ),
			'rows_swapped'      => count( $ids_swapped ),
			'first_difference'  => $first_diff,
			'today_runs_ms'     => array_map( static fn( float $value ): float => round( $value, 3 ), $today_ms ),
			'swapped_runs_ms'   => array_map( static fn( float $value ): float => round( $value, 3 ), $swapped_ms ),
			'today_median_ms'   => round( $today_median, 3 ),
			'swapped_median_ms' => round( $swapped_median, 3 ),
			'allowed_ms'        => round( $allowed, 3 ),
			'bar_met'           => $swapped_median <= $allowed,
			'weedout'           => $weedout,
			'plan_today'        => $plan_today,
			'plan_swapped'      => $plan_swapped,
		];

		++$totals[ $match ? 'ids_match' : 'ids_differ' ];
		++$totals[ $result['bar_met'] ? 'bar_met' : 'bar_missed' ];
		$totals['weedout'] += $weedout ? 1 : 0;

		$flags = ( $match ? '' : '  IDS DIFFER at ' . $first_diff . ' (' . count( $ids_today ) . ' rows today, ' . count( $ids_swapped ) . ' swapped)' ) . ( $weedout ? '  WEEDOUT' : '' );

		printf(
			"%-6s %s  today %.2f ms  swapped %.2f ms  rows %d%s\n",
			$match ? ( $result['bar_met'] ? 'PASS' : 'MISS' ) : 'DIFF',
			$name,
			$today_median,
			$swapped_median,
			count( $ids_today ),
			$flags
		);
	} catch ( Throwable $exception ) {
		$result['error'] = $exception->getMessage();

		++$totals['errors'];

		echo "ERROR {$name}: {$result['error']}\n";

		// A stopped or failed statement can leave the connection unusable, so check it.
		try {
			$db->query( 'SELECT 1' );
		} catch ( Throwable ) {
			fail( 'The connection is gone. Stopped at ' . $name . '.' );
		}
	}

	$results[] = $result;
}

$load_end = load_average();

printf(
	"Total: %d pairs, IDs match %d, IDs differ %d, bar met %d, bar missed %d, weedout %d, errors %d, skipped %d. Load at end: %s\n",
	$totals['pairs'],
	$totals['ids_match'],
	$totals['ids_differ'],
	$totals['bar_met'],
	$totals['bar_missed'],
	$totals['weedout'],
	$totals['errors'],
	$totals['skipped'],
	implode( ' ', $load_end )
);

$json = json_encode(
	[
		'server'     => $version,
		'engine'     => $engine,
		'database'   => $database,
		'port'       => $port,
		'pairs_file' => $pairs_file,
		'runs'       => $runs,
		'load_start' => $load_start,
		'load_end'   => $load_end,
		'totals'     => $totals,
		'results'    => $results,
	],
	JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
);

if ( ! is_string( $json ) || false === file_put_contents( $out, $json . "\n" ) ) {
	fail( "Cannot write {$out}." );
}

echo "Numbers written to {$out}\n";

if ( $totals['ids_differ'] > 0 ) {
	exit( 2 );
}

exit( $totals['bar_missed'] + $totals['weedout'] + $totals['errors'] > 0 ? 1 : 0 );
