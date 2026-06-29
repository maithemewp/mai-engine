<?php
namespace BizBudding\MaiEngine\Tests\Unit;

use Brain\Monkey\Functions;
use BizBudding\MaiEngine\Tests\TestCase;
use Mai_Query_Cache;

final class MaiQueryCacheInvalidationTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'apply_filters' )->alias( fn( $tag, $value ) => $value );
	}

	/** A spy standing in for the mai_cache() instance. */
	private function spy(): object {
		return new class {
			public array $bumped  = [];
			public array $written = [];
			public int $reads     = 0;
			public function bump( $scope ) { $this->bumped[] = $scope; return true; }
			public function write_swr( $key, $value, $version, $ttl ) { $this->written[] = compact( 'key', 'value', 'version', 'ttl' ); return true; }
			public function read_swr( $key, $version ) { $this->reads++; return null; }
			public function version( $scopes ) { return 'v'; }
			public function lock( $key, $ttl = 30 ) { return true; }
		};
	}

	private function call( object $obj, string $method, ...$args ) {
		return ( new \ReflectionMethod( $obj, $method ) )->invoke( $obj, ...$args );
	}

	// ---- on_transition: rotate only on publish-affecting, non-revision changes ----

	private function transition( object $cache, string $new, string $old, array $post, bool $isRevision = false ): void {
		Functions\when( 'mai_cache' )->justReturn( $cache );
		Functions\when( 'wp_is_post_revision' )->justReturn( $isRevision );
		( new Mai_Query_Cache() )->on_transition( $new, $old, (object) $post );
	}

	public function test_transition_draft_to_draft_does_not_bump(): void {
		$c = $this->spy();
		$this->transition( $c, 'draft', 'draft', [ 'ID' => 1, 'post_type' => 'post' ] );
		$this->assertSame( [], $c->bumped );
	}

	public function test_transition_revision_does_not_bump_even_when_publish(): void {
		$c = $this->spy();
		$this->transition( $c, 'publish', 'publish', [ 'ID' => 1, 'post_type' => 'post' ], true );
		$this->assertSame( [], $c->bumped );
	}

	public function test_transition_revision_post_type_does_not_bump(): void {
		$c = $this->spy();
		$this->transition( $c, 'publish', 'publish', [ 'ID' => 1, 'post_type' => 'revision' ] );
		$this->assertSame( [], $c->bumped );
	}

	public function test_transition_draft_to_publish_bumps(): void {
		$c = $this->spy();
		$this->transition( $c, 'publish', 'draft', [ 'ID' => 1, 'post_type' => 'post' ] );
		$this->assertSame( [ 'post' ], $c->bumped );
	}

	public function test_transition_publish_to_publish_edit_bumps(): void {
		$c = $this->spy();
		$this->transition( $c, 'publish', 'publish', [ 'ID' => 1, 'post_type' => 'page' ] );
		$this->assertSame( [ 'page' ], $c->bumped );
	}

	public function test_transition_publish_to_trash_bumps(): void {
		$c = $this->spy();
		$this->transition( $c, 'trash', 'publish', [ 'ID' => 1, 'post_type' => 'post' ] );
		$this->assertSame( [ 'post' ], $c->bumped );
	}

	// ---- on_delete: rotate only on hard delete of a published, non-revision post ----

	private function delete( object $cache, $post ): void {
		Functions\when( 'mai_cache' )->justReturn( $cache );
		( new Mai_Query_Cache() )->on_delete( 1, $post );
	}

	public function test_delete_null_post_does_not_bump(): void {
		$c = $this->spy();
		$this->delete( $c, null );
		$this->assertSame( [], $c->bumped );
	}

	public function test_delete_draft_does_not_bump(): void {
		$c = $this->spy();
		$this->delete( $c, (object) [ 'post_status' => 'draft', 'post_type' => 'post' ] );
		$this->assertSame( [], $c->bumped );
	}

	public function test_delete_revision_does_not_bump(): void {
		$c = $this->spy();
		$this->delete( $c, (object) [ 'post_status' => 'publish', 'post_type' => 'revision' ] );
		$this->assertSame( [], $c->bumped );
	}

	public function test_delete_published_bumps(): void {
		$c = $this->spy();
		$this->delete( $c, (object) [ 'post_status' => 'publish', 'post_type' => 'post' ] );
		$this->assertSame( [ 'post' ], $c->bumped );
	}

	// ---- pre_query: stays out of the way for uncacheable / unflagged queries ----

	public function test_pre_query_passes_through_without_flag(): void {
		$c = $this->spy();
		Functions\when( 'mai_cache' )->justReturn( $c );
		$query = (object) [ 'query_vars' => [ 'post_type' => 'post' ] ]; // no mai_cache
		$posts = [ 'sentinel' ];
		$this->assertSame( $posts, ( new Mai_Query_Cache() )->pre_query( $posts, $query ) );
		$this->assertSame( 0, $c->reads );
	}

	public function test_pre_query_passes_through_when_not_cacheable(): void {
		$c = $this->spy();
		Functions\when( 'mai_cache' )->justReturn( $c );
		$query = (object) [ 'query_vars' => [ 'post_type' => 'post', 'mai_cache' => true, 'query_by' => 'id' ] ];
		$posts = [ 'sentinel' ];
		$this->assertSame( $posts, ( new Mai_Query_Cache() )->pre_query( $posts, $query ) );
		$this->assertSame( 0, $c->reads );
	}

	// ---- the_posts: no-op without a flagged miss, stores + clears keys with one ----

	public function test_the_posts_noop_without_store_key(): void {
		$c = $this->spy();
		Functions\when( 'mai_cache' )->justReturn( $c );
		$posts = [ 'a' ];
		$this->assertSame( $posts, ( new Mai_Query_Cache() )->the_posts( $posts, (object) [] ) );
		$this->assertSame( [], $c->written );
	}

	public function test_the_posts_stores_ids_and_found_then_clears_keys(): void {
		$c = $this->spy();
		Functions\when( 'mai_cache' )->justReturn( $c );
		Functions\when( 'wp_list_pluck' )->alias( fn( $posts, $f ) => array_map( fn( $p ) => $p->ID, $posts ) );
		$query = (object) [ 'mai_cache_store_key' => 'k', 'mai_cache_store_version' => 'v', 'query_vars' => [], 'found_posts' => 42 ];
		$posts = [ (object) [ 'ID' => 7 ], (object) [ 'ID' => 8 ] ];
		$this->assertSame( $posts, ( new Mai_Query_Cache() )->the_posts( $posts, $query ) );
		$this->assertCount( 1, $c->written );
		$this->assertSame( [ 'ids' => [ 7, 8 ], 'found' => 42 ], $c->written[0]['value'] );
		$this->assertFalse( isset( $query->mai_cache_store_key ) );
	}

	// ---- serve: malformed envelope -> null; found/ppp edge cases ----

	public function test_serve_returns_null_for_malformed_envelope(): void {
		$gc    = new Mai_Query_Cache();
		$query = (object) [ 'query_vars' => [] ];
		$this->assertNull( $this->call( $gc, 'serve', $query, 'not-an-array' ) );
		$this->assertNull( $this->call( $gc, 'serve', $query, [ 'found' => 5 ] ) );    // missing ids
		$this->assertNull( $this->call( $gc, 'serve', $query, [ 'ids' => 'nope' ] ) ); // ids not array
	}

	public function test_serve_found_falls_back_to_id_count(): void {
		Functions\when( '_prime_post_caches' )->justReturn( null );
		Functions\when( 'get_post' )->alias( fn( $id ) => (object) [ 'ID' => $id, 'post_status' => 'publish' ] );
		$query = (object) [ 'query_vars' => [ 'posts_per_page' => 10, 'post_status' => 'publish' ] ];
		$this->call( new Mai_Query_Cache(), 'serve', $query, [ 'ids' => [ 1, 2, 3 ] ] ); // no 'found'
		$this->assertSame( 3, $query->found_posts );
		$this->assertSame( 1, $query->max_num_pages );
	}

	public function test_serve_zero_posts_per_page_yields_one_page(): void {
		Functions\when( '_prime_post_caches' )->justReturn( null );
		Functions\when( 'get_post' )->alias( fn( $id ) => (object) [ 'ID' => $id, 'post_status' => 'publish' ] );
		$query = (object) [ 'query_vars' => [ 'posts_per_page' => 0, 'post_status' => 'publish' ] ];
		$this->call( new Mai_Query_Cache(), 'serve', $query, [ 'ids' => [ 1 ], 'found' => 50 ] );
		$this->assertSame( 1, $query->max_num_pages );
	}
}
