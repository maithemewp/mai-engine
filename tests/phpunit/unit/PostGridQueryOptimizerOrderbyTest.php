<?php
// tests/phpunit/unit/PostGridQueryOptimizerOrderbyTest.php
namespace BizBudding\MaiEngine\Tests\Unit;

use BizBudding\MaiEngine\Tests\TestCase;
use Mai_Post_Grid_Query_Optimizer;

final class PostGridQueryOptimizerOrderbyTest extends TestCase {
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

	public function test_appends_id_desc_for_date_desc(): void {
		$out = ( new Mai_Post_Grid_Query_Optimizer() )->add_orderby_tiebreaker( 'wp_posts.post_date DESC', $this->query_with( [ 1 ] ) );
		$this->assertSame( 'wp_posts.post_date DESC, wp_posts.ID DESC', $out );
	}

	public function test_matches_asc_direction(): void {
		$out = ( new Mai_Post_Grid_Query_Optimizer() )->add_orderby_tiebreaker( 'wp_posts.post_date ASC', $this->query_with( [ 1 ] ) );
		$this->assertSame( 'wp_posts.post_date ASC, wp_posts.ID ASC', $out );
	}

	public function test_noop_when_not_flagged(): void {
		$out = ( new Mai_Post_Grid_Query_Optimizer() )->add_orderby_tiebreaker( 'wp_posts.post_date DESC', $this->query_with( null ) );
		$this->assertSame( 'wp_posts.post_date DESC', $out );
	}

	public function test_noop_when_not_ordering_by_date(): void {
		$out = ( new Mai_Post_Grid_Query_Optimizer() )->add_orderby_tiebreaker( 'wp_posts.menu_order ASC', $this->query_with( [ 1 ] ) );
		$this->assertSame( 'wp_posts.menu_order ASC', $out );
	}
}
