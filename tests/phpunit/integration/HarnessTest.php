<?php

namespace BizBudding\MaiEngine\Tests\Integration;


/**
 * Verifies the harness itself: WordPress boots, the plugin loader ran, and the database is
 * configured for the content this plugin handles. Kept separate from feature tests so a
 * broken harness fails with an obvious cause.
 */
final class HarnessTest extends MaiIntegrationTestCase {

	public function test_wordpress_is_loaded(): void {
		$this->assertTrue( class_exists( 'WP_HTML_Tag_Processor' ) );
		$this->assertTrue( function_exists( 'mai_isset' ) );
	}

	public function test_plugin_loader_registered_the_filter(): void {
		$this->assertNotFalse( has_filter( 'render_block', 'mai_render_block_handle_link_color' ) );
	}

	public function test_factories_and_utf8mb4(): void {
		$id = self::factory()->post->create( [ 'post_title' => 'Zażółć gęślą 🎉' ] );
		$this->assertSame( 'Zażółć gęślą 🎉', get_post( $id )->post_title );
	}

	public function test_grid_classes_are_available(): void {
		$this->assertTrue( class_exists( 'Mai_Grid' ), 'plugin-loader.php must register the Mai_* autoloader' );
		$this->assertTrue( class_exists( 'Mai_Query_Cache' ) );
		$this->assertTrue( function_exists( 'mai_get_wp_query_defaults' ), 'lib/fields/wp-query.php must be loaded' );
	}

	public function test_query_cache_is_hooked(): void {
		// query-cache.php hooks on init, and init has already fired by the time a test runs,
		// so this only passes if plugin-loader.php loaded it on muplugins_loaded.
		//
		// Assert on the callback, not just on has_filter(). Core hooks the_posts itself, so a
		// bare has_filter( 'the_posts' ) is green even when the cache never loaded.
		$cache = null;

		foreach ( $GLOBALS['wp_filter']['posts_pre_query']->callbacks ?? [] as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				if ( is_array( $callback['function'] ) && $callback['function'][0] instanceof \Mai_Query_Cache ) {
					$cache = $callback['function'][0];
				}
			}
		}

		$this->assertInstanceOf( \Mai_Query_Cache::class, $cache, 'Mai_Query_Cache is not on posts_pre_query' );
	}

	public function test_a_grid_can_be_built(): void {
		$term = self::factory()->term->create( [ 'taxonomy' => 'category' ] );
		self::factory()->post->create( [ 'post_status' => 'publish', 'post_category' => [ $term ] ] );

		$grid = new \Mai_Grid( [
			'type'           => 'post',
			'post_type'      => [ 'post' ],
			'query_by'       => 'tax_meta',
			'posts_per_page' => 3,
			'taxonomies'     => [
				[ 'taxonomy' => 'category', 'terms' => [ $term ], 'current' => false, 'operator' => 'IN' ],
			],
		] );

		$this->assertInstanceOf( \WP_Query::class, $grid->get_query() );
	}
}
