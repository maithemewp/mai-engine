<?php
// tests/phpunit/unit/PostGridQueryOptimizerClassifyTest.php
namespace BizBudding\MaiEngine\Tests\Unit;

use BizBudding\MaiEngine\Tests\TestCase;
use Mai_Post_Grid_Query_Optimizer;
use ReflectionMethod;

final class PostGridQueryOptimizerClassifyTest extends TestCase {
	private function classify( array $query_args ): ?array {
		$m = new ReflectionMethod( Mai_Post_Grid_Query_Optimizer::class, 'get_simple_in_clause' );
		return $m->invoke( new Mai_Post_Grid_Query_Optimizer(), $query_args );
	}

	public function test_single_in_clause_is_simple(): void {
		$clause = [ 'taxonomy' => 'category', 'field' => 'id', 'terms' => [ 1, 2 ], 'operator' => 'IN' ];
		$this->assertSame( $clause, $this->classify( [ 'tax_query' => [ $clause ] ] ) );
	}

	public function test_missing_operator_defaults_to_in(): void {
		$clause = [ 'taxonomy' => 'category', 'field' => 'id', 'terms' => [ 3 ] ];
		$this->assertNotNull( $this->classify( [ 'tax_query' => [ $clause ] ] ) );
	}

	public function test_not_in_is_not_simple(): void {
		$clause = [ 'taxonomy' => 'category', 'terms' => [ 1 ], 'operator' => 'NOT IN' ];
		$this->assertNull( $this->classify( [ 'tax_query' => [ $clause ] ] ) );
	}

	public function test_multiple_clauses_not_simple(): void {
		$tax = [
			'relation' => 'AND',
			[ 'taxonomy' => 'category', 'terms' => [ 1 ], 'operator' => 'IN' ],
			[ 'taxonomy' => 'post_tag', 'terms' => [ 2 ], 'operator' => 'IN' ],
		];
		$this->assertNull( $this->classify( [ 'tax_query' => $tax ] ) );
	}

	public function test_no_tax_query_not_simple(): void {
		$this->assertNull( $this->classify( [ 'post_type' => 'post' ] ) );
	}

	public function test_empty_terms_not_simple(): void {
		$clause = [ 'taxonomy' => 'category', 'terms' => [], 'operator' => 'IN' ];
		$this->assertNull( $this->classify( [ 'tax_query' => [ $clause ] ] ) );
	}
}
