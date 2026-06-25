<?php
// tests/phpunit/unit/PostGridQueryOptimizerWhereTest.php
namespace BizBudding\MaiEngine\Tests\Unit;

use BizBudding\MaiEngine\Tests\TestCase;
use Mai_Post_Grid_Query_Optimizer;

final class PostGridQueryOptimizerWhereTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		global $wpdb;
		$wpdb = new class {
			public string $posts = 'wp_posts';
			public string $term_relationships = 'wp_term_relationships';
		};
	}

	private function query_with( $tt_ids ) {
		return new class( $tt_ids ) {
			public array $query_vars;
			public function __construct( $tt_ids ) {
				$this->query_vars = null === $tt_ids ? [] : [ 'mai_post_grid_tt_ids' => $tt_ids ];
			}
		};
	}

	public function test_appends_subquery_for_flagged_query(): void {
		$out = ( new Mai_Post_Grid_Query_Optimizer() )->add_subquery_where( ' AND 1=1', $this->query_with( [ 110, 111 ] ) );
		$this->assertStringContainsString( 'SELECT mtr.object_id FROM wp_term_relationships mtr', $out );
		$this->assertStringContainsString( 'mtr.object_id = wp_posts.ID', $out );
		$this->assertStringContainsString( 'mtr.term_taxonomy_id IN (110,111)', $out );
		$this->assertStringContainsString( 'LIMIT 1) IS NOT NULL', $out );
	}

	public function test_untouched_when_not_flagged(): void {
		$out = ( new Mai_Post_Grid_Query_Optimizer() )->add_subquery_where( ' AND 1=1', $this->query_with( null ) );
		$this->assertSame( ' AND 1=1', $out );
	}
}
