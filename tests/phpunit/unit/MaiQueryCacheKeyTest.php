<?php
namespace BizBudding\MaiEngine\Tests\Unit;

use Brain\Monkey\Functions;
use BizBudding\MaiEngine\Tests\TestCase;
use Mai_Query_Cache;

final class MaiQueryCacheKeyTest extends TestCase {
	/** @var mixed The $wpdb global as it was before the test, or null when there was none. */
	private $saved_wpdb;

	protected function setUp(): void {
		parent::setUp();
		$this->saved_wpdb = $GLOBALS['wpdb'] ?? null;
	}

	protected function tearDown(): void {
		if ( null === $this->saved_wpdb ) {
			unset( $GLOBALS['wpdb'] );
		} else {
			$GLOBALS['wpdb'] = $this->saved_wpdb;
		}

		parent::tearDown();
	}

	/**
	 * Installs a $wpdb stand-in whose placeholder escape is $placeholder, as one request's
	 * $wpdb->placeholder_escape() would return. Core makes a new one on every request.
	 */
	private function use_placeholder( string $placeholder ): void {
		$GLOBALS['wpdb'] = new class( $placeholder ) {
			public function __construct( private string $placeholder ) {}

			public function placeholder_escape(): string {
				return $this->placeholder;
			}

			public function remove_placeholder_escape( $query ) {
				return str_replace( $this->placeholder, '%', $query );
			}
		};
	}

	/**
	 * The vars and SQL of a grid with a LIKE '%foo%' clause, as $wpdb->prepare() leaves them
	 * in one request: every % swapped for that request's placeholder. Core keeps a prepared
	 * clause in search_orderby_title, so the placeholder reaches the query vars too.
	 *
	 * @return array{0:array,1:string}
	 */
	private function like_grid( string $placeholder ): array {
		$like = "{$placeholder}foo{$placeholder}";

		return [
			[
				'post_type'            => 'post',
				's'                    => $like,
				'search_orderby_title' => [ "wp_posts.post_title LIKE '{$like}'" ],
			],
			"SELECT wp_posts.* FROM wp_posts WHERE 1=1 AND wp_posts.post_title LIKE '{$like}' LIMIT 0, 12",
		];
	}

	public function test_placeholder_escape_does_not_change_key(): void {
		$c    = new Mai_Query_Cache();
		$keys = [];

		foreach ( [ '{1a2b3c4d}', '{5e6f7a8b}' ] as $placeholder ) {
			$this->use_placeholder( $placeholder );

			[ $vars, $sql ] = $this->like_grid( $placeholder );
			$keys[]         = $c->cache_key( $vars, $sql );
		}

		$this->assertSame( $keys[0], $keys[1] );
	}

	public function test_placeholder_escape_is_not_stripped_without_a_full_wpdb(): void {
		// Two unit test files install a $wpdb stand-in without the escape methods and leave it
		// behind, so the key has to build without them rather than fatal.
		[ $vars, $sql ] = $this->like_grid( '{1a2b3c4d}' );
		$c              = new Mai_Query_Cache();

		unset( $GLOBALS['wpdb'] );
		$without = $c->cache_key( $vars, $sql );

		$GLOBALS['wpdb'] = new class {
			public string $posts = 'wp_posts';
		};

		$this->assertSame( $without, $c->cache_key( $vars, $sql ) );
	}

	public function test_arg_order_and_volatile_vars_do_not_change_the_key(): void {
		$c = new Mai_Query_Cache();
		$a = $c->cache_key( [ 'post_type' => 'post', 'post__in' => [ 3, 1, 2 ], 'fields' => 'ids', 'cache_results' => true, 'update_post_meta_cache' => false ], 'SELECT 1' );
		$b = $c->cache_key( [ 'post__in' => [ 1, 2, 3 ], 'post_type' => 'post' ], 'SELECT 1' );
		$this->assertSame( $a, $b );
	}

	public function test_different_sql_changes_the_key(): void {
		$c = new Mai_Query_Cache();
		$this->assertNotSame(
			$c->cache_key( [ 'post_type' => 'post' ], 'SELECT 1' ),
			$c->cache_key( [ 'post_type' => 'post' ], 'SELECT 2' )
		);
	}

	public function test_custom_args_are_kept_in_the_key(): void {
		$c = new Mai_Query_Cache();
		$this->assertNotSame(
			$c->cache_key( [ 'post_type' => 'post', 'my_custom' => 'x' ], 'SELECT 1' ),
			$c->cache_key( [ 'post_type' => 'post', 'my_custom' => 'y' ], 'SELECT 1' )
		);
	}

	public function test_post_status_order_does_not_change_the_key(): void {
		$c = new Mai_Query_Cache();
		$this->assertSame(
			$c->cache_key( [ 'post_type' => 'post', 'post_status' => [ 'publish', 'private' ] ], 'SELECT 1' ),
			$c->cache_key( [ 'post_type' => 'post', 'post_status' => [ 'private', 'publish' ] ], 'SELECT 1' )
		);
	}

	public function test_unset_orderby_matches_explicit_date(): void {
		$c = new Mai_Query_Cache();
		$this->assertSame(
			$c->cache_key( [ 'post_type' => 'post' ], 'SELECT 1' ),
			$c->cache_key( [ 'post_type' => 'post', 'orderby' => 'date' ], 'SELECT 1' )
		);
	}

	public function test_select_field_list_is_normalized_out(): void {
		$c     = new Mai_Query_Cache();
		$split = 'SELECT wp_posts.ID FROM wp_posts WHERE 1=1 ORDER BY wp_posts.post_date DESC LIMIT 0, 12';
		$full  = 'SELECT wp_posts.* FROM wp_posts WHERE 1=1 ORDER BY wp_posts.post_date DESC LIMIT 0, 12';
		$this->assertSame(
			$c->cache_key( [ 'post_type' => 'post' ], $split ),
			$c->cache_key( [ 'post_type' => 'post' ], $full )
		);
	}

	public function test_where_still_changes_the_key_after_field_normalization(): void {
		$c = new Mai_Query_Cache();
		$this->assertNotSame(
			$c->cache_key( [ 'post_type' => 'post' ], 'SELECT wp_posts.ID FROM wp_posts WHERE a=1' ),
			$c->cache_key( [ 'post_type' => 'post' ], 'SELECT wp_posts.* FROM wp_posts WHERE a=2' )
		);
	}

	public function test_a_datetime_in_the_sql_is_truncated_to_the_hour(): void {
		$c = new Mai_Query_Cache();
		$this->assertSame(
			$c->cache_key( [ 'post_type' => 'post' ], "SELECT wp_posts.* FROM wp_posts WHERE wp_posts.post_date > '2026-09-30 15:24:24'" ),
			$c->cache_key( [ 'post_type' => 'post' ], "SELECT wp_posts.* FROM wp_posts WHERE wp_posts.post_date > '2026-09-30 15:59:59'" )
		);
	}

	/**
	 * Freezes "now" at 15:24:30 site time, 19:24:30 UTC.
	 */
	private function freeze_now(): void {
		Functions\when( 'current_time' )->alias(
			fn( $type, $gmt = false ) => $gmt ? '2026-09-30 19:24:30' : '2026-09-30 15:24:30'
		);
	}

	/**
	 * The Events Calendar's end-date clause, with "now" as its value.
	 */
	private function event_vars( string $now ): array {
		return [
			'post_type'  => 'tribe_events',
			'meta_query' => [
				'tec_event_end_date' => [ 'key' => '_EventEndDate', 'value' => $now, 'compare' => '>=', 'type' => 'DATETIME' ],
			],
		];
	}

	public function test_a_query_that_holds_now_is_not_cacheable(): void {
		// The Events Calendar writes "now" into the meta_query of every event query, to the
		// second, so the key changes on every view. Caching it would never hit.
		$this->freeze_now();
		Functions\when( 'apply_filters' )->returnArg( 2 );

		$this->assertFalse( ( new Mai_Query_Cache() )->is_cacheable( $this->event_vars( '2026-09-30 15:24:24' ) ) );
	}

	public function test_a_query_that_holds_now_in_utc_is_not_cacheable(): void {
		$this->freeze_now();
		Functions\when( 'apply_filters' )->returnArg( 2 );

		$this->assertFalse( ( new Mai_Query_Cache() )->is_cacheable( $this->event_vars( '2026-09-30 19:24:24' ) ) );
	}

	public function test_a_datetime_far_from_now_keeps_its_own_key(): void {
		// Two grids set to different absolute datetimes in the same hour, say "posts before this
		// article" on two articles published half an hour apart, must not share an entry.
		$this->freeze_now();

		$c = new Mai_Query_Cache();

		$this->assertNotSame(
			$c->cache_key( [ 'post_type' => 'post', 'date_query' => [ 'before' => '2026-06-01 10:05:00' ] ], 'SELECT 1' ),
			$c->cache_key( [ 'post_type' => 'post', 'date_query' => [ 'before' => '2026-06-01 10:40:00' ] ], 'SELECT 1' )
		);

		// And a datetime set on purpose, far from now, is still cached.
		Functions\when( 'apply_filters' )->returnArg( 2 );
		$this->assertTrue( $c->is_cacheable( [ 'post_type' => 'post', 'date_query' => [ 'before' => '2026-06-01 10:05:00' ] ] ) );
	}

	/**
	 * pre_query() passes on whether the vars hold the current time, so kept-only does not check
	 * them again. Null means an earlier rule refused the vars and they were never checked.
	 */
	public function test_is_cacheable_reports_whether_the_vars_hold_now(): void {
		$this->freeze_now();
		Functions\when( 'apply_filters' )->returnArg( 2 );

		$c = new Mai_Query_Cache();

		// Each starts at a value the call has to overwrite.
		$holds_now = false;
		$this->assertFalse( $c->is_cacheable( $this->event_vars( '2026-09-30 15:24:24' ), $holds_now ) );
		$this->assertTrue( $holds_now );

		$holds_now = true;
		$this->assertTrue( $c->is_cacheable( $this->event_vars( '2026-06-01 10:05:00' ), $holds_now ) );
		$this->assertFalse( $holds_now );

		$holds_now = true;
		$this->assertFalse( $c->is_cacheable( [ 'post_type' => 'post', 'orderby' => 'rand' ], $holds_now ) );
		$this->assertNull( $holds_now, 'a random order is refused before the vars are checked' );
	}

	public function test_a_filter_that_caches_a_query_holding_now_still_reports_it(): void {
		$this->freeze_now();
		Functions\when( 'apply_filters' )->justReturn( true );

		$holds_now = false;

		$this->assertTrue( ( new Mai_Query_Cache() )->is_cacheable( $this->event_vars( '2026-09-30 15:24:24' ), $holds_now ) );
		$this->assertTrue( $holds_now, 'kept-only must still step aside for it' );
	}

	public function test_a_relative_date_in_the_query_vars_still_changes_the_key(): void {
		// Two grids whose relative dates resolve within the same hour still differ in the vars.
		$c = new Mai_Query_Cache();
		$this->assertNotSame(
			$c->cache_key( [ 'post_type' => 'post', 'date_query' => [ 'after' => '3 months ago' ] ], 'SELECT 1' ),
			$c->cache_key( [ 'post_type' => 'post', 'date_query' => [ 'after' => '90 days ago' ] ], 'SELECT 1' )
		);
	}

	public function test_only_a_whole_datetime_value_is_truncated(): void {
		// A value that merely contains a datetime is not one, and stays as it is.
		$c = new Mai_Query_Cache();
		$this->assertNotSame(
			$c->cache_key( [ 'post_type' => 'post', 's' => 'at 2026-09-30 15:24:24 sharp' ], 'SELECT 1' ),
			$c->cache_key( [ 'post_type' => 'post', 's' => 'at 2026-09-30 15:24:26 sharp' ], 'SELECT 1' )
		);
	}
}
