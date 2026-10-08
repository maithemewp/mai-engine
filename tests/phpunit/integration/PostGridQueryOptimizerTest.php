<?php
declare(strict_types=1);

namespace BizBudding\MaiEngine\Tests\Integration;

use Mai_Post_Grid_Query_Optimizer;
use Mai_Post_Grid_Query_Optimizer_Database;
use Mai_Post_Grid_Query_Optimizer_Sql;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionObject;
use ReflectionProperty;
use RuntimeException;
use WP_Query;
use WP_UnitTest_Factory;

/**
 * The grid query optimizer's marks, looks, prepared swaps and the swap.
 *
 * Each run marks a new WP_Query the way a grid or its copy would, runs it, and captures every
 * statement sent, with a query callback at PHP_INT_MAX added after the optimizer's, so it sees
 * the swapped text. A swapped run is compared with the same query when the filter turns the
 * optimizer off, and every run must have sent its statement to the posts table, so a run
 * answered from a cache cannot pass by luck.
 *
 * Every run holds recover() back at posts_results (without_recovery()), so these tests read
 * outcome() and the prepared swaps themselves. PostGridQueryOptimizerGridTest tests recover().
 */
final class PostGridQueryOptimizerTest extends MaiIntegrationTestCase {

	use PostGridQueryOptimizerFixture;

	/**
	 * What only Mai's swap writes. Core's own EXISTS operator writes `EXISTS (` too.
	 */
	private const SWAP = 'EXISTS ( SELECT ';

	/**
	 * The MySQL hint, as written after SELECT inside each EXISTS.
	 */
	private const HINT = '/*+ NO_SEMIJOIN(DUPSWEEDOUT) */ ';

	/**
	 * The fixture's IDs, built once for the class.
	 *
	 * @var array
	 */
	private static array $fixture = [];

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ): void {
		self::$fixture = self::create_optimizer_fixture( $factory );
	}

	public static function wpTearDownAfterClass(): void {
		self::remove_optimizer_fixture();
	}

	/** Cases where today's statement must go out unchanged. */
	public static function step_aside_cases(): array {
		$names = [
			'filter false',
			'transient set',
			'unmarked',
			'meta query',
			's set',
			's set, its search SQL and order removed',
			'no_found_rows false',
			'orderby rand',
			'orderby RAND(7)',
			'orderby menu order',
			'orderby ID',
			'tiebreaker removed',
			'ORDER BY names the term table',
			'term order put first after the tiebreaker',
			'ID key taken off after the tiebreaker',
			'no LIMIT',
			'nested tax query',
			'OR with NOT IN',
			'OR with IN then lowercase in',
			'only NOT IN',
			'deleted term',
			'WordPress joins another table',
			'where changed at 10',
			'join changed at 10',
			'groupby changed at 10',
			'distinct changed at 10',
			'fields changed at 10',
			'where appended at posts_clauses 999',
			'posts_search adds text with s empty',
			'posts_request changes the statement',
			'posts_request_ids changes the split statement',
			'query callback at 10 changes the statement',
			'posts_where callbacks removed',
			'title with a percent sign',
		];

		return array_combine( $names, array_map( static fn( string $name ): array => [ $name ], $names ) );
	}

	public function test_swaps_a_covered_query(): void {
		// Read before the next run, which starts a new request and clears the record.
		$swapped = $this->run_marked( [] );
		$outcome = Mai_Post_Grid_Query_Optimizer::instance()->outcome( $swapped['query'] );
		$again   = Mai_Post_Grid_Query_Optimizer::instance()->outcome( $swapped['query'] );
		$today   = $this->run_today( [] );

		$statements = self::swapped( $swapped );

		$this->assertCount( 1, $statements );
		$this->assertStringNotContainsString( 'GROUP BY', $statements[0] );
		$this->assert_hint( $statements[0], 1 );
		$this->assertSame( $today['ids'], $swapped['ids'] );
		$this->assertSame( array_slice( self::$fixture['posts']['big'], 0, 7 ), $swapped['ids'] );
		$this->assertNotSame( array_slice( self::$fixture['newest'], 0, 7 ), $swapped['ids'] );

		// The query's own text is never changed, only the statement on its way out.
		$this->assertStringNotContainsString( self::SWAP, $swapped['query']->request );
		$this->assertStringContainsString( 'GROUP BY', $swapped['query']->request );

		$this->assertSame( 'ok', $outcome['status'] ?? null );
		$this->assertSame( 'split', $outcome['form'] ?? null );
		$this->assertNull( $again, 'outcome() forgets the record.' );
	}

	public function test_swaps_an_and_query_with_two_in_filters(): void {
		$args = [
			'tax_query' => [
				'relation' => 'AND',
				[ 'taxonomy' => 'category', 'terms' => [ self::$fixture['big'] ] ],
				[ 'taxonomy' => 'post_tag', 'terms' => [ self::$fixture['tag'] ] ],
			],
		];

		$swapped = $this->run_marked( $args );
		$today   = $this->run_today( $args );

		$statements = self::swapped( $swapped );

		$this->assertCount( 1, $statements );
		$this->assert_hint( $statements[0], 2 );
		$this->assertSame( $today['ids'], $swapped['ids'] );
		$this->assertSame( array_slice( array_values( array_intersect( self::$fixture['posts']['big'], self::$fixture['posts']['tag'] ) ), 0, 7 ), $swapped['ids'] );
	}

	public function test_swaps_the_full_form(): void {
		global $wpdb;

		add_filter( 'split_the_query', '__return_false' );

		$swapped = $this->run_marked( [] );
		$outcome = Mai_Post_Grid_Query_Optimizer::instance()->outcome( $swapped['query'] );
		$today   = $this->run_today( [] );

		$statements = self::swapped( $swapped );

		$this->assertCount( 1, $statements );
		$this->assertStringStartsWith( "SELECT   {$wpdb->posts}.*", $statements[0] );
		$this->assertStringNotContainsString( 'GROUP BY', $statements[0] );
		$this->assertSame( $today['ids'], $swapped['ids'] );
		$this->assertSame( array_slice( self::$fixture['posts']['big'], 0, 7 ), $swapped['ids'] );
		$this->assertSame( 'ok', $outcome['status'] ?? null );
		$this->assertSame( 'full', $outcome['form'] ?? null );
	}

	public function test_ids_fields_only_for_copies(): void {
		global $wpdb;

		$args = [ 'fields' => 'ids' ];

		$copy    = $this->run_marked( $args, 'copy' );
		$outcome = Mai_Post_Grid_Query_Optimizer::instance()->outcome( $copy['query'] );
		$today   = $this->run_today( $args, 'copy' );

		$statements = self::swapped( $copy );

		$this->assertCount( 1, $statements );
		$this->assertStringStartsWith( "SELECT   {$wpdb->posts}.ID", $statements[0] );
		$this->assertSame( $today['ids'], $copy['ids'] );
		$this->assertSame( array_slice( self::$fixture['posts']['big'], 0, 7 ), $copy['ids'] );
		$this->assertSame( 'ok', $outcome['status'] ?? null, 'A good copy statement is the only one sent, so the statement count matches. Only the empty last_error keeps it from reading as failed.' );
		$this->assertSame( 'copy', $outcome['form'] ?? null );

		$grid    = $this->run_marked( $args, 'grid' );
		$outcome = Mai_Post_Grid_Query_Optimizer::instance()->outcome( $grid['query'] );
		$today   = $this->run_today( $args, 'grid' );

		$this->assertSame( [], self::swapped( $grid ) );
		$this->assertNull( $outcome );
		$this->assertSame( $today['ids'], $grid['ids'] );
	}

	#[DataProvider( 'step_aside_cases' )]
	public function test_steps_aside( string $case ): void {
		[ $args, $role, $sorted ] = $this->set_up_case( $case );

		$run     = $this->run_marked( $args, $role );
		$outcome = Mai_Post_Grid_Query_Optimizer::instance()->outcome( $run['query'] );
		$today   = $this->run_today( $args, $role );

		$this->assertSame( [], self::swapped( $run ) );
		$this->assertNull( $outcome );

		if ( $sorted ) {
			sort( $run['ids'] );
			sort( $today['ids'] );
		}

		$this->assertSame( $today['ids'], $run['ids'] );
	}

	public function test_callbacks_accept_null(): void {
		$optimizer = Mai_Post_Grid_Query_Optimizer::instance();
		$marked    = new WP_Query();

		$optimizer->mark( $marked, 'grid' );

		foreach ( [ null, $marked ] as $query ) {
			foreach ( [ 'where', 'join', 'groupby', 'distinct', 'fields' ] as $part ) {
				$this->assertNull( $optimizer->first_look( $part, null, $query ) );
			}

			$this->assertNull( $optimizer->last_search( null, $query ) );
			$this->assertNull( $optimizer->last_look( null, $query ) );
			$this->assertNull( $optimizer->first_request( null, $query ) );
			$this->assertNull( $optimizer->prepare( null, $query ) );
		}

		// The closures registered for the first looks, as core would call them.
		foreach ( [ 'posts_where', 'posts_join', 'posts_groupby', 'posts_distinct', 'posts_fields' ] as $hook ) {
			$callbacks = $GLOBALS['wp_filter'][ $hook ]->callbacks[ PHP_INT_MIN ] ?? [];

			$this->assertNotEmpty( $callbacks, $hook );

			foreach ( $callbacks as $callback ) {
				$this->assertNull( call_user_func( $callback['function'], null, null ) );
				$this->assertNull( call_user_func( $callback['function'], null, $marked ) );
			}
		}

		$this->assertNull( $optimizer->swap( null ) );

		// And with a prepared swap waiting.
		$this->prepare_without_sending( [] );

		$this->assertNull( $optimizer->swap( null ) );
	}

	public function test_a_prepared_swap_is_used_once(): void {
		$swapped = $this->run_marked( [] );

		$this->assertCount( 1, self::swapped( $swapped ) );

		// The identical statement, from a query nobody marked, in the same request.
		$again = $this->run_marked( [], '', false );

		$this->assertSame( [], self::swapped( $again ) );
		$this->assertSame( $swapped['ids'], $again['ids'] );
	}

	public function test_the_most_recent_prepared_swap_wins(): void {
		$this->new_request();

		// Two prepared swaps with the same text, as a copy inside its grid's query has.
		$older = $this->prepare_without_sending( [] );
		$newer = $this->prepare_without_sending( [] );

		$sent = $this->run_marked( [], '', false );

		$this->assertCount( 1, self::swapped( $sent ) );
		$this->assertSame( 'ok', Mai_Post_Grid_Query_Optimizer::instance()->outcome( $newer )['status'] ?? null );
		$this->assertNull( Mai_Post_Grid_Query_Optimizer::instance()->outcome( $older ) );
	}

	public function test_drop_removes_a_prepared_swap(): void {
		$this->new_request();

		// A prepared swap matches any statement with its exact text, so an unmarked query sending
		// the same statement takes it. That is what a copy inside its grid's query relies on.
		$this->prepare_without_sending( [] );

		$taken = $this->run_marked( [], '', false );

		$this->assertCount( 1, self::swapped( $taken ) );

		$this->new_request();

		$pending = $this->prepare_without_sending( [] );

		Mai_Post_Grid_Query_Optimizer::instance()->drop( $pending );

		$left = $this->run_marked( [], '', false );

		$this->assertSame( [], self::swapped( $left ) );
		$this->assertSame( $taken['ids'], $left['ids'] );
	}

	public function test_turn_off(): void {
		$optimizer = Mai_Post_Grid_Query_Optimizer::instance();

		// A swap leaves a record until outcome() reads it, and a query answered elsewhere leaves
		// its prepared swap waiting.
		$swapped = $this->run_marked( [] );

		$this->assertCount( 1, self::swapped( $swapped ) );

		$this->prepare_without_sending( [] );

		$logged = [];

		$optimizer->set_logger(
			static function ( string $message ) use ( &$logged ): void {
				$logged[] = $message;
			}
		);

		$optimizer->turn_off( 'failed', 'x' );

		$this->assertSame( 'failed: x', get_transient( Mai_Post_Grid_Query_Optimizer::TRANSIENT ), 'the transient holds the reason' );
		$this->assertEqualsWithDelta( time() + DAY_IN_SECONDS, (int) get_option( '_transient_timeout_' . Mai_Post_Grid_Query_Optimizer::TRANSIENT ), 5, 'for a day' );
		$this->assertSame( [ 'Grid query optimizer off for 24 hours (failed): x' ], $logged );
		$this->assertNull( $optimizer->outcome( $swapped['query'] ) );

		// For the rest of this request the flag alone keeps it off, with the transient gone.
		delete_transient( Mai_Post_Grid_Query_Optimizer::TRANSIENT );

		$unmarked = $this->run_marked( [], '', false );
		$next     = $this->run_marked( [], 'grid', false );

		$this->assertSame( [], self::swapped( $unmarked ), 'The waiting prepared swap was cleared.' );
		$this->assertSame( [], self::swapped( $next ) );
		$this->assertSame( array_slice( self::$fixture['posts']['big'], 0, 7 ), $next['ids'] );
	}

	/**
	 * The transient write can be a statement of its own, which comes back through swap(). By then
	 * nothing may be left to swap.
	 */
	public function test_turn_off_clears_the_request_before_writing_the_transient(): void {
		$optimizer = Mai_Post_Grid_Query_Optimizer::instance();

		$this->new_request();
		$this->prepare_without_sending( [] );

		$seen = null;

		add_filter(
			'pre_set_transient_' . Mai_Post_Grid_Query_Optimizer::TRANSIENT,
			static function ( $value ) use ( &$seen, $optimizer ) {
				$seen = [
					'prepared' => self::prepared_count(),
					'off'      => ( new ReflectionProperty( Mai_Post_Grid_Query_Optimizer::class, 'off' ) )->getValue( $optimizer ),
				];

				return $value;
			}
		);

		$optimizer->set_logger( static function (): void {} );
		$optimizer->turn_off( 'failed', 'x' );

		$this->assertSame(
			[
				'prepared' => 0,
				'off'      => true,
			],
			$seen
		);
	}

	public function test_turn_off_says_so_when_the_transient_did_not_stick(): void {
		$optimizer = Mai_Post_Grid_Query_Optimizer::instance();
		$logged    = [];

		// The read back finds nothing, as after a write that failed.
		add_filter( 'transient_' . Mai_Post_Grid_Query_Optimizer::TRANSIENT, '__return_false' );

		$optimizer->set_logger(
			static function ( string $message ) use ( &$logged ): void {
				$logged[] = $message;
			}
		);

		$optimizer->turn_off( 'failed', 'x' );

		$this->assertSame( [ 'Grid query optimizer off for this request only (failed): x' ], $logged );
	}

	public function test_the_default_logger_writes_whatever_wp_debug_log_says(): void {
		$this->assertFalse( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG, 'the debug log is off in this suite' );

		$file = (string) tempnam( sys_get_temp_dir(), 'mai-test-log-' );
		$was  = ini_set( 'error_log', $file );

		try {
			Mai_Post_Grid_Query_Optimizer::instance()->turn_off( 'failed', 'x' );
		} finally {
			ini_set( 'error_log', (string) $was );
		}

		$log = (string) file_get_contents( $file );

		unlink( $file );

		$this->assertStringContainsString( 'Mai Engine: Grid query optimizer off for 24 hours (failed): x', $log );
	}

	public function test_steps_aside_when_the_database_check_fails(): void {
		$optimizer = Mai_Post_Grid_Query_Optimizer::instance();
		$today     = $this->run_today( [] );

		$this->new_request();

		( new ReflectionProperty( Mai_Post_Grid_Query_Optimizer::class, 'database' ) )->setValue( $optimizer, false );

		$reads = 0;

		add_filter(
			'pre_transient_' . Mai_Post_Grid_Query_Optimizer::TRANSIENT,
			static function ( $pre ) use ( &$reads ) {
				++$reads;

				return $pre;
			}
		);

		$run = $this->run_marked( [], 'grid', false );

		$this->assertSame( [], self::swapped( $run ) );
		$this->assertSame( $today['ids'], $run['ids'] );
		$this->assertSame( 0, $reads, 'a database that cannot take the swap costs no transient read' );

		// And nothing more is prepared for the rest of the request.
		$this->prepare_without_sending( [], 0 );
	}

	public function test_steps_aside_for_another_database_layer(): void {
		global $wpdb;

		$optimizer = Mai_Post_Grid_Query_Optimizer::instance();

		$this->new_request();

		$split = (string) Mai_Post_Grid_Query_Optimizer_Sql::split( (string) $this->prepare_without_sending( [] )->request, $wpdb->posts );
		$real  = $wpdb;

		// Another layer on the same connection and transaction: every property of $wpdb, the
		// connection included, on an object of another class. Its constructor would connect again.
		// Not the open result, which a statement sent through the copy would free under $wpdb.
		$layer = new class() extends \wpdb {
			public function __construct() {}
		};

		foreach ( ( new ReflectionObject( $real ) )->getProperties() as $property ) {
			if ( ! $property->isStatic() && 'result' !== $property->getName() ) {
				$property->setValue( $layer, $property->getValue( $real ) );
			}
		}

		$this->assertSame( $real->dbh, $layer->dbh );

		$GLOBALS['wpdb'] = $layer;

		try {
			$sent = $optimizer->swap( $split );
		} finally {
			$GLOBALS['wpdb'] = $real;
		}

		$this->assertSame( $split, $sent, 'another layer gets the statement unchanged' );
		$this->prepare_without_sending( [], 0 );

		// WordPress's own layer swaps the same statement.
		$this->new_request();
		$this->prepare_without_sending( [] );

		$this->assertStringContainsString( self::SWAP, (string) $optimizer->swap( $split ) );
	}

	public function test_an_exception_inside_the_swap_sends_the_statement_unchanged_and_turns_it_off(): void {
		$optimizer = Mai_Post_Grid_Query_Optimizer::instance();
		$today     = $this->run_today( [] );
		$logged    = [];

		$this->new_request();

		$optimizer->set_logger(
			static function ( string $message ) use ( &$logged ): void {
				$logged[] = $message;
			}
		);

		// As a cache drop-in that throws on a read would, once, so the turn-off can read its
		// transient back.
		$thrown = 0;
		$throw  = static function ( $pre ) use ( &$thrown ) {
			if ( 0 === $thrown++ ) {
				throw new RuntimeException( 'Test cache read failure' );
			}

			return $pre;
		};

		add_filter( 'pre_transient_' . Mai_Post_Grid_Query_Optimizer::TRANSIENT, $throw );

		$run = $this->run_marked( [], 'grid', false );

		remove_filter( 'pre_transient_' . Mai_Post_Grid_Query_Optimizer::TRANSIENT, $throw );

		$this->assertGreaterThan( 0, $thrown );
		$this->assertSame( [], self::swapped( $run ) );
		$this->assertSame( $today['ids'], $run['ids'] );
		$this->assertSame( [ 'Grid query optimizer off for 24 hours (error): Test cache read failure' ], $logged, 'one line, so a cause that repeats logs once a day' );
		$this->assertSame( 'error: Test cache read failure', get_transient( Mai_Post_Grid_Query_Optimizer::TRANSIENT ), 'off for a day, with the reason' );

		$this->prepare_without_sending( [], 0 );

		$next = $this->run_marked( [], 'grid', false );

		$this->assertSame( [], self::swapped( $next ), 'off for the rest of the request' );
	}

	/** What can change between prepare() and the statement reaching the database. */
	public static function changes_before_the_statement(): array {
		return [
			'off for the request'        => [ 'off' ],
			'the filter says no'         => [ 'filter' ],
			'recover() taken off'        => [ 'recover' ],
			'nothing, so it is swapped'  => [ 'nothing' ],
		];
	}

	#[DataProvider( 'changes_before_the_statement' )]
	public function test_a_prepared_swap_is_checked_again_when_sent( string $change ): void {
		global $wpdb;

		$optimizer = Mai_Post_Grid_Query_Optimizer::instance();

		$this->new_request();

		$query = $this->prepare_without_sending( [] );
		$split = (string) Mai_Post_Grid_Query_Optimizer_Sql::split( (string) $query->request, $wpdb->posts );

		match ( $change ) {
			'off'     => ( new ReflectionProperty( Mai_Post_Grid_Query_Optimizer::class, 'off' ) )->setValue( $optimizer, true ),
			'filter'  => ( new ReflectionProperty( Mai_Post_Grid_Query_Optimizer::class, 'allowed' ) )->setValue( $optimizer, false ),
			'recover' => remove_filter( 'posts_results', [ $optimizer, 'recover' ], PHP_INT_MIN ),
			'nothing' => null,
		};

		$sent = $this->send( $split );

		if ( 'nothing' === $change ) {
			$this->assertCount( 1, self::swapped( $sent ) );
		} else {
			$this->assertSame( [], self::swapped( $sent ) );
		}

		$this->assertSame( array_slice( self::$fixture['posts']['big'], 0, 7 ), $sent['ids'] );
	}

	/**
	 * A copy's statement has the text of its grid's split form. A copy that prepared no swap of
	 * its own must not take the grid's.
	 */
	public function test_a_copy_cannot_take_its_grids_split_entry(): void {
		global $wpdb;

		$optimizer = Mai_Post_Grid_Query_Optimizer::instance();

		$this->new_request();

		$grid  = $this->prepare_without_sending( [] );
		$split = (string) Mai_Post_Grid_Query_Optimizer_Sql::split( (string) $grid->request, $wpdb->posts );
		$copy  = new WP_Query();

		$optimizer->mark( $copy, 'copy' );

		$while = $this->send( $split );

		$this->assertSame( [], self::swapped( $while ), 'not while the copy runs' );
		$this->assertSame( 1, self::prepared_count(), 'and the grid\'s swap is still waiting' );

		$optimizer->drop( $copy );

		$after = $this->send( $split );

		$this->assertCount( 1, self::swapped( $after ), 'the grid\'s statement takes it once the copy is done' );
		$this->assertSame( $while['ids'], $after['ids'] );
	}

	public function test_an_owner_has_one_prepared_swap(): void {
		$this->new_request();

		$query  = new WP_Query();
		$answer = static fn() => [];

		Mai_Post_Grid_Query_Optimizer::instance()->mark( $query, 'grid' );

		add_filter( 'posts_pre_query', $answer );

		// The same query run twice, answered both times before its statement is sent.
		$this->without_recovery( static fn() => $query->query( self::base_args() ) );
		$this->without_recovery( static fn() => $query->query( self::base_args() ) );

		remove_filter( 'posts_pre_query', $answer );

		$this->assertSame( 1, self::prepared_count() );
	}

	public function test_outcome_reports_a_changed_statement_that_failed(): void {
		global $wpdb;

		// A later query callback breaks the swapped text, so only the statement count is left to
		// pin the failure on the swap. The query is given posts, so the empty-posts rule cannot.
		$done    = false;
		$breaker = static function ( $sql ) use ( &$done ) {
			if ( ! $done && is_string( $sql ) && str_contains( $sql, self::SWAP ) ) {
				$done = true;

				return $sql . ' BROKEN';
			}

			return $sql;
		};

		add_filter( 'query', $breaker, PHP_INT_MAX );

		$suppress = $wpdb->suppress_errors( true );

		try {
			$run = $this->run_marked( [] );

			$run['query']->posts = [ 1 ];

			$outcome = Mai_Post_Grid_Query_Optimizer::instance()->outcome( $run['query'] );
		} finally {
			$wpdb->suppress_errors( $suppress );
		}

		$this->assertSame( [], $run['ids'] );
		$this->assertSame( 'failed', $outcome['status'] ?? null );
		$this->assertSame( 'split', $outcome['form'] ?? null );
	}

	public function test_outcome_reports_a_refused_statement_after_another_one(): void {
		// MySQL refuses the swapped text unchanged, and the refusal sends a statement of its own
		// first, so only the text is left to pin the failure on the swap. The query is given
		// posts, so the empty-posts rule cannot. Read inside the callback, since refuse_once()
		// sends one more statement when it is done.
		[ [ $run, $outcome ], $refused ] = $this->refuse_once(
			static fn( string $sql ): bool => str_contains( $sql, self::SWAP ),
			function (): array {
				$run = $this->run_marked( [] );

				$run['query']->posts = [ 1 ];

				return [ $run, Mai_Post_Grid_Query_Optimizer::instance()->outcome( $run['query'] ) ];
			}
		);

		$this->assertTrue( $refused );
		$this->assertSame( [], $run['ids'] );
		$this->assertSame( 'failed', $outcome['status'] ?? null );
	}

	public function test_outcome_reports_a_changed_statement_that_failed_after_another_one(): void {
		global $wpdb;

		// A later query callback breaks the swapped text and sends a statement of its own, so
		// neither the count nor the text pins the failure. The query's empty posts do.
		$done    = false;
		$breaker = static function ( $sql ) use ( &$done, $wpdb ) {
			if ( ! $done && is_string( $sql ) && str_contains( $sql, self::SWAP ) ) {
				$done = true;

				$wpdb->query( 'SELECT 1' );

				return $sql . ' BROKEN';
			}

			return $sql;
		};

		add_filter( 'query', $breaker, PHP_INT_MAX );

		$suppress = $wpdb->suppress_errors( true );

		try {
			$run     = $this->run_marked( [] );
			$outcome = Mai_Post_Grid_Query_Optimizer::instance()->outcome( $run['query'] );
		} finally {
			$wpdb->suppress_errors( $suppress );
		}

		$this->assertTrue( $done );
		$this->assertSame( [], $run['ids'] );
		$this->assertSame( 'failed', $outcome['status'] ?? null );
		$this->assertSame( 'split', $outcome['form'] ?? null );
	}

	public function test_outcome_reports_an_empty_swapped_statement_as_ok(): void {
		$run     = $this->run_marked( [ 'tax_query' => [ [ 'taxonomy' => 'category', 'terms' => [ self::$fixture['empty'] ] ] ] ] );
		$outcome = Mai_Post_Grid_Query_Optimizer::instance()->outcome( $run['query'] );

		$this->assertCount( 1, self::swapped( $run ) );
		$this->assertSame( [], $run['ids'] );
		$this->assertSame( 'ok', $outcome['status'] ?? null );
		$this->assertSame( 'split', $outcome['form'] ?? null );
	}

	/**
	 * A later query callback returns an empty string for the swapped text. $wpdb then sends
	 * nothing and returns before it clears the last result, so get_col() hands back the rows of
	 * the statement before, with no error.
	 */
	public function test_outcome_reports_a_swapped_statement_that_was_never_sent(): void {
		global $wpdb;

		$this->new_request();

		$query = $this->prepare_without_sending( [] );
		$split = (string) Mai_Post_Grid_Query_Optimizer_Sql::split( (string) $query->request, $wpdb->posts );
		$done  = false;

		add_filter(
			'query',
			static function ( $sql ) use ( &$done ) {
				if ( ! $done && is_string( $sql ) && str_contains( $sql, self::SWAP ) ) {
					$done = true;

					return '';
				}

				return $sql;
			},
			PHP_INT_MAX
		);

		// Read once here, so the optimizer's own read of it sends nothing in between.
		get_transient( Mai_Post_Grid_Query_Optimizer::TRANSIENT );

		$wpdb->query( 'SELECT 42' );

		$ids     = $wpdb->get_col( $split );
		$outcome = Mai_Post_Grid_Query_Optimizer::instance()->outcome( $query );

		$this->assertTrue( $done );
		$this->assertSame( [ '42' ], $ids, 'the rows of the statement before' );
		$this->assertSame( '', $wpdb->last_error );
		$this->assertSame( 'failed', $outcome['status'] ?? null );
		$this->assertSame( 'statement never sent', $outcome['error'] ?? null );
	}

	/**
	 * A later query callback sends a statement of its own while the swapped text passes through
	 * it, then lets the swapped text go out unchanged. That statement comes before the swapped
	 * one reaches the database, so it does not count as the next one.
	 */
	public function test_a_statement_sent_inside_the_swapped_statements_filters_is_not_the_next_one(): void {
		global $wpdb;

		$done = false;

		add_filter(
			'query',
			static function ( $sql ) use ( &$done, $wpdb ) {
				if ( ! $done && is_string( $sql ) && str_contains( $sql, self::SWAP ) ) {
					$done = true;

					$wpdb->query( 'SELECT 1' );
				}

				return $sql;
			},
			PHP_INT_MAX
		);

		$run     = $this->run_marked( [] );
		$outcome = Mai_Post_Grid_Query_Optimizer::instance()->outcome( $run['query'] );

		$this->assertTrue( $done );
		$this->assertCount( 1, self::swapped( $run ) );
		$this->assertSame( array_slice( self::$fixture['posts']['big'], 0, 7 ), $run['ids'] );
		$this->assertSame( 'ok', $outcome['status'] ?? null );
	}

	/**
	 * MySQL refuses the swapped text unchanged, and refuse_once() then sends a statement that
	 * works, which clears $wpdb's error. The query is given posts, so the empty-posts rule cannot
	 * catch it. Only the list of failed statements WordPress keeps is left.
	 */
	public function test_outcome_reports_a_refused_statement_after_a_later_one_worked(): void {
		global $wpdb;

		[ $run, $refused ] = $this->refuse_once(
			static fn( string $sql ): bool => str_contains( $sql, self::SWAP ),
			fn(): array => $this->run_marked( [] )
		);

		$run['query']->posts = [ 1 ];

		$this->assertTrue( $refused );
		$this->assertSame( '', $wpdb->last_error, 'a later statement cleared the error' );
		$this->assertSame( 'failed', Mai_Post_Grid_Query_Optimizer::instance()->outcome( $run['query'] )['status'] ?? null );
	}

	/** The hooks whose look a second run of the same query loses. */
	public static function second_run_hooks(): array {
		return [
			'posts_join'   => [ 'posts_join' ],
			'posts_search' => [ 'posts_search' ],
		];
	}

	#[DataProvider( 'second_run_hooks' )]
	public function test_a_second_run_never_reuses_old_looks( string $hook ): void {
		$first = $this->run_marked( [] );

		$this->assertCount( 1, self::swapped( $first ) );

		// The same query object again, in the same request, with its look on this hook gone. The
		// look from the first run must not stand in for it.
		remove_all_filters( $hook );

		$second = $this->run_query( $first['query'], [] );

		$this->assertSame( [], self::swapped( $second ) );
		$this->assertSame( $first['ids'], $second['ids'] );
	}

	public function test_a_rerun_without_its_looks_reuses_no_verdict(): void {
		$first = $this->run_marked( [] );

		$this->assertCount( 1, self::swapped( $first ) );
		$this->assertSame( 0, self::prepared_count() );

		// The same query object again, with both the look that starts a fresh record and the one
		// that gives the verdict gone. The first run's verdict must not stand in for the second's.
		remove_all_filters( 'posts_where' );
		remove_all_filters( 'posts_clauses_request' );

		$second = $this->run_query( $first['query'], [] );

		$this->assertSame( 0, self::prepared_count() );
		$this->assertSame( [], self::swapped( $second ) );
		$this->assertSame( $first['ids'], $second['ids'] );
	}

	/**
	 * PHP gives a freed object's ID to the next object it makes. A rebuild kept by the query's
	 * object ID would hand the first grid's taxonomy SQL to the second, which would then step
	 * aside.
	 */
	public function test_rebuilds_are_not_shared_between_grids(): void {
		$big = $this->run_marked( [] );
		$id  = spl_object_id( $big['query'] );

		$this->assertCount( 1, self::swapped( $big ) );
		$this->assertSame( array_slice( self::$fixture['posts']['big'], 0, 7 ), $big['ids'] );

		unset( $big );

		$tag = $this->run_marked( [ 'tax_query' => [ [ 'taxonomy' => 'post_tag', 'terms' => [ self::$fixture['tag'] ] ] ] ], 'grid', false );

		$this->assertSame( $id, spl_object_id( $tag['query'] ), 'the second query has the first one\'s object ID' );
		$this->assertCount( 1, self::swapped( $tag ) );
		$this->assertSame( array_slice( self::$fixture['posts']['tag'], 0, 7 ), $tag['ids'] );
	}

	/**
	 * Adds a step-aside case's hooks, and returns the args and role to run it with.
	 *
	 * @param string $case The case name.
	 *
	 * @return array{0:array,1:string,2:bool} Query args, role ('' for unmarked), and whether to
	 *                                        compare the IDs sorted.
	 */
	private function set_up_case( string $case ): array {
		global $wpdb;

		$posts  = $wpdb->posts;
		$terms  = $wpdb->term_relationships;
		$users  = $wpdb->users;
		$big    = [ 'taxonomy' => 'category', 'terms' => [ self::$fixture['big'] ] ];
		$tag    = [ 'taxonomy' => 'post_tag', 'terms' => [ self::$fixture['tag'] ] ];
		$args   = [];
		$role   = 'grid';
		$sorted = false;

		switch ( $case ) {
			case 'filter false':
				add_filter( Mai_Post_Grid_Query_Optimizer::FILTER, '__return_false' );
				break;

			case 'transient set':
				set_transient( Mai_Post_Grid_Query_Optimizer::TRANSIENT, 'failed', DAY_IN_SECONDS );
				break;

			case 'unmarked':
				$role = '';
				break;

			case 'meta query':
				$args = [ 'meta_query' => [ [ 'key' => 'mai_test_none', 'compare' => 'NOT EXISTS' ] ] ];
				break;

			case 's set':
				$args = [ 's' => 'title' ];
				break;

			case 's set, its search SQL and order removed':
				// Only the s check is left to catch it.
				add_filter( 'posts_search', '__return_empty_string' );
				add_filter( 'posts_search_orderby', '__return_empty_string' );

				$args = [ 's' => 'title' ];
				break;

			case 'no_found_rows false':
				// The full form, since core's split statement for a counted query never matches split().
				add_filter( 'split_the_query', '__return_false' );

				$args = [ 'no_found_rows' => false ];
				break;

			case 'orderby rand':
				// Unseeded, so each run picks its own order. All of big's posts, compared sorted.
				$args   = [ 'orderby' => 'rand', 'posts_per_page' => 100 ];
				$sorted = true;
				break;

			case 'orderby RAND(7)':
				$args = [ 'orderby' => 'RAND(7)' ];
				break;

			case 'orderby menu order':
				// A sort that is left out, since the swapped form was slower on a mid-size category.
				$args = [ 'orderby' => 'menu_order' ];
				break;

			case 'orderby ID':
				// Left out since 2026-10-06: local MySQL 9.7.1 flipped its plan between runs.
				$args = [ 'orderby' => 'ID' ];
				break;

			case 'tiebreaker removed':
				remove_all_filters( 'posts_orderby' );
				break;

			case 'ORDER BY names the term table':
				// term_order is 0 on every row, so the order is still date, then ID.
				add_filter( 'posts_orderby', static fn( $orderby ) => "{$terms}.term_order ASC, {$orderby}" );
				break;

			case 'term order put first after the tiebreaker':
				// Priority 100, after Mai's tiebreaker at 99. term_order is 0 on every row.
				add_filter( 'posts_orderby', static fn( $orderby ) => "{$terms}.term_order ASC, {$orderby}", 100 );
				break;

			case 'ID key taken off after the tiebreaker':
				// Priority 100, after Mai's tiebreaker at 99. Big's dates all differ, so the order
				// is the same without it.
				add_filter( 'posts_orderby', static fn( $orderby ) => (string) preg_replace( '/,\s*' . preg_quote( "{$posts}.ID", '/' ) . '\s+(?:ASC|DESC)\s*$/', '', $orderby ), 100 );
				break;

			case 'no LIMIT':
				$args = [ 'posts_per_page' => -1 ];
				break;

			case 'nested tax query':
				$args = [ 'tax_query' => [ 'relation' => 'AND', [ 'relation' => 'OR', $big, $tag ], $big ] ];
				break;

			case 'OR with NOT IN':
				$args = [ 'tax_query' => [ 'relation' => 'OR', $big, $tag + [ 'operator' => 'NOT IN' ] ] ];
				break;

			case 'OR with IN then lowercase in':
				$args = [ 'tax_query' => [ 'relation' => 'OR', $big, $tag + [ 'operator' => 'in' ] ] ];
				break;

			case 'only NOT IN':
				$args = [ 'tax_query' => [ $tag + [ 'operator' => 'NOT IN' ] ] ];
				break;

			case 'deleted term':
				$deleted = self::factory()->category->create( [ 'name' => 'Optimizer deleted' ] );

				wp_delete_term( $deleted, 'category' );

				$args = [ 'tax_query' => [ [ 'taxonomy' => 'category', 'terms' => [ $deleted ] ] ] ];
				break;

			case 'WordPress joins another table':
				// With a custom taxonomy, attachments and a post status, core joins the posts table
				// again, as p2, to check each attachment's parent.
				$args = [
					'post_type'   => [ 'post', 'attachment' ],
					'post_status' => 'publish',
					'tax_query'   => [ [ 'taxonomy' => 'mai_test_tax', 'terms' => [ self::$fixture['custom'] ] ] ],
				];
				break;

			case 'where changed at 10':
				add_filter( 'posts_where', static fn( $where ) => $where . " AND {$posts}.ID > 0" );
				break;

			case 'join changed at 10':
				add_filter( 'posts_join', static fn( $join ) => $join . " LEFT JOIN {$users} AS mai_test_users ON (mai_test_users.ID = {$posts}.post_author)" );
				break;

			case 'groupby changed at 10':
				add_filter( 'posts_groupby', static fn( $groupby ) => $groupby . ", {$posts}.post_date" );
				break;

			case 'distinct changed at 10':
				add_filter( 'posts_distinct', static fn() => 'DISTINCT' );
				break;

			case 'fields changed at 10':
				add_filter( 'posts_fields', static fn( $fields ) => $fields . ', 1 AS mai_test_one' );
				break;

			case 'where appended at posts_clauses 999':
				add_filter(
					'posts_clauses',
					static function ( $clauses ) use ( $posts ) {
						$clauses['where'] .= " AND {$posts}.ID > 0";

						return $clauses;
					},
					999
				);
				break;

			case 'posts_search adds text with s empty':
				add_filter( 'posts_search', static fn( $search ) => $search . " AND {$posts}.ID > 0" );
				break;

			case 'posts_request changes the statement':
				add_filter( 'posts_request', static fn( $request ) => $request . ' ' );
				break;

			case 'posts_request_ids changes the split statement':
				add_filter( 'posts_request_ids', static fn( $request ) => $request . ' ' );
				break;

			case 'query callback at 10 changes the statement':
				add_filter( 'query', static fn( $sql ) => is_string( $sql ) && str_contains( $sql, 'GROUP BY' ) ? $sql . ' ' : $sql );
				break;

			case 'posts_where callbacks removed':
				remove_all_filters( 'posts_where' );
				break;

			case 'title with a percent sign':
				// Core writes the title with prepare(), which escapes the % in both looks.
				$args = [ 'title' => '50% off' ];
				break;

			default:
				$this->fail( "Unknown case: {$case}" );
		}

		return [ $args, $role, $sorted ];
	}

	/**
	 * Runs a query the way a grid or its copy would, marked before it runs.
	 *
	 * @param array  $args        Query args, on top of base_args().
	 * @param string $role        'grid' or 'copy'. An empty string leaves the query unmarked.
	 * @param bool   $new_request Whether to start as a new request: an empty object cache and a
	 *                            reset optimizer. False runs it in the same request as the last.
	 *
	 * @return array{query:WP_Query,ids:int[],statements:string[]}
	 */
	private function run_marked( array $args, string $role = 'grid', bool $new_request = true ): array {
		if ( $new_request ) {
			$this->new_request();
		}

		$query = new WP_Query();

		if ( '' !== $role ) {
			Mai_Post_Grid_Query_Optimizer::instance()->mark( $query, $role );
		}

		return $this->run_query( $query, $args );
	}

	/**
	 * Runs a query, new or already run, in the current request, capturing every statement sent.
	 *
	 * @param WP_Query $query The query.
	 * @param array    $args  Query args, on top of base_args().
	 *
	 * @return array{query:WP_Query,ids:int[],statements:string[]}
	 */
	private function run_query( WP_Query $query, array $args ): array {
		$statements = [];
		$capture    = static function ( $sql ) use ( &$statements ) {
			if ( is_string( $sql ) ) {
				$statements[] = $sql;
			}

			return $sql;
		};

		add_filter( 'query', $capture, PHP_INT_MAX );

		try {
			$this->without_recovery( static fn() => $query->query( $args + self::base_args() ) );
		} finally {
			remove_filter( 'query', $capture, PHP_INT_MAX );
		}

		$run = [
			'query'      => $query,
			'ids'        => array_map( static fn( $post ): int => (int) ( is_object( $post ) ? $post->ID : $post ), $query->posts ),
			'statements' => $statements,
		];

		$this->assert_sent( $run );

		return $run;
	}

	/**
	 * Runs a query as run_marked() does, with the filter turning the optimizer off, so today's
	 * statement goes out unchanged.
	 *
	 * @param array  $args Query args, on top of base_args().
	 * @param string $role 'grid' or 'copy'. An empty string leaves the query unmarked.
	 *
	 * @return array{query:WP_Query,ids:int[],statements:string[]}
	 */
	private function run_today( array $args, string $role = 'grid' ): array {
		add_filter( Mai_Post_Grid_Query_Optimizer::FILTER, '__return_false' );

		try {
			$run = $this->run_marked( $args, $role );
		} finally {
			remove_filter( Mai_Post_Grid_Query_Optimizer::FILTER, '__return_false' );
		}

		$this->assertSame( [], self::swapped( $run ) );

		return $run;
	}

	/**
	 * Marks and runs a query that another callback answers at posts_pre_query, so its swap is
	 * prepared but its statement is never sent.
	 *
	 * @param array $args  Query args, on top of base_args().
	 * @param int   $added How many swaps the run should prepare: 1, or 0 when the swap is off.
	 *
	 * @return WP_Query
	 */
	private function prepare_without_sending( array $args, int $added = 1 ): WP_Query {
		$answer = static fn() => [];
		$before = self::prepared_count();

		add_filter( 'posts_pre_query', $answer );

		try {
			$query = new WP_Query();

			Mai_Post_Grid_Query_Optimizer::instance()->mark( $query, 'grid' );

			$this->without_recovery( static fn() => $query->query( $args + self::base_args() ) );
		} finally {
			remove_filter( 'posts_pre_query', $answer );
		}

		$this->assertSame( $before + $added, self::prepared_count(), 1 === $added ? 'A swap was prepared for the query.' : 'No swap was prepared.' );

		return $query;
	}

	/**
	 * Sends a statement as WordPress would, outside any query, capturing every statement sent.
	 *
	 * @param string $sql The statement. It selects post IDs.
	 *
	 * @return array{ids:int[],statements:string[]}
	 */
	private function send( string $sql ): array {
		global $wpdb;

		$statements = [];
		$capture    = static function ( $sql ) use ( &$statements ) {
			if ( is_string( $sql ) ) {
				$statements[] = $sql;
			}

			return $sql;
		};

		add_filter( 'query', $capture, PHP_INT_MAX );

		try {
			$ids = $wpdb->get_col( $sql );
		} finally {
			remove_filter( 'query', $capture, PHP_INT_MAX );
		}

		$this->assertSame( '', $wpdb->last_error );

		return [
			'ids'        => array_map( 'intval', $ids ),
			'statements' => $statements,
		];
	}

	/**
	 * Runs the callback with recover() off posts_results while each query reaches that hook.
	 *
	 * recover() reads and forgets a grid's outcome, and drops its prepared swaps, at
	 * posts_results. These tests read both themselves. prepare() only prepares a grid's swap
	 * while recover() is registered, and swap() only swaps it while recover() still is, so
	 * recover() is taken off once the query's statement is on its way: by a query callback added
	 * after swap() at the same priority, armed by a posts_request callback added after prepare()
	 * at the same priority. A query another callback answers at posts_pre_query sends no
	 * statement, so it is taken off there instead. It is put back when the callback returns. The
	 * core test case restores every hook after the test, so the next test has the original order
	 * again.
	 *
	 * @param callable $callback The code to run.
	 *
	 * @return mixed What the callback returns.
	 */
	private function without_recovery( callable $callback ): mixed {
		$optimizer = Mai_Post_Grid_Query_Optimizer::instance();
		$armed     = false;
		$hold      = static function () use ( &$armed, $optimizer ): void {
			if ( $armed ) {
				$armed = false;

				remove_filter( 'posts_results', [ $optimizer, 'recover' ], PHP_INT_MIN );
			}
		};
		$arm       = static function ( $request ) use ( &$armed ) {
			$armed = true;

			return $request;
		};
		$sent      = static function ( $sql ) use ( $hold ) {
			$hold();

			return $sql;
		};
		$answered  = static function ( $posts ) use ( $hold ) {
			if ( null !== $posts ) {
				$hold();
			}

			return $posts;
		};

		add_filter( 'posts_request', $arm, PHP_INT_MAX );
		add_filter( 'query', $sent, PHP_INT_MAX );
		add_filter( 'posts_pre_query', $answered, PHP_INT_MAX );

		try {
			return $callback();
		} finally {
			remove_filter( 'posts_pre_query', $answered, PHP_INT_MAX );
			remove_filter( 'query', $sent, PHP_INT_MAX );
			remove_filter( 'posts_request', $arm, PHP_INT_MAX );

			if ( false === has_filter( 'posts_results', [ $optimizer, 'recover' ] ) ) {
				add_filter( 'posts_results', [ $optimizer, 'recover' ], PHP_INT_MIN, 2 );
			}
		}
	}

	/**
	 * Asserts a swapped statement has the expected number of Mai's EXISTS, and that each carries
	 * the MySQL hint when this database takes it, and none does otherwise.
	 *
	 * @param string $statement The swapped statement.
	 * @param int    $exists    How many EXISTS it should have.
	 *
	 * @return void
	 */
	private function assert_hint( string $statement, int $exists ): void {
		global $wpdb;

		$this->assertSame( $exists, substr_count( $statement, self::SWAP ) );

		if ( Mai_Post_Grid_Query_Optimizer_Database::hint( Mai_Post_Grid_Query_Optimizer_Database::server_info( $wpdb ) ) ) {
			$this->assertSame( $exists, substr_count( $statement, 'EXISTS ( SELECT ' . self::HINT . '1 FROM ' ), 'every EXISTS carries the hint' );
		} else {
			$this->assertSame( 0, substr_count( $statement, self::HINT ), 'no hint on a database that does not read it' );
		}
	}

	/**
	 * How many swaps are prepared and waiting for their statement.
	 *
	 * @return int
	 */
	private static function prepared_count(): int {
		return count( ( new ReflectionProperty( Mai_Post_Grid_Query_Optimizer::class, 'prepared' ) )->getValue( Mai_Post_Grid_Query_Optimizer::instance() ) );
	}

	/**
	 * Starts what the optimizer and WordPress treat as a new request.
	 *
	 * @return void
	 */
	private function new_request(): void {
		wp_cache_flush();
		Mai_Post_Grid_Query_Optimizer::instance()->reset();
	}

	/**
	 * Asserts the run sent its own statement to the posts table, rather than being answered from
	 * a cache. `WHERE 1=1` is WP_Query's own template; post cache fills do not have it.
	 *
	 * @param array $run What run_marked() returned.
	 *
	 * @return void
	 */
	private function assert_sent( array $run ): void {
		global $wpdb;

		$from = "FROM {$wpdb->posts} ";
		$sent = array_filter( $run['statements'], static fn( string $sql ): bool => str_contains( $sql, $from ) && str_contains( $sql, 'WHERE 1=1' ) );

		$this->assertNotEmpty( $sent, 'The run sent no statement to the posts table.' );
	}

	/**
	 * The statements of a run that Mai swapped.
	 *
	 * @param array $run What run_marked() returned.
	 *
	 * @return string[]
	 */
	private static function swapped( array $run ): array {
		return array_values( array_filter( $run['statements'], static fn( string $sql ): bool => str_contains( $sql, self::SWAP ) ) );
	}

	/**
	 * The args every run starts from. A grid of 7 of big's posts, newest first, with the ID
	 * tiebreaker and nothing cached by WordPress.
	 *
	 * @return array
	 */
	private static function base_args(): array {
		return [
			'post_type'           => 'post',
			'tax_query'           => [ [ 'taxonomy' => 'category', 'terms' => [ self::$fixture['big'] ] ] ],
			'posts_per_page'      => 7,
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
			'mai_grid_tiebreak'   => true,
			'cache_results'       => false,
		];
	}
}
