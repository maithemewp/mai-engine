<?php

namespace BizBudding\MaiEngine\Tests\Integration;

/**
 * Covers the post terms and gallery shortcode filters, and the WooCommerce product loop start.
 */
final class ShortcodesTagProcessorTest extends MaiIntegrationTestCase {

	private const GALLERY = '<div id="gallery-1" class="gallery galleryid-1 gallery-columns-4 gallery-size-thumbnail"><dl class="gallery-item"></dl></div>';

	public function tear_down() {
		unset( $GLOBALS['mai_test_wc_loop_columns'] );
		parent::tear_down();
	}

	// ---- Post terms ----

	public function test_post_terms_gains_taxonomy_class(): void {
		$out = mai_post_terms_shortcode_classes( '<span class="entry-terms">Cat</span>', [], [ 'taxonomy' => 'category' ] );

		$this->assertEqualsCanonicalizing( [ 'entry-terms', 'entry-terms-category' ], $this->tagClasses( $out, 'span' ) );
		$this->assertStringContainsString( '>Cat</span>', $out );
	}

	public function test_post_terms_missing_or_empty_taxonomy_is_unchanged(): void {
		$html = '<span class="entry-terms">Cat</span>';

		$this->assertSame( $html, mai_post_terms_shortcode_classes( $html, [], [] ) );
		$this->assertSame( $html, mai_post_terms_shortcode_classes( $html, [], [ 'taxonomy' => '' ] ) );
	}

	public function test_post_terms_no_matching_tag(): void {
		foreach ( [ '', 'plain text', '<!-- c -->' ] as $input ) {
			$this->assertSame( $input, mai_post_terms_shortcode_classes( $input, [], [ 'taxonomy' => 'category' ] ) );
		}
	}

	// ---- Gallery ----

	public function test_gallery_breakpoint_custom_properties(): void {
		$out    = mai_gallery_shortcode_tag( self::GALLERY, 'gallery', [ 'columns' => 4 ], [] );
		$styles = $this->tagStyles( $out, 'div' );

		$this->assertSame(
			[ '--gallery-columns-xs:1', '--gallery-columns-sm:2', '--gallery-columns-md:4', '--gallery-columns-lg:4' ],
			array_slice( $styles, -4 )
		);
		$this->assertStringContainsString( '<dl class="gallery-item"></dl>', $out );
	}

	public function test_gallery_existing_style_without_semicolon(): void {
		$html = '<div class="gallery" style="color:red"><dl></dl></div>';
		$out  = mai_gallery_shortcode_tag( $html, 'gallery', [ 'columns' => 4 ], [] );

		$styles = $this->tagStyles( $out, 'div' );

		$this->assertSame( 'color:red', $styles[0] );
		$this->assertSame( '--gallery-columns-xs:1', $styles[1] );
	}

	public function test_gallery_other_tag_or_type_is_unchanged(): void {
		$this->assertSame( self::GALLERY, mai_gallery_shortcode_tag( self::GALLERY, 'caption', [ 'columns' => 4 ], [] ) );
		$this->assertSame( self::GALLERY, mai_gallery_shortcode_tag( self::GALLERY, 'gallery', [ 'type' => 'rectangular' ], [] ) );
	}

	public function test_gallery_no_matching_tag(): void {
		foreach ( [ '', 'plain text', '<!-- c -->' ] as $input ) {
			$this->assertSame( $input, mai_gallery_shortcode_tag( $input, 'gallery', [ 'columns' => 4 ], [] ) );
		}
	}

	// ---- WooCommerce loop start ----

	public function test_product_loop_start_sets_columns_and_stays_open(): void {
		$GLOBALS['mai_test_wc_loop_columns'] = 4;

		$out = mai_product_loop_start_columns( '<ul class="products columns-4">' );

		$this->assertSame(
			[ '--columns-xs:1', '--columns-sm:2', '--columns-md:4', '--columns-lg:4' ],
			$this->tagStyles( $out, 'ul' )
		);
		$this->assertContains( 'products', $this->tagClasses( $out, 'ul' ) );
		$this->assertStringNotContainsString( '</ul>', $out );
		$this->assertStringStartsWith( '<ul', $out );
	}

	public function test_product_loop_start_non_numeric_columns_is_unchanged(): void {
		$GLOBALS['mai_test_wc_loop_columns'] = '';
		$html = '<ul class="products columns-4">';

		$this->assertSame( $html, mai_product_loop_start_columns( $html ) );
	}

	public function test_product_loop_start_no_matching_tag(): void {
		$GLOBALS['mai_test_wc_loop_columns'] = 4;

		foreach ( [ '', 'plain text' ] as $input ) {
			$this->assertSame( $input, mai_product_loop_start_columns( $input ) );
		}
	}
}
