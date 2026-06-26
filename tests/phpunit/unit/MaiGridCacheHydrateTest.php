<?php
namespace BizBudding\MaiEngine\Tests\Unit;

use Brain\Monkey\Functions;
use BizBudding\MaiEngine\Tests\TestCase;
use Mai_Grid_Cache;

final class MaiGridCacheHydrateTest extends TestCase {
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
		$posts = ( new Mai_Grid_Cache() )->hydrate( [ 1, 2, 3 ], [ 'post_status' => 'publish' ] );
		$this->assertSame( [ 1, 3 ], $this->ids( $posts ) );
	}

	public function test_preserves_stored_order(): void {
		$this->stub_posts( [ 1 => 'publish', 2 => 'publish', 3 => 'publish' ] );
		$posts = ( new Mai_Grid_Cache() )->hydrate( [ 3, 1, 2 ], [ 'post_status' => 'publish' ] );
		$this->assertSame( [ 3, 1, 2 ], $this->ids( $posts ) );
	}

	public function test_editor_status_set_keeps_private_drops_draft(): void {
		$this->stub_posts( [ 1 => 'publish', 2 => 'private', 3 => 'draft' ] );
		$posts = ( new Mai_Grid_Cache() )->hydrate( [ 1, 2, 3 ], [ 'post_status' => [ 'publish', 'private' ] ] );
		$this->assertSame( [ 1, 2 ], $this->ids( $posts ) );
	}

	public function test_no_status_filter_when_post_status_unset(): void {
		$this->stub_posts( [ 1 => 'publish', 2 => 'draft' ] );
		$posts = ( new Mai_Grid_Cache() )->hydrate( [ 1, 2 ], [] );
		$this->assertSame( [ 1, 2 ], $this->ids( $posts ) );
	}

	public function test_any_status_skips_filter(): void {
		$this->stub_posts( [ 1 => 'publish', 2 => 'draft' ] );
		$posts = ( new Mai_Grid_Cache() )->hydrate( [ 1, 2 ], [ 'post_status' => 'any' ] );
		$this->assertSame( [ 1, 2 ], $this->ids( $posts ) );
	}

	public function test_drops_hard_deleted_ids(): void {
		$this->stub_posts( [ 1 => 'publish' ] ); // id 2 deleted -> get_post null
		$posts = ( new Mai_Grid_Cache() )->hydrate( [ 1, 2 ], [ 'post_status' => 'publish' ] );
		$this->assertSame( [ 1 ], $this->ids( $posts ) );
	}

	public function test_empty_ids_returns_empty(): void {
		$this->assertSame( [], ( new Mai_Grid_Cache() )->hydrate( [], [ 'post_status' => 'publish' ] ) );
	}
}
