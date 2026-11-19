<?php

namespace BizBudding\MaiEngine\Tests\Integration;

use WP_Styles;

/**
 * Covers mai_remove_legacy_block_css() in lib/functions/enqueue.php, against the installed
 * WordPress's own `block-library/common.css`.
 *
 * This fails when a new WordPress renames the legacy rules, moves its inlining off
 * `wp_head` at 1, or prints styles somewhere other than 8. Any of those is the signal to
 * read core again.
 */
final class LegacyBlockCssTest extends MaiIntegrationTestCase {

	/**
	 * The styles registry before the test, put back afterwards.
	 *
	 * @var WP_Styles|null
	 */
	private $saved_styles;

	public function set_up() {
		parent::set_up();

		$this->saved_styles = $GLOBALS['wp_styles'] ?? null;

		// Gives wp-block-library core's common.css path, as a classic theme gets on WP 6.9+.
		add_filter( 'should_load_separate_core_block_assets', '__return_true' );
		$GLOBALS['wp_styles'] = null;
	}

	public function tear_down() {
		$GLOBALS['wp_styles'] = $this->saved_styles;

		parent::tear_down();
	}

	/**
	 * Inlines wp-block-library the way core does on wp_head at 1, then runs Mai's hook.
	 *
	 * @return string The inline CSS core would print.
	 */
	private function inline_block_library_css(): string {
		wp_enqueue_style( 'wp-block-library' );
		wp_maybe_inline_styles();
		mai_remove_legacy_block_css();

		return implode( '', wp_styles()->registered['wp-block-library']->extra['after'] );
	}

	public function test_hook_runs_after_core_inlines_and_before_it_prints(): void {
		$this->assertSame( 1, has_action( 'wp_head', 'wp_maybe_inline_styles' ) );
		$this->assertSame( 2, has_action( 'wp_head', 'mai_remove_legacy_block_css' ) );
		$this->assertSame( 8, has_action( 'wp_head', 'wp_print_styles' ) );
	}

	public function test_inline_copy_loses_the_legacy_rules_and_keeps_the_rest(): void {
		$path = wp_styles()->get_data( 'wp-block-library', 'path' );

		$this->assertIsString( $path );
		$this->assertMatchesRegularExpression( '~/block-library/common(\.min)?\.css$~', $path );

		$original = (string) file_get_contents( $path );
		$css      = $this->inline_block_library_css();

		$this->assertStringContainsString( 'has-huge-font-size', $original, 'Core still ships the rules.' );
		$this->assertStringNotContainsString( 'has-vivid-green-cyan-to-vivid-cyan-blue-gradient-background', $css );
		$this->assertStringNotContainsString( 'has-midnight-gradient-background', $css );
		$this->assertStringNotContainsString( 'has-very-light-gray-background-color', $css );
		$this->assertStringNotContainsString( 'has-very-dark-gray-color', $css );
		$this->assertStringNotContainsString( 'has-huge-font-size', $css );
		$this->assertStringNotContainsString( 'has-regular-font-size', $css );
		$this->assertStringContainsString( '.has-border-color', $css );
		$this->assertStringContainsString( '.screen-reader-text', $css );
		$this->assertSame( substr_count( $css, '{' ), substr_count( $css, '}' ) );
		$this->assertLessThan( strlen( $original ), strlen( $css ) );
	}

	public function test_other_inline_css_on_the_handle_is_kept(): void {
		$theirs = '.has-huge-font-size{font-size:3rem}:root .has-midnight-gradient-background{background:red}';

		wp_add_inline_style( 'wp-block-library', $theirs );

		$this->assertStringEndsWith( $theirs, $this->inline_block_library_css() );
	}

	public function test_a_linked_stylesheet_is_left_alone(): void {
		$theirs = '.has-huge-font-size{font-size:3rem}';

		add_filter( 'styles_inline_size_limit', '__return_zero' );
		wp_add_inline_style( 'wp-block-library', $theirs );

		wp_enqueue_style( 'wp-block-library' );
		wp_maybe_inline_styles();
		mai_remove_legacy_block_css();

		$style = wp_styles()->registered['wp-block-library'];

		$this->assertNotFalse( $style->src );
		$this->assertSame( [ $theirs ], $style->extra['after'] );
	}
}
