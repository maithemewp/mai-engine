<?php

namespace BizBudding\MaiEngine\Tests\Integration;

/**
 * Covers the paragraph, heading, group and button render_block filters.
 *
 * Expected values were measured against the DOMDocument implementation before the
 * conversion. Assertions are about parsed meaning (class tokens, style declarations),
 * with a few byte checks that untouched markup survives.
 */
final class BlockFiltersTagProcessorTest extends MaiIntegrationTestCase {

	private function block( string $name, array $attrs ): array {
		return [ 'blockName' => $name, 'attrs' => $attrs ];
	}

	// ---- Paragraph and heading ----

	public function test_paragraph_start_and_end(): void {
		$html = '<p class="has-text-align-left">Hello there</p>';

		$out = mai_render_paragraph_block( $html, $this->block( 'core/paragraph', [ 'contentAlign' => 'start' ] ) );
		$this->assertContains( 'has-no-margin-left', $this->tagClasses( $out, 'p' ) );
		$this->assertContains( 'has-text-align-left', $this->tagClasses( $out, 'p' ) );

		$out = mai_render_paragraph_block( $html, $this->block( 'core/paragraph', [ 'contentAlign' => 'end' ] ) );
		$this->assertContains( 'has-no-margin-right', $this->tagClasses( $out, 'p' ) );
	}

	public function test_paragraph_without_class_attribute(): void {
		$out = mai_render_paragraph_block( '<p>Hello there</p>', $this->block( 'core/paragraph', [ 'contentAlign' => 'start' ] ) );
		$this->assertSame( [ 'has-no-margin-left' ], $this->tagClasses( $out, 'p' ) );
	}

	public function test_paragraph_center_is_untouched(): void {
		$html = '<p class="x">Hello there</p>';
		$this->assertSame( $html, mai_render_paragraph_block( $html, $this->block( 'core/paragraph', [ 'contentAlign' => 'center' ] ) ) );
	}

	public function test_heading_start_end_center(): void {
		$html = '<h2 class="wp-block-heading">Hello</h2>';

		$out = mai_render_heading_block( $html, $this->block( 'core/heading', [ 'contentAlign' => 'start' ] ) );
		$this->assertContains( 'has-no-margin-left', $this->tagClasses( $out, 'h2' ) );
		$this->assertContains( 'wp-block-heading', $this->tagClasses( $out, 'h2' ) );

		$out = mai_render_heading_block( $html, $this->block( 'core/heading', [ 'contentAlign' => 'end' ] ) );
		$this->assertContains( 'has-no-margin-right', $this->tagClasses( $out, 'h2' ) );

		$this->assertSame( $html, mai_render_heading_block( $html, $this->block( 'core/heading', [ 'contentAlign' => 'center' ] ) ) );
	}

	// ---- Group ----

	public function test_group_align_and_light_background(): void {
		$html = '<div class="wp-block-group" style="padding:1px"><p>x</p></div>';
		$out  = mai_render_group_block( $html, $this->block( 'core/group', [ 'contentAlign' => 'center', 'customBackgroundColor' => '#ffffff' ] ) );

		$this->assertSame(
			[ '--group-block-justify-content:' . mai_get_flex_align( 'center' ), 'padding:1px' ],
			$this->tagStyles( $out, 'div' )
		);
		$this->assertContains( 'has-light-background', $this->tagClasses( $out, 'div' ) );
		$this->assertContains( 'wp-block-group', $this->tagClasses( $out, 'div' ) );
	}

	public function test_group_dark_background(): void {
		$out = mai_render_group_block( '<div class="wp-block-group"><p>x</p></div>', $this->block( 'core/group', [ 'customBackgroundColor' => '#111111' ] ) );

		$this->assertContains( 'has-dark-background', $this->tagClasses( $out, 'div' ) );
		$this->assertSame( [], $this->tagStyles( $out, 'div' ) );
	}

	public function test_group_inner_svg_survives_byte_for_byte(): void {
		$svg  = '<svg viewBox="0 0 1 1"><path d="M0"/></svg>';
		$out  = mai_render_group_block( '<div class="wp-block-group">' . $svg . '</div>', $this->block( 'core/group', [ 'contentAlign' => 'center' ] ) );

		$this->assertStringContainsString( $svg, $out );
	}

	public function test_group_valueless_style_is_treated_as_empty(): void {
		$out = mai_render_group_block( '<div class="wp-block-group" style><p>x</p></div>', $this->block( 'core/group', [ 'contentAlign' => 'center' ] ) );

		$styles = $this->tagStyles( $out, 'div' );

		$this->assertSame( [ '--group-block-justify-content:' . mai_get_flex_align( 'center' ) ], $styles );
	}

	// ---- Button ----

	public function test_button_legacy_attributes(): void {
		$html  = '<div class="wp-block-button button-small" style="color:red;"><a class="wp-block-button__link has-text-color has-background has-red-color has-blue-background-color has-blue-background wp-element-button" href="#">Go</a></div>';
		$block = $this->block( 'core/button', [
			'className'       => 'button-small',
			'textColor'       => 'red',
			'backgroundColor' => 'blue',
			'style'           => [ 'border' => [ 'radius' => '4px' ] ],
		] );
		$out   = mai_render_button_block( $html, $block );

		$this->assertSame(
			[ 'color:red', '--button-color:red', '--button-background:blue', '--button-background-hover:#0000cc', '--button-border-radius:4px' ],
			$this->tagStyles( $out, 'div' )
		);

		$classes = $this->tagClasses( $out, 'a' );
		$this->assertContains( 'button-small', $classes );
		$this->assertContains( 'button', $classes );
		$this->assertContains( 'wp-element-button', $classes );

		foreach ( [ 'has-text-color', 'has-background', 'has-red-color', 'has-blue-background-color', 'has-blue-background' ] as $gone ) {
			$this->assertNotContains( $gone, $classes );
		}
	}

	public function test_button_style_color_attributes_keep_slug_classes(): void {
		$html  = '<div class="wp-block-button"><a class="wp-block-button__link has-red-color has-blue-background-color" href="#">Go</a></div>';
		$block = $this->block( 'core/button', [ 'style' => [ 'color' => [ 'text' => '#ff0000', 'background' => '#0000ff' ] ] ] );
		$out   = mai_render_button_block( $html, $block );

		$classes = $this->tagClasses( $out, 'a' );
		$this->assertContains( 'has-red-color', $classes );
		$this->assertContains( 'has-blue-background-color', $classes );
		$this->assertSame( '--button-color:#ff0000', $this->tagStyles( $out, 'div' )[0] );
	}

	public function test_button_removes_color_classes_as_whole_names(): void {
		$html  = '<div class="wp-block-button"><a class="wp-block-button__link has-background-blur has-background" href="#">Go</a></div>';
		$block = $this->block( 'core/button', [ 'style' => [ 'border' => [ 'radius' => '4px' ] ] ] );
		$out   = mai_render_button_block( $html, $block );

		$classes = $this->tagClasses( $out, 'a' );
		$this->assertContains( 'has-background-blur', $classes );
		$this->assertNotContains( 'has-background', $classes );
	}

	public function test_button_updates_every_div_and_a(): void {
		$html  = '<div class="wp-block-buttons"><div class="wp-block-button"><a class="wp-block-button__link has-background" href="#">A</a></div><div class="wp-block-button"><a class="wp-block-button__link has-background" href="#">B</a></div></div>';
		$block = $this->block( 'core/button', [ 'style' => [ 'border' => [ 'radius' => '4px' ] ] ] );
		$out   = mai_render_button_block( $html, $block );

		for ( $nth = 1; $nth <= 3; $nth++ ) {
			$this->assertSame( [ '--button-border-radius:4px' ], $this->tagStyles( $out, 'div', $nth ), "div $nth" );
		}

		for ( $nth = 1; $nth <= 2; $nth++ ) {
			$this->assertNotContains( 'has-background', $this->tagClasses( $out, 'a', $nth ), "a $nth" );
		}
	}

	// ---- No tag ----

	/**
	 * @dataProvider noTagProvider
	 */
	public function test_no_tag_input_is_returned_unchanged( string $html ): void {
		$this->assertSame( $html, mai_render_paragraph_block( $html, $this->block( 'core/paragraph', [ 'contentAlign' => 'start' ] ) ) );
		$this->assertSame( $html, mai_render_heading_block( $html, $this->block( 'core/heading', [ 'contentAlign' => 'start' ] ) ) );
		$this->assertSame( $html, mai_render_group_block( $html, $this->block( 'core/group', [ 'contentAlign' => 'center', 'customBackgroundColor' => '#ffffff' ] ) ) );
		$this->assertSame( $html, mai_render_button_block( $html, $this->block( 'core/button', [ 'style' => [ 'border' => [ 'radius' => '4px' ] ] ] ) ) );
	}

	public static function noTagProvider(): array {
		return [
			'empty'   => [ '' ],
			'text'    => [ 'text' ],
			'comment' => [ '<!-- just a comment -->' ],
		];
	}
}
