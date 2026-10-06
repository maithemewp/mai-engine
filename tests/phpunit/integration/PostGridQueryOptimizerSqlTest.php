<?php
declare(strict_types=1);

namespace BizBudding\MaiEngine\Tests\Integration;

use Mai_Post_Grid_Query_Optimizer_Database;
use Mai_Post_Grid_Query_Optimizer_Sql;
use PHPUnit\Framework\Attributes\DataProvider;
use WP_Query;
use WP_Tax_Query;
use WP_Term;
use WP_UnitTest_Factory;

/**
 * Rebuilding WordPress's tax SQL with WordPress's own code, and the EXISTS form written from it.
 *
 * The rebuild must equal what core writes, character for character, or Mai steps aside. The
 * EXISTS form must return the same posts as today's statement, in the same order, with and
 * without a LIMIT. Every case also checks it differs from the same query with no tax filter,
 * and each AND or OR case from the same query with any one of its filters removed, so a swap
 * that dropped the filter, or one piece of it, cannot pass by luck.
 */
final class PostGridQueryOptimizerSqlTest extends MaiIntegrationTestCase {

	use PostGridQueryOptimizerFixture;

	private const HINT = '/*+ NO_SEMIJOIN(DUPSWEEDOUT) */ ';

	/**
	 * Stands for big's two newest posts in a provider row, which runs before the fixture exists.
	 */
	private const BIG_NEWEST_TWO = 'big newest two';

	/**
	 * The fixture's IDs, built once for the class.
	 *
	 * @var array
	 */
	private static array $fixture = [];

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ): void {
		self::$fixture = self::create_optimizer_fixture( $factory );
	}

	public static function wpTearDownAfterClass(): void {
		self::remove_optimizer_fixture();
	}

	/** Shapes Mai covers, and how many filters write a condition. */
	public static function covered(): array {
		return [
			'big'                        => [ 'big', 1 ],
			'tag'                        => [ 'tag', 1 ],
			'custom'                     => [ 'custom', 1 ],
			'big AND tag'                => [ 'big AND tag', 2 ],
			'three IN'                   => [ 'three IN', 3 ],
			'big OR tag'                 => [ 'big OR tag', 2 ],
			'OR, lowercase in then IN'   => [ 'OR, lowercase in then IN', 2 ],
			'IN AND NOT IN'              => [ 'IN AND NOT IN', 2 ],
			'IN AND NOT IN, all deleted' => [ 'IN AND NOT IN, all deleted', 1 ],
			'IN AND operator AND'        => [ 'IN AND operator AND', 2 ],
			'IN AND operator EXISTS'     => [ 'IN AND operator EXISTS', 2 ],
		];
	}

	/** Shapes Mai steps aside for. */
	public static function declined(): array {
		return self::cases(
			[
				'nested',
				'OR with NOT IN',
				'OR with lowercase in',
				'only NOT IN',
				'deleted term',
				'unregistered taxonomy',
				'empty tax query',
			]
		);
	}

	/** Shape, extra query args, the post count when it is asserted instead, shared date. */
	public static function same_posts_cases(): array {
		$cases = [];

		foreach ( self::covered() as $name => [ $shape ] ) {
			$cases[ $name ] = [ $shape, [], null, false ];
		}

		return $cases + [
			'29 terms'           => [ '29 terms', [], null, false ],
			'empty'              => [ 'empty', [], 0, false ],
			'offset 5'           => [ 'big', [ 'offset' => 5 ], null, false ],
			'posts_per_page 200' => [ 'big', [ 'posts_per_page' => 200 ], 40, false ],
			'one shared date'    => [ 'big', [], null, true ],
			'post__not_in'       => [ 'big', [ 'post__not_in' => self::BIG_NEWEST_TWO ], null, false ],
		];
	}

	public function test_fixture_is_skewed(): void {
		$term_ids = [];
		$tt_ids   = [];

		foreach ( [ 'big', 'child', 'small', 'tag', 'custom', 'empty' ] as $name ) {
			$term = get_term( self::$fixture[ $name ] );

			$this->assertInstanceOf( WP_Term::class, $term, $name );

			$term_ids[] = $term->term_id;
			$tt_ids[]   = $term->term_taxonomy_id;
		}

		$this->assertSame( [], array_values( array_intersect( $term_ids, $tt_ids ) ) );
	}

	#[DataProvider( 'covered' )]
	public function test_rebuild_matches_core( string $shape, int $pieces ): void {
		global $wpdb;

		$tax_query = new WP_Tax_Query( self::shape( $shape ) );
		$core      = $tax_query->get_sql( $wpdb->posts, 'ID' );

		// The object has written its SQL once already, as WP_Query's has by the time Mai rebuilds.
		$rebuilt = Mai_Post_Grid_Query_Optimizer_Sql::rebuild( $tax_query, $wpdb->posts );

		$this->assertNotNull( $rebuilt );
		$this->assertSame( $core['join'], $rebuilt['join'] );
		$this->assertSame( $core['where'], $rebuilt['where'] );
		$this->assertSame( $tax_query->relation, $rebuilt['relation'] );

		// condition() writes AND filters from the pieces, so they must be core's chunks exactly.
		$this->assertCount( $pieces, $rebuilt['pieces'] );
		$this->assertSame(
			$core['where'],
			' AND ( ' . "\n  " . implode( " \n  {$rebuilt['relation']} \n  ", array_column( $rebuilt['pieces'], 'where' ) ) . "\n)"
		);
	}

	#[DataProvider( 'declined' )]
	public function test_rebuild_declines( string $shape ): void {
		global $wpdb;

		$this->assertNull( Mai_Post_Grid_Query_Optimizer_Sql::rebuild( new WP_Tax_Query( self::shape( $shape ) ), $wpdb->posts ) );
	}

	public function test_condition(): void {
		global $wpdb;

		$p      = $wpdb->posts;
		$t      = $wpdb->term_relationships;
		$tag    = get_term( self::$fixture['tag'] )->term_taxonomy_id;
		$custom = get_term( self::$fixture['custom'] )->term_taxonomy_id;
		$hint   = self::HINT;

		$tag_clause    = [ 'taxonomy' => 'post_tag', 'terms' => [ self::$fixture['tag'] ] ];
		$custom_clause = [ 'taxonomy' => 'mai_test_tax', 'terms' => [ self::$fixture['custom'] ] ];
		$tag_exists    = "EXISTS ( SELECT 1 FROM {$t} WHERE {$t}.object_id = {$p}.ID AND {$t}.term_taxonomy_id IN ({$tag}) )";

		// One filter, without and with the hint.
		$one = $this->rebuild( [ $tag_clause ] );

		$this->assertSame( " AND {$tag_exists}", Mai_Post_Grid_Query_Optimizer_Sql::condition( $one, $p, $t, false ) );
		$this->assertSame(
			" AND EXISTS ( SELECT {$hint}1 FROM {$t} WHERE {$t}.object_id = {$p}.ID AND {$t}.term_taxonomy_id IN ({$tag}) )",
			Mai_Post_Grid_Query_Optimizer_Sql::condition( $one, $p, $t, true )
		);

		// AND with two IN filters: each its own EXISTS, on the table name WordPress gave it.
		$two = $this->rebuild( [ 'relation' => 'AND', $tag_clause, $custom_clause ] );

		$this->assertSame( [ $t, 'tt1' ], array_column( $two['pieces'], 'alias' ) );
		$this->assertSame(
			" AND {$tag_exists} AND EXISTS ( SELECT 1 FROM {$t} AS tt1 WHERE tt1.object_id = {$p}.ID AND tt1.term_taxonomy_id IN ({$custom}) )",
			Mai_Post_Grid_Query_Optimizer_Sql::condition( $two, $p, $t, false )
		);
		$this->assertSame(
			" AND EXISTS ( SELECT {$hint}1 FROM {$t} WHERE {$t}.object_id = {$p}.ID AND {$t}.term_taxonomy_id IN ({$tag}) ) AND EXISTS ( SELECT {$hint}1 FROM {$t} AS tt1 WHERE tt1.object_id = {$p}.ID AND tt1.term_taxonomy_id IN ({$custom}) )",
			Mai_Post_Grid_Query_Optimizer_Sql::condition( $two, $p, $t, true )
		);

		// AND with IN and NOT IN: the NOT IN text is core's, unchanged.
		$not_in_clause = $custom_clause + [ 'operator' => 'NOT IN' ];
		$core_not_in   = ( new WP_Tax_Query( [ $not_in_clause ] ) )->get_sql( $p, 'ID' )['where'];
		$not_in_text   = substr( $core_not_in, 10, -2 );
		$not_in        = $this->rebuild( [ 'relation' => 'AND', $tag_clause, $not_in_clause ] );

		$this->assertStringStartsWith( " AND ( \n  {$p}.ID NOT IN (", $core_not_in );
		$this->assertSame( [ 'where' => $not_in_text, 'alias' => null ], $not_in['pieces'][1] );
		$this->assertSame( " AND {$tag_exists} AND {$not_in_text}", Mai_Post_Grid_Query_Optimizer_Sql::condition( $not_in, $p, $t, false ) );

		// OR: WordPress's whole condition, brackets included, inside one EXISTS.
		$or = $this->rebuild( [ 'relation' => 'OR', $tag_clause, $custom_clause ] );

		$this->assertSame(
			" AND EXISTS ( SELECT 1 FROM {$t} WHERE {$t}.object_id = {$p}.ID AND ( \n  {$t}.term_taxonomy_id IN ({$tag}) \n  OR \n  {$t}.term_taxonomy_id IN ({$custom})\n) )",
			Mai_Post_Grid_Query_Optimizer_Sql::condition( $or, $p, $t, false )
		);
		$this->assertSame(
			" AND EXISTS ( SELECT {$hint}1 FROM {$t} WHERE {$t}.object_id = {$p}.ID AND ( \n  {$t}.term_taxonomy_id IN ({$tag}) \n  OR \n  {$t}.term_taxonomy_id IN ({$custom})\n) )",
			Mai_Post_Grid_Query_Optimizer_Sql::condition( $or, $p, $t, true )
		);
	}

	public function test_condition_refuses_an_unknown_relation(): void {
		global $wpdb;

		$rebuilt = $this->rebuild( [ [ 'taxonomy' => 'post_tag', 'terms' => [ self::$fixture['tag'] ] ] ] );

		foreach ( [ 'XOR', 'and', '' ] as $relation ) {
			$this->assertSame( '', Mai_Post_Grid_Query_Optimizer_Sql::condition( [ 'relation' => $relation ] + $rebuilt, $wpdb->posts, $wpdb->term_relationships, false ), $relation );
		}
	}

	public function test_swap(): void {
		global $wpdb;

		$p         = $wpdb->posts;
		$t         = $wpdb->term_relationships;
		$join      = " LEFT JOIN {$t} ON ({$p}.ID = {$t}.object_id)";
		$where     = " AND ( \n  {$t}.term_taxonomy_id IN (5)\n)";
		$rebuilt   = [
			'join'     => $join,
			'where'    => $where,
			'relation' => 'AND',
			'pieces'   => [
				[
					'where' => "{$t}.term_taxonomy_id IN (5)",
					'alias' => $t,
				],
			],
		];
		$condition = ' AND EXISTS ( SELECT 1 )';
		$statement = static fn( string $join, string $where, string $groupby ): string => "SELECT   {$p}.ID\n FROM {$p} {$join}\n WHERE 1=1 {$where} AND {$p}.post_type = 'post'\n {$groupby}\n ORDER BY {$p}.post_date DESC, {$p}.ID DESC\n LIMIT 0, 10";

		$this->assertSame(
			$statement( '', $condition, '' ),
			Mai_Post_Grid_Query_Optimizer_Sql::swap( $statement( $join, $where, "GROUP BY {$p}.ID" ), $rebuilt, $condition, $p )
		);

		$declined = [
			'join twice'      => [ $statement( $join . $join, $where, "GROUP BY {$p}.ID" ), $rebuilt, $condition ],
			'where twice'     => [ $statement( $join, $where . $where, "GROUP BY {$p}.ID" ), $rebuilt, $condition ],
			'no join'         => [ $statement( '', $where, "GROUP BY {$p}.ID" ), $rebuilt, $condition ],
			'no where'        => [ $statement( $join, '', "GROUP BY {$p}.ID" ), $rebuilt, $condition ],
			'no GROUP BY'     => [ $statement( $join, $where, '' ), $rebuilt, $condition ],
			'GROUP BY twice'  => [ $statement( $join, $where, "GROUP BY {$p}.ID GROUP BY {$p}.ID" ), $rebuilt, $condition ],
			'empty join'      => [ $statement( $join, $where, "GROUP BY {$p}.ID" ), [ 'join' => '' ] + $rebuilt, $condition ],
			'empty where'     => [ $statement( $join, $where, "GROUP BY {$p}.ID" ), [ 'where' => '' ] + $rebuilt, $condition ],
			'empty condition' => [ $statement( $join, $where, "GROUP BY {$p}.ID" ), $rebuilt, '' ],
		];

		// An empty needle would throw a ValueError from substr_count() rather than return null.
		foreach ( $declined as $name => [ $sql, $rebuilt_case, $condition_case ] ) {
			$this->assertNull( Mai_Post_Grid_Query_Optimizer_Sql::swap( $sql, $rebuilt_case, $condition_case, $p ), $name );
		}
	}

	public function test_split(): void {
		global $wpdb;

		$p = $wpdb->posts;

		$this->assertSame( "SELECT   {$p}.ID FROM {$p} WHERE 1=1", Mai_Post_Grid_Query_Optimizer_Sql::split( "SELECT   {$p}.* FROM {$p} WHERE 1=1", $p ) );

		$declined = [
			'one space'        => "SELECT {$p}.* FROM {$p} WHERE 1=1",
			'found rows'       => "SELECT SQL_CALC_FOUND_ROWS  {$p}.* FROM {$p} WHERE 1=1",
			'distinct'         => "SELECT  DISTINCT {$p}.* FROM {$p} WHERE 1=1",
			'IDs already'      => "SELECT   {$p}.ID FROM {$p} WHERE 1=1",
			'leading new line' => "\nSELECT   {$p}.* FROM {$p} WHERE 1=1",
			'other table'      => "SELECT   other_posts.* FROM other_posts WHERE 1=1",
			'empty'            => '',
		];

		foreach ( $declined as $name => $sql ) {
			$this->assertNull( Mai_Post_Grid_Query_Optimizer_Sql::split( $sql, $p ), $name );
		}

		// The split form equals the IDs statement core writes when it splits the query itself.
		$full = null;
		$ids  = null;

		add_filter( 'split_the_query', '__return_true' );
		add_filter(
			'posts_request',
			static function ( $sql ) use ( &$full ) {
				$full = $sql;

				return $sql;
			},
			PHP_INT_MAX
		);
		add_filter(
			'posts_request_ids',
			static function ( $sql ) use ( &$ids ) {
				$ids = $sql;

				return $sql;
			},
			PHP_INT_MAX
		);

		new WP_Query(
			[
				'post_type'      => 'post',
				'tax_query'      => self::shape( 'big' ),
				'posts_per_page' => 10,
				'no_found_rows'  => true,
				'cache_results'  => false,
			]
		);

		$this->assertIsString( $full );
		$this->assertIsString( $ids );
		$this->assertSame( $ids, Mai_Post_Grid_Query_Optimizer_Sql::split( $full, $p ) );
	}

	public function test_orderby_ok(): void {
		global $wpdb;

		$p = $wpdb->posts;
		$t = $wpdb->term_relationships;

		$allowed = [
			"{$p}.post_date DESC, {$p}.ID DESC",
			"{$p}.post_date ASC, {$p}.ID DESC",
			"{$p}.post_date ASC, {$p}.ID ASC",
			"{$p}.post_author DESC, {$p}.ID DESC",
			"{$p}.post_author ASC, {$p}.ID ASC",
			"{$p}.ID DESC",
			"{$p}.ID ASC",
		];

		foreach ( $allowed as $orderby ) {
			$this->assertTrue( Mai_Post_Grid_Query_Optimizer_Sql::orderby_ok( $orderby, $p ), $orderby );
		}

		$refused = [
			"{$p}.post_title ASC, {$p}.ID DESC",
			"{$p}.menu_order ASC, {$p}.post_date DESC, {$p}.ID DESC",
			"{$p}.post_modified DESC, {$p}.ID DESC",
			"{$p}.comment_count DESC, {$p}.ID DESC",
			"{$p}.post_name ASC, {$p}.ID DESC",
			"{$p}.post_parent ASC, {$p}.ID DESC",
			"{$p}.post_type ASC, {$p}.ID DESC",
			"RAND(7), {$p}.ID DESC",
			"RAND(7), {$p}.post_date DESC, {$p}.ID DESC",
			"{$p}.post_date DESC",
			"{$t}.term_order ASC, {$p}.ID DESC",
			"tt1.term_order ASC, {$p}.ID DESC",
			"{$p}.post_date DESC, {$p}.ID DESC, tt1.term_order ASC",
			"{$p}.post_content DESC, {$p}.ID DESC",
			"{$p}.ID",
			"{$p}.ID DESC\n",
			"other_posts.post_date DESC, other_posts.ID DESC",
			"{$p}.post_date_gmt ASC, {$p}.post_date DESC, {$p}.ID DESC",
			"{$p}.post_date asc, {$p}.ID ASC",
			'',
		];

		foreach ( $refused as $orderby ) {
			$this->assertFalse( Mai_Post_Grid_Query_Optimizer_Sql::orderby_ok( $orderby, $p ), $orderby );
		}
	}

	#[DataProvider( 'same_posts_cases' )]
	public function test_same_posts( string $shape, array $args, ?int $count, bool $shared_date ): void {
		global $wpdb;

		if ( $shared_date ) {
			$this->share_one_date();
		}

		$excluded = [];

		if ( self::BIG_NEWEST_TWO === ( $args['post__not_in'] ?? null ) ) {
			$excluded             = array_slice( self::$fixture['posts']['big'], 0, 2 );
			$args['post__not_in'] = $excluded;
		}

		$args      = array_merge( self::query_args(), $args );
		$tax_query = self::shape( $shape );
		$limited   = new WP_Query( [ 'tax_query' => $tax_query ] + $args );
		$unlimited = new WP_Query( [ 'tax_query' => $tax_query, 'nopaging' => true ] + $args );
		$untaxed   = $this->ids( ( new WP_Query( $args ) )->request );

		$this->assertStringContainsString( 'LIMIT', $limited->request );
		$this->assertStringNotContainsString( 'LIMIT', $unlimited->request );

		$rebuilt = Mai_Post_Grid_Query_Optimizer_Sql::rebuild( $limited->tax_query, $wpdb->posts );

		$this->assertNotNull( $rebuilt );

		$condition = Mai_Post_Grid_Query_Optimizer_Sql::condition( $rebuilt, $wpdb->posts, $wpdb->term_relationships, $this->hint() );
		$today     = [];

		foreach ( [ 'with LIMIT' => $limited, 'without LIMIT' => $unlimited ] as $form => $query ) {
			$swapped = Mai_Post_Grid_Query_Optimizer_Sql::swap( $query->request, $rebuilt, $condition, $wpdb->posts );

			$this->assertNotNull( $swapped, $form );
			$this->assertStringContainsString( 'EXISTS ( SELECT ', $swapped, $form );
			$this->assertStringNotContainsString( 'GROUP BY', $swapped, $form );

			$today[ $form ] = $this->ids( $query->request );

			$this->assertSame( $today[ $form ], $this->ids( $swapped ), $form );
		}

		if ( $excluded ) {
			$this->assertStringContainsString( "{$wpdb->posts}.ID NOT IN (" . implode( ',', $excluded ) . ')', (string) $swapped, 'the excluded posts are in the swapped statement' );
			$this->assertSame( [], array_values( array_intersect( $excluded, $today['without LIMIT'] ) ) );
			$this->assertSame( array_slice( self::$fixture['posts']['big'], 2, 10 ), $today['with LIMIT'] );
		}

		if ( null === $count ) {
			$this->assertNotEmpty( $today['with LIMIT'] );
		} else {
			$this->assertCount( $count, $today['with LIMIT'] );
			$this->assertCount( $count, $today['without LIMIT'] );
		}

		$this->assertNotEmpty( $untaxed );
		$this->assertNotSame( $untaxed, $today['with LIMIT'] );

		// With several filters, removing any one that writes a condition changes the posts, so a
		// swap that lost that filter's piece would fail above.
		$filters = array_filter( $tax_query, 'is_int', ARRAY_FILTER_USE_KEY );

		if ( count( $filters ) < 2 ) {
			return;
		}

		$checked = 0;

		foreach ( $filters as $key => $filter ) {
			if ( '' === ( new WP_Tax_Query( [ $filter ] ) )->get_sql( $wpdb->posts, 'ID' )['where'] ) {
				continue;
			}

			$without = $tax_query;

			unset( $without[ $key ] );

			$this->assertNotSame( $this->ids( ( new WP_Query( [ 'tax_query' => $without ] + $args ) )->request ), $today['with LIMIT'], "without filter {$key}" );

			++$checked;
		}

		$this->assertSame( count( $rebuilt['pieces'] ), $checked );
	}

	public function test_or_without_brackets_would_be_wrong(): void {
		global $wpdb;

		$query   = new WP_Query( [ 'tax_query' => self::shape( 'big OR tag' ) ] + self::query_args() );
		$rebuilt = Mai_Post_Grid_Query_Optimizer_Sql::rebuild( $query->tax_query, $wpdb->posts );

		$this->assertNotNull( $rebuilt );
		$this->assertStringStartsWith( " AND ( \n", $rebuilt['where'] );
		$this->assertStringEndsWith( "\n)", $rebuilt['where'] );

		$stripped = [ 'where' => ' AND ' . substr( $rebuilt['where'], 7, -1 ) ] + $rebuilt;
		$hint     = $this->hint();
		$good     = Mai_Post_Grid_Query_Optimizer_Sql::swap( $query->request, $rebuilt, Mai_Post_Grid_Query_Optimizer_Sql::condition( $rebuilt, $wpdb->posts, $wpdb->term_relationships, $hint ), $wpdb->posts );
		$bad      = Mai_Post_Grid_Query_Optimizer_Sql::swap( $query->request, $rebuilt, Mai_Post_Grid_Query_Optimizer_Sql::condition( $stripped, $wpdb->posts, $wpdb->term_relationships, $hint ), $wpdb->posts );
		$today    = $this->ids( $query->request );

		$this->assertNotNull( $good );
		$this->assertNotNull( $bad );
		$this->assertNotEmpty( $today );
		$this->assertSame( $today, $this->ids( $good ) );
		$this->assertNotSame( $today, $this->ids( $bad ) );
	}

	/**
	 * Today's statement settings for test_same_posts(), as a grid's ID-only copy sends them.
	 *
	 * @return array
	 */
	private static function query_args(): array {
		return [
			'post_type'      => 'post',
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'posts_per_page' => 10,
			'orderby'        => [
				'date' => 'DESC',
				'ID'   => 'DESC',
			],
			'cache_results'  => false,
		];
	}

	/**
	 * A tax query by name, with the fixture's IDs. Some shapes make terms, rolled back with the test.
	 *
	 * @param string $name The shape.
	 *
	 * @return array
	 */
	private static function shape( string $name ): array {
		$f      = self::$fixture;
		$big    = [ 'taxonomy' => 'category', 'terms' => [ $f['big'] ] ];
		$tag    = [ 'taxonomy' => 'post_tag', 'terms' => [ $f['tag'] ] ];
		$custom = [ 'taxonomy' => 'mai_test_tax', 'terms' => [ $f['custom'] ] ];

		return match ( $name ) {
			'big'                        => [ $big ],
			'tag'                        => [ $tag ],
			'custom'                     => [ $custom ],
			'big AND tag'                => [ 'relation' => 'AND', $big, $tag ],
			'three IN'                   => [ 'relation' => 'AND', $big, $tag, $custom ],
			'big OR tag'                 => [ 'relation' => 'OR', $big, $tag ],
			'OR, lowercase in then IN'   => [ 'relation' => 'OR', $big + [ 'operator' => 'in' ], $tag ],
			'IN AND NOT IN'              => [ 'relation' => 'AND', $big, $tag + [ 'operator' => 'NOT IN' ] ],
			'IN AND NOT IN, all deleted' => [ 'relation' => 'AND', $big, [ 'taxonomy' => 'category', 'terms' => [ self::deleted_term() ], 'operator' => 'NOT IN' ] ],
			'IN AND operator AND'        => [ 'relation' => 'AND', $big, $tag + [ 'operator' => 'AND' ] ],
			'IN AND operator EXISTS'     => [ 'relation' => 'AND', $big, [ 'taxonomy' => 'mai_test_tax', 'operator' => 'EXISTS' ] ],
			'29 terms'                   => [ [ 'taxonomy' => 'category', 'terms' => self::twenty_nine_terms() ] ],
			'empty'                      => [ [ 'taxonomy' => 'category', 'terms' => [ $f['empty'] ] ] ],
			'nested'                     => [ 'relation' => 'AND', [ 'relation' => 'OR', $big, $tag ], $custom ],
			'OR with NOT IN'             => [ 'relation' => 'OR', $big, $tag + [ 'operator' => 'NOT IN' ] ],
			'OR with lowercase in'       => [ 'relation' => 'OR', $big, $tag + [ 'operator' => 'in' ] ],
			'only NOT IN'                => [ $tag + [ 'operator' => 'NOT IN' ] ],
			'deleted term'               => [ [ 'taxonomy' => 'category', 'terms' => [ self::deleted_term() ] ] ],
			'unregistered taxonomy'      => [ [ 'taxonomy' => 'mai_no_such_tax', 'terms' => [ $f['big'] ] ] ],
			'empty tax query'            => [],
		};
	}

	/**
	 * Provider rows from shape names, keyed by name.
	 *
	 * @param string[] $names Shape names.
	 *
	 * @return array<string,array{0:string}>
	 */
	private static function cases( array $names ): array {
		return array_combine( $names, array_map( static fn( string $name ): array => [ $name ], $names ) );
	}

	/**
	 * A category that existed and was deleted.
	 *
	 * @return int
	 */
	private static function deleted_term(): int {
		$term = self::factory()->category->create( [ 'name' => 'Optimizer deleted' ] );

		wp_delete_term( $term, 'category' );

		return $term;
	}

	/**
	 * 29 categories, as many as eurweb's biggest filter. Category $k holds big's posts $k and
	 * $k + 7, so most posts sit in two of them and the join lists them twice.
	 *
	 * @return int[]
	 */
	private static function twenty_nine_terms(): array {
		$posts = self::$fixture['posts']['big'];
		$terms = [];

		for ( $k = 0; $k < 29; $k++ ) {
			$term = self::factory()->category->create( [ 'name' => "Optimizer many {$k}" ] );

			wp_set_object_terms( $posts[ $k ], [ $term ], 'category', true );
			wp_set_object_terms( $posts[ $k + 7 ], [ $term ], 'category', true );

			$terms[] = $term;
		}

		return $terms;
	}

	/**
	 * Gives every fixture post the same date, so the ID tiebreaker decides the whole order.
	 *
	 * @return void
	 */
	private function share_one_date(): void {
		global $wpdb;

		$f   = self::$fixture;
		$ids = array_merge( ...array_values( $f['posts'] ), ...[ $f['private'], $f['newest'], [ $f['page'] ] ] );

		foreach ( array_unique( $ids ) as $id ) {
			$wpdb->update(
				$wpdb->posts,
				[
					'post_date'     => '2020-01-01 12:00:00',
					'post_date_gmt' => '2020-01-01 12:00:00',
				],
				[ 'ID' => $id ]
			);

			clean_post_cache( $id );
		}
	}

	/**
	 * Whether this database takes the hint, as the optimizer decides it.
	 *
	 * @return bool
	 */
	private function hint(): bool {
		global $wpdb;

		return Mai_Post_Grid_Query_Optimizer_Database::hint( Mai_Post_Grid_Query_Optimizer_Database::server_info( $wpdb ) );
	}

	/**
	 * Runs a statement and returns its post IDs, failing on a database error.
	 *
	 * @param string $sql The statement.
	 *
	 * @return int[]
	 */
	private function ids( string $sql ): array {
		global $wpdb;

		$ids = $wpdb->get_col( $sql );

		$this->assertSame( '', $wpdb->last_error, $sql );

		return array_map( 'intval', $ids );
	}

	/**
	 * Rebuilds a tax query, failing when it is not covered.
	 *
	 * @param array $tax_query The tax query.
	 *
	 * @return array
	 */
	private function rebuild( array $tax_query ): array {
		global $wpdb;

		$rebuilt = Mai_Post_Grid_Query_Optimizer_Sql::rebuild( new WP_Tax_Query( $tax_query ), $wpdb->posts );

		$this->assertNotNull( $rebuilt );

		return $rebuilt;
	}
}
