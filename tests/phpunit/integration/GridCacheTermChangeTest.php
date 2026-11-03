<?php
declare(strict_types=1);

namespace BizBudding\MaiEngine\Tests\Integration;

use Mai_Query_Cache;

/**
 * Term changes rotate the grid cache's post type token, through the hooks core really fires.
 *
 * - A post a grid can show (published or private) that gains or loses a term rotates its type,
 *   once per call however many terms change.
 * - Setting the terms a post already has, or changing a draft's terms, does not.
 * - Deleting a term rotates every type using the taxonomy once, however many posts had it.
 * - Moving a term to a new parent rotates every type using the taxonomy. Renaming it does not.
 */
final class GridCacheTermChangeTest extends MaiIntegrationTestCase {

	private int $post_id = 0;

	private int $term_id = 0;

	public function set_up(): void {
		parent::set_up();

		$this->term_id = self::factory()->term->create( [ 'taxonomy' => 'category' ] );
		$this->post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );

		( new Mai_Query_Cache() )->flush_all();
	}

	public function tear_down(): void {
		unregister_taxonomy( 'mai_page_only' );
		unregister_taxonomy( 'mai_shared_tax' );

		parent::tear_down();
	}

	private function version( string $type = 'post' ): string {
		return mai_cache( 'grid' )->version( [ $type ] );
	}

	/**
	 * How many times a type's token is written while a callback runs. The token is stored under
	 * a key ending in `v_{type}`, through set_transient().
	 */
	private function rotations( callable $change, string $type = 'post' ): int {
		$count = 0;
		$watch = static function ( $transient ) use ( &$count, $type ) {
			if ( str_ends_with( (string) $transient, "_v_{$type}" ) ) {
				++$count;
			}
		};

		add_action( 'set_transient', $watch );
		$change();
		remove_action( 'set_transient', $watch );

		return $count;
	}

	public function test_hooks_are_registered(): void {
		$cache = Mai_Query_Cache::instance();

		$this->assertSame( 10, has_action( 'added_term_relationship', [ $cache, 'on_add_term' ] ) );
		$this->assertSame( 10, has_action( 'set_object_terms', [ $cache, 'on_set_terms' ] ) );
		$this->assertSame( 10, has_action( 'deleted_term_relationships', [ $cache, 'on_remove_terms' ] ) );
		$this->assertSame( 10, has_action( 'pre_delete_term', [ $cache, 'before_term_delete' ] ) );
		$this->assertSame( 10, has_action( 'delete_term', [ $cache, 'on_term_deleted' ] ) );
		$this->assertSame( 10, has_action( 'edit_terms', [ $cache, 'before_term_edit' ] ) );
		$this->assertSame( 10, has_action( 'edited_term', [ $cache, 'on_term_edited' ] ) );
	}

	public function test_adding_a_term_rotates(): void {
		$before = $this->version();

		wp_set_object_terms( $this->post_id, $this->term_id, 'category', true );

		$this->assertNotSame( $before, $this->version() );
	}

	public function test_adding_a_term_to_a_private_post_rotates(): void {
		wp_update_post( [ 'ID' => $this->post_id, 'post_status' => 'private' ] );
		$before = $this->version();

		wp_set_object_terms( $this->post_id, $this->term_id, 'category', true );

		$this->assertNotSame( $before, $this->version() );
	}

	public function test_adding_ten_tags_rotates_once(): void {
		$tags = self::factory()->term->create_many( 10, [ 'taxonomy' => 'post_tag' ] );

		$this->assertSame( 1, $this->rotations( fn() => wp_set_object_terms( $this->post_id, $tags, 'post_tag' ) ) );
	}

	public function test_setting_the_same_terms_does_not_rotate(): void {
		wp_set_object_terms( $this->post_id, $this->term_id, 'category' );
		$before = $this->version();

		wp_set_object_terms( $this->post_id, $this->term_id, 'category' );
		wp_set_object_terms( $this->post_id, $this->term_id, 'category', true );

		$this->assertSame( $before, $this->version() );
	}

	public function test_removing_a_term_rotates(): void {
		wp_set_object_terms( $this->post_id, $this->term_id, 'category', true );
		$before = $this->version();

		wp_remove_object_terms( $this->post_id, $this->term_id, 'category' );

		$this->assertNotSame( $before, $this->version() );
	}

	/** A replace that adds nothing still rotates, through the removal. */
	public function test_replacing_terms_with_fewer_rotates(): void {
		$other = self::factory()->term->create( [ 'taxonomy' => 'category' ] );
		wp_set_object_terms( $this->post_id, [ $this->term_id, $other ], 'category' );
		$before = $this->version();

		wp_set_object_terms( $this->post_id, $this->term_id, 'category' );

		$this->assertNotSame( $before, $this->version() );
	}

	public function test_a_draft_gaining_a_term_does_not_rotate(): void {
		$draft = self::factory()->post->create( [ 'post_status' => 'draft' ] );
		$added = did_action( 'added_term_relationship' );

		$rotations = $this->rotations(
			function () use ( $draft ) {
				wp_set_object_terms( $draft, $this->term_id, 'category', true );
				wp_remove_object_terms( $draft, $this->term_id, 'category' );
			}
		);

		$this->assertSame( $added + 1, did_action( 'added_term_relationship' ), 'the term was added' );
		$this->assertSame( 0, $rotations );
	}

	/**
	 * Core lets any object ID join any taxonomy. When the post's type does not use the taxonomy,
	 * the ID belongs to something else (a link, a user), so the cache treats the post as unchanged.
	 */
	public function test_a_taxonomy_the_post_type_does_not_use_does_not_rotate(): void {
		register_taxonomy( 'mai_page_only', 'page' );
		$term   = self::factory()->term->create( [ 'taxonomy' => 'mai_page_only' ] );
		$added  = did_action( 'added_term_relationship' );
		$before = $this->version();

		wp_set_object_terms( $this->post_id, $term, 'mai_page_only', true );

		$this->assertSame( $added + 1, did_action( 'added_term_relationship' ), 'the term was added' );
		$this->assertSame( $before, $this->version() );
	}

	public function test_deleting_a_tag_on_many_posts_rotates_once(): void {
		$tag   = self::factory()->term->create( [ 'taxonomy' => 'post_tag' ] );
		$posts = self::factory()->post->create_many( 5, [ 'post_status' => 'publish' ] );

		foreach ( $posts as $post ) {
			wp_set_object_terms( $post, $tag, 'post_tag' );
		}

		$this->assertSame( 1, $this->rotations( fn() => wp_delete_term( $tag, 'post_tag' ) ) );
	}

	/** Posts left with no category get the default one, which must not rotate per post either. */
	public function test_deleting_a_posts_only_category_rotates_once(): void {
		$posts = self::factory()->post->create_many( 5, [ 'post_status' => 'publish', 'post_category' => [ $this->term_id ] ] );

		$this->assertSame( 1, $this->rotations( fn() => wp_delete_term( $this->term_id, 'category' ) ) );
		$this->assertSame( [ (int) get_option( 'default_category' ) ], wp_get_post_categories( $posts[0] ) );
	}

	/**
	 * A grid on a parent that includes children matches posts in the child. Deleting the parent
	 * moves the child up without any post losing a term.
	 */
	public function test_deleting_a_parent_with_no_posts_of_its_own_rotates(): void {
		$parent = self::factory()->term->create( [ 'taxonomy' => 'category' ] );
		$child  = self::factory()->term->create( [ 'taxonomy' => 'category', 'parent' => $parent ] );
		wp_set_object_terms( $this->post_id, $child, 'category' );
		$before = $this->version();

		wp_delete_term( $parent, 'category' );

		$this->assertNotSame( $before, $this->version() );
	}

	public function test_a_later_removal_still_rotates_after_a_delete(): void {
		wp_delete_term( self::factory()->term->create( [ 'taxonomy' => 'category' ] ), 'category' );
		wp_set_object_terms( $this->post_id, $this->term_id, 'category', true );
		$before = $this->version();

		wp_remove_object_terms( $this->post_id, $this->term_id, 'category' );

		$this->assertNotSame( $before, $this->version() );
	}

	public function test_moving_a_term_to_a_new_parent_rotates_every_type_using_it(): void {
		register_taxonomy( 'mai_shared_tax', [ 'post', 'page' ], [ 'hierarchical' => true ] );
		$parent = self::factory()->term->create( [ 'taxonomy' => 'mai_shared_tax' ] );
		$child  = self::factory()->term->create( [ 'taxonomy' => 'mai_shared_tax' ] );
		$posts  = $this->version( 'post' );
		$pages  = $this->version( 'page' );

		wp_update_term( $child, 'mai_shared_tax', [ 'parent' => $parent ] );

		$this->assertNotSame( $posts, $this->version( 'post' ) );
		$this->assertNotSame( $pages, $this->version( 'page' ) );
	}

	public function test_renaming_a_term_does_not_rotate(): void {
		$before = $this->version();

		wp_update_term( $this->term_id, 'category', [ 'name' => 'Renamed' ] );

		$this->assertSame( $before, $this->version() );
	}
}
