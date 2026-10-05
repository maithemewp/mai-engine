<?php
declare(strict_types=1);

namespace BizBudding\MaiEngine\Tests\Integration;

use Mai_Grid;
use Mai_Post_Grid_Query_Optimizer;
use Mai_Query_Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;
use WP_Query;
use WP_UnitTest_Factory;

/**
 * The grid query optimizer wired into Mai's grids: the grid's own query and its ID-only copy are
 * swapped, and a swapped statement that fails or is slow turns the swap off and still shows the
 * right posts.
 *
 * Every grid is built through Mai_Grid, as on a page. Each run starts as a new request on a site
 * without a persistent object cache: an empty object cache, and the optimizer reset with a logger
 * this test reads. Mai's stored grid results are transients, so they outlive that, as they do
 * between requests.
 *
 * Every statement is captured by a query callback at PHP_INT_MAX added in the test, after the
 * optimizer's, so it sees the text that went to the database. The breaker is a query callback at
 * the same priority, also added after the optimizer's, that breaks the first swapped statement.
 */
final class PostGridQueryOptimizerGridTest extends MaiIntegrationTestCase {

	use PostGridQueryOptimizerFixture;

	/**
	 * What only Mai's swap writes. Core's own EXISTS operator writes `EXISTS (` too.
	 */
	private const SWAP = 'EXISTS ( SELECT ';

	private const PER_PAGE = 7;

	/**
	 * The fixture's IDs, built once for the class.
	 *
	 * @var array
	 */
	private static array $fixture = [];

	/** @var string[] The lines the optimizer logged. */
	private array $logged = [];

	/** The statement the breaker broke, before it broke it. Empty until it has. */
	private string $broken = '';

	/** What $wpdb->suppress_errors() was before the test. */
	private bool $suppressed = false;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ): void {
		self::$fixture = self::create_optimizer_fixture( $factory );
	}

	public static function wpTearDownAfterClass(): void {
		self::remove_optimizer_fixture();
	}

	public function set_up(): void {
		global $wpdb;

		parent::set_up();

		$this->logged = [];
		$this->broken = '';

		// The breaker's statements fail on purpose. Their errors are not printed.
		$this->suppressed = (bool) $wpdb->suppress_errors( true );

		( new Mai_Query_Cache() )->flush_all();
	}

	public function tear_down(): void {
		global $wpdb;

		$wpdb->suppress_errors( $this->suppressed );

		Mai_Post_Grid_Query_Optimizer::$slow = 1.0;
		Mai_Grid::$existing_post_ids        = [];

		parent::tear_down();
	}

	/** The two forms of a grid's own statement. */
	public static function grid_forms(): array {
		return [
			'split' => [ 'split' ],
			'full'  => [ 'full' ],
		];
	}

	/** A failed copy, and a failed grid statement. */
	public static function owners(): array {
		return [
			'the copy' => [ 'copy' ],
			'the grid' => [ 'split' ],
		];
	}

	public function test_a_deferring_grid_swaps_its_copy_and_stores_the_note(): void {
		$args     = $this->deferring_args();
		$expected = $this->today( $args );

		$this->fresh_start();

		$run        = $this->render( $args );
		$swapped    = self::swapped( $run );
		$statements = self::grid_statements( $run );

		$this->assertCount( 1, $statements, 'only the copy ran, since the note answered the grid' );
		$this->assertCount( 1, $swapped );
		$this->assertStringContainsString( 'LIMIT 0, ' . ( self::PER_PAGE + 1 ), $swapped[0], 'the padded copy' );
		$this->assertSame( $expected, $run['ids'] );
		$this->assertSame( $this->shown_with_current_excluded(), $run['ids'] );
		$this->assertSame( [ [ 'ids' => array_slice( self::$fixture['posts']['big'], 0, self::PER_PAGE + 1 ), 'found' => 0, 'by' => 'ids' ] ], $run['stored'] );
		$this->assertSame( [], $this->logged );
		$this->assertFalse( get_transient( Mai_Post_Grid_Query_Optimizer::TRANSIENT ) );

		$this->new_request();

		$next = $this->render( $args );

		$this->assertSame( [], self::grid_statements( $next ), 'the next view reads the note' );
		$this->assertSame( $expected, $next['ids'] );
	}

	public function test_a_grid_without_excludes_swaps_its_own_query(): void {
		global $wpdb;

		$args     = $this->plain_args();
		$expected = $this->today( $args );

		$this->fresh_start();

		$run     = $this->render( $args );
		$swapped = self::swapped( $run );

		$this->assertCount( 1, self::grid_statements( $run ) );
		$this->assertCount( 1, $swapped );
		$this->assertStringStartsWith( "SELECT   {$wpdb->posts}.ID", $swapped[0], 'the split form' );
		$this->assertStringContainsString( 'LIMIT 0, ' . self::PER_PAGE, $swapped[0] );
		$this->assertSame( $expected, $run['ids'] );
		$this->assertSame( array_slice( self::$fixture['posts']['big'], 0, self::PER_PAGE ), $run['ids'] );
		$this->assertSame( [ $expected ], array_column( $run['stored'], 'ids' ) );
		$this->assertSame( [], $this->logged );
		$this->assertFalse( get_transient( Mai_Post_Grid_Query_Optimizer::TRANSIENT ) );
	}

	public function test_a_grid_with_the_cache_off_swaps_and_recovers(): void {
		add_filter( 'mai_post_grid_cache', '__return_false' );

		$args     = $this->plain_args();
		$expected = $this->today( $args );

		$this->fresh_start();

		$swapped = $this->render( $args );

		$this->assertCount( 1, self::swapped( $swapped ) );
		$this->assertSame( $expected, $swapped['ids'] );
		$this->assertSame( [], $swapped['stored'], 'the result cache is off' );

		$this->new_request();
		$this->break_next_swap();

		$before = wp_cache_get_last_changed( 'posts' );
		$broken = $this->render( $args );

		$this->assertNotSame( '', $this->broken, 'the swapped statement was broken' );
		$this->assertSame( $expected, $broken['ids'] );
		$this->assert_turned_off( 'failed' );
		$this->assertNotSame( $before, wp_cache_get_last_changed( 'posts' ), 'core forgot the failed result' );
	}

	public function test_a_failed_copy_swap_recovers(): void {
		$this->assert_a_failure_recovers( 'copy', false );
	}

	/** The full form is forced with split_the_query false. */
	#[DataProvider( 'grid_forms' )]
	public function test_a_failed_grid_swap_recovers( string $form ): void {
		$this->assert_a_failure_recovers( $form, false );
	}

	#[DataProvider( 'owners' )]
	public function test_a_failure_is_caught_when_the_breaker_sends_its_own_query( string $form ): void {
		$this->assert_a_failure_recovers( $form, true );
	}

	/**
	 * The swapped statement is broken, then MySQL refuses the resend without changing its text,
	 * so today's failure path takes over and nothing is stored. For the copy that means the
	 * grid's own query answers, unswapped. For the grid, the failed statement shows no posts, as
	 * it does today.
	 */
	#[DataProvider( 'owners' )]
	public function test_a_failed_resend_stores_nothing( string $form ): void {
		$args     = 'copy' === $form ? $this->deferring_args() : $this->plain_args();
		$expected = $this->today( $args );

		$this->fresh_start();
		$this->break_next_swap();

		$before = wp_cache_get_last_changed( 'posts' );

		// Refuses the first unswapped WP_Query statement after the break, which is the resend.
		[ $run, $refused ] = $this->refuse_once(
			fn( string $sql ): bool => '' !== $this->broken && ! str_contains( $sql, self::SWAP ) && [] !== self::grid_statements( [ 'statements' => [ $sql ] ] ),
			fn(): array => $this->render( $args )
		);

		$this->assertNotSame( '', $this->broken, 'the swapped statement was broken' );
		$this->assertTrue( $refused, 'and the resend was refused' );
		$this->assertSame( 'copy' === $form ? $expected : [], $run['ids'] );
		$this->assertSame( [], $run['stored'], 'nothing stored' );
		$this->assert_turned_off( 'failed' );
		$this->assertNotSame( $before, wp_cache_get_last_changed( 'posts' ) );
	}

	public function test_a_failed_swap_turns_it_off_for_the_page(): void {
		$big_args = $this->plain_args();
		$tag_args = $this->plain_args( [ [ 'taxonomy' => 'post_tag', 'terms' => [ self::$fixture['tag'] ], 'current' => false, 'operator' => 'IN' ] ] );
		$big      = $this->today( $big_args );
		$tag      = $this->today( $tag_args );

		$this->fresh_start();
		$this->break_next_swap();

		$first  = $this->render( $big_args );
		$second = $this->render( $tag_args );

		$this->assertCount( 1, self::swapped( $first ), 'the first grid was swapped, and broken' );
		$this->assertNotSame( '', $this->broken );
		$this->assertNotEmpty( self::grid_statements( $second ), 'the second grid sent its statement' );
		$this->assertSame( [], self::swapped( $second ), 'and it was not swapped' );
		$this->assertSame( $big, $first['ids'] );
		$this->assertSame( $tag, $second['ids'] );
		$this->assertSame( array_slice( self::$fixture['posts']['tag'], 0, self::PER_PAGE ), $second['ids'] );
		$this->assert_turned_off( 'failed' );
	}

	public function test_a_slow_copy_turns_it_off(): void {
		Mai_Post_Grid_Query_Optimizer::$slow = 0.05;

		$args     = $this->deferring_args();
		$expected = $this->today( $args );

		$this->fresh_start();

		$held = 0;

		add_filter(
			'query',
			static function ( $sql ) use ( &$held ) {
				if ( is_string( $sql ) && str_contains( $sql, self::SWAP ) ) {
					++$held;

					usleep( 60000 );
				}

				return $sql;
			},
			PHP_INT_MAX
		);

		$before = wp_cache_get_last_changed( 'posts' );
		$run    = $this->render( $args );

		$this->assertSame( 1, $held, 'the swapped copy was held' );
		$this->assertCount( 1, self::swapped( $run ) );
		$this->assertSame( $expected, $run['ids'] );
		$this->assertSame( [ array_slice( self::$fixture['posts']['big'], 0, self::PER_PAGE + 1 ) ], array_column( $run['stored'], 'ids' ), 'the slow copy\'s list is right, so it is kept' );
		$this->assert_turned_off( 'slow' );
		$this->assertSame( $before, wp_cache_get_last_changed( 'posts' ), 'nothing failed, so core\'s query cache is left alone' );
	}

	public function test_recovery_is_not_armed_without_its_callback(): void {
		$args     = $this->plain_args();
		$expected = $this->today( $args );

		$this->fresh_start();

		$this->assertCount( 1, self::swapped( $this->render( $args ) ), 'swapped while the callback is registered' );

		remove_all_filters( 'posts_results' );

		$this->fresh_start();

		$run = $this->render( $args );

		$this->assertNotEmpty( self::grid_statements( $run ), 'the grid sent its statement' );
		$this->assertSame( [], self::swapped( $run ) );
		$this->assertSame( $expected, $run['ids'] );
	}

	public function test_a_grid_answered_without_its_statement_leaves_nothing_prepared(): void {
		$args = $this->deferring_args();

		$this->fresh_start();

		$miss = $this->render( $args );

		$this->assertCount( 1, self::swapped( $miss ), 'the copy was swapped' );
		$this->assertSame( 0, self::prepared_count(), 'the grid\'s own swap, never sent, was dropped' );

		// Nothing stored by Mai, and core's query cache kept, as a persistent object cache would.
		// Core answers the copy itself.
		( new Mai_Query_Cache() )->flush_all();
		Mai_Post_Grid_Query_Optimizer::instance()->reset();

		$copy_cached = $this->render( $args );

		$this->assertSame( [], self::grid_statements( $copy_cached ), 'core answered the copy from its cache' );
		$this->assertSame( $miss['ids'], $copy_cached['ids'] );
		$this->assertSame( 0, self::prepared_count(), 'the copy\'s own swap, never sent, was dropped' );

		$this->new_request();

		$hit = $this->render( $args );

		$this->assertSame( [], self::grid_statements( $hit ), 'answered from the note' );
		$this->assertSame( $miss['ids'], $hit['ids'] );
		$this->assertSame( 0, self::prepared_count() );
	}

	public function test_recover_accepts_null(): void {
		$optimizer = Mai_Post_Grid_Query_Optimizer::instance();
		$marked    = new WP_Query();

		$optimizer->mark( $marked, 'grid' );

		$this->assertNull( $optimizer->recover( null, null ) );
		$this->assertNull( $optimizer->recover( null, $marked ) );
	}

	/**
	 * Breaks one swapped statement and checks the grid still shows today's posts, the right list
	 * is stored, the next view reads it back, and the swap is off for the day.
	 *
	 * @param string $form     'copy' for a deferring grid's copy, 'split' or 'full' for a grid's
	 *                         own statement.
	 * @param bool   $send_own Whether the breaker also sends a statement of its own.
	 *
	 * @return void
	 */
	private function assert_a_failure_recovers( string $form, bool $send_own ): void {
		global $wpdb;

		if ( 'full' === $form ) {
			add_filter( 'split_the_query', '__return_false' );
		}

		$args     = 'copy' === $form ? $this->deferring_args() : $this->plain_args();
		$expected = $this->today( $args );
		$stored   = 'copy' === $form ? array_slice( self::$fixture['posts']['big'], 0, self::PER_PAGE + 1 ) : $expected;

		$this->fresh_start();
		$this->break_next_swap( $send_own );

		$before = wp_cache_get_last_changed( 'posts' );
		$run    = $this->render( $args );

		$this->assertNotSame( '', $this->broken, 'a swapped statement was broken' );
		$this->assertStringStartsWith( 'full' === $form ? "SELECT   {$wpdb->posts}.*" : "SELECT   {$wpdb->posts}.ID", $this->broken );
		$this->assertStringContainsString( 'LIMIT 0, ' . ( 'copy' === $form ? self::PER_PAGE + 1 : self::PER_PAGE ), $this->broken );
		$this->assertSame( $expected, $run['ids'], 'the grid shows today\'s posts' );
		$this->assertNotContains( '', wp_list_pluck( $run['query']->posts, 'post_title' ), 'whole posts, not rows holding only the ID' );
		$this->assertSame( [ $stored ], array_column( $run['stored'], 'ids' ), 'and the right list is stored' );
		$this->assert_turned_off( 'failed' );
		$this->assertStringContainsString( 'BROKEN', $this->logged[0], 'the line carries the database error' );
		$this->assertSame( 0, self::prepared_count(), 'every prepared swap was cleared' );
		$this->assertNotSame( $before, wp_cache_get_last_changed( 'posts' ), 'core forgot the failed result' );

		add_filter( Mai_Post_Grid_Query_Optimizer::FILTER, '__return_false' );

		$this->new_request();

		$next = $this->render( $args );

		$this->assertSame( [], self::grid_statements( $next ), 'the next view reads the stored list' );
		$this->assertSame( $expected, $next['ids'] );
	}

	/**
	 * Asserts the swap was turned off once, for the given reason: the transient is set and one
	 * line was logged.
	 *
	 * @param string $why 'failed' or 'slow'.
	 *
	 * @return void
	 */
	private function assert_turned_off( string $why ): void {
		$this->assertSame( $why, get_transient( Mai_Post_Grid_Query_Optimizer::TRANSIENT ) );
		$this->assertCount( 1, $this->logged );
		$this->assertStringStartsWith( "Grid query optimizer off for 24 hours ({$why}): ", $this->logged[0] );
	}

	/**
	 * A grid of big's newest posts, on the home page, with no excludes, so it does not defer and
	 * runs its own statement.
	 *
	 * @param array|null $taxonomies The grid's taxonomies setting, or null for big.
	 *
	 * @return array
	 */
	private function plain_args( ?array $taxonomies = null ): array {
		$this->go_to( home_url( '/' ) );

		return $this->grid_args( [ 'excludes' => [] ], $taxonomies );
	}

	/**
	 * A grid of big's newest posts, on the page of big's third newest, with Exclude current, so it
	 * defers and its ID-only copy runs.
	 *
	 * @return array
	 */
	private function deferring_args(): array {
		$this->go_to( get_permalink( self::$fixture['posts']['big'][2] ) );

		return $this->grid_args( [ 'excludes' => [ 'exclude_current' ] ] );
	}

	/** What the deferring grid shows: big's newest, without the post being viewed. */
	private function shown_with_current_excluded(): array {
		$ids = array_slice( self::$fixture['posts']['big'], 0, self::PER_PAGE + 1 );

		unset( $ids[2] );

		return array_values( $ids );
	}

	private function grid_args( array $overrides, ?array $taxonomies = null ): array {
		return array_merge(
			[
				'type'           => 'post',
				'post_type'      => [ 'post' ],
				'query_by'       => 'tax_meta',
				'posts_per_page' => self::PER_PAGE,
				'excludes'       => [],
				'taxonomies'     => $taxonomies ?? [
					[ 'taxonomy' => 'category', 'terms' => [ self::$fixture['big'] ], 'current' => false, 'operator' => 'IN' ],
				],
			],
			$overrides
		);
	}

	/**
	 * Renders the grid as a new request with the filter turning the optimizer off and nothing
	 * stored, and returns the posts it shows.
	 *
	 * @param array $args The grid args.
	 *
	 * @return int[]
	 */
	private function today( array $args ): array {
		add_filter( Mai_Post_Grid_Query_Optimizer::FILTER, '__return_false' );

		try {
			$this->fresh_start();

			$run = $this->render( $args );
		} finally {
			remove_filter( Mai_Post_Grid_Query_Optimizer::FILTER, '__return_false' );
		}

		$this->assertNotEmpty( self::grid_statements( $run ), 'today\'s run sent its statement' );
		$this->assertSame( [], self::swapped( $run ) );
		$this->assertCount( self::PER_PAGE, $run['ids'] );

		return $run['ids'];
	}

	/**
	 * Starts a new request with nothing stored by Mai's result cache.
	 *
	 * @return void
	 */
	private function fresh_start(): void {
		$this->new_request();

		( new Mai_Query_Cache() )->flush_all();
	}

	/**
	 * Starts what WordPress and the optimizer treat as a new request: an empty object cache, and
	 * the optimizer reset, logging to this test.
	 *
	 * @return void
	 */
	private function new_request(): void {
		wp_cache_flush();

		$optimizer = Mai_Post_Grid_Query_Optimizer::instance();

		$optimizer->reset();
		$optimizer->set_logger(
			function ( string $message ): void {
				$this->logged[] = $message;
			}
		);
	}

	/**
	 * Breaks the next swapped statement on its way to the database, once, by appending ` BROKEN`.
	 * Added after the optimizer's query callback, at the same priority, so it sees the swapped
	 * text. The text before the break is kept in $this->broken.
	 *
	 * @param bool $send_own Whether to send a statement of its own first, as a plugin might.
	 *
	 * @return void
	 */
	private function break_next_swap( bool $send_own = false ): void {
		global $wpdb;

		add_filter(
			'query',
			function ( $sql ) use ( $send_own, $wpdb ) {
				if ( '' !== $this->broken || ! is_string( $sql ) || ! str_contains( $sql, self::SWAP ) ) {
					return $sql;
				}

				$this->broken = $sql;

				if ( $send_own ) {
					$wpdb->query( 'SELECT 1' );
				}

				return $sql . ' BROKEN';
			},
			PHP_INT_MAX
		);
	}

	/**
	 * Renders a grid through Mai_Grid, capturing every statement as it went to the database and
	 * every grid result Mai stored.
	 *
	 * mai-cache writes through transients here, and a stored grid result is the only envelope
	 * whose value carries `ids`.
	 *
	 * @param array $args The grid args.
	 *
	 * @return array{query:WP_Query,ids:int[],statements:string[],stored:array[]}
	 */
	private function render( array $args ): array {
		$statements = [];
		$stored     = [];

		$capture = static function ( $sql ) use ( &$statements ) {
			if ( is_string( $sql ) ) {
				$statements[] = $sql;
			}

			return $sql;
		};

		$store = static function ( $transient, $value ) use ( &$stored ) {
			if ( is_array( $value ) && isset( $value['value']['ids'] ) ) {
				$stored[] = $value['value'];
			}
		};

		add_filter( 'query', $capture, PHP_INT_MAX );
		add_action( 'set_transient', $store, 10, 2 );

		try {
			$query = ( new Mai_Grid( $args ) )->get_query();
		} finally {
			remove_action( 'set_transient', $store, 10 );
			remove_filter( 'query', $capture, PHP_INT_MAX );
		}

		$this->assertInstanceOf( WP_Query::class, $query );

		return [
			'query'      => $query,
			'ids'        => array_map( 'intval', wp_list_pluck( $query->posts, 'ID' ) ),
			'statements' => $statements,
			'stored'     => $stored,
		];
	}

	/**
	 * A run's WP_Query statements against the posts table, swapped or not. `WHERE 1=1` is
	 * WP_Query's own template; priming reads do not have it.
	 *
	 * @param array $run What render() returned.
	 *
	 * @return string[]
	 */
	private static function grid_statements( array $run ): array {
		global $wpdb;

		$from = '/\bFROM\s+' . preg_quote( $wpdb->posts, '/' ) . '\s/';

		return array_values( array_filter( $run['statements'], static fn( string $sql ): bool => (bool) preg_match( $from, $sql ) && str_contains( $sql, 'WHERE 1=1' ) ) );
	}

	/**
	 * The statements of a run that Mai swapped.
	 *
	 * @param array $run What render() returned.
	 *
	 * @return string[]
	 */
	private static function swapped( array $run ): array {
		return array_values( array_filter( $run['statements'], static fn( string $sql ): bool => str_contains( $sql, self::SWAP ) ) );
	}

	/**
	 * How many swaps are prepared and waiting for their statement.
	 *
	 * @return int
	 */
	private static function prepared_count(): int {
		return count( ( new ReflectionProperty( Mai_Post_Grid_Query_Optimizer::class, 'prepared' ) )->getValue( Mai_Post_Grid_Query_Optimizer::instance() ) );
	}
}
