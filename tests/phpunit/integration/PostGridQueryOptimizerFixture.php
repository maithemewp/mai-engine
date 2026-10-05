<?php
declare(strict_types=1);

namespace BizBudding\MaiEngine\Tests\Integration;

/**
 * Posts and terms for the grid query optimizer tests.
 *
 * Build it once per class in wpSetUpBeforeClass(), which commits it, and call
 * remove_optimizer_fixture() from wpTearDownAfterClass(). The core test case deletes the
 * posts and terms after the class; that call removes what it does not.
 *
 * Dates, newest first: `newest` (the last 15 hours), then `big` (one a day, 1 to 40 days ago),
 * with tag's and custom's own posts, the two private posts and the page between big's days,
 * then `small` (2015).
 */
trait PostGridQueryOptimizerFixture {

	/**
	 * Registers the test's custom taxonomy, hierarchical like a recipe or product category.
	 *
	 * @return void
	 */
	protected static function register_optimizer_taxonomy(): void {
		register_taxonomy( 'mai_test_tax', [ 'post', 'page' ], [ 'hierarchical' => true ] );
	}

	/**
	 * Creates the fixture.
	 *
	 * Before any term is made, 100 spare rows go into the term_taxonomy table with plain
	 * INSERTs, so every test term's term ID differs from its term_taxonomy ID, as on real sites
	 * (a fresh install keeps them equal). ALTER TABLE ... AUTO_INCREMENT would do it in one
	 * statement, but it commits the open transaction, so a test could no longer roll back.
	 *
	 * Returns, by name:
	 * - Term IDs: `big` and its child `child` (category), `small` (category), `tag` (post_tag),
	 *   `custom` (mai_test_tax), `empty` (category, no posts).
	 * - Post IDs: `private` (2 private posts in big), `newest` (15 published posts newer than
	 *   all others, in no test term), `page` (one page in big).
	 * - `posts`: the published post IDs in each term, newest first. `big` lists all 40,
	 *   including the 5 in `child`, 2 of which are also in big itself. `tag` is 8 of big's
	 *   posts, spread across its days, and 2 of its own, 2.5 and 8.5 days old, so big OR tag
	 *   differs from big within the newest 10, and big AND tag differs from tag. `custom` is 4
	 *   of big's posts (2 of them tagged), 4 posts of its own, and tag's 2.5-day post.
	 *
	 * @param \WP_UnitTest_Factory $factory The test factory.
	 *
	 * @return array{big:int,child:int,small:int,tag:int,custom:int,empty:int,private:int[],newest:int[],page:int,posts:array<string,int[]>}
	 */
	protected static function create_optimizer_fixture( $factory ): array {
		global $wpdb;

		self::register_optimizer_taxonomy();

		for ( $i = 0; $i < 100; $i++ ) {
			$wpdb->insert(
				$wpdb->term_taxonomy,
				[
					'term_id'     => 0,
					'taxonomy'    => "mai_test_spare_{$i}",
					'description' => '',
					'parent'      => 0,
					'count'       => 0,
				]
			);
		}

		$big    = $factory->category->create( [ 'name' => 'Optimizer big' ] );
		$child  = $factory->category->create( [ 'name' => 'Optimizer child', 'parent' => $big ] );
		$small  = $factory->category->create( [ 'name' => 'Optimizer small' ] );
		$tag    = $factory->tag->create( [ 'name' => 'Optimizer tag' ] );
		$custom = $factory->term->create( [ 'name' => 'Optimizer custom', 'taxonomy' => 'mai_test_tax' ] );
		$empty  = $factory->category->create( [ 'name' => 'Optimizer empty' ] );

		$now   = time();
		$date  = static fn( float $seconds_ago ): string => gmdate( 'Y-m-d H:i:s', (int) ( $now - $seconds_ago ) );
		$posts = [
			'big'    => [],
			'child'  => [],
			'small'  => [],
			'tag'    => [],
			'custom' => [],
		];

		// Big's posts, newest first. Index $i is ($i + 1) days old.
		$child_only = [ 11, 27, 35 ];
		$child_both = [ 3, 19 ];
		$tagged     = [ 2, 7, 12, 17, 22, 27, 32, 37 ];
		$customs    = [ 2, 7, 15, 25 ];

		for ( $i = 0; $i < 40; $i++ ) {
			$categories = in_array( $i, $child_only, true ) ? [ $child ] : [ $big ];

			if ( in_array( $i, $child_both, true ) ) {
				$categories[] = $child;
			}

			$id = $factory->post->create(
				[
					'post_status'   => 'publish',
					'post_date'     => $date( ( $i + 1 ) * DAY_IN_SECONDS ),
					'post_category' => $categories,
				]
			);

			$posts['big'][] = $id;

			if ( in_array( $i, $child_only, true ) || in_array( $i, $child_both, true ) ) {
				$posts['child'][] = $id;
			}

			if ( in_array( $i, $tagged, true ) ) {
				wp_set_object_terms( $id, [ $tag ], 'post_tag' );
				$posts['tag'][] = $id;
			}

			if ( in_array( $i, $customs, true ) ) {
				wp_set_object_terms( $id, [ $custom ], 'mai_test_tax' );
				$posts['custom'][] = $id;
			}
		}

		// Custom's own posts, half a day older than big's posts 4, 14, 24 and 34.
		foreach ( [ 4, 14, 24, 34 ] as $i ) {
			$id = $factory->post->create(
				[
					'post_status' => 'publish',
					'post_date'   => $date( ( $i + 1.5 ) * DAY_IN_SECONDS ),
				]
			);

			wp_set_object_terms( $id, [ $custom ], 'mai_test_tax' );
			$posts['custom'][] = $id;
		}

		// Tag's own posts, in no test category. The newer one is in custom too.
		foreach ( [ 2.5, 8.5 ] as $days ) {
			$id = $factory->post->create(
				[
					'post_status' => 'publish',
					'post_date'   => $date( $days * DAY_IN_SECONDS ),
				]
			);

			wp_set_object_terms( $id, [ $tag ], 'post_tag' );
			$posts['tag'][] = $id;

			if ( 2.5 === $days ) {
				wp_set_object_terms( $id, [ $custom ], 'mai_test_tax' );
				$posts['custom'][] = $id;
			}
		}

		foreach ( [ '2015-11-10 09:00:00', '2015-06-10 09:00:00', '2015-01-10 09:00:00' ] as $old ) {
			$posts['small'][] = $factory->post->create(
				[
					'post_status'   => 'publish',
					'post_date'     => $old,
					'post_category' => [ $small ],
				]
			);
		}

		$private = [];

		foreach ( [ 3.5, 20.5 ] as $days ) {
			$private[] = $factory->post->create(
				[
					'post_status'   => 'private',
					'post_date'     => $date( $days * DAY_IN_SECONDS ),
					'post_category' => [ $big ],
				]
			);
		}

		$page = $factory->post->create(
			[
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_date'   => $date( 10.5 * DAY_IN_SECONDS ),
			]
		);

		wp_set_object_terms( $page, [ $big ], 'category' );

		// Newest last, so they also have the highest IDs.
		$newest = [];

		for ( $i = 15; $i >= 1; $i-- ) {
			$newest[] = $factory->post->create(
				[
					'post_status' => 'publish',
					'post_date'   => $date( $i * HOUR_IN_SECONDS ),
				]
			);
		}

		$newest_first = static fn( int $a, int $b ): int => strcmp( get_post( $b )->post_date, get_post( $a )->post_date );

		usort( $posts['tag'], $newest_first );
		usort( $posts['custom'], $newest_first );

		return [
			'big'     => $big,
			'child'   => $child,
			'small'   => $small,
			'tag'     => $tag,
			'custom'  => $custom,
			'empty'   => $empty,
			'private' => $private,
			'newest'  => array_reverse( $newest ),
			'page'    => $page,
			'posts'   => $posts,
		];
	}

	/**
	 * Removes what the core test case leaves after the class: the custom taxonomy, and the
	 * cached term trees, which would still name the deleted terms.
	 *
	 * @return void
	 */
	protected static function remove_optimizer_fixture(): void {
		unregister_taxonomy( 'mai_test_tax' );
		delete_option( 'category_children' );
		delete_option( 'mai_test_tax_children' );
	}
}
