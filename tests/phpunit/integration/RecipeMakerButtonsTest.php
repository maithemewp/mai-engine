<?php

namespace BizBudding\MaiEngine\Tests\Integration;

use WP_HTML_Tag_Processor;

/**
 * Covers the WP Recipe Maker button conversion, in particular the SVG fill recolor.
 */
final class RecipeMakerButtonsTest extends MaiIntegrationTestCase {

	/**
	 * Returns the fill of every svg, g and path opener, in document order, as
	 * [ 'tag', fill ] pairs. A missing fill is null.
	 */
	private function fills( string $html ): array {
		$tags = new WP_HTML_Tag_Processor( $html );
		$out  = [];

		while ( $tags->next_tag() ) {
			$tag = strtolower( $tags->get_tag() );

			if ( ! in_array( $tag, [ 'svg', 'g', 'path' ], true ) ) {
				continue;
			}

			$fill  = $tags->get_attribute( 'fill' );
			$out[] = [ $tag, is_string( $fill ) ? $fill : null ];
		}

		return $out;
	}

	public function test_button_svg_fills_are_recolored(): void {
		$html = '<a class="wprm-recipe-print-button"><svg fill="#333"><g fill="none"><path fill="#333"/></g><path/></svg></a>';
		$out  = mai_wprm_convert_buttons( $html );

		$this->assertSame(
			[
				[ 'svg', 'currentColor' ],
				[ 'g', 'none' ],
				[ 'path', 'currentColor' ],
				[ 'path', null ],
			],
			$this->fills( $out )
		);
	}

	public function test_svg_without_fill_stays_without_fill(): void {
		$html = '<a class="wprm-recipe-print-button"><svg><path fill="#111"/></svg></a>';

		$this->assertSame(
			[ [ 'svg', null ], [ 'path', 'currentColor' ] ],
			$this->fills( mai_wprm_convert_buttons( $html ) )
		);
	}

	public function test_svgs_outside_buttons_are_unchanged(): void {
		$html = '<a class="plain-link" href="#"><svg fill="#222"><path fill="#222"/></svg></a><svg fill="#444"><path fill="#444"/></svg>';

		$this->assertSame(
			[ [ 'svg', '#222' ], [ 'path', '#222' ], [ 'svg', '#444' ], [ 'path', '#444' ] ],
			$this->fills( mai_wprm_convert_buttons( $html ) )
		);
	}

	public function test_self_closing_svg_does_not_leak_to_following_markup(): void {
		$html = '<a class="wprm-recipe-print-button"><svg fill="#000"/></a><p><svg><path fill="#111"/></svg></p>';

		$this->assertSame(
			[ [ 'svg', 'currentColor' ], [ 'svg', null ], [ 'path', '#111' ] ],
			$this->fills( mai_wprm_convert_buttons( $html ) )
		);
	}

	public function test_self_closing_svg_followed_by_markup_inside_the_same_button(): void {
		$html = '<a class="wprm-recipe-print-button"><svg fill="#000"/><path fill="#111"/></a><svg></svg>';

		$this->assertSame(
			[ [ 'svg', 'currentColor' ], [ 'path', '#111' ], [ 'svg', null ] ],
			$this->fills( mai_wprm_convert_buttons( $html ) )
		);
	}

	public function test_unclosed_svg_does_not_carry_into_the_next_button(): void {
		$html = '<a class="wprm-recipe-print-button"><svg fill="#1"><path fill="#2"/></a>'
			. '<a class="wprm-recipe-pin-button"><path fill="#3"/><svg fill="#4"><path fill="#5"/></svg></a>';

		$this->assertSame(
			[
				[ 'svg', 'currentColor' ],
				[ 'path', 'currentColor' ],
				[ 'path', '#3' ],
				[ 'svg', 'currentColor' ],
				[ 'path', 'currentColor' ],
			],
			$this->fills( mai_wprm_convert_buttons( $html ) )
		);
	}

	public function test_input_without_closing_svg_gets_no_fill_changes(): void {
		$html = '<a class="wprm-recipe-print-button"><svg fill="#000"/></a>';
		$out  = mai_wprm_convert_buttons( $html );

		$this->assertSame( [ [ 'svg', '#000' ] ], $this->fills( $out ) );
	}

	public function test_text_survives_byte_for_byte(): void {
		$html = '<p>Salt &amp; pepper ’ done</p><a class="wprm-recipe-print-button"><svg fill="#000"><path fill="#000"/></svg></a>';
		$out  = mai_wprm_convert_buttons( $html );

		$this->assertStringStartsWith( '<p>Salt &amp; pepper ’ done</p>', $out );
	}

	public function test_html_without_tags_is_returned_unchanged(): void {
		foreach ( [ '', 'Just text', '<!-- note -->' ] as $html ) {
			$this->assertSame( $html, mai_wprm_convert_buttons( $html ) );
		}
	}
}
