<?php

namespace BizBudding\MaiEngine\Tests\Integration;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;

/**
 * mai_get_archive_post_type(), which names the post type of a post type archive.
 *
 * WooCommerce 11+ sets the shop page (a WP_Post) as the queried object on the product
 * archive. Before, the archive name fell back to `post`, which always has Mai's loop, so
 * the shop showed every product twice (issue #673). Older WooCommerce keeps the post type
 * as the queried object, and that must keep working too.
 */
class ArchivePostTypeTest extends MaiIntegrationTestCase {

	/**
	 * Registers a post type with an archive, standing in for WooCommerce's `product`.
	 */
	public function set_up(): void {
		parent::set_up();

		register_post_type(
			'mai_test_product',
			[
				'public'      => true,
				'has_archive' => true,
			]
		);
	}

	/**
	 * Removes the test post type.
	 */
	public function tear_down(): void {
		unregister_post_type( 'mai_test_product' );

		parent::tear_down();
	}

	/**
	 * Visits the test archive with a page as the queried object, as WooCommerce 11+ does.
	 *
	 * @return void
	 */
	private function go_to_shop_with_page_object(): void {
		$this->go_to( home_url( '/?post_type=mai_test_product' ) );

		$this->set_queried_object( self::factory()->post->create_and_get( [ 'post_type' => 'page' ] ) );
	}

	/**
	 * Sets the queried object on both query globals.
	 *
	 * @param object $object The queried object.
	 *
	 * @return void
	 */
	private function set_queried_object( object $object ): void {
		$GLOBALS['wp_query']->queried_object    = $object;
		$GLOBALS['wp_query']->queried_object_id = $object->ID ?? 0;
	}

	public function test_a_post_type_object_gives_its_name(): void {
		$this->go_to( home_url( '/?post_type=mai_test_product' ) );

		$this->assertInstanceOf( 'WP_Post_Type', get_queried_object() );
		$this->assertSame( 'mai_test_product', mai_get_archive_post_type() );
		$this->assertSame( 'mai_test_product', mai_get_post_type() );
	}

	public function test_a_page_object_gives_the_query_var(): void {
		$this->go_to_shop_with_page_object();

		$this->assertInstanceOf( 'WP_Post', get_queried_object() );
		$this->assertSame( 'mai_test_product', mai_get_archive_post_type() );
		$this->assertSame( 'mai_test_product', mai_get_post_type() );
	}

	public function test_a_post_type_object_wins_over_an_array_query_var(): void {
		$this->go_to( home_url( '/?post_type=mai_test_product' ) );
		set_query_var( 'post_type', [ 'post', 'mai_test_product' ] );

		$this->assertSame( 'mai_test_product', mai_get_archive_post_type() );
	}

	public function test_a_page_object_with_an_array_query_var_gives_the_first(): void {
		$this->go_to_shop_with_page_object();
		set_query_var( 'post_type', [ 'mai_test_product', 'post' ] );

		$this->assertSame( 'mai_test_product', mai_get_archive_post_type() );
	}

	/**
	 * The bug itself. Its own process, because the archive name and the loop check are cached
	 * in static variables for the rest of the request.
	 */
	#[RunInSeparateProcess]
	public function test_a_page_object_does_not_fall_back_to_the_post_loop(): void {
		$GLOBALS['mai_test_config'] = [
			'settings' => [
				'content-archives' => [ 'enable' => [ 'post' ] ],
			],
		];

		$this->go_to_shop_with_page_object();

		$this->assertSame( 'mai_test_product', mai_get_archive_args_name() );
		$this->assertFalse( mai_has_custom_loop(), 'a post type without archive settings keeps its own loop' );
	}
}
