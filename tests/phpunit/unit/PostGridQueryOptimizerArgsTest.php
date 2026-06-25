<?php
// tests/phpunit/unit/PostGridQueryOptimizerArgsTest.php
namespace BizBudding\MaiEngine\Tests\Unit;

use Brain\Monkey\Functions;
use BizBudding\MaiEngine\Tests\TestCase;
use Mai_Post_Grid_Query_Optimizer;

final class PostGridQueryOptimizerArgsTest extends TestCase {
	public function test_resolves_terms_with_children_to_tt_ids(): void {
		Functions\when( 'is_taxonomy_hierarchical' )->justReturn( true );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'get_term_children' )->alias( fn( $id ) => 10 === $id ? [ 11 ] : [] );
		Functions\when( 'get_term' )->alias(
			fn( $id ) => (object) [ 'term_taxonomy_id' => [ 10 => 110, 11 => 111 ][ $id ] ?? 0 ]
		);

		$o   = new Mai_Post_Grid_Query_Optimizer();
		$ref = new \ReflectionMethod( $o, 'resolve_tt_ids' );

		$this->assertSame( [ 110, 111 ], $ref->invoke( $o, 'category', [ 10 ] ) );
	}

	public function test_strips_tax_query_and_stashes_ids_and_sets_no_found_rows(): void {
		Functions\when( 'is_taxonomy_hierarchical' )->justReturn( false );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'get_term' )->justReturn( (object) [ 'term_taxonomy_id' => 200 ] );
		Functions\when( 'apply_filters' )->alias( fn( $tag, $value ) => $value ); // found_rows stays false

		$args_in = [
			'post_type' => 'post',
			'tax_query' => [ [ 'taxonomy' => 'category', 'field' => 'id', 'terms' => [ 5 ], 'operator' => 'IN' ] ],
		];

		$out = ( new Mai_Post_Grid_Query_Optimizer() )->maybe_optimize( $args_in, [] );

		$this->assertArrayNotHasKey( 'tax_query', $out );
		$this->assertSame( [ 200 ], $out['mai_post_grid_tt_ids'] );
		$this->assertTrue( $out['no_found_rows'] );
	}

	public function test_keeps_found_rows_when_opted_in(): void {
		Functions\when( 'is_taxonomy_hierarchical' )->justReturn( false );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'get_term' )->justReturn( (object) [ 'term_taxonomy_id' => 1 ] );
		Functions\when( 'apply_filters' )->alias(
			fn( $tag, $value ) => 'mai_post_grid_found_rows' === $tag ? true : $value
		);

		$args_in = [ 'tax_query' => [ [ 'taxonomy' => 'category', 'terms' => [ 5 ], 'operator' => 'IN' ] ] ];

		$out = ( new Mai_Post_Grid_Query_Optimizer() )->maybe_optimize( $args_in, [] );

		$this->assertArrayNotHasKey( 'no_found_rows', $out );
		$this->assertSame( [ 1 ], $out['mai_post_grid_tt_ids'] );
	}

	public function test_non_tax_grid_gets_no_found_rows_but_no_rewrite(): void {
		Functions\when( 'apply_filters' )->alias( fn( $tag, $value ) => $value ); // found_rows stays false

		$args_in = [ 'post_type' => 'post', 'post__not_in' => [ 5, 6 ] ];

		$out = ( new Mai_Post_Grid_Query_Optimizer() )->maybe_optimize( $args_in, [] );

		$this->assertTrue( $out['no_found_rows'] );                   // broad no_found_rows applies
		$this->assertArrayNotHasKey( 'mai_post_grid_tt_ids', $out );  // but no tax rewrite
		$this->assertSame( [ 5, 6 ], $out['post__not_in'] );          // grid otherwise untouched
	}
}
