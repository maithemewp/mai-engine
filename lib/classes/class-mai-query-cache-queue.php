<?php
declare(strict_types=1);

/**
 * Mai Grid Result Cache queue.
 *
 * One per request. Holds the grid results waiting to be stored after the page, the rebuild
 * jobs waiting to run after it, and the keys already rebuilt during it. It also decides
 * whether this request can finish its response early, and finishes it.
 *
 * Four things are seams, each a callable passed to the constructor: whether the server can
 * finish the response early, finishing it, asking page caches not to keep the page, and whether
 * this is a page view. Tests replace them, so they never call fastcgi_finish_request() or
 * define DONOTCACHEPAGE. ignore_user_abort() and session_write_close() are called directly.
 *
 * @package BizBudding\MaiEngine
 */

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

class Mai_Query_Cache_Queue {

	/**
	 * Whether the server can finish the response early.
	 */
	private Closure $can_finish;

	/**
	 * Finishes the response.
	 */
	private Closure $finish;

	/**
	 * Asks page caches not to keep this page.
	 */
	private Closure $no_page_cache;

	/**
	 * Whether this request is a normal front-end page view.
	 */
	private Closure $is_page_view;

	/**
	 * Results waiting to be stored, by cache key.
	 *
	 * @var array<string,array{version:string,value:array,soft:int,hard:int}>
	 */
	private array $stores = [];

	/**
	 * Rebuild jobs waiting to run, by cache key, in the order they were added.
	 *
	 * @var array<string,array>
	 */
	private array $jobs = [];

	/**
	 * Keys rebuilt during this request, as keys.
	 *
	 * @var array<string,true>
	 */
	private array $rebuilt = [];

	/**
	 * Whether ignore_user_abort( true ) has been called.
	 */
	private bool $abort_ignored = false;

	/**
	 * Whether close() has run.
	 */
	private bool $closed = false;

	/**
	 * Whether finish() has run.
	 */
	private bool $finished = false;

	/**
	 * When finish() ran, from hrtime( true ), in nanoseconds.
	 */
	private ?int $started = null;

	/**
	 * Sets up the seams. Each one left null uses the real behaviour.
	 *
	 * @since TBD
	 *
	 * @param callable|null $can_finish    Returns whether the server can finish the response
	 *                                     early. Default: fastcgi_finish_request() or
	 *                                     litespeed_finish_request() exists.
	 * @param callable|null $finish        Finishes the response. Default: whichever of those two
	 *                                     exists.
	 * @param callable|null $no_page_cache Asks page caches not to keep this page. Default:
	 *                                     defines DONOTCACHEPAGE as true, unless something
	 *                                     already defined it.
	 * @param callable|null $is_page_view  Returns whether this request is a normal front-end
	 *                                     page view. Default: see request_is_page_view().
	 */
	public function __construct( ?callable $can_finish = null, ?callable $finish = null, ?callable $no_page_cache = null, ?callable $is_page_view = null ) {
		$this->can_finish    = Closure::fromCallable( $can_finish ?? self::server_can_finish( ... ) );
		$this->finish        = Closure::fromCallable( $finish ?? self::finish_response( ... ) );
		$this->no_page_cache = Closure::fromCallable( $no_page_cache ?? self::define_no_page_cache( ... ) );
		$this->is_page_view  = Closure::fromCallable( $is_page_view ?? self::request_is_page_view( ... ) );
	}

	/**
	 * Whether this request is a normal front-end page view.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function is_page_view(): bool {
		return (bool) ( $this->is_page_view )();
	}

	/**
	 * Whether this request can send the visitor their page and keep running afterwards.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function can_finish_early(): bool {
		return (bool) ( $this->can_finish )() && $this->is_page_view();
	}

	/**
	 * Holds a result to store after the page.
	 *
	 * @since TBD
	 *
	 * @param string $key     Cache key.
	 * @param string $version The version read before the query ran.
	 * @param array  $value   The value to store.
	 * @param int    $soft    Soft lifetime in seconds.
	 * @param int    $hard    Hard lifetime in seconds.
	 *
	 * @return void
	 */
	public function add_store( string $key, string $version, array $value, int $soft, int $hard ): void {
		$this->ignore_abort();

		$this->stores[ $key ] = [
			'version' => $version,
			'value'   => $value,
			'soft'    => $soft,
			'hard'    => $hard,
		];
	}

	/**
	 * The result waiting to be stored under a key, if there is one.
	 *
	 * @since TBD
	 *
	 * @param string $key Cache key.
	 *
	 * @return array{version:string,value:array,soft:int,hard:int}|null
	 */
	public function pending( string $key ): ?array {
		return $this->stores[ $key ] ?? null;
	}

	/**
	 * Returns every result waiting to be stored, by cache key, and empties the list.
	 *
	 * @since TBD
	 *
	 * @return array<string,array{version:string,value:array,soft:int,hard:int}>
	 */
	public function take_stores(): array {
		$stores       = $this->stores;
		$this->stores = [];

		return $stores;
	}

	/**
	 * Queues a rebuild job. A second job for a key already queued is ignored.
	 *
	 * @since TBD
	 *
	 * @param array $job The job. 'key' is the cache key, and 'written' is when the entry was
	 *                   written (a timestamp), or null when that is not known.
	 *
	 * @return void
	 */
	public function add_job( array $job ): void {
		$key = (string) ( $job['key'] ?? '' );

		if ( '' === $key || isset( $this->jobs[ $key ] ) ) {
			return;
		}

		$this->ignore_abort();

		$this->jobs[ $key ] = $job;
	}

	/**
	 * Whether a job is queued for a key.
	 *
	 * @since TBD
	 *
	 * @param string $key Cache key.
	 *
	 * @return bool
	 */
	public function has_job( string $key ): bool {
		return isset( $this->jobs[ $key ] );
	}

	/**
	 * The queued jobs, oldest entry first. A job whose entry has no write time comes first.
	 * Jobs written at the same time keep the order they were added in.
	 *
	 * @since TBD
	 *
	 * @return array[]
	 */
	public function jobs(): array {
		$jobs = array_values( $this->jobs );

		usort( $jobs, static fn( array $a, array $b ): int => ( $a['written'] ?? PHP_INT_MIN ) <=> ( $b['written'] ?? PHP_INT_MIN ) );

		return $jobs;
	}

	/**
	 * Returns the queued jobs, oldest entry first, and empties the list, so no job runs twice.
	 *
	 * @since TBD
	 *
	 * @return array[]
	 */
	public function take_jobs(): array {
		$jobs       = $this->jobs();
		$this->jobs = [];

		return $jobs;
	}

	/**
	 * Records that a key was rebuilt during this request.
	 *
	 * @since TBD
	 *
	 * @param string $key Cache key.
	 *
	 * @return void
	 */
	public function mark_rebuilt( string $key ): void {
		$this->rebuilt[ $key ] = true;
	}

	/**
	 * Whether a key was rebuilt during this request.
	 *
	 * @since TBD
	 *
	 * @param string $key Cache key.
	 *
	 * @return bool
	 */
	public function was_rebuilt( string $key ): bool {
		return isset( $this->rebuilt[ $key ] );
	}

	/**
	 * Asks page caches not to keep this page, on a page view only.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function no_page_cache(): void {
		if ( $this->is_page_view() ) {
			( $this->no_page_cache )();
		}
	}

	/**
	 * Whether nothing is waiting: no stores and no jobs.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function is_empty(): bool {
		return ! $this->stores && ! $this->jobs;
	}

	/**
	 * Marks the queue as run. Called first thing on shutdown, even when nothing is waiting.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function close(): void {
		$this->closed = true;
	}

	/**
	 * Whether the queue has run, or the response is finished. A store or job added after this
	 * would never run, so the caller does the work at once instead.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function closed(): bool {
		return $this->closed || $this->finished;
	}

	/**
	 * Sends the visitor their page, once. Anything after this runs after the page.
	 *
	 * A PHP session is closed first, so the visitor's next request is not kept waiting for
	 * its lock.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function finish(): void {
		if ( $this->finished ) {
			return;
		}

		$this->finished = true;

		if ( PHP_SESSION_ACTIVE === session_status() ) {
			session_write_close();
		}

		( $this->finish )();

		$this->started = hrtime( true );
	}

	/**
	 * Whether finish() has run.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function finished(): bool {
		return $this->finished;
	}

	/**
	 * How long the work after the page has run, in milliseconds. 0 before finish().
	 *
	 * Real time from hrtime(), never Cache::now(), which counts whole seconds and which tests
	 * freeze.
	 *
	 * @since TBD
	 *
	 * @return float
	 */
	public function elapsed_ms(): float {
		return null === $this->started ? 0.0 : ( hrtime( true ) - $this->started ) / 1e6;
	}

	/**
	 * Keeps PHP running when the visitor leaves early, so queued work is not cut short.
	 * Called once, when the first store or job is added. wp-cron.php does the same before it
	 * finishes its response.
	 *
	 * @return void
	 */
	private function ignore_abort(): void {
		if ( $this->abort_ignored ) {
			return;
		}

		$this->abort_ignored = true;

		ignore_user_abort( true );
	}

	/**
	 * Whether the server can finish the response early: PHP-FPM or LiteSpeed.
	 *
	 * @return bool
	 */
	private static function server_can_finish(): bool {
		return function_exists( 'fastcgi_finish_request' ) || function_exists( 'litespeed_finish_request' );
	}

	/**
	 * Finishes the response with whichever function the server has. WordPress core uses the
	 * same two in wp-cron.php.
	 *
	 * @return void
	 */
	private static function finish_response(): void {
		if ( function_exists( 'fastcgi_finish_request' ) ) {
			fastcgi_finish_request();
		} elseif ( function_exists( 'litespeed_finish_request' ) ) {
			litespeed_finish_request();
		}
	}

	/**
	 * Defines DONOTCACHEPAGE as true, unless a page cache plugin or theme already defined it.
	 *
	 * @return void
	 */
	private static function define_no_page_cache(): void {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
	}

	/**
	 * Whether this request is a normal front-end page view: a GET request that is not admin,
	 * AJAX, REST, cron, WP-CLI, XML-RPC, a feed, or inside switch_to_blog().
	 *
	 * ms_is_switched() only exists on multisite, so it is only called there.
	 *
	 * @return bool
	 */
	private static function request_is_page_view(): bool {
		return 'GET' === ( $_SERVER['REQUEST_METHOD'] ?? '' )
			&& ! is_admin()
			&& ! wp_doing_ajax()
			&& ! self::is_rest_request()
			&& ! wp_doing_cron()
			&& ! ( defined( 'WP_CLI' ) && WP_CLI )
			&& ! ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST )
			&& ! is_feed()
			&& ! ( is_multisite() && ms_is_switched() );
	}

	/**
	 * Whether this is a REST request. wp_is_serving_rest_request() arrived in WordPress 6.5.
	 * Before that, the REST_REQUEST constant says the same.
	 *
	 * @return bool
	 */
	private static function is_rest_request(): bool {
		if ( function_exists( 'wp_is_serving_rest_request' ) ) {
			return (bool) wp_is_serving_rest_request();
		}

		return defined( 'REST_REQUEST' ) && REST_REQUEST;
	}
}
