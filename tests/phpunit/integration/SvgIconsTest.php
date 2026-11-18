<?php

namespace BizBudding\MaiEngine\Tests\Integration;

use WP_HTML_Tag_Processor;

/**
 * Covers mai_get_svg() and mai_get_svg_icon(). Both cache files in statics, so
 * every test uses its own file name.
 */
final class SvgIconsTest extends MaiIntegrationTestCase {

	/**
	 * Returns an attribute of the first svg, or null when absent.
	 */
	private function svgAttr( string $html, string $name ): ?string {
		$tags = new WP_HTML_Tag_Processor( $html );

		if ( ! $tags->next_tag( 'svg' ) ) {
			return null;
		}

		$value = $tags->get_attribute( $name );

		return is_string( $value ) ? $value : null;
	}

	public function test_get_svg_adds_classes_and_keeps_markup_intact(): void {
		$raw = trim( (string) file_get_contents( mai_get_dir() . 'assets/svg/divider-wave.svg' ) );
		$out = mai_get_svg( 'divider-wave', 'a  b' );

		$this->assertSame( [ 'a', 'b' ], $this->tagClasses( $out, 'svg' ) );
		$this->assertStringContainsString( 'viewBox="0 0 1000 120"', $out );
		$this->assertStringContainsString( 'Z"/></svg>', $out );
		$this->assertSame( str_replace( '<svg ', '<svg class="a b" ', $raw ), $out );
	}

	public function test_get_svg_without_class_returns_file_unchanged(): void {
		$raw = trim( (string) file_get_contents( mai_get_dir() . 'assets/svg/divider-curve.svg' ) );

		$this->assertSame( $raw, mai_get_svg( 'divider-curve' ) );
	}

	public function test_get_svg_missing_file_returns_empty_string(): void {
		$this->assertSame( '', mai_get_svg( 'no-such-svg-file', 'x' ) );
	}

	public function test_get_svg_icon_sets_default_size_and_keeps_markup(): void {
		$out = mai_get_svg_icon( 'test-size', 'light' );

		$this->assertSame( '24', $this->svgAttr( $out, 'width' ) );
		$this->assertSame( '24', $this->svgAttr( $out, 'height' ) );
		$this->assertSame( '0 0 576 512', $this->svgAttr( $out, 'viewBox' ) );
		$this->assertStringContainsString( '<!--! Font Awesome Pro -->', $out );
		$this->assertStringContainsString( 'z"/></svg>', $out );
	}

	public function test_get_svg_icon_passed_width_wins(): void {
		$out = mai_get_svg_icon( 'test-override', 'light', [ 'width' => '1em' ] );

		$this->assertSame( '1em', $this->svgAttr( $out, 'width' ) );
		$this->assertSame( '24', $this->svgAttr( $out, 'height' ) );
	}

	public function test_get_svg_icon_class_replaces_existing_class(): void {
		$out = mai_get_svg_icon( 'test-class', 'light', [ 'class' => 'x' ] );

		$this->assertSame( [ 'x' ], $this->tagClasses( $out, 'svg' ) );
	}

	public function test_get_svg_icon_missing_file_returns_empty_string(): void {
		$this->assertSame( '', mai_get_svg_icon( 'no-such-icon', 'light' ) );
	}

	public function test_get_svg_icon_without_svg_tag_returns_contents_unchanged(): void {
		$this->assertSame( '<!-- just a comment -->', mai_get_svg_icon( 'test-nosvg', 'light' ) );
	}
}
