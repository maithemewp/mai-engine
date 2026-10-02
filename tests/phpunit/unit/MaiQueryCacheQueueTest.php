<?php
declare(strict_types=1);

namespace BizBudding\MaiEngine\Tests\Unit;

use Brain\Monkey\Functions;
use BizBudding\MaiEngine\Tests\TestCase;
use Mai\Cache\Cache;
use Mai_Query_Cache_Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

/**
 * The per-request queue: what counts as a page view, when the response can be finished
 * early, and the lists of pending stores and jobs.
 *
 * Every seam that would touch the real request (finishing the response, DONOTCACHEPAGE) is
 * replaced here, except in the separate-process tests, where a defined constant cannot leak
 * into another test.
 */
final class MaiQueryCacheQueueTest extends TestCase {

	/** The ignore_user_abort setting before the test, put back after it. */
	private int $abort_setting = 0;

	/** Whether $_SERVER held a request method before the test, and which. */
	private ?string $method = null;

	protected function setUp(): void {
		parent::setUp();

		$this->abort_setting = ignore_user_abort();
		$this->method        = $_SERVER['REQUEST_METHOD'] ?? null;
	}

	protected function tearDown(): void {
		ignore_user_abort( (bool) $this->abort_setting );
		Cache::set_clock( null );

		if ( null === $this->method ) {
			unset( $_SERVER['REQUEST_METHOD'] );
		} else {
			$_SERVER['REQUEST_METHOD'] = $this->method;
		}

		parent::tearDown();
	}

	// ---- Helpers ----

	/**
	 * Stubs a plain front-end GET page view, with some rules changed.
	 *
	 * @param array    $changes   Function name => return value, or 'method' => the request
	 *                            method (null for none).
	 * @param string[] $leave_out Functions not to stub, so a test can expect or omit them.
	 *
	 * @return void
	 */
	private function request( array $changes = [], array $leave_out = [] ): void {
		$method = array_key_exists( 'method', $changes ) ? $changes['method'] : 'GET';

		if ( null === $method ) {
			unset( $_SERVER['REQUEST_METHOD'] );
		} else {
			$_SERVER['REQUEST_METHOD'] = $method;
		}

		$stubs = [
			'is_admin'                   => false,
			'wp_doing_ajax'              => false,
			'wp_is_serving_rest_request' => false,
			'wp_doing_cron'              => false,
			'is_feed'                    => false,
			'is_multisite'               => false,
			'ms_is_switched'             => false,
		];

		foreach ( $stubs as $function => $return ) {
			if ( ! in_array( $function, $leave_out, true ) ) {
				Functions\when( $function )->justReturn( $changes[ $function ] ?? $return );
			}
		}
	}

	/** A queue with the default page-view rules and stand-ins for everything else. */
	private function queue( bool $can_finish = true ): Mai_Query_Cache_Queue {
		return new Mai_Query_Cache_Queue( static fn() => $can_finish, static fn() => null, static fn() => null );
	}

	// ---- What counts as a page view ----

	/** Changes to a plain GET page view, and whether it still counts as one. */
	public static function requests(): array {
		return [
			'a plain GET'                     => [ [], true ],
			'a GET on multisite, not switched' => [ [ 'is_multisite' => true ], true ],
			'a POST'                          => [ [ 'method' => 'POST' ], false ],
			'a HEAD'                          => [ [ 'method' => 'HEAD' ], false ],
			'no request method'               => [ [ 'method' => null ], false ],
			'admin'                           => [ [ 'is_admin' => true ], false ],
			'AJAX'                            => [ [ 'wp_doing_ajax' => true ], false ],
			'REST'                            => [ [ 'wp_is_serving_rest_request' => true ], false ],
			'cron'                            => [ [ 'wp_doing_cron' => true ], false ],
			'a feed'                          => [ [ 'is_feed' => true ], false ],
			'inside switch_to_blog()'         => [ [ 'is_multisite' => true, 'ms_is_switched' => true ], false ],
		];
	}

	#[DataProvider( 'requests' )]
	public function test_page_view_rules( array $changes, bool $expected ): void {
		$this->request( $changes );

		$this->assertSame( $expected, $this->queue()->is_page_view() );
	}

	#[RunInSeparateProcess]
	public function test_page_view_rules_wp_cli(): void {
		$this->request();

		$this->assertTrue( $this->queue()->is_page_view(), 'a page view before the constant' );

		define( 'WP_CLI', true );

		$this->assertFalse( $this->queue()->is_page_view() );
	}

	#[RunInSeparateProcess]
	public function test_page_view_rules_xmlrpc(): void {
		$this->request();

		$this->assertTrue( $this->queue()->is_page_view(), 'a page view before the constant' );

		define( 'XMLRPC_REQUEST', true );

		$this->assertFalse( $this->queue()->is_page_view() );
	}

	/** WordPress before 6.5 has no wp_is_serving_rest_request(), only the constant. */
	#[RunInSeparateProcess]
	public function test_page_view_rules_rest_constant_before_wordpress_6_5(): void {
		$this->request( [], [ 'wp_is_serving_rest_request' ] );

		$this->assertFalse( function_exists( 'wp_is_serving_rest_request' ), 'the function must be missing, as on 6.4' );
		$this->assertTrue( $this->queue()->is_page_view(), 'a page view before the constant' );

		define( 'REST_REQUEST', true );

		$this->assertFalse( $this->queue()->is_page_view() );
	}

	/** ms_is_switched() only exists on multisite (wp-settings.php), so a single site must not call it. */
	public function test_single_site_does_not_call_ms_is_switched(): void {
		$this->request( [], [ 'ms_is_switched' ] );

		Functions\expect( 'ms_is_switched' )->never();

		$this->assertTrue( $this->queue()->is_page_view() );
	}

	/** The page-view seam replaces the rules. */
	public function test_page_view_seam_replaces_the_rules(): void {
		$this->request( [ 'method' => 'POST' ] );

		$this->assertTrue( ( new Mai_Query_Cache_Queue( null, null, null, static fn() => true ) )->is_page_view() );
	}

	// ---- Finishing early ----

	public function test_can_finish_early_needs_page_view_and_finish_function(): void {
		$cases = [
			'both'               => [ true, true, true ],
			'no finish function' => [ true, false, false ],
			'not a page view'    => [ false, true, false ],
			'neither'            => [ false, false, false ],
		];

		foreach ( $cases as $name => [ $page_view, $can_finish, $expected ] ) {
			$queue = new Mai_Query_Cache_Queue( static fn() => $can_finish, null, null, static fn() => $page_view );

			$this->assertSame( $expected, $queue->can_finish_early(), $name );
		}
	}

	/** The default finish seam looks for the two functions PHP CLI does not have, so every test using it stores inline. */
	public function test_default_cannot_finish_early_under_php_cli(): void {
		$this->request();

		$this->assertFalse( function_exists( 'fastcgi_finish_request' ) );
		$this->assertFalse( function_exists( 'litespeed_finish_request' ) );
		$this->assertTrue( ( new Mai_Query_Cache_Queue() )->is_page_view() );
		$this->assertFalse( ( new Mai_Query_Cache_Queue() )->can_finish_early() );
	}

	public function test_finish_runs_once(): void {
		$calls = 0;
		$queue = new Mai_Query_Cache_Queue( static fn() => true, static function () use ( &$calls ) {
			++$calls;
		} );

		$this->assertFalse( $queue->finished() );

		$queue->finish();
		$queue->finish();

		$this->assertSame( 1, $calls );
		$this->assertTrue( $queue->finished() );
	}

	/**
	 * Measured with hrtime(), never Cache::now(), which counts whole seconds and is frozen here
	 * on purpose.
	 */
	public function test_elapsed_uses_real_time(): void {
		Cache::set_clock( static fn() => 1000 );

		$queue = $this->queue();

		$this->assertSame( 0.0, $queue->elapsed_ms(), 'nothing has run after the page yet' );

		$queue->finish();
		usleep( 30000 );

		$elapsed = $queue->elapsed_ms();

		$this->assertGreaterThanOrEqual( 30.0, $elapsed );
		$this->assertLessThan( 1000.0, $elapsed, 'milliseconds, not a smaller unit' );
	}

	// ---- Pending stores ----

	public function test_pending_store_round_trip(): void {
		$queue = $this->queue();
		$value = [ 'ids' => [ 3, 2, 1 ], 'found' => 3, 'by' => 'ids' ];

		$this->assertTrue( $queue->is_empty() );
		$this->assertNull( $queue->pending( 'k' ) );

		$queue->add_store( 'k', 'v1', $value, 60, 120 );

		$this->assertFalse( $queue->is_empty() );
		$this->assertSame( [ 'version' => 'v1', 'value' => $value, 'soft' => 60, 'hard' => 120 ], $queue->pending( 'k' ) );
		$this->assertSame( [ 'k' => [ 'version' => 'v1', 'value' => $value, 'soft' => 60, 'hard' => 120 ] ], $queue->take_stores() );
		$this->assertSame( [], $queue->take_stores(), 'taking the stores empties the list' );
		$this->assertNull( $queue->pending( 'k' ) );
		$this->assertTrue( $queue->is_empty() );
	}

	public function test_ignore_user_abort_on_first_add(): void {
		ignore_user_abort( false );

		$queue = $this->queue();

		$this->assertSame( 0, ignore_user_abort(), 'not on construction' );

		$this->queue()->finish();

		$this->assertSame( 0, ignore_user_abort(), 'not in finish()' );

		$queue->add_store( 'k', 'v', [ 'ids' => [], 'found' => 0 ], 60, 120 );

		$this->assertSame( 1, ignore_user_abort(), 'on the first store' );

		ignore_user_abort( false );
		$queue->add_store( 'k2', 'v', [ 'ids' => [], 'found' => 0 ], 60, 120 );
		$queue->add_job( [ 'key' => 'j', 'written' => null ] );

		$this->assertSame( 0, ignore_user_abort(), 'only once' );

		ignore_user_abort( false );
		$jobs_first = $this->queue();
		$jobs_first->add_job( [ 'key' => 'j', 'written' => null ] );

		$this->assertSame( 1, ignore_user_abort(), 'a first job counts too' );
	}

	// ---- Jobs ----

	public function test_add_job_once_per_key(): void {
		$queue = $this->queue();

		$queue->add_job( [ 'key' => 'a', 'written' => 100, 'tag' => 'first' ] );
		$queue->add_job( [ 'key' => 'a', 'written' => 50, 'tag' => 'second' ] );
		$queue->add_job( [ 'key' => 'b', 'written' => 200 ] );

		$this->assertTrue( $queue->has_job( 'a' ) );
		$this->assertTrue( $queue->has_job( 'b' ) );
		$this->assertFalse( $queue->has_job( 'c' ) );
		$this->assertFalse( $queue->is_empty() );

		$jobs = $queue->jobs();

		$this->assertCount( 2, $jobs );
		$this->assertSame( 'first', $jobs[0]['tag'], 'the second job for a key is ignored' );
	}

	public function test_jobs_oldest_first_null_first(): void {
		$queue = $this->queue();

		$queue->add_job( [ 'key' => 'c', 'written' => 300 ] );
		$queue->add_job( [ 'key' => 'a', 'written' => null ] );
		$queue->add_job( [ 'key' => 'b', 'written' => 100 ] );
		$queue->add_job( [ 'key' => 'd' ] );
		$queue->add_job( [ 'key' => 'e', 'written' => 100 ] );

		$this->assertSame( [ 'a', 'd', 'b', 'e', 'c' ], array_column( $queue->jobs(), 'key' ) );
	}

	/** Taking the jobs empties the list, so a second run of the queue cannot run them again. */
	public function test_take_jobs_oldest_first_and_empties(): void {
		$queue = $this->queue();

		$queue->add_job( [ 'key' => 'b', 'written' => 200 ] );
		$queue->add_job( [ 'key' => 'a', 'written' => 100 ] );

		$this->assertSame( [ 'a', 'b' ], array_column( $queue->take_jobs(), 'key' ) );
		$this->assertSame( [], $queue->take_jobs() );
		$this->assertSame( [], $queue->jobs() );
		$this->assertFalse( $queue->has_job( 'a' ) );
		$this->assertTrue( $queue->is_empty() );
	}

	/** Closed once the queue starts running, or once the response is finished. */
	public function test_closed(): void {
		$closed = $this->queue();

		$this->assertFalse( $closed->closed() );

		$closed->close();

		$this->assertTrue( $closed->closed() );
		$this->assertFalse( $closed->finished(), 'closing does not finish the response' );

		$finished = $this->queue();

		$finished->finish();

		$this->assertTrue( $finished->closed() );
	}

	public function test_rebuilt_keys(): void {
		$queue = $this->queue();

		$this->assertFalse( $queue->was_rebuilt( 'k' ) );

		$queue->mark_rebuilt( 'k' );

		$this->assertTrue( $queue->was_rebuilt( 'k' ) );
		$this->assertFalse( $queue->was_rebuilt( 'other' ) );
	}

	// ---- DONOTCACHEPAGE ----

	public function test_no_page_cache_only_on_page_view(): void {
		foreach ( [ [ true, 1 ], [ false, 0 ] ] as [ $page_view, $expected ] ) {
			$calls = 0;
			$queue = new Mai_Query_Cache_Queue(
				static fn() => true,
				static fn() => null,
				static function () use ( &$calls ) {
					++$calls;
				},
				static fn() => $page_view
			);

			$queue->no_page_cache();

			$this->assertSame( $expected, $calls, $page_view ? 'a page view' : 'not a page view' );
		}
	}

	#[RunInSeparateProcess]
	public function test_default_no_page_cache_defines_the_constant(): void {
		$this->assertFalse( defined( 'DONOTCACHEPAGE' ) );

		( new Mai_Query_Cache_Queue( null, null, null, static fn() => true ) )->no_page_cache();

		$this->assertTrue( constant( 'DONOTCACHEPAGE' ) );
	}

	/** A page cache plugin or theme got there first. Defining it again would raise a warning. */
	#[RunInSeparateProcess]
	public function test_default_no_page_cache_skips_when_defined(): void {
		define( 'DONOTCACHEPAGE', false );

		( new Mai_Query_Cache_Queue( null, null, null, static fn() => true ) )->no_page_cache();

		$this->assertFalse( constant( 'DONOTCACHEPAGE' ), 'left as it was' );
	}
}
