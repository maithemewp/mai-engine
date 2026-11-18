<?php

namespace BizBudding\MaiEngine\Tests\Integration;

/**
 * Covers entry pagination, comment reply links, archive pagination and adjacent entry.
 *
 * Markup shapes are copied from real Genesis and WordPress output.
 */
final class StructureLinksTest extends MaiIntegrationTestCase {

	private const NUMERIC = '<div class="archive-pagination pagination" role="navigation" aria-label="Pagination"><ul>'
		. '<li class="pagination-previous"><a href="/page/1/">&#xAB; <span class="screen-reader-text">Go to</span> Previous Page</a></li>' . "\n"
		. '<li><a href="/">1</a></li>' . "\n"
		. '<li class="active" ><a href="/page/2/" aria-current="page">2</a></li>' . "\n"
		. '<li><a href="/page/3/">3</a></li>' . "\n"
		. '<li class="pagination-next"><a href="/page/3/">Next Page &#xBB;</a></li>' . "\n"
		. '</ul></div>';

	private const PREV_NEXT = '<div class="archive-pagination pagination"><div class="pagination-previous"><a href="/page/1/">&#xAB; Previous Page</a></div><div class="pagination-next"><a href="/page/3/">Next Page &#xBB;</a></div></div>';

	public function tear_down() {
		remove_all_filters( 'mai_archive_pagination_link_classes' );
		parent::tear_down();
	}

	// ---- Entry pagination ----

	public function test_entry_pagination_current_link(): void {
		$out = mai_entry_pagination_button_class( '<span class="post-page-numbers current" aria-current="page">2</span>', 2 );
		$classes = $this->tagClasses( $out, 'span' );

		$this->assertContains( 'button', $classes );
		$this->assertContains( 'button-small', $classes );
		$this->assertContains( 'current', $classes );
		$this->assertNotContains( 'button-secondary', $classes );
	}

	public function test_entry_pagination_other_link(): void {
		$out = mai_entry_pagination_button_class( '<a href="/p/3/" class="post-page-numbers">3</a>', 3 );

		$this->assertEqualsCanonicalizing(
			[ 'post-page-numbers', 'button', 'button-secondary', 'button-small' ],
			$this->tagClasses( $out, 'a' )
		);
	}

	public function test_entry_pagination_no_matching_tag(): void {
		foreach ( [ '', 'plain text', '<!-- c -->' ] as $input ) {
			$this->assertSame( $input, mai_entry_pagination_button_class( $input, 1 ) );
		}
	}

	// ---- Comments ----

	public function test_comment_reply_button_classes(): void {
		$link = '<a rel="nofollow" class="comment-reply-link" href="/?replytocom=5#respond" data-commentid="5" aria-label="Reply to Bob">Reply</a>';
		$out  = mai_comment_reply_button_class( $link );

		$this->assertEqualsCanonicalizing(
			[ 'comment-reply-link', 'button', 'button-secondary', 'button-small' ],
			$this->tagClasses( $out, 'a' )
		);
		$this->assertStringContainsString( 'data-commentid="5"', $out );
	}

	public function test_comment_reply_button_valueless_class(): void {
		$out = mai_comment_reply_button_class( '<a class href="#">Reply</a>' );

		$this->assertEqualsCanonicalizing( [ 'button', 'button-secondary', 'button-small' ], $this->tagClasses( $out, 'a' ) );
		$this->assertStringNotContainsString( '1--', $out );
	}

	public function test_comment_reply_button_no_link(): void {
		foreach ( [ '', 'Log in to reply', '<!-- c -->' ] as $input ) {
			$this->assertSame( $input, mai_comment_reply_button_class( $input ) );
		}
	}

	public function test_cancel_reply_link(): void {
		$link = '<a rel="nofollow" id="cancel-comment-reply-link" href="/post/#respond" style="display:none;">Cancel reply</a>';
		$out  = mai_comment_reply_link( $link, '/post/#respond', 'Cancel reply' );

		$this->assertSame( [ 'cancel-comment-reply-link' ], $this->tagClasses( $out, 'a' ) );
		$this->assertStringContainsString( 'style="display:none;"', $out );
	}

	public function test_cancel_reply_link_empty_or_tagless(): void {
		$this->assertSame( '', mai_comment_reply_link( '', '', '' ) );
		$this->assertSame( 'text', mai_comment_reply_link( 'text', '', '' ) );
	}

	// ---- Archive pagination ----

	public function test_pagination_wrap_closes_when_open_and_close_arrive_together(): void {
		$out = mai_archive_pagination_wrap( self::PREV_NEXT, [ 'open' => '<div>', 'close' => '</div>', 'content' => self::PREV_NEXT ] );

		$this->assertStringStartsWith( '<div class="wrap archive-pagination-wrap', $out );
		$this->assertStringEndsWith( '</div>', $out );
		$this->assertSame( substr_count( $out, '<div' ), substr_count( $out, '</div>' ) );
	}

	public function test_pagination_wrap_open_only_adds_opener(): void {
		$out = mai_archive_pagination_wrap( '<nav></nav>', [ 'open' => '<div>' ] );

		$this->assertStringStartsWith( '<div class="wrap archive-pagination-wrap', $out );
		$this->assertStringNotContainsString( '</div>', $out );
	}

	public function test_pagination_wrap_close_only_adds_one_closer(): void {
		$out = mai_archive_pagination_wrap( '</nav>', [ 'close' => '</div>' ] );

		$this->assertSame( '</nav></div>', $out );
	}

	public function test_pagination_wrap_without_open_or_close_is_unchanged(): void {
		$this->assertSame( '<nav></nav>', mai_archive_pagination_wrap( '<nav></nav>', [] ) );
	}

	public function test_numeric_nav_classes(): void {
		$out = mai_get_posts_nav_with_classes( self::NUMERIC, 'numeric' );

		$base = [ 'pagination-link', 'button', 'button-small' ];

		// Page 2's li is active; link order: previous, 1, 2, 3, next.
		$this->assertEqualsCanonicalizing( array_merge( $base, [ 'button-secondary' ] ), $this->tagClasses( $out, 'a', 1 ) );
		$this->assertEqualsCanonicalizing( array_merge( $base, [ 'button-secondary' ] ), $this->tagClasses( $out, 'a', 2 ) );
		$this->assertEqualsCanonicalizing( $base, $this->tagClasses( $out, 'a', 3 ) );
		$this->assertEqualsCanonicalizing( array_merge( $base, [ 'button-secondary' ] ), $this->tagClasses( $out, 'a', 4 ) );
		$this->assertEqualsCanonicalizing( array_merge( $base, [ 'button-secondary' ] ), $this->tagClasses( $out, 'a', 5 ) );
	}

	public function test_numeric_nav_link_outside_li_is_untouched(): void {
		$html = '<div class="archive-pagination"><a href="/x/">Stray</a><ul><li class="active"><a href="/y/">2</a></li></ul></div>';
		$out  = mai_get_posts_nav_with_classes( $html, 'numeric' );

		$this->assertSame( [], $this->tagClasses( $out, 'a', 1 ) );
		$this->assertEqualsCanonicalizing( [ 'pagination-link', 'button', 'button-small' ], $this->tagClasses( $out, 'a', 2 ) );
	}

	public function test_numeric_nav_active_does_not_leak_past_li(): void {
		$html = '<ul><li class="active"><a href="/1/">1</a></li><li><a href="/2/">2</a></li></ul>';
		$out  = mai_get_posts_nav_with_classes( $html, 'numeric' );

		$this->assertNotContains( 'button-secondary', $this->tagClasses( $out, 'a', 1 ) );
		$this->assertContains( 'button-secondary', $this->tagClasses( $out, 'a', 2 ) );
	}

	public function test_prev_next_nav_classes(): void {
		$out = mai_get_posts_nav_with_classes( self::PREV_NEXT, 'prev-next' );

		foreach ( [ 1, 2 ] as $nth ) {
			$this->assertEqualsCanonicalizing(
				[ 'pagination-link', 'button', 'button-small', 'button-secondary' ],
				$this->tagClasses( $out, 'a', $nth )
			);
		}
	}

	public function test_nav_without_links_is_unchanged(): void {
		foreach ( [ '', 'plain', '<!-- c -->' ] as $input ) {
			$this->assertSame( $input, mai_get_posts_nav_with_classes( $input, 'numeric' ) );
			$this->assertSame( $input, mai_get_posts_nav_with_classes( $input, 'prev-next' ) );
		}
	}

	public function test_filter_returning_string(): void {
		add_filter( 'mai_archive_pagination_link_classes', static fn() => 'one two' );

		$out = mai_get_posts_nav_with_classes( self::PREV_NEXT, 'prev-next' );

		$this->assertSame( [ 'one', 'two' ], $this->tagClasses( $out, 'a', 1 ) );
	}

	public function test_filter_returning_array_with_spaces(): void {
		add_filter( 'mai_archive_pagination_link_classes', static fn() => [ 'one two', 'three', 'one' ] );

		$out = mai_get_posts_nav_with_classes( self::PREV_NEXT, 'prev-next' );

		$this->assertSame( [ 'one', 'two', 'three' ], $this->tagClasses( $out, 'a', 1 ) );
	}

	public function test_filter_returning_null_adds_nothing(): void {
		add_filter( 'mai_archive_pagination_link_classes', static fn() => null );

		$out = mai_get_posts_nav_with_classes( '<div><a class="keep" href="/">x</a><a href="/">y</a></div>', 'prev-next' );

		$this->assertSame( [ 'keep' ], $this->tagClasses( $out, 'a', 1 ) );
		$this->assertSame( [], $this->tagClasses( $out, 'a', 2 ) );
	}

	public function test_filter_runs_once_per_link_with_args(): void {
		$calls = [];
		add_filter(
			'mai_archive_pagination_link_classes',
			static function ( $classes, $active, $type ) use ( &$calls ) {
				$calls[] = [ $active, $type ];
				return $classes;
			},
			10,
			3
		);

		mai_get_posts_nav_with_classes( self::NUMERIC, 'numeric' );
		$this->assertCount( 5, $calls );
		$this->assertSame( [ true, 'numeric' ], $calls[2] );
		$this->assertSame( [ false, 'numeric' ], $calls[0] );

		$calls = [];
		mai_get_posts_nav_with_classes( self::PREV_NEXT, 'prev-next' );
		$this->assertSame( [ [ false, 'prev-next' ], [ false, 'prev-next' ] ], $calls );
	}

	public function test_valueless_link_class_in_nav(): void {
		$out = mai_get_posts_nav_with_classes( '<div><a class href="/">x</a></div>', 'prev-next' );

		$this->assertEqualsCanonicalizing(
			[ 'pagination-link', 'button', 'button-small', 'button-secondary' ],
			$this->tagClasses( $out, 'a' )
		);
	}

	// ---- Adjacent entry ----

	public function test_adjacent_entry_classes(): void {
		$post_id = self::factory()->post->create( [ 'post_title' => 'Next one' ] );
		$this->go_to( get_permalink( $post_id ) );

		$content = '<a href="/next/" rel="next">Next one</a>';
		$out     = mai_adjacent_entry( '<div class="adjacent-post-link"><a href="/next/" rel="next">Next one</a></div>', [] );

		$this->assertEqualsCanonicalizing( [ 'adjacent-entry-link-inner', 'adjacent-entry-link' ], $this->tagClasses( $out, 'div' ) );
		$this->assertStringNotContainsString( 'adjacent-post-link', $out );
		$this->assertStringContainsString( $content, $out );
	}

	public function test_adjacent_entry_empty_and_non_singular(): void {
		$this->assertSame( '', mai_adjacent_entry( '', [] ) );

		$this->go_to( home_url( '/' ) );
		$html = '<div class="adjacent-post-link"><a href="/">x</a></div>';
		$this->assertSame( $html, mai_adjacent_entry( $html, [] ) );
	}

	public function test_adjacent_entry_c1_character_passes_through(): void {
		$post_id = self::factory()->post->create();
		$this->go_to( get_permalink( $post_id ) );

		// U+0092 stays as stored. DOMDocument used to repair it to U+2019. That repair is dropped
		// on purpose; see "Legacy C1 and double-encoded text" in
		// docs/specs/2026-11-16-dom-to-tag-processor.md.
		$title = "Don\u{0092}t stop";
		$out   = mai_adjacent_entry( '<div class="adjacent-post-link"><a href="/">' . $title . '</a></div>', [] );

		$this->assertStringContainsString( $title, $out );
		$this->assertStringNotContainsString( "\u{2019}", $out );
	}
}
