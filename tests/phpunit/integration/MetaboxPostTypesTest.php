<?php

namespace BizBudding\MaiEngine\Tests\Integration;

/**
 * mai_get_metabox_post_types() and its `mai_metabox_post_types` filter, which decide the
 * post types that get Mai's Hide Elements and Page Header metaboxes.
 */
class MetaboxPostTypesTest extends MaiIntegrationTestCase {

	/**
	 * Registers a public custom post type.
	 */
	public function set_up(): void {
		parent::set_up();

		register_post_type( 'mai_test_cpt', [ 'public' => true ] );
	}

	/**
	 * Removes the test post type.
	 */
	public function tear_down(): void {
		unregister_post_type( 'mai_test_cpt' );

		parent::tear_down();
	}

	/**
	 * Checks whether Hide Elements shows on a post type's edit screen.
	 *
	 * @param string $post_type The post type.
	 *
	 * @return bool
	 */
	private function shows_on( string $post_type ): bool {
		return mai_acf_public_post_type_rule_match( false, [], [ 'post_type' => $post_type ], [] );
	}

	public function test_public_post_types_get_hide_elements(): void {
		$this->assertTrue( $this->shows_on( 'post' ) );
		$this->assertTrue( $this->shows_on( 'mai_test_cpt' ) );
	}

	public function test_non_public_post_types_and_screens_without_one_do_not(): void {
		$this->assertFalse( $this->shows_on( 'wp_block' ) );
		$this->assertFalse( mai_acf_public_post_type_rule_match( true, [], [], [] ) );
	}

	public function test_the_filter_gets_the_list_and_the_metabox_name(): void {
		$seen = [];

		add_filter(
			'mai_metabox_post_types',
			function ( $post_types, $metabox ) use ( &$seen ) {
				$seen = [ $post_types, $metabox ];

				return $post_types;
			},
			10,
			2
		);

		$this->shows_on( 'post' );

		$this->assertContains( 'mai_test_cpt', $seen[0] );
		$this->assertSame( 'hide-elements', $seen[1] );
	}

	public function test_a_removed_post_type_loses_hide_elements(): void {
		add_filter( 'mai_metabox_post_types', fn( $post_types ) => array_diff( $post_types, [ 'mai_test_cpt' ] ) );

		$this->assertFalse( $this->shows_on( 'mai_test_cpt' ) );
		$this->assertTrue( $this->shows_on( 'post' ) );
	}

	public function test_an_added_post_type_gets_hide_elements(): void {
		add_filter( 'mai_metabox_post_types', fn( $post_types ) => array_merge( $post_types, [ 'wp_block' ] ) );

		$this->assertTrue( $this->shows_on( 'wp_block' ) );
	}

	public function test_a_bad_filter_value_shows_it_nowhere(): void {
		add_filter( 'mai_metabox_post_types', '__return_false' );

		$this->assertFalse( $this->shows_on( 'post' ) );
	}

	public function test_page_header_starts_from_its_single_types(): void {
		$this->assertSame( array_values( array_unique( mai_get_page_header_types( 'single' ) ) ), mai_get_metabox_post_types( 'page-header' ) );
	}

	public function test_the_filter_can_change_page_header_only(): void {
		add_filter(
			'mai_metabox_post_types',
			fn( $post_types, $metabox ) => 'page-header' === $metabox ? array_diff( $post_types, [ 'post' ] ) : $post_types,
			10,
			2
		);

		$this->assertContains( 'post', mai_get_page_header_types( 'single' ) );
		$this->assertNotContains( 'post', mai_get_metabox_post_types( 'page-header' ) );
		$this->assertTrue( $this->shows_on( 'post' ) );
	}

	public function test_the_result_keeps_unique_strings_only(): void {
		add_filter( 'mai_metabox_post_types', fn() => [ 'post', 'post', 5, null, 'page' ] );

		$this->assertSame( [ 'post', 'page' ], mai_get_metabox_post_types( 'hide-elements' ) );
	}

	public function test_an_unknown_metabox_starts_empty(): void {
		$this->assertSame( [], mai_get_metabox_post_types( 'nope' ) );
	}
}
