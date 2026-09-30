<?php
namespace BizBudding\MaiEngine\Tests\Unit;

use Brain\Monkey\Functions;
use BizBudding\MaiEngine\Tests\TestCase;
use Mai_Query_Cache;

/**
 * The kept-only path in Mai_Query_Cache: reading the grid's request, building the ID-only
 * statement, and loading only the posts the grid keeps.
 */
final class MaiQueryCacheKeepTest extends TestCase {
	private const SQL = "SELECT   wp_posts.*
					 FROM wp_posts  LEFT JOIN wp_term_relationships ON (wp_posts.ID = wp_term_relationships.object_id)
					 WHERE 1=1  AND ( wp_term_relationships.term_taxonomy_id IN (6) ) AND wp_posts.post_type = 'post'
					 GROUP BY wp_posts.ID
					 ORDER BY wp_posts.post_date DESC, wp_posts.ID DESC
					 LIMIT 0, 7";

	/** @var object Stand-in for $wpdb, recording what get_col() was asked to run. */
	private $wpdb;

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'apply_filters' )->alias( fn( $tag, $value ) => $value );

		$this->wpdb = new class {
			public $posts = 'wp_posts';
			public $ran   = [];

			public function get_col( $sql ) {
				$this->ran[] = $sql;

				return [ '12', '10', '11' ];
			}
		};

		$GLOBALS['wpdb'] = $this->wpdb;
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wpdb'] );

		parent::tearDown();
	}

	/**
	 * Invoke a private method.
	 */
	private function call( string $method, ...$args ) {
		$cache = new Mai_Query_Cache();

		// Private methods are invocable via reflection without setAccessible() on PHP 8.1+.
		return ( new \ReflectionMethod( $cache, $method ) )->invoke( $cache, ...$args );
	}

	private function query( array $query_vars = [], string $request = self::SQL ): object {
		return (object) [
			'query_vars' => $query_vars + [ 'mai_grid_keep' => [ 'exclude' => [ 10 ], 'count' => 2 ] ],
			'request'    => $request,
		];
	}

	// ---- note_request() ----

	public function test_note_request_records_the_statement_for_a_kept_only_query(): void {
		$query = $this->query();

		$this->assertSame( self::SQL, ( new Mai_Query_Cache() )->note_request( self::SQL, $query ) );
		$this->assertSame( self::SQL, $query->mai_grid_request );
	}

	public function test_note_request_leaves_every_other_query_alone(): void {
		$query = (object) [ 'query_vars' => [] ];

		( new Mai_Query_Cache() )->note_request( self::SQL, $query );

		$this->assertObjectNotHasProperty( 'mai_grid_request', $query );
	}

	// ---- keep_request() ----

	public function test_keep_request_reads_the_marker(): void {
		$query = $this->query( [ 'mai_grid_keep' => [ 'exclude' => [ '10', 11 ], 'count' => '2' ] ] );

		$this->assertSame( [ 'exclude' => [ 10, 11 ], 'count' => 2 ], $this->call( 'keep_request', null, $query ) );
	}

	public function test_keep_request_declines_when_something_already_answered(): void {
		$this->assertNull( $this->call( 'keep_request', [ 1, 2 ], $this->query() ) );
	}

	public function test_keep_request_declines_for_ids_and_id_parent_queries(): void {
		// Core returns these as ints or stdClass straight out of get_posts().
		$this->assertNull( $this->call( 'keep_request', null, $this->query( [ 'fields' => 'ids' ] ) ) );
		$this->assertNull( $this->call( 'keep_request', null, $this->query( [ 'fields' => 'id=>parent' ] ) ) );
	}

	public function test_keep_request_declines_without_a_usable_marker(): void {
		$this->assertNull( $this->call( 'keep_request', null, (object) [ 'query_vars' => [] ] ) );
		$this->assertNull( $this->call( 'keep_request', null, $this->query( [ 'mai_grid_keep' => true ] ) ) );
		$this->assertNull( $this->call( 'keep_request', null, $this->query( [ 'mai_grid_keep' => [ 'exclude' => '10', 'count' => 2 ] ] ) ) );
		$this->assertNull( $this->call( 'keep_request', null, $this->query( [ 'mai_grid_keep' => [ 'exclude' => [ 10 ] ] ] ) ) );
	}

	// ---- pre_query_kept() ----

	/** Stubs what keep() needs, so pre_query_kept() can answer. */
	private function stub_every_post_published(): void {
		Functions\when( '_prime_post_caches' )->justReturn( null );
		Functions\when( 'get_post' )->alias( fn( $id ) => (object) [ 'ID' => $id, 'post_status' => 'publish' ] );
	}

	public function test_pre_query_kept_answers_a_miss_nobody_else_answered(): void {
		$this->stub_every_post_published();

		$query                   = $this->query();
		$query->mai_grid_request = self::SQL;

		$posts = ( new Mai_Query_Cache() )->pre_query_kept( null, $query );

		// get_col() returned 12, 10, 11. 10 is excluded, and 2 are asked for.
		$this->assertSame( [ 12, 11 ], array_map( fn( $p ) => $p->ID, $posts ) );
		$this->assertSame( $posts, $query->mai_grid_kept );
		$this->assertObjectNotHasProperty( 'mai_grid_request', $query, 'the capture must not outlive the query' );
	}

	public function test_pre_query_kept_leaves_another_callbacks_answer_alone(): void {
		// The Events Calendar's custom tables query answers at priority 100, for one.
		$query                   = $this->query();
		$query->mai_grid_request = self::SQL;

		$theirs = [ (object) [ 'ID' => 5 ] ];

		$this->assertSame( $theirs, ( new Mai_Query_Cache() )->pre_query_kept( $theirs, $query ) );
		$this->assertSame( [], $this->wpdb->ran, 'no ID query when the miss is already answered' );
		$this->assertObjectNotHasProperty( 'mai_grid_kept', $query, 'Mai_Grid must filter their answer itself' );
		$this->assertObjectNotHasProperty( 'mai_grid_request', $query, 'cleared even when declining' );
	}

	public function test_pre_query_kept_keeps_a_cached_kept_answer_that_survived(): void {
		$ours                 = [ (object) [ 'ID' => 12 ] ];
		$query                = $this->query();
		$query->mai_grid_kept = $ours;

		$this->assertSame( $ours, ( new Mai_Query_Cache() )->pre_query_kept( $ours, $query ) );
		$this->assertSame( $ours, $query->mai_grid_kept );
		$this->assertSame( [], $this->wpdb->ran );
	}

	public function test_pre_query_kept_drops_the_flag_when_a_later_callback_replaced_the_answer(): void {
		$query                = $this->query();
		$query->mai_grid_kept = [ (object) [ 'ID' => 12 ] ];

		$theirs = [ (object) [ 'ID' => 10 ], (object) [ 'ID' => 12 ] ];

		$this->assertSame( $theirs, ( new Mai_Query_Cache() )->pre_query_kept( $theirs, $query ) );
		$this->assertObjectNotHasProperty( 'mai_grid_kept', $query, 'what replaced it may hold the excludes' );
	}

	public function test_pre_query_kept_ignores_queries_without_the_marker(): void {
		$query = (object) [ 'query_vars' => [], 'request' => self::SQL ];

		$this->assertNull( ( new Mai_Query_Cache() )->pre_query_kept( null, $query ) );
		$this->assertSame( [], $this->wpdb->ran );
	}

	// ---- fetch_ids() ----

	public function test_fetch_ids_selects_only_the_id_from_the_same_statement(): void {
		$ids = $this->call( 'fetch_ids', $this->query(), self::SQL );

		$this->assertSame( [ 12, 10, 11 ], $ids, 'in the order the database returned them, as ints' );
		$this->assertCount( 1, $this->wpdb->ran );
		$this->assertSame( str_replace( 'SELECT   wp_posts.*', 'SELECT   wp_posts.ID', self::SQL ), $this->wpdb->ran[0], 'only the select list may change' );
	}

	public function test_fetch_ids_keeps_distinct(): void {
		$sql = str_replace( 'SELECT   wp_posts.*', 'SELECT  DISTINCT wp_posts.*', self::SQL );

		$this->call( 'fetch_ids', $this->query( [], $sql ), $sql );

		$this->assertStringStartsWith( 'SELECT  DISTINCT wp_posts.ID', $this->wpdb->ran[0] );
	}

	public function test_fetch_ids_declines_when_a_posts_request_callback_rewrote_the_statement(): void {
		$this->assertNull( $this->call( 'fetch_ids', $this->query( [], self::SQL . ' /* rewritten */' ), self::SQL ) );
		$this->assertSame( [], $this->wpdb->ran );
	}

	public function test_fetch_ids_declines_without_a_capture(): void {
		// posts_request is skipped under suppress_filters, so nothing was noted.
		$this->assertNull( $this->call( 'fetch_ids', $this->query(), null ) );
		$this->assertSame( [], $this->wpdb->ran );
	}

	public function test_fetch_ids_declines_when_more_than_wp_posts_star_is_selected(): void {
		// An ORDER BY can sort on a column a posts_fields callback added.
		$sql = str_replace( 'wp_posts.*', 'wp_posts.*, 1 AS distance', self::SQL );

		$this->assertNull( $this->call( 'fetch_ids', $this->query( [], $sql ), $sql ) );
		$this->assertSame( [], $this->wpdb->ran );
	}

	public function test_fetch_ids_declines_when_the_query_counts_rows(): void {
		$sql = str_replace( 'SELECT   wp_posts.*', 'SELECT SQL_CALC_FOUND_ROWS  wp_posts.*', self::SQL );

		$this->assertNull( $this->call( 'fetch_ids', $this->query( [], $sql ), $sql ) );
		$this->assertSame( [], $this->wpdb->ran );
	}

	public function test_fetch_ids_runs_posts_request_ids(): void {
		Functions\when( 'apply_filters' )->alias(
			fn( $tag, $value ) => 'posts_request_ids' === $tag ? $value . ' /* ids */' : $value
		);

		$this->call( 'fetch_ids', $this->query(), self::SQL );

		$this->assertStringEndsWith( ' /* ids */', $this->wpdb->ran[0] );
	}

	// ---- keep() ----

	/**
	 * @param array<int,string> $map id => post_status ( ids absent from the map resolve to null ).
	 */
	private function stub_posts( array $map, array &$primed ): void {
		Functions\when( '_prime_post_caches' )->alias(
			function ( $ids, $terms, $meta ) use ( &$primed ) {
				$primed[] = [ $ids, $terms, $meta ];
			}
		);

		Functions\when( 'get_post' )->alias(
			fn( $id ) => isset( $map[ $id ] ) ? (object) [ 'ID' => $id, 'post_status' => $map[ $id ] ] : null
		);
	}

	public function test_keep_drops_the_excludes_and_primes_only_the_kept_posts(): void {
		$primed = [];
		$this->stub_posts( [ 1 => 'publish', 2 => 'publish', 3 => 'publish', 4 => 'publish', 5 => 'publish' ], $primed );

		$query = (object) [ 'query_vars' => [ 'post_status' => 'publish', 'update_post_term_cache' => true, 'update_post_meta_cache' => true ] ];
		$posts = $this->call( 'keep', $query, [ 'exclude' => [ 1, 3 ], 'count' => 2 ], [ 1, 2, 3, 4, 5 ] );

		$this->assertSame( [ 2, 4 ], array_map( fn( $p ) => $p->ID, $posts ) );
		$this->assertSame( [ [ [ 2, 4 ], true, true ] ], $primed, 'one priming call, for the kept posts only' );
		$this->assertSame( $posts, $query->mai_grid_kept, 'Mai_Grid must be told not to filter again' );
	}

	public function test_keep_tops_up_when_a_stale_entry_holds_a_post_that_left(): void {
		// 3 went to draft after the entry was stored, and 4 was deleted.
		$primed = [];
		$this->stub_posts( [ 1 => 'publish', 2 => 'publish', 3 => 'draft', 5 => 'publish', 6 => 'publish' ], $primed );

		$query = (object) [ 'query_vars' => [ 'post_status' => 'publish' ] ];
		$posts = $this->call( 'keep', $query, [ 'exclude' => [ 1 ], 'count' => 3 ], [ 1, 2, 3, 4, 5, 6 ] );

		$this->assertSame( [ 2, 5, 6 ], array_map( fn( $p ) => $p->ID, $posts ), 'the grid still fills, in stored order' );
		$this->assertSame( [ [ 2, 3, 4 ], [ 5, 6 ] ], array_column( $primed, 0 ), 'each top-up asks only for what is still missing' );
	}

	public function test_keep_returns_what_there_is_when_the_list_runs_out(): void {
		$primed = [];
		$this->stub_posts( [ 1 => 'publish', 2 => 'publish' ], $primed );

		$query = (object) [ 'query_vars' => [] ];
		$posts = $this->call( 'keep', $query, [ 'exclude' => [ 1 ], 'count' => 6 ], [ 1, 2 ] );

		$this->assertSame( [ 2 ], array_map( fn( $p ) => $p->ID, $posts ) );
	}

	public function test_keep_with_nothing_left_loads_nothing(): void {
		$primed = [];
		$this->stub_posts( [], $primed );

		$query = (object) [ 'query_vars' => [] ];

		$this->assertSame( [], $this->call( 'keep', $query, [ 'exclude' => [ 1, 2 ], 'count' => 3 ], [ 1, 2 ] ) );
		$this->assertSame( [], $primed );
		$this->assertSame( [], $query->mai_grid_kept, 'an empty grid is still an answered one' );
	}
}
