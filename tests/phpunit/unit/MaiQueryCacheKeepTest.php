<?php
namespace BizBudding\MaiEngine\Tests\Unit;

use Brain\Monkey\Functions;
use BizBudding\MaiEngine\Tests\TestCase;
use Mai_Query_Cache;

/**
 * The kept-only path in Mai_Query_Cache: reading the grid's request, deciding when to answer,
 * and loading only the posts the grid keeps. The ID-only query itself needs WordPress, so the
 * integration suite covers it (GridKeptOnlyTest).
 */
final class MaiQueryCacheKeepTest extends TestCase {
	private const SQL = "SELECT   wp_posts.*
					 FROM wp_posts  LEFT JOIN wp_term_relationships ON (wp_posts.ID = wp_term_relationships.object_id)
					 WHERE 1=1  AND ( wp_term_relationships.term_taxonomy_id IN (6) ) AND wp_posts.post_type = 'post'
					 GROUP BY wp_posts.ID
					 ORDER BY wp_posts.post_date DESC, wp_posts.ID DESC
					 LIMIT 0, 7";

	/**
	 * Invoke a private method.
	 */
	private function call( string $method, ...$args ) {
		$cache = new Mai_Query_Cache();

		// Private methods are invocable via reflection without setAccessible() on PHP 8.1+.
		return ( new \ReflectionMethod( $cache, $method ) )->invoke( $cache, ...$args );
	}

	/**
	 * A query carrying the kept-only marker. Pass null for $keep to leave the marker off.
	 */
	private function query( array $query_vars = [], string $request = self::SQL, $keep = [ 'exclude' => [ 10 ], 'count' => 2 ] ): object {
		$query = (object) [
			'query_vars' => $query_vars,
			'request'    => $request,
		];

		if ( null !== $keep ) {
			$query->mai_grid_keep = $keep;
		}

		return $query;
	}

	// ---- keep_request() ----

	public function test_keep_request_reads_the_marker(): void {
		$query = $this->query( [], self::SQL, [ 'exclude' => [ '10', 11 ], 'count' => '2', 'cache_results' => 0 ] );

		$this->assertSame( [ 'exclude' => [ 10, 11 ], 'count' => 2, 'cache_results' => false ], $this->call( 'keep_request', null, $query ) );
	}

	public function test_keep_request_defaults_cache_results_to_on(): void {
		// WP_Query's own default.
		$this->assertTrue( $this->call( 'keep_request', null, $this->query() )['cache_results'] );
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
		$this->assertNull( $this->call( 'keep_request', null, $this->query( [], self::SQL, true ) ) );
		$this->assertNull( $this->call( 'keep_request', null, $this->query( [], self::SQL, [ 'exclude' => '10', 'count' => 2 ] ) ) );
		$this->assertNull( $this->call( 'keep_request', null, $this->query( [], self::SQL, [ 'exclude' => [ 10 ] ] ) ) );
	}

	public function test_keep_request_ignores_the_marker_as_a_query_var(): void {
		// A plugin that answers posts_pre_query with a copy built from this query's vars must
		// not get a kept-only copy. Only the property on the grid's own query counts.
		$query = $this->query( [ 'mai_grid_keep' => [ 'exclude' => [ 10 ], 'count' => 2 ] ], self::SQL, null );

		$this->assertNull( $this->call( 'keep_request', null, $query ) );
	}

	// ---- pre_query_kept() ----

	public function test_pre_query_kept_leaves_another_callbacks_answer_alone(): void {
		// The Events Calendar's custom tables query answers at priority 100, for one. No
		// WP_Query exists in this suite, so building the ID query here would fatal.
		$query  = $this->query();
		$theirs = [ (object) [ 'ID' => 5 ] ];

		$this->assertSame( $theirs, ( new Mai_Query_Cache() )->pre_query_kept( $theirs, $query ) );
		$this->assertObjectNotHasProperty( 'mai_grid_kept', $query, 'Mai_Grid must filter their answer itself' );
	}

	public function test_pre_query_kept_declines_when_the_query_counts_rows(): void {
		// The total is core's job. Only a pre_get_posts callback can turn counting on for a
		// deferring grid, and then core runs the grid's query as usual.
		$query = $this->query( [ 'no_found_rows' => false ] );

		$this->assertNull( ( new Mai_Query_Cache() )->pre_query_kept( null, $query ) );
		$this->assertObjectNotHasProperty( 'mai_grid_kept', $query );
	}

	public function test_pre_query_kept_keeps_a_cached_kept_answer_that_survived(): void {
		$ours                 = [ (object) [ 'ID' => 12 ] ];
		$query                = $this->query();
		$query->mai_grid_kept = $ours;

		$this->assertSame( $ours, ( new Mai_Query_Cache() )->pre_query_kept( $ours, $query ) );
		$this->assertSame( $ours, $query->mai_grid_kept );
	}

	public function test_pre_query_kept_drops_the_flag_when_a_later_callback_replaced_the_answer(): void {
		$query                       = $this->query();
		$query->mai_grid_kept        = [ (object) [ 'ID' => 12 ] ];
		$query->mai_grid_kept_primed = [ 'update_post_term_cache' => true, 'update_post_meta_cache' => true ];

		$theirs = [ (object) [ 'ID' => 10 ], (object) [ 'ID' => 12 ] ];

		$this->assertSame( $theirs, ( new Mai_Query_Cache() )->pre_query_kept( $theirs, $query ) );
		$this->assertObjectNotHasProperty( 'mai_grid_kept', $query, 'what replaced it may hold the excludes' );
		$this->assertObjectNotHasProperty( 'mai_grid_kept_primed', $query, 'what replaced it was never primed' );
	}

	public function test_pre_query_kept_takes_the_key_record_off_the_query_when_it_declines(): void {
		// The record holds the query's vars. Left on the query it would outlive this run.
		$record = [ 'key' => 'k', 'query_vars' => [], 'request' => self::SQL ];

		$theirs                         = [ (object) [ 'ID' => 5 ] ];
		$answered                       = $this->query();
		$answered->mai_cache_key_record = $record;

		$this->assertSame( $theirs, ( new Mai_Query_Cache() )->pre_query_kept( $theirs, $answered ) );
		$this->assertObjectNotHasProperty( 'mai_cache_key_record', $answered );

		$counts                       = $this->query( [ 'no_found_rows' => false ] );
		$counts->mai_cache_key_record = $record;

		$this->assertNull( ( new Mai_Query_Cache() )->pre_query_kept( null, $counts ) );
		$this->assertObjectNotHasProperty( 'mai_cache_key_record', $counts );
	}

	public function test_pre_query_kept_ignores_queries_without_the_marker(): void {
		$query = (object) [ 'query_vars' => [], 'request' => self::SQL ];

		$this->assertNull( ( new Mai_Query_Cache() )->pre_query_kept( null, $query ) );
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

	public function test_keep_records_the_flags_it_primed_with(): void {
		// Mai_Grid compares these with the flags the grid asked for before priming again.
		$primed = [];
		$this->stub_posts( [ 1 => 'publish', 2 => 'publish' ], $primed );

		$query = (object) [ 'query_vars' => [ 'post_status' => 'publish', 'update_post_term_cache' => true, 'update_post_meta_cache' => false ] ];

		$this->call( 'keep', $query, [ 'exclude' => [], 'count' => 2 ], [ 1, 2 ] );

		$this->assertSame( [ [ [ 1, 2 ], true, false ] ], $primed );
		$this->assertSame( [ 'update_post_term_cache' => true, 'update_post_meta_cache' => false ], $query->mai_grid_kept_primed );
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
