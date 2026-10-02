<?php
namespace BizBudding\MaiEngine\Tests\Unit;

use Brain\Monkey\Functions;
use BizBudding\MaiEngine\Tests\TestCase;
use Mai_Query_Cache;

final class MaiQueryCacheHydrateTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		Functions\when( '_prime_post_caches' )->justReturn( null );
	}

	/**
	 * Stub get_post to resolve a fixed id => status map into post-like objects.
	 *
	 * @param array<int,string> $map id => post_status ( ids absent from the map resolve to null ).
	 */
	private function stub_posts( array $map ): void {
		Functions\when( 'get_post' )->alias(
			function ( $id ) use ( $map ) {
				return isset( $map[ $id ] ) ? (object) [ 'ID' => $id, 'post_status' => $map[ $id ] ] : null;
			}
		);
	}

	private function ids( array $posts ): array {
		return array_map( static fn( $p ) => $p->ID, $posts );
	}

	public function test_drops_posts_whose_status_left_the_allowed_set(): void {
		$this->stub_posts( [ 1 => 'publish', 2 => 'draft', 3 => 'publish' ] );
		$posts = ( new Mai_Query_Cache() )->hydrate( [ 1, 2, 3 ], [ 'post_status' => 'publish' ] );
		$this->assertSame( [ 1, 3 ], $this->ids( $posts ) );
	}

	public function test_preserves_stored_order(): void {
		$this->stub_posts( [ 1 => 'publish', 2 => 'publish', 3 => 'publish' ] );
		$posts = ( new Mai_Query_Cache() )->hydrate( [ 3, 1, 2 ], [ 'post_status' => 'publish' ] );
		$this->assertSame( [ 3, 1, 2 ], $this->ids( $posts ) );
	}

	public function test_editor_status_set_keeps_private_drops_draft(): void {
		$this->stub_posts( [ 1 => 'publish', 2 => 'private', 3 => 'draft' ] );
		$posts = ( new Mai_Query_Cache() )->hydrate( [ 1, 2, 3 ], [ 'post_status' => [ 'publish', 'private' ] ] );
		$this->assertSame( [ 1, 2 ], $this->ids( $posts ) );
	}

	public function test_no_status_filter_when_post_status_unset(): void {
		$this->stub_posts( [ 1 => 'publish', 2 => 'draft' ] );
		$posts = ( new Mai_Query_Cache() )->hydrate( [ 1, 2 ], [] );
		$this->assertSame( [ 1, 2 ], $this->ids( $posts ) );
	}

	public function test_any_status_skips_filter(): void {
		$this->stub_posts( [ 1 => 'publish', 2 => 'draft' ] );
		$posts = ( new Mai_Query_Cache() )->hydrate( [ 1, 2 ], [ 'post_status' => 'any' ] );
		$this->assertSame( [ 1, 2 ], $this->ids( $posts ) );
	}

	public function test_all_status_skips_filter(): void {
		$this->stub_posts( [ 1 => 'publish', 2 => 'draft' ] );
		$posts = ( new Mai_Query_Cache() )->hydrate( [ 1, 2 ], [ 'post_status' => 'all' ] );
		$this->assertSame( [ 1, 2 ], $this->ids( $posts ) );
	}

	public function test_drops_hard_deleted_ids(): void {
		$this->stub_posts( [ 1 => 'publish' ] ); // id 2 deleted -> get_post null
		$posts = ( new Mai_Query_Cache() )->hydrate( [ 1, 2 ], [ 'post_status' => 'publish' ] );
		$this->assertSame( [ 1 ], $this->ids( $posts ) );
	}

	public function test_primes_meta_and_terms_as_the_query_asks(): void {
		$calls = [];
		Functions\when( '_prime_post_caches' )->alias(
			function ( $ids, $terms, $meta ) use ( &$calls ) {
				$calls[] = [ $ids, $terms, $meta ];
			}
		);
		$this->stub_posts( [ 1 => 'publish' ] );

		$cache = new Mai_Query_Cache();
		$cache->hydrate( [ 1 ], [ 'update_post_term_cache' => false, 'update_post_meta_cache' => true ] );
		$cache->hydrate( [ 1 ], [ 'update_post_term_cache' => true, 'update_post_meta_cache' => false ] );
		$cache->hydrate( [ 1 ], [] );

		// _prime_post_caches() takes the term flag before the meta flag.
		$this->assertSame(
			[
				[ [ 1 ], false, true ],
				[ [ 1 ], true, false ],
				[ [ 1 ], true, true ],
			],
			$calls
		);
	}

	/**
	 * set_post_type() only cleans the post cache, it fires no status transition, so a cached
	 * list can still hold a post that has since become a page.
	 */
	public function test_hydrate_drops_wrong_post_type(): void {
		Functions\when( 'get_post' )->alias(
			function ( $id ) {
				$types = [ 1 => 'post', 2 => 'page', 3 => 'post' ];

				return (object) [ 'ID' => $id, 'post_status' => 'publish', 'post_type' => $types[ $id ] ];
			}
		);

		$cache = new Mai_Query_Cache();

		$this->assertSame( [ 1, 3 ], $this->ids( $cache->hydrate( [ 1, 2, 3 ], [ 'post_type' => 'post' ] ) ), 'a string post_type' );
		$this->assertSame( [ 1, 3 ], $this->ids( $cache->hydrate( [ 1, 2, 3 ], [ 'post_type' => [ 'post' ] ] ) ), 'an array post_type' );
		$this->assertSame( [ 1, 2, 3 ], $this->ids( $cache->hydrate( [ 1, 2, 3 ], [ 'post_type' => [ 'post', 'page' ] ] ) ), 'both types asked for' );
		$this->assertSame( [ 1, 2, 3 ], $this->ids( $cache->hydrate( [ 1, 2, 3 ], [ 'post_type' => 'any' ] ) ), "'any' keeps every type" );
		$this->assertSame( [ 1, 2, 3 ], $this->ids( $cache->hydrate( [ 1, 2, 3 ], [ 'post_type' => '' ] ) ), 'an empty post_type keeps every type' );
		$this->assertSame( [ 1, 2, 3 ], $this->ids( $cache->hydrate( [ 1, 2, 3 ], [] ) ), 'no post_type keeps every type' );
	}

	public function test_empty_ids_returns_empty(): void {
		$this->assertSame( [], ( new Mai_Query_Cache() )->hydrate( [], [ 'post_status' => 'publish' ] ) );
	}
}
