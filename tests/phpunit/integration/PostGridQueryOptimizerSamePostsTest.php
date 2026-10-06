<?php
declare(strict_types=1);

namespace BizBudding\MaiEngine\Tests\Integration;

use LogicException;
use Mai_Grid;
use Mai_Post_Grid_Query_Optimizer;
use Mai_Query_Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use WP_UnitTest_Factory;

/**
 * Every grid the optimizer covers shows the same posts in the same order with the swap on and
 * off.
 *
 * Each case builds the grid through Mai_Grid, as on a page, and renders it as a new request
 * three times: with the filter turning the optimizer off, then on, then on again with no
 * taxonomy filter. Between the runs the object cache, Mai's stored grid results and the
 * optimizer's per-request state are cleared, so a run reads nothing the one before it left. A
 * case passes when:
 *
 * - the off run and the on run show the same post IDs in the same order,
 * - the off run sent no swapped statement and the on run sent one, unless the sort is one the
 *   optimizer leaves out, which sends none on either run,
 * - the swap stayed on, since a swapped statement that fails is sent again unswapped, which
 *   would show today's posts and hide the break,
 * - the on run's posts differ from those of the run with no taxonomy filter, so the taxonomy
 *   filter really narrowed the list, and
 * - no excluded post is shown.
 *
 * The fixture's posts get spread values for every sort column on top of the shared fixture, so
 * that each sort orders them differently from the date, with ties for the ID tiebreaker. Eight
 * posts in no test term are made before the fixture, so the lowest IDs are not all big's.
 */
final class PostGridQueryOptimizerSamePostsTest extends MaiIntegrationTestCase {

	use PostGridQueryOptimizerFixture;

	/**
	 * What only Mai's swap writes. Core's own EXISTS operator writes `EXISTS (` too.
	 */
	private const SWAP = 'EXISTS ( SELECT ';

	/**
	 * The shapes of a taxonomy filter the swap covers, by name. See taxonomies().
	 */
	private const SHAPES = [
		'big with children',
		'tag',
		'custom',
		'big AND tag',
		'big OR tag',
		'big IN and small NOT IN',
		'big IN and custom NOT IN',
		'small',
		'29 terms',
	];

	/**
	 * The sorts of the first provider: the argument Mai's grid reads in `orderby`. Every sort stays
	 * in the matrix, so a sort the optimizer leaves out still shows the same posts on and off.
	 */
	private const SORTS = [
		'date'          => 'date',
		'modified'      => 'modified',
		'title'         => 'title',
		'name'          => 'name',
		'menu order'    => 'menu_order',
		'comment count' => 'comment_count',
		'ID'            => 'ID',
		'author'        => 'author',
	];

	/**
	 * The sorts above that the optimizer swaps: date and author, the ones that met the speed bar.
	 * ID sorts left on 2026-10-06, after local MySQL 9.7.1 flipped their plan between runs. Written
	 * out here, not read from SORT_COLUMNS, so adding a sort to that list fails these cases until
	 * the sort is measured and listed here.
	 */
	private const SWAPPED_SORTS = [ 'date', 'author' ];

	/**
	 * The fixture's IDs, built once for the class.
	 *
	 * @var array
	 */
	private static array $fixture = [];

	/**
	 * An editor, who may read private posts.
	 */
	private static int $editor = 0;

	/**
	 * 29 categories, as many as eurweb's biggest filter.
	 *
	 * @var int[]
	 */
	private static array $many = [];

	/**
	 * Posts made before the fixture, so they have the lowest IDs, in no test term.
	 *
	 * @var int[]
	 */
	private static array $leading = [];

	/** @var string[] The lines the optimizer logged. */
	private array $logged = [];

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ): void {
		self::$leading = self::create_leading_posts( $factory );
		self::$fixture = self::create_optimizer_fixture( $factory );
		self::$editor  = $factory->user->create( [ 'role' => 'editor' ] );
		self::$many    = self::create_many_terms( $factory );

		self::spread_sort_values( $factory );
	}

	public static function wpTearDownAfterClass(): void {
		self::remove_optimizer_fixture();
	}

	public function set_up(): void {
		parent::set_up();

		$this->logged = [];
	}

	public function tear_down(): void {
		Mai_Grid::$existing_post_ids = [];

		wp_set_current_user( 0 );

		parent::tear_down();
	}

	/**
	 * Every shape with every sort, ascending and descending, as a visitor with no excludes, 7 to
	 * a page.
	 *
	 * @return array<string,array{0:array,1:string}>
	 */
	public static function shapes_by_sorts(): array {
		$cases = [];

		foreach ( self::SHAPES as $shape ) {
			foreach ( self::SORTS as $sort => $orderby ) {
				foreach ( [ 'ASC', 'DESC' ] as $order ) {
					$cases[ "{$shape}, by {$sort} {$order}" ] = [
						[
							'shape'    => $shape,
							'orderby'  => $orderby,
							'order'    => $order,
							'per_page' => 7,
							'excludes' => 'none',
						],
						'visitor',
					];
				}
			}
		}

		return $cases;
	}

	/**
	 * Four shapes with three page sizes, three kinds of excludes and three contexts, by date.
	 *
	 * @return array<string,array{0:array,1:string}>
	 */
	public static function limits_excludes_and_contexts(): array {
		$excludes = [
			'none'      => 'no excludes',
			'current'   => 'exclude current',
			'displayed' => 'exclude displayed',
		];
		$contexts = [
			'visitor' => 'visitor',
			'editor'  => 'editor logged in',
			'pages'   => 'posts and pages',
		];
		$cases    = [];

		foreach ( [ 'big with children', 'big AND tag', 'big IN and small NOT IN', 'big IN and custom NOT IN' ] as $shape ) {
			foreach ( [ 2, 32, 200 ] as $per_page ) {
				foreach ( $excludes as $exclude => $exclude_name ) {
					foreach ( $contexts as $context => $context_name ) {
						$cases[ "{$shape}, {$per_page} per page, {$exclude_name}, {$context_name}" ] = [
							[
								'shape'    => $shape,
								'orderby'  => 'date',
								'order'    => 'DESC',
								'per_page' => $per_page,
								'excludes' => $exclude,
							],
							$context,
						];
					}
				}
			}
		}

		return $cases;
	}

	/**
	 * @param array{shape:string,orderby:string,order:string,per_page:int,excludes:string} $grid
	 *        The grid, by name: a shape from SHAPES, a sort, a page size, and 'none', 'current' or
	 *        'displayed' for what it excludes. Names, because a provider runs before the fixture exists.
	 * @param string $context 'visitor', 'editor' for an editor logged in, or 'pages' for post types post and page.
	 */
	#[DataProvider( 'shapes_by_sorts' )]
	#[DataProvider( 'limits_excludes_and_contexts' )]
	public function test_same_posts( array $grid, string $context ): void {
		[ $args, $excluded ] = $this->prepare( $grid, $context );

		$off        = $this->render( $args, false );
		$on         = $this->render( $args, true );
		$unfiltered = $this->render( array_merge( $args, [ 'taxonomies' => [] ] ), true );

		$this->assertNotEmpty( $on['ids'], 'the grid shows posts' );
		$this->assertSame( [], $off['swapped'], 'the off run was not swapped' );

		if ( in_array( $args['orderby'], self::SWAPPED_SORTS, true ) ) {
			$this->assertNotEmpty( $on['swapped'], 'the on run sent the swapped statement' );
		} else {
			$this->assertSame( [], $on['swapped'], 'the on run kept today\'s statement, because the sort is left out' );
		}

		$this->assertSame( $off['ids'], $on['ids'], 'the same posts in the same order' );
		$this->assertSame( [], $this->logged, 'the swap did not fail or turn itself off' );
		$this->assertFalse( get_transient( Mai_Post_Grid_Query_Optimizer::TRANSIENT ), 'and it is not off for the day' );
		$this->assertNotSame( $unfiltered['ids'], $on['ids'], 'the taxonomy filter changes what the grid shows' );
		$this->assertSame( [], array_values( array_intersect( $on['ids'], $excluded ) ), 'no excluded post is shown' );
	}

	/**
	 * The swapped sorts other than the ID, each way.
	 *
	 * @return array<string,array{0:string,1:string}>
	 */
	public static function tied_sorts(): array {
		return [
			'date ASC'    => [ 'date', 'ASC' ],
			'date DESC'   => [ 'date', 'DESC' ],
			'author ASC'  => [ 'author', 'ASC' ],
			'author DESC' => [ 'author', 'DESC' ],
		];
	}

	/**
	 * When every post shares one date and one author, the ID tiebreaker decides the whole order.
	 * The swapped statement must still show the same posts as today's, and those are big's posts by
	 * ID in the sort's direction. The dates and authors are set inside the test, so they roll back.
	 */
	#[DataProvider( 'tied_sorts' )]
	public function test_same_posts_when_every_date_and_author_ties( string $orderby, string $order ): void {
		$this->tie_every_date_and_author();

		[ $args ] = $this->prepare(
			[
				'shape'    => 'big with children',
				'orderby'  => $orderby,
				'order'    => $order,
				'per_page' => 7,
				'excludes' => 'none',
			],
			'visitor'
		);

		$off      = $this->render( $args, false );
		$on       = $this->render( $args, true );
		$expected = self::$fixture['posts']['big'];

		if ( 'ASC' === $order ) {
			sort( $expected );
		} else {
			rsort( $expected );
		}

		$this->assertSame( [], $off['swapped'], 'the off run was not swapped' );
		$this->assertNotEmpty( $on['swapped'], 'the on run sent the swapped statement' );
		$this->assertSame( $off['ids'], $on['ids'], 'the same posts in the same order' );
		$this->assertSame( array_slice( $expected, 0, 7 ), $on['ids'], 'big\'s posts by ID, in the sort\'s direction' );
		$this->assertSame( [], $this->logged, 'the swap did not fail or turn itself off' );
	}

	/**
	 * Pins what the three contexts and the two kinds of excludes do, so the cases above are not
	 * running the same grid under three names.
	 */
	public function test_the_contexts_and_excludes_change_what_the_grid_shows(): void {
		$grid = [
			'shape'    => 'big with children',
			'orderby'  => 'date',
			'order'    => 'DESC',
			'per_page' => 200,
			'excludes' => 'none',
		];

		$ids = function ( array $grid, string $context ): array {
			[ $args ] = $this->prepare( $grid, $context );

			return $this->render( $args, true )['ids'];
		};

		$big     = self::$fixture['posts']['big'];
		$private = self::$fixture['private'];
		$page    = self::$fixture['page'];

		$visitor = $ids( $grid, 'visitor' );
		$editor  = $ids( $grid, 'editor' );
		$pages   = $ids( $grid, 'pages' );

		$this->assertSame( $big, $visitor, 'a visitor sees big\'s 40 published posts, newest first' );
		$this->assertSame( [], array_values( array_intersect( $visitor, $private ) ), 'and no private post' );
		$this->assertCount( 42, $editor );
		$this->assertSame( [], array_values( array_diff( $private, $editor ) ), 'an editor sees both private posts' );
		$this->assertCount( 41, $pages );
		$this->assertContains( $page, $pages, 'the page is in the posts and pages grid' );
		$this->assertNotContains( $page, $visitor );

		$current   = $ids( array_merge( $grid, [ 'excludes' => 'current' ] ), 'visitor' );
		$displayed = $ids( array_merge( $grid, [ 'excludes' => 'displayed' ] ), 'visitor' );

		$this->assertSame( array_values( array_diff( $visitor, [ self::members( 'big with children' )[0] ] ) ), $current );
		$this->assertSame( array_values( array_diff( $visitor, self::displayed( 'big with children' ) ) ), $displayed );
	}

	/**
	 * Gets the page ready for a case and returns the grid args and the IDs it excludes.
	 *
	 * @param array  $grid    The grid, as test_same_posts() describes it.
	 * @param string $context The context.
	 *
	 * @return array{0:array,1:int[]}
	 */
	private function prepare( array $grid, string $context ): array {
		$shape    = $grid['shape'];
		$current  = [];
		$existing = [];

		if ( 'current' === $grid['excludes'] ) {
			$current = [ self::members( $shape )[0] ];
		} elseif ( 'displayed' === $grid['excludes'] ) {
			$existing = self::displayed( $shape );
		}

		// A new page view: the query, the globals and the object cache start over.
		$this->go_to( $current ? get_permalink( $current[0] ) : home_url( '/' ) );

		Mai_Grid::$existing_post_ids = $existing ? [ 'post' => $existing ] : [];

		wp_set_current_user( 'editor' === $context ? self::$editor : 0 );

		$args = [
			'type'           => 'post',
			'post_type'      => 'pages' === $context ? [ 'post', 'page' ] : [ 'post' ],
			'query_by'       => 'tax_meta',
			'posts_per_page' => $grid['per_page'],
			'excludes'       => match ( $grid['excludes'] ) {
				'none'      => [],
				'current'   => [ 'exclude_current' ],
				'displayed' => [ 'exclude_displayed' ],
			},
			'taxonomies'     => self::taxonomies( $shape ),
			'orderby'        => $grid['orderby'],
			'order'          => $grid['order'],
		];

		if ( 'big OR tag' === $shape ) {
			$args['taxonomies_relation'] = 'OR';
		} elseif ( 'big AND tag' === $shape ) {
			$args['taxonomies_relation'] = 'AND';
		}

		return [ $args, array_merge( $current, $existing ) ];
	}

	/**
	 * A grid's taxonomies setting, as Mai's block saves it.
	 *
	 * @param string $shape A shape from SHAPES.
	 *
	 * @return array[]
	 */
	private static function taxonomies( string $shape ): array {
		$f      = self::$fixture;
		$filter = static fn( string $taxonomy, array $terms, string $operator = 'IN' ): array => [
			'taxonomy' => $taxonomy,
			'terms'    => $terms,
			'current'  => false,
			'operator' => $operator,
		];

		return match ( $shape ) {
			'big with children'        => [ $filter( 'category', [ $f['big'] ] ) ],
			'tag'                      => [ $filter( 'post_tag', [ $f['tag'] ] ) ],
			'custom'                   => [ $filter( 'mai_test_tax', [ $f['custom'] ] ) ],
			'big AND tag', 'big OR tag' => [ $filter( 'category', [ $f['big'] ] ), $filter( 'post_tag', [ $f['tag'] ] ) ],
			'big IN and small NOT IN'  => [ $filter( 'category', [ $f['big'] ] ), $filter( 'category', [ $f['small'] ], 'NOT IN' ) ],
			'big IN and custom NOT IN' => [ $filter( 'category', [ $f['big'] ] ), $filter( 'mai_test_tax', [ $f['custom'] ], 'NOT IN' ) ],
			'small'                    => [ $filter( 'category', [ $f['small'] ] ) ],
			'29 terms'                 => [ $filter( 'category', self::$many ) ],
		};
	}

	/**
	 * The published posts a shape holds, newest first. Only for the shapes the second provider
	 * uses, so each is a big post whose first entries are in the newest window.
	 *
	 * @param string $shape A shape from SHAPES.
	 *
	 * @return int[]
	 */
	private static function members( string $shape ): array {
		$f = self::$fixture;

		return match ( $shape ) {
			'big with children', 'big IN and small NOT IN' => $f['posts']['big'],
			'big AND tag'                                  => array_values( array_intersect( $f['posts']['big'], $f['posts']['tag'] ) ),
			'big IN and custom NOT IN'                     => array_values( array_diff( $f['posts']['big'], $f['posts']['custom'] ) ),
			default                                        => throw new LogicException( "No members listed for {$shape}." ),
		};
	}

	/**
	 * Posts an earlier grid on the page already showed: the shape's newest, third newest and
	 * fourth newest, so the newest window has gaps.
	 *
	 * @param string $shape A shape from SHAPES.
	 *
	 * @return int[]
	 */
	private static function displayed( string $shape ): array {
		$members = self::members( $shape );

		return [ $members[0], $members[2], $members[3] ];
	}

	/**
	 * Renders the grid as a new request with the swap allowed or not, and returns the posts it
	 * shows, the statements that carried the swap, and nothing from the run before.
	 *
	 * @param array $args     The grid args.
	 * @param bool  $optimize Whether the filter allows the swap.
	 *
	 * @return array{ids:int[],swapped:string[]}
	 */
	private function render( array $args, bool $optimize ): array {
		wp_cache_flush();

		( new Mai_Query_Cache() )->flush_all();

		$optimizer = Mai_Post_Grid_Query_Optimizer::instance();

		// The filter is read once per request.
		$optimizer->reset();
		$optimizer->set_logger(
			function ( string $message ): void {
				$this->logged[] = $message;
			}
		);

		// After reset(), which puts the limit back. A busy machine must not turn the swap off for
		// being slow. PostGridQueryOptimizerGridTest covers that.
		Mai_Post_Grid_Query_Optimizer::$slow = 60.0;

		$statements = [];
		$answer     = $optimize ? '__return_true' : '__return_false';

		// Added after the optimizer's own query callback, so it sees the text the database got.
		$capture = static function ( $sql ) use ( &$statements ) {
			if ( is_string( $sql ) ) {
				$statements[] = $sql;
			}

			return $sql;
		};

		add_filter( Mai_Post_Grid_Query_Optimizer::FILTER, $answer );
		add_filter( 'query', $capture, PHP_INT_MAX );

		try {
			$query = ( new Mai_Grid( $args ) )->get_query();
		} finally {
			remove_filter( 'query', $capture, PHP_INT_MAX );
			remove_filter( Mai_Post_Grid_Query_Optimizer::FILTER, $answer );
		}

		$this->assertInstanceOf( \WP_Query::class, $query );

		return [
			'ids'     => array_map( 'intval', wp_list_pluck( $query->posts, 'ID' ) ),
			'swapped' => array_values( array_filter( $statements, static fn( string $sql ): bool => str_contains( $sql, self::SWAP ) ) ),
		];
	}

	/**
	 * Gives every post of the fixture, and the posts made before it, one date and one author, so
	 * date and author sorts tie on every post. Called inside a test, so the change rolls back.
	 *
	 * @return void
	 */
	private function tie_every_date_and_author(): void {
		global $wpdb;

		$f      = self::$fixture;
		$ids    = array_unique( array_merge( self::$leading, ...array_values( $f['posts'] ), ...[ $f['private'], $f['newest'], [ $f['page'] ] ] ) );
		$author = self::factory()->user->create( [ 'role' => 'author' ] );

		foreach ( $ids as $id ) {
			$wpdb->update(
				$wpdb->posts,
				[
					'post_date'     => '2020-01-01 12:00:00',
					'post_date_gmt' => '2020-01-01 12:00:00',
					'post_author'   => $author,
				],
				[ 'ID' => $id ]
			);

			clean_post_cache( $id );
		}
	}

	/**
	 * Creates 8 posts in no test term, before the fixture, so they have the lowest IDs. The fixture
	 * makes big's posts first, so without these the 7 lowest IDs of the whole site are big's, and
	 * a grid of big sorted by ID ascending would show what the same grid shows with no taxonomy
	 * filter. They are 100 days old, between big's oldest post and small's.
	 *
	 * @param WP_UnitTest_Factory $factory The test factory.
	 *
	 * @return int[]
	 */
	private static function create_leading_posts( WP_UnitTest_Factory $factory ): array {
		$ids = [];

		for ( $i = 0; $i < 8; $i++ ) {
			$ids[] = $factory->post->create(
				[
					'post_status' => 'publish',
					'post_date'   => gmdate( 'Y-m-d H:i:s', time() - ( 100 + $i ) * DAY_IN_SECONDS ),
				]
			);
		}

		return $ids;
	}

	/**
	 * Creates 29 categories. Category $k holds big's posts $k and $k + 7, so most posts sit in
	 * two of them and the join lists them twice. Big's posts 0 to 35 end up in at least one.
	 *
	 * @param WP_UnitTest_Factory $factory The test factory.
	 *
	 * @return int[]
	 */
	private static function create_many_terms( WP_UnitTest_Factory $factory ): array {
		$posts = self::$fixture['posts']['big'];
		$terms = [];

		for ( $k = 0; $k < 29; $k++ ) {
			$term = $factory->category->create( [ 'name' => "Optimizer many {$k}" ] );

			wp_set_object_terms( $posts[ $k ], [ $term ], 'category', true );
			wp_set_object_terms( $posts[ $k + 7 ], [ $term ], 'category', true );

			$terms[] = $term;
		}

		return $terms;
	}

	/**
	 * Gives every fixture post a title, slug, menu order, comment count, author and modified date
	 * that do not follow the post date. Each of those sorts then orders the posts differently from
	 * the date. Each column also repeats its values across posts, so posts tie on it and the ID
	 * tiebreaker decides part of that sort's order. Without this the factory leaves menu order,
	 * comment count and author the same on every post.
	 *
	 * @param WP_UnitTest_Factory $factory The test factory.
	 *
	 * @return void
	 */
	private static function spread_sort_values( WP_UnitTest_Factory $factory ): void {
		global $wpdb;

		$f   = self::$fixture;
		$ids = array_unique( array_merge( self::$leading, ...array_values( $f['posts'] ), ...[ $f['private'], $f['newest'], [ $f['page'] ] ] ) );
		$now = time();

		sort( $ids );

		$authors = [
			$factory->user->create( [ 'role' => 'author' ] ),
			$factory->user->create( [ 'role' => 'author' ] ),
			$factory->user->create( [ 'role' => 'author' ] ),
		];

		foreach ( $ids as $n => $id ) {
			$modified = gmdate( 'Y-m-d H:i:s', $now - ( ( $n * 19 ) % 67 ) * HOUR_IN_SECONDS );

			$wpdb->update(
				$wpdb->posts,
				[
					'post_title'        => sprintf( 'Optimizer %02d', ( $n * 17 ) % 31 ),
					'post_name'         => sprintf( 'optimizer-%02d', ( $n * 11 ) % 29 ),
					'menu_order'        => ( $n * 7 ) % 11,
					'comment_count'     => ( $n * 5 ) % 13,
					'post_author'       => $authors[ $n % 3 ],
					'post_modified'     => $modified,
					'post_modified_gmt' => $modified,
				],
				[ 'ID' => $id ]
			);

			clean_post_cache( $id );
		}
	}
}
