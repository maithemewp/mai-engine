<?php
declare(strict_types=1);

/**
 * Mai grid optimizer replay (dev tool, not shipped).
 *
 * Runs the statement pairs from bin/grid-optimizer-pairs.php against a MySQL or MariaDB server,
 * and compares today's statement with the swapped EXISTS form. The session is set up the way
 * WordPress sets up its own: utf8mb4, and the SQL modes wpdb::set_sql_mode() removes (such as
 * ONLY_FULL_GROUP_BY and the STRICT modes) removed. On MariaDB the query cache is turned off for
 * the session, so a repeat run is never a cache hit.
 *
 * Before any pair runs, it counts the rows of the posts table and stops when that is zero, so a
 * database import that loaded nothing cannot read as a pass. It also prints the size of that table
 * and the server's InnoDB buffer pool, and warns when the pool is the smaller. A statement then
 * reads some pages from disk or the operating system's cache on every run, and its time depends
 * on what ran before it: the same fast statement was measured at 7 ms and at 30 ms on a server
 * with a 128 MB pool and a 900 MB posts table. Start the server with a pool larger than the data
 * before trusting a small difference. For each pair:
 *
 *   1. The IDs of both forms must match, in the same order. A pair whose IDs differ is a
 *      correctness bug. It is not timed and not counted against the bar. A pair where both forms
 *      return no rows is counted as empty, and is not compared or timed.
 *   2. Both forms run in one session, 10 runs each, timed on the server with NOW(6). The form that
 *      goes first alternates run by run. The time runs from one NOW(6) to the next, around the
 *      statement and the transfer of its rows, so it includes two short round trips. Both forms
 *      carry the same extra. The medians are compared.
 *   3. EXPLAIN FORMAT=TREE on MySQL, plain EXPLAIN on MariaDB. A swapped MySQL plan containing
 *      "weedout" or "Remove duplicates" is flagged.
 *   4. The bar passes when the swapped median is no more than today's median plus the larger of
 *      0.5 ms and 10%.
 *
 * It prints one summary line per pair and a total, and writes every number as JSON, also when
 * the run stops early (the file then says "complete": false). Exit code:
 *   0  every compared pair passes.
 *   1  a pair misses the bar, a swapped MySQL plan is flagged, a pair failed to run, an option is
 *      wrong, or the connection was lost.
 *   2  the IDs of a pair differ.
 *   3  nothing was compared: no pairs to run, every pair skipped, an empty posts table, or every
 *      pair empty.
 *
 * Usage:
 *   php bin/grid-optimizer-replay.php --pairs=FILE --db=NAME --engine=mysql|mariadb \
 *     [--host=127.0.0.1] [--port=3306] [--user=root] [--pass=] [--out=FILE] [--runs=10]
 *
 *   --pairs   The JSON lines file bin/grid-optimizer-pairs.php wrote.
 *   --engine  mysql uses "swapped_mysql" (with the hint). mariadb uses "swapped_mariadb". The
 *             server must be the engine named, or the run stops.
 *   --port    A number from 1 to 65535.
 *   --user    Defaults to root, with no password, as in a throwaway Docker container.
 *   --out     Defaults to /tmp/mai-optimizer-replay-<engine>-<db>-<port>.json.
 *   --runs    Timed runs of each form, a whole number, 10 unless set.
 *
 * Every option is written --name=value. An unknown option, an option given twice or a bad number
 * stops the run before anything is sent to the database.
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
 * The SQL modes WordPress takes out of its connection. The list in wpdb::$incompatible_modes.
 */
const INCOMPATIBLE_MODES = [ 'NO_ZERO_DATE', 'ONLY_FULL_GROUP_BY', 'STRICT_TRANS_TABLES', 'STRICT_ALL_TABLES', 'TRADITIONAL', 'ANSI' ];

/**
 * Prints a message to stderr and stops.
 *
 * @param string $message The message.
 * @param int    $code    The exit code.
 *
 * @return never
 */
function fail( string $message, int $code = 1 ): never {
	fwrite( STDERR, "Error: {$message}\n" );
	exit( $code );
}

/**
 * Reads the command line options, strictly.
 *
 * Every argument must be --name=value with a known name, given once.
 *
 * @param list<string> $argv  The arguments, the script name first.
 * @param list<string> $names The known option names.
 *
 * @return array<string,string>
 */
function read_options( array $argv, array $names ): array {
	$options = [];

	foreach ( array_slice( $argv, 1 ) as $argument ) {
		if ( 1 !== preg_match( '/^--([a-z]+)=(.*)$/s', $argument, $match ) ) {
			fail( "Unrecognized argument \"{$argument}\". Options are written --name=value." );
		}

		[ , $name, $value ] = $match;

		if ( ! in_array( $name, $names, true ) ) {
			fail( "Unknown option --{$name}." );
		}

		if ( array_key_exists( $name, $options ) ) {
			fail( "--{$name} was given more than once." );
		}

		$options[ $name ] = $value;
	}

	return $options;
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

/**
 * Sets the session up the way WordPress sets up its own connection.
 *
 * The character set is utf8mb4. The SQL modes are the server's, less the ones wpdb::set_sql_mode()
 * removes. On MariaDB the query cache is turned off, and an error from a server that has no such
 * variable is ignored.
 *
 * @param mysqli $db     The connection.
 * @param string $engine mysql or mariadb.
 *
 * @return array{charset:string,sql_mode:string,query_cache:string}
 */
function prepare_session( mysqli $db, string $engine ): array {
	$db->set_charset( 'utf8mb4' );

	$result = $db->query( 'SELECT @@SESSION.sql_mode' );
	$row    = $result instanceof mysqli_result ? $result->fetch_row() : null;
	$modes  = is_array( $row ) && is_string( $row[0] ?? null ) && '' !== $row[0] ? explode( ',', $row[0] ) : [];

	// As wpdb does: nothing to change when the server has no modes.
	if ( $modes ) {
		$kept = array_filter( $modes, static fn( string $mode ): bool => ! in_array( $mode, INCOMPATIBLE_MODES, true ) );

		$db->query( "SET SESSION sql_mode='" . $db->real_escape_string( implode( ',', $kept ) ) . "'" );
	}

	$query_cache = 'not applicable, MySQL 8 has no query cache';

	if ( 'mariadb' === $engine ) {
		try {
			$db->query( 'SET SESSION query_cache_type = OFF' );

			$query_cache = 'off for the session';
		} catch ( mysqli_sql_exception $exception ) {
			$query_cache = 'not set: ' . $exception->getMessage();
		}
	}

	$result = $db->query( 'SELECT @@SESSION.sql_mode, @@character_set_connection' );
	$row    = $result instanceof mysqli_result ? $result->fetch_row() : null;
	$row    = is_array( $row ) ? $row : [ '', '' ];

	return [
		'charset'     => (string) $row[1],
		'sql_mode'    => (string) $row[0],
		'query_cache' => $query_cache,
	];
}

/**
 * Runs one pair and returns what happened. It never prints.
 *
 * The status is "empty" when both forms return no rows, "diff" when the IDs differ, otherwise
 * "pass" or "miss" against the bar. Only a pass or a miss is timed.
 *
 * @param mysqli $db      The connection.
 * @param string $name    The pair's name.
 * @param string $today   Today's statement.
 * @param string $swapped The swapped statement.
 * @param string $engine  mysql or mariadb.
 * @param int    $runs    Timed runs of each form.
 *
 * @return array<string,mixed>
 */
function run_pair( mysqli $db, string $name, string $today, string $swapped, string $engine, int $runs ): array {
	// The first run of each form checks the IDs and warms the buffer pool. It is not timed.
	$ids_today   = fetch_ids( $db, $today );
	$ids_swapped = fetch_ids( $db, $swapped );

	$result = [
		'name'         => $name,
		'status'       => 'pass',
		'error'        => null,
		'ids_match'    => $ids_today === $ids_swapped,
		'rows'         => count( $ids_today ),
		'rows_swapped' => count( $ids_swapped ),
		'weedout'      => false,
	];

	if ( [] === $ids_today && [] === $ids_swapped ) {
		$result['status'] = 'empty';

		return $result;
	}

	if ( ! $result['ids_match'] ) {
		$first = count( $ids_today );

		foreach ( $ids_today as $index => $id ) {
			if ( ( $ids_swapped[ $index ] ?? null ) !== $id ) {
				$first = $index;
				break;
			}
		}

		$result['status']           = 'diff';
		$result['first_difference'] = $first;
		$result['plan_today']       = plan_of( $db, $today, $engine );
		$result['plan_swapped']     = plan_of( $db, $swapped, $engine );
		$result['weedout']          = 'mysql' === $engine && 1 === preg_match( '/weedout|Remove duplicates/i', $result['plan_swapped'] );

		return $result;
	}

	$today_ms   = [];
	$swapped_ms = [];

	// The form that goes first alternates, so neither one always runs on a warmer cache.
	for ( $run = 0; $run < $runs; $run++ ) {
		if ( 0 === $run % 2 ) {
			$today_ms[]   = timed_ms( $db, $today );
			$swapped_ms[] = timed_ms( $db, $swapped );
		} else {
			$swapped_ms[] = timed_ms( $db, $swapped );
			$today_ms[]   = timed_ms( $db, $today );
		}
	}

	$plan_today     = plan_of( $db, $today, $engine );
	$plan_swapped   = plan_of( $db, $swapped, $engine );
	$today_median   = median( $today_ms );
	$swapped_median = median( $swapped_ms );
	$allowed        = $today_median + max( 0.5, 0.10 * $today_median );
	$round          = static fn( float $value ): float => round( $value, 3 );

	$result['today_runs_ms']     = array_map( $round, $today_ms );
	$result['swapped_runs_ms']   = array_map( $round, $swapped_ms );
	$result['today_median_ms']   = $round( $today_median );
	$result['swapped_median_ms'] = $round( $swapped_median );
	$result['allowed_ms']        = $round( $allowed );
	$result['bar_met']           = $swapped_median <= $allowed;
	$result['status']            = $result['bar_met'] ? 'pass' : 'miss';
	$result['weedout']           = 'mysql' === $engine && 1 === preg_match( '/weedout|Remove duplicates/i', $plan_swapped );
	$result['plan_today']        = $plan_today;
	$result['plan_swapped']      = $plan_swapped;

	return $result;
}

$options = read_options( $_SERVER['argv'], [ 'pairs', 'db', 'engine', 'host', 'port', 'user', 'pass', 'out', 'runs' ] );

$pairs_file = $options['pairs'] ?? '';
$database   = $options['db'] ?? '';
$engine     = $options['engine'] ?? '';
$host       = $options['host'] ?? '127.0.0.1';
$user       = $options['user'] ?? 'root';
$password   = $options['pass'] ?? '';

if ( '' === $pairs_file || '' === $database || ! in_array( $engine, [ 'mysql', 'mariadb' ], true ) ) {
	fail( 'Usage: php bin/grid-optimizer-replay.php --pairs=FILE --db=NAME --engine=mysql|mariadb [--host=127.0.0.1] [--port=3306] [--user=root] [--pass=] [--out=FILE] [--runs=10]' );
}

$port = $options['port'] ?? '3306';
$runs = $options['runs'] ?? '10';

if ( ! ctype_digit( $port ) || (int) $port < 1 || (int) $port > 65535 ) {
	fail( "--port must be a number from 1 to 65535, not \"{$port}\"." );
}

if ( ! ctype_digit( $runs ) || (int) $runs < 1 ) {
	fail( "--runs must be a whole number of 1 or more, not \"{$runs}\"." );
}

$port = (int) $port;
$runs = (int) $runs;

if ( ! is_readable( $pairs_file ) ) {
	fail( "Cannot read {$pairs_file}." );
}

$out = $options['out'] ?? "/tmp/mai-optimizer-replay-{$engine}-{$database}-{$port}.json";

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

$session    = prepare_session( $db, $engine );
$key        = 'mysql' === $engine ? 'swapped_mysql' : 'swapped_mariadb';
$load_start = load_average();
$totals     = [
	'pairs'      => 0,
	'empty'      => 0,
	'ids_match'  => 0,
	'ids_differ' => 0,
	'bar_met'    => 0,
	'bar_missed' => 0,
	'weedout'    => 0,
	'errors'     => 0,
	'skipped'    => 0,
];

// Everything written to the JSON. The shutdown function writes it, so the numbers survive a lost
// connection or any other early stop.
$report = [
	'server'         => $version,
	'engine'         => $engine,
	'database'       => $database,
	'port'           => $port,
	'pairs_file'     => $pairs_file,
	'runs'           => $runs,
	'session'        => $session,
	'tables'         => [],
	'buffer_pool_mb' => 0,
	'load_start'     => $load_start,
	'load_end'       => [],
	'complete'       => false,
	'totals'         => &$totals,
	'results'        => [],
];

register_shutdown_function(
	static function () use ( &$report, $out ): void {
		$report['load_end'] = load_average();

		$json = json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE );

		if ( ! is_string( $json ) || false === file_put_contents( $out, $json . "\n" ) ) {
			fwrite( STDERR, "Error: cannot write {$out}.\n" );
			return;
		}

		echo "Numbers written to {$out}" . ( $report['complete'] ? '' : ' (the run did not finish)' ) . "\n";
	}
);

echo "Server {$version}, database {$database}, {$runs} runs of each form.\n";
echo "Session: character set {$session['charset']}, sql_mode '{$session['sql_mode']}', query cache {$session['query_cache']}.\n";
echo 'Load at start: ' . implode( ' ', $load_start ) . "\n";

if ( isset( $load_start[0] ) && $load_start[0] >= 20 ) {
	echo "Warning: the 1-minute load is 20 or more. Wait for it to drop before trusting these times.\n";
}

// Read every pair first, so the preflight below knows which tables to count.
$runnable = [];

foreach ( (array) file( $pairs_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) as $number => $row ) {
	$pair = json_decode( (string) $row, true );
	$name = is_array( $pair ) && is_string( $pair['name'] ?? null ) ? $pair['name'] : 'line ' . ( $number + 1 );

	if ( ! is_array( $pair ) || ! is_string( $pair['original'] ?? null ) ) {
		echo "SKIP  {$name}: not a pair line\n";
		++$totals['skipped'];
		continue;
	}

	$swapped = $pair[ $key ] ?? null;

	if ( ! is_string( $swapped ) || '' === $swapped ) {
		echo "SKIP  {$name}: no {$key} text\n";
		++$totals['skipped'];
		continue;
	}

	if ( ! str_starts_with( ltrim( $pair['original'] ), 'SELECT' ) || ! str_starts_with( ltrim( $swapped ), 'SELECT' ) ) {
		echo "SKIP  {$name}: not a SELECT\n";
		++$totals['skipped'];
		continue;
	}

	$runnable[] = [
		'name'    => $name,
		'today'   => $pair['original'],
		'swapped' => $swapped,
	];
}

if ( ! $runnable ) {
	fail( "Nothing to run: no usable pair in {$pairs_file} ({$totals['skipped']} skipped).", 3 );
}

// Preflight: the posts table must hold rows. The first FROM of each statement is its posts table.
$tables = [];

foreach ( $runnable as $pair ) {
	if ( 1 === preg_match( '/^\s*SELECT\b.*?\bFROM\s+`?([A-Za-z0-9_$]+)`?/s', $pair['today'], $match ) ) {
		$tables[ $match[1] ] = true;
	}
}

if ( ! $tables ) {
	fail( 'Cannot find the posts table in the statements.', 3 );
}

foreach ( array_keys( $tables ) as $table ) {
	try {
		$result = $db->query( "SELECT COUNT(*) FROM `{$table}`" );
		$row    = $result instanceof mysqli_result ? $result->fetch_row() : null;
		$count  = is_array( $row ) ? (int) $row[0] : 0;
	} catch ( mysqli_sql_exception $exception ) {
		fail( "Cannot count the rows of {$table} in {$database}: " . $exception->getMessage(), 3 );
	}

	$result  = $db->query( "SELECT data_length + index_length FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '" . $db->real_escape_string( $table ) . "'" );
	$row     = $result instanceof mysqli_result ? $result->fetch_row() : null;
	$size_mb = is_array( $row ) ? (int) round( (float) $row[0] / 1048576 ) : 0;

	$report['tables'][ $table ] = [
		'rows'    => $count,
		'size_mb' => $size_mb,
	];

	echo "Preflight: {$table} has {$count} rows, {$size_mb} MB with its indexes.\n";

	if ( 0 === $count ) {
		fail( "{$table} is empty in {$database}. A pass here would mean nothing. Check that the import loaded.", 3 );
	}
}

$result = $db->query( 'SELECT @@innodb_buffer_pool_size' );
$row    = $result instanceof mysqli_result ? $result->fetch_row() : null;
$pool   = is_array( $row ) ? (int) round( (float) $row[0] / 1048576 ) : 0;

$report['buffer_pool_mb'] = $pool;

echo "Preflight: InnoDB buffer pool is {$pool} MB.\n";

foreach ( $report['tables'] as $table => $info ) {
	if ( $pool > 0 && $pool < $info['size_mb'] ) {
		echo "Warning: the buffer pool ({$pool} MB) is smaller than {$table} ({$info['size_mb']} MB). Times then depend on what the operating system has cached, and a fast statement can be off by tens of milliseconds. Start the server with a larger innodb_buffer_pool_size.\n";
	}
}

foreach ( $runnable as $pair ) {
	++$totals['pairs'];

	try {
		$result = run_pair( $db, $pair['name'], $pair['today'], $pair['swapped'], $engine, $runs );
	} catch ( Throwable $exception ) {
		$result = [
			'name'   => $pair['name'],
			'status' => 'error',
			'error'  => $exception->getMessage(),
		];

		++$totals['errors'];

		echo "ERROR {$pair['name']}: {$result['error']}\n";

		$report['results'][] = $result;

		// A stopped or failed statement can leave the connection unusable, so check it.
		try {
			$db->query( 'SELECT 1' );
		} catch ( Throwable ) {
			fail( 'The connection is gone. Stopped at ' . $pair['name'] . '.' );
		}

		continue;
	}

	$report['results'][] = $result;

	$totals['weedout'] += $result['weedout'] ? 1 : 0;

	$flag = $result['weedout'] ? '  WEEDOUT' : '';

	switch ( $result['status'] ) {
		case 'empty':
			++$totals['empty'];

			echo "EMPTY  {$pair['name']}  both forms returned 0 rows, not compared\n";
			break;

		case 'diff':
			++$totals['ids_differ'];

			printf(
				"DIFF   %s  IDS DIFFER at %d (%d rows today, %d swapped)%s\n",
				$pair['name'],
				$result['first_difference'],
				$result['rows'],
				$result['rows_swapped'],
				$flag
			);
			break;

		default:
			// The bar counts only pairs whose IDs match.
			++$totals['ids_match'];
			++$totals[ 'pass' === $result['status'] ? 'bar_met' : 'bar_missed' ];

			printf(
				"%-6s %s  today %.2f ms  swapped %.2f ms  rows %d%s\n",
				'pass' === $result['status'] ? 'PASS' : 'MISS',
				$pair['name'],
				$result['today_median_ms'],
				$result['swapped_median_ms'],
				$result['rows'],
				$flag
			);
	}
}

$report['complete'] = true;

printf(
	"Total: %d pairs, %d empty (both forms returned 0 rows, not compared), IDs match %d, IDs differ %d, bar met %d, bar missed %d, weedout %d, errors %d, skipped %d. Load at end: %s\n",
	$totals['pairs'],
	$totals['empty'],
	$totals['ids_match'],
	$totals['ids_differ'],
	$totals['bar_met'],
	$totals['bar_missed'],
	$totals['weedout'],
	$totals['errors'],
	$totals['skipped'],
	implode( ' ', load_average() )
);

if ( $totals['ids_differ'] > 0 ) {
	exit( 2 );
}

if ( $totals['bar_missed'] + $totals['weedout'] + $totals['errors'] > 0 ) {
	exit( 1 );
}

if ( 0 === $totals['ids_match'] ) {
	fwrite( STDERR, "Error: nothing was compared. Every pair was empty or skipped.\n" );
	exit( 3 );
}

exit( 0 );
