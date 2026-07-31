<?php

namespace BizBudding\MaiEngine\Tests\Integration;

/**
 * Covers mai_render_block_handle_link_color() in lib/blocks/general.php.
 *
 * Two shipped regressions are locked here:
 *
 * BUG1, the guard. Introduced in 366b63788, fixed in d2a559cb2. A
 * str_contains( $block_content, 'has-link-color' ) guard early-returns on background and
 * overlay blocks, because has-link-background-color does not contain has-link-color.
 *
 * BUG2, the lost text rewrite. Present since d23afd367, fixed in 89250280f. The background
 * pass rebuilt the processor from a $block_content that had not absorbed the text pass, and
 * the return came from that processor.
 *
 * Four further tests pin behavior that is currently WRONG, so a fix shows up as a deliberate
 * diff rather than an accident. See F1 and F2 in
 * docs/superpowers/specs/2026-07-30-wordpress-phpunit-suite-design.md.
 *
 * Every expected string below was measured against the implementation, not written by hand.
 */
final class RenderBlockLinkColorTest extends MaiIntegrationTestCase {

	/**
	 * Always passes an attrs key. Without one the function emits three "Undefined array key"
	 * warnings, which failOnWarning turns into a test failure. render_block is a public
	 * filter, so third parties can call it without attrs; guarding the function itself is a
	 * recommended follow-up, not something these tests should paper over.
	 */
	private function render( string $html, array $attrs ): string {
		return mai_render_block_handle_link_color( $html, [ 'attrs' => $attrs ] );
	}

	// ---- Regression locks ----

	/** BUG1: a guard matching only has-link-color skips this entirely. */
	public function test_background_color_is_renamed(): void {
		$out = $this->render(
			'<div class="wp-block-group has-link-background-color"><p>x</p></div>',
			[ 'backgroundColor' => 'link' ]
		);

		$this->assertSame( '<div class="wp-block-group has-links-background-color"><p>x</p></div>', $out );
		$this->assertNoTagHasClass( $out, 'has-link-background-color' );
	}

	/** BUG1, and the only case that reaches the $overlay branch. */
	public function test_overlay_color_is_renamed(): void {
		$out = $this->render(
			'<div class="wp-block-cover has-link-background-color"><p>x</p></div>',
			[ 'overlayColor' => 'link' ]
		);

		$this->assertSame( '<div class="wp-block-cover has-links-background-color"><p>x</p></div>', $out );
		$this->assertNoTagHasClass( $out, 'has-link-background-color' );
	}

	/** BUG2: the background pass used to rebuild the processor and drop the text rewrite. */
	public function test_text_and_background_together_both_survive(): void {
		$out = $this->render(
			'<div class="has-link-color has-link-background-color">x</div>',
			[ 'textColor' => 'link', 'backgroundColor' => 'link' ]
		);

		$this->assertSame( '<div class="has-links-color has-links-background-color">x</div>', $out );
		$this->assertNoTagHasClass( $out, 'has-link-color' );
		$this->assertNoTagHasClass( $out, 'has-link-background-color' );
	}

	/** BUG2 through the second branch of the same condition. */
	public function test_text_and_overlay_together_both_survive(): void {
		$out = $this->render(
			'<div class="has-link-color has-link-background-color">x</div>',
			[ 'textColor' => 'link', 'overlayColor' => 'link' ]
		);

		$this->assertSame( '<div class="has-links-color has-links-background-color">x</div>', $out );
		$this->assertNoTagHasClass( $out, 'has-link-color' );
	}

	// ---- Characterization pins: these record DEFECTS, see F1 and F2 in the spec ----

	/**
	 * PINS F1. Each pass breaks after the first match, so a second highlight in the same
	 * paragraph keeps has-link-color, which WordPress 6.4+ reads as link element color. That
	 * is the exact collision this filter exists to prevent, and two highlights in a paragraph
	 * is ordinary content.
	 *
	 * Fixing it is not just deleting the break: the guard comment notes this filter runs for
	 * every block on every page and that an unmatched next_tag() walks the whole subtree, so
	 * removing the early exit needs its own performance measurement.
	 */
	public function test_only_the_first_mark_is_renamed(): void {
		$out = $this->render(
			'<p><mark class="has-link-color">one</mark> and <mark class="has-link-color">two</mark></p>',
			[]
		);

		$this->assertSame(
			'<p><mark class="has-links-color">one</mark> and <mark class="has-link-color">two</mark></p>',
			$out
		);
	}

	/** PINS F1, text pass. */
	public function test_only_the_first_text_element_is_renamed(): void {
		$out = $this->render(
			'<div class="has-link-color"><span class="has-link-color">x</span></div>',
			[ 'textColor' => 'link' ]
		);

		$this->assertSame( '<div class="has-links-color"><span class="has-link-color">x</span></div>', $out );
	}

	/** PINS F1, background pass. */
	public function test_only_the_first_background_element_is_renamed(): void {
		$out = $this->render(
			'<div class="has-link-background-color"><span class="has-link-background-color">x</span></div>',
			[ 'backgroundColor' => 'link' ]
		);

		$this->assertSame(
			'<div class="has-links-background-color"><span class="has-link-background-color">x</span></div>',
			$out
		);
	}

	/**
	 * PINS F2. The implementation splits classes on the space character with explode( ' ' );
	 * the HTML API splits on all ASCII whitespace. So next_tag() matches, the unset() misses,
	 * and the output carries both the old and new class.
	 */
	public function test_tab_separated_class_list_keeps_both_classes(): void {
		$out = $this->render( "<p class=\"has-text-color\thas-link-color\">x</p>", [ 'textColor' => 'link' ] );

		$this->assertSame( "<p class=\"has-text-color\thas-link-color has-links-color\">x</p>", $out );
	}

	// ---- No-op and guard coverage ----

	/** The guard is a raw substring check, so text content can satisfy it with nothing to match. */
	public function test_has_link_in_text_content_is_left_alone(): void {
		$html = '<p>the has-link-color class</p>';

		$this->assertSame( $html, $this->render( $html, [ 'textColor' => 'link' ] ) );
	}

	/** Guard passes on the background class, but textColor finds no matching element. */
	public function test_text_color_with_no_matching_element_is_unchanged(): void {
		$html = '<p class="has-link-background-color">x</p>';

		$this->assertSame( $html, $this->render( $html, [ 'textColor' => 'link' ] ) );
	}

	/** Early return: no has-link- substring at all. */
	public function test_content_without_link_classes_passes_through(): void {
		$html = '<p class="has-text-color">x</p>';

		$this->assertSame( $html, $this->render( $html, [ 'textColor' => 'link' ] ) );
	}

	/** The mark pass, text pass and background pass all applying to one block. */
	public function test_mark_text_and_background_all_apply(): void {
		$out = $this->render(
			'<div class="has-link-color has-link-background-color"><mark class="has-link-color">m</mark></div>',
			[ 'textColor' => 'link', 'backgroundColor' => 'link' ]
		);

		$this->assertSame(
			'<div class="has-links-color has-links-background-color"><mark class="has-links-color">m</mark></div>',
			$out
		);
	}
}
