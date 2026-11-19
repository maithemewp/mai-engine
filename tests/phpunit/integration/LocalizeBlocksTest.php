<?php

namespace BizBudding\MaiEngine\Tests\Integration;

use WP_Error;
use WP_HTML_Tag_Processor;

/**
 * Covers the demo template image import in mai_localize_blocks().
 *
 * Every fixture image has a matching attachment on this site, so only the existing-attachment
 * path runs. Any HTTP request fails the test, so a fixture that reaches the download path
 * fails loudly instead of fetching.
 */
final class LocalizeBlocksTest extends MaiIntegrationTestCase {

	private const REMOTE = 'https://demo.example.com/wp-content/uploads/2020/01/';

	public function set_up() {
		parent::set_up();

		add_filter( 'pre_http_request', [ $this, 'block_http' ], 10, 3 );
	}

	public function tear_down() {
		remove_filter( 'pre_http_request', [ $this, 'block_http' ], 10 );

		parent::tear_down();
	}

	/**
	 * Fails the test on any HTTP request.
	 *
	 * @param false|array|WP_Error $response Short-circuit value.
	 * @param array                $args     Request arguments.
	 * @param string               $url      Request URL.
	 *
	 * @return WP_Error
	 */
	public function block_http( $response, $args, $url ) {
		$this->fail( 'mai_localize_blocks() made an HTTP request to ' . $url );

		return new WP_Error( 'blocked', 'HTTP is blocked in this test.' );
	}

	// ---- Image ----

	public function test_image_gets_local_url_id_and_class(): void {
		$new_id = $this->attachment( 'photo' );
		$out    = mai_localize_blocks( $this->image( 5, 'photo-300x200.jpg', 'large' ) );
		$block  = $this->block( $out, 1 );

		$this->assertSame( $new_id, $block['attrs']['id'] );
		$this->assertSame( wp_get_attachment_image_src( $new_id, 'large' )[0], $block['src'] );
		$this->assertContains( 'wp-image-' . $new_id, $block['class'] );
		$this->assertNotContains( 'wp-image-5', $block['class'] );
		$this->assertStringNotContainsString( self::REMOTE, $out );
	}

	public function test_image_without_size_slug_uses_full(): void {
		$new_id = $this->attachment( 'photo' );
		$out    = mai_localize_blocks( $this->image( 5, 'photo-300x200.jpg' ) );
		$block  = $this->block( $out, 1 );

		$this->assertSame( $new_id, $block['attrs']['id'] );
		$this->assertSame( wp_get_attachment_url( $new_id ), $block['src'] );
		$this->assertContains( 'wp-image-' . $new_id, $block['class'] );
	}

	public function test_already_local_image_is_unchanged(): void {
		$this->attachment( 'photo' );
		$html = $this->image( 5, 'photo-300x200.jpg', 'large', home_url( '/wp-content/uploads/2020/01/' ) );

		$this->assertSame( $html, mai_localize_blocks( $html ) );
	}

	public function test_existing_attachment_without_image_src_is_unchanged(): void {
		// No file, so wp_get_attachment_image_src() returns false for it.
		self::factory()->attachment->create(
			[
				'post_name'      => 'photo',
				'post_mime_type' => 'image/jpeg',
			]
		);

		$html = $this->image( 5, 'photo-300x200.jpg', 'large' );

		$this->assertSame( $html, mai_localize_blocks( $html ) );
	}

	// ---- Media & Text ----

	public function test_media_text_first_in_content_is_localized(): void {
		$new_id = $this->attachment( 'other' );
		$out    = mai_localize_blocks( $this->media_text( 5, 'other-1024x683.jpg' ) );
		$block  = $this->block( $out, 1 );

		$this->assertSame( $new_id, $block['attrs']['mediaId'] );
		$this->assertArrayNotHasKey( 'id', $block['attrs'] );
		$this->assertSame( wp_get_attachment_image_src( $new_id, 'large' )[0], $block['src'] );
		$this->assertContains( 'wp-image-' . $new_id, $block['class'] );
		$this->assertNotContains( 'wp-image-5', $block['class'] );
		$this->assertStringContainsString( '<p>Text</p>', $out );
	}

	public function test_image_then_media_text_each_get_their_own_image(): void {
		$image_id = $this->attachment( 'photo' );
		$media_id = $this->attachment( 'other' );
		$out      = mai_localize_blocks( $this->image( 7, 'photo-300x200.jpg', 'large' ) . "\n\n" . $this->media_text( 5, 'other-1024x683.jpg' ) );
		$image    = $this->block( $out, 1 );
		$media    = $this->block( $out, 2 );

		$this->assertSame( $image_id, $image['attrs']['id'] );
		$this->assertSame( wp_get_attachment_image_src( $image_id, 'large' )[0], $image['src'] );
		$this->assertContains( 'wp-image-' . $image_id, $image['class'] );

		$this->assertSame( $media_id, $media['attrs']['mediaId'] );
		$this->assertSame( wp_get_attachment_image_src( $media_id, 'large' )[0], $media['src'] );
		$this->assertContains( 'wp-image-' . $media_id, $media['class'] );
	}

	public function test_media_text_without_img_is_left_alone(): void {
		$image_id = $this->attachment( 'photo' );

		// A large size whose URL differs from the full one, so a replacement taken over by the media-text shows.
		wp_update_attachment_metadata(
			$image_id,
			[
				'width'  => 2000,
				'height' => 1333,
				'file'   => 'photo.jpg',
				'sizes'  => [
					'large' => [
						'file'      => 'photo-1024x683.jpg',
						'width'     => 1024,
						'height'    => 683,
						'mime-type' => 'image/jpeg',
					],
				],
			]
		);

		$media_text = '<!-- wp:media-text {"mediaId":5,"mediaType":"image"} -->' . "\n"
			. '<div class="wp-block-media-text is-stacked-on-mobile"><figure class="wp-block-media-text__media"></figure><div class="wp-block-media-text__content"><!-- wp:paragraph -->' . "\n"
			. '<p>Text</p>' . "\n"
			. '<!-- /wp:paragraph --></div></div>' . "\n"
			. '<!-- /wp:media-text -->';

		$out   = mai_localize_blocks( $this->image( 7, 'photo-2000x1333.jpg', 'full' ) . "\n\n" . $media_text );
		$image = $this->block( $out, 1 );
		$media = $this->block( $out, 2 );

		$this->assertSame( wp_get_attachment_url( $image_id ), $image['src'] );
		$this->assertSame( $image_id, $image['attrs']['id'] );

		$this->assertSame( 5, $media['attrs']['mediaId'] );
		$this->assertArrayNotHasKey( 'id', $media['attrs'] );
		$this->assertStringContainsString( '<figure class="wp-block-media-text__media"></figure>', $out );
	}

	public function test_already_local_media_text_is_unchanged(): void {
		$this->attachment( 'other' );
		$html = $this->media_text( 5, 'other-1024x683.jpg', home_url( '/wp-content/uploads/2020/01/' ) );

		$this->assertSame( $html, mai_localize_blocks( $html ) );
	}

	// ---- Cover ----

	public function test_cover_gets_local_url_id_and_class(): void {
		$new_id = $this->attachment( 'photo' );
		$out    = mai_localize_blocks( $this->cover( 5, 'photo.jpg' ) );
		$block  = $this->block( $out, 1 );

		$this->assertSame( $new_id, $block['attrs']['id'] );
		$this->assertSame( wp_get_attachment_url( $new_id ), $block['attrs']['url'] );
		$this->assertSame( wp_get_attachment_url( $new_id ), $block['src'] );
		$this->assertContains( 'wp-image-' . $new_id, $block['class'] );
		$this->assertNotContains( 'wp-image-5', $block['class'] );
		$this->assertStringNotContainsString( self::REMOTE, $out );
	}

	public function test_cover_without_image_is_unchanged(): void {
		$html = "<!-- wp:cover {\"overlayColor\":\"primary\",\"dimRatio\":100} -->\n<div class=\"wp-block-cover\"><span aria-hidden=\"true\" class=\"wp-block-cover__background has-primary-background-color has-background-dim-100 has-background-dim\"></span><div class=\"wp-block-cover__inner-container\"><!-- wp:paragraph -->\n<p>Text</p>\n<!-- /wp:paragraph --></div></div>\n<!-- /wp:cover -->";

		$this->assertSame( $html, mai_localize_blocks( $html ) );
	}

	public function test_cover_and_image_with_overlapping_old_ids_each_get_their_own_id(): void {
		$cover_id = $this->attachment( 'twelve' );
		$image_id = $this->attachment( 'one-two-three' );
		$out      = mai_localize_blocks( $this->cover( 12, 'twelve.jpg' ) . "\n\n" . $this->image( 123, 'one-two-three-300x200.jpg', 'large' ) );

		$this->assertSame( [ 'wp-image-' . $cover_id ], $this->image_id_classes( $this->block( $out, 1 )['class'] ) );
		$this->assertSame( [ 'wp-image-' . $image_id ], $this->image_id_classes( $this->block( $out, 2 )['class'] ) );
		$this->assertSame( $cover_id, $this->block( $out, 1 )['attrs']['id'] );
		$this->assertSame( $image_id, $this->block( $out, 2 )['attrs']['id'] );
	}

	// ---- Image ID classes ----

	public function test_overlapping_old_ids_are_rewritten_exactly(): void {
		$id_12  = $this->attachment( 'twelve' );
		$id_123 = $this->attachment( 'one-two-three' );
		$out    = mai_localize_blocks( $this->image( 12, 'twelve-300x200.jpg', 'large' ) . "\n\n" . $this->image( 123, 'one-two-three-300x200.jpg', 'large' ) );

		$this->assertSame( [ 'wp-image-' . $id_12 ], $this->image_id_classes( $this->block( $out, 1 )['class'] ) );
		$this->assertSame( [ 'wp-image-' . $id_123 ], $this->image_id_classes( $this->block( $out, 2 )['class'] ) );
	}

	public function test_chained_ids_are_each_rewritten_once(): void {
		$a = $this->attachment( 'alpha' );
		$b = $this->attachment( 'beta' );

		// An ID no attachment on this site has. Lookup goes by filename, so it maps to A.
		$unused = max( $a, $b ) + 1000;

		// Block 1's old ID maps to A, and block 2's old ID is A and maps to B.
		$out    = mai_localize_blocks( $this->image( $unused, 'alpha-300x200.jpg', 'large' ) . "\n\n" . $this->image( $a, 'beta-300x200.jpg', 'large' ) );
		$first  = $this->block( $out, 1 );
		$second = $this->block( $out, 2 );

		$this->assertSame( $a, $first['attrs']['id'] );
		$this->assertSame( [ 'wp-image-' . $a ], $this->image_id_classes( $first['class'] ) );

		$this->assertSame( $b, $second['attrs']['id'] );
		$this->assertSame( [ 'wp-image-' . $b ], $this->image_id_classes( $second['class'] ) );
	}

	// ---- No matching block or tag ----

	public function test_content_without_image_blocks_is_unchanged(): void {
		$inputs = [
			'',
			'plain text',
			'<!-- c -->',
			"<!-- wp:paragraph -->\n<p>Hello</p>\n<!-- /wp:paragraph -->",
			"<!-- wp:image {\"id\":5,\"sizeSlug\":\"large\"} -->\n<figure class=\"wp-block-image size-large\"></figure>\n<!-- /wp:image -->",
		];

		foreach ( $inputs as $input ) {
			$this->assertSame( $input, mai_localize_blocks( $input ) );
		}
	}

	// ---- Demo URL lookup by file path ----

	public function test_matches_exact_upload_path(): void {
		$id = $this->attachment_at( '2024/02/photo-scaled-1.jpg' );

		$this->assertSame( $id, mai_get_attachment_id_from_demo_url( 'https://demo.example/site/wp-content/uploads/sites/83/2024/02/photo-scaled-1.jpg' ) );
	}

	public function test_matches_without_multisite_prefix(): void {
		$id = $this->attachment_at( '2024/02/photo.jpg' );

		$this->assertSame( $id, mai_get_attachment_id_from_demo_url( 'https://demo.example/wp-content/uploads/2024/02/photo.jpg' ) );
	}

	public function test_matches_sized_url_to_original(): void {
		$id = $this->attachment_at( '2024/03/Asset-2@4x-e1711650607873.png' );

		$this->assertSame( $id, mai_get_attachment_id_from_demo_url( 'https://demo.example/wp-content/uploads/sites/83/2024/03/Asset-2@4x-e1711650607873-300x238.png' ) );
	}

	public function test_matches_sized_url_to_scaled_original(): void {
		$id = $this->attachment_at( '2024/05/big-scaled.jpg' );

		$this->assertSame( $id, mai_get_attachment_id_from_demo_url( 'https://demo.example/wp-content/uploads/2024/05/big-300x200.jpg' ) );
	}

	public function test_no_match_returns_zero(): void {
		$this->assertSame( 0, mai_get_attachment_id_from_demo_url( 'https://demo.example/wp-content/uploads/2024/06/missing.jpg' ) );
		$this->assertSame( 0, mai_get_attachment_id_from_demo_url( 'https://demo.example/images/photo.jpg' ) );
		$this->assertSame( 0, mai_get_attachment_id_from_demo_url( '' ) );
	}

	// ---- Nested blocks and file path matching ----

	public function test_nested_image_is_localized(): void {
		$new_id = $this->attachment( 'photo' );
		$html   = $this->wrap( 'group', $this->wrap( 'columns', $this->wrap( 'column', $this->image( 5, 'photo-300x200.jpg', 'large' ) ) ) );
		$out    = mai_localize_blocks( $html );
		$block  = $this->find_block( parse_blocks( $out ), 'core/image' );

		$this->assertNotNull( $block );
		$this->assertSame( $new_id, $block['attrs']['id'] );

		$tags = new WP_HTML_Tag_Processor( $block['innerHTML'] );
		$tags->next_tag( 'img' );

		$this->assertSame( wp_get_attachment_image_src( $new_id, 'large' )[0], $tags->get_attribute( 'src' ) );
		$this->assertContains( 'wp-image-' . $new_id, $this->tagClasses( $block['innerHTML'], 'img' ) );
		$this->assertStringNotContainsString( self::REMOTE, $out );
	}

	public function test_nested_cover_and_media_text_are_localized(): void {
		$cover_id = $this->attachment( 'photo' );
		$media_id = $this->attachment( 'other' );
		$html     = $this->wrap( 'group', $this->cover( 5, 'photo.jpg' ) ) . "\n\n" . $this->wrap( 'columns', $this->wrap( 'column', $this->media_text( 6, 'other-1024x683.jpg' ) ) );
		$out      = mai_localize_blocks( $html );
		$blocks   = parse_blocks( $out );
		$cover    = $this->find_block( $blocks, 'core/cover' );
		$media    = $this->find_block( $blocks, 'core/media-text' );

		$this->assertSame( $cover_id, $cover['attrs']['id'] );
		$this->assertSame( wp_get_attachment_url( $cover_id ), $cover['attrs']['url'] );
		$this->assertContains( 'wp-image-' . $cover_id, $this->tagClasses( $cover['innerHTML'], 'img' ) );

		$this->assertSame( $media_id, $media['attrs']['mediaId'] );
		$this->assertContains( 'wp-image-' . $media_id, $this->tagClasses( $media['innerHTML'], 'img' ) );
		$this->assertStringNotContainsString( self::REMOTE, $out );
	}

	public function test_file_path_match_wins_over_slug(): void {
		$new_id = self::factory()->attachment->create(
			[
				'file'           => '2024/03/Asset-2@4x-e1711650607873.png',
				'post_name'      => 'asset-24x',
				'post_mime_type' => 'image/png',
			]
		);

		$base  = 'https://demo.example.com/wp-content/uploads/sites/83/2024/03/';
		$html  = $this->image( 5, 'Asset-2@4x-e1711650607873-300x238.png', 'large', $base );
		$out   = mai_localize_blocks( $html );
		$block = $this->block( $out, 1 );

		$this->assertSame( $new_id, $block['attrs']['id'] );
		$this->assertStringNotContainsString( 'demo.example.com', $out );
	}

	public function test_same_image_twice_uses_one_attachment(): void {
		$new_id = $this->attachment( 'photo' );
		$before = (int) wp_count_posts( 'attachment' )->inherit;
		$out    = mai_localize_blocks( $this->image( 5, 'photo-300x200.jpg', 'large' ) . "\n\n" . $this->image( 5, 'photo-300x200.jpg', 'large' ) );

		$this->assertSame( $new_id, $this->block( $out, 1 )['attrs']['id'] );
		$this->assertSame( $new_id, $this->block( $out, 2 )['attrs']['id'] );
		$this->assertSame( $before, (int) wp_count_posts( 'attachment' )->inherit );
	}

	public function test_second_import_changes_nothing(): void {
		$this->attachment( 'photo' );
		$once = mai_localize_blocks( $this->wrap( 'group', $this->image( 5, 'photo-300x200.jpg', 'large' ) ) );

		$this->assertSame( $once, mai_localize_blocks( $once ) );
	}

	// ---- Helpers ----

	/**
	 * Wraps serialized blocks in a core container block.
	 *
	 * @param string $name  Block name without the core/ prefix.
	 * @param string $inner Serialized inner blocks.
	 *
	 * @return string
	 */
	private function wrap( string $name, string $inner ): string {
		return sprintf( "<!-- wp:%1\$s -->\n<div class=\"wp-block-%1\$s\">%2\$s</div>\n<!-- /wp:%1\$s -->", $name, $inner );
	}

	/**
	 * Finds the first block with a name, searching inner blocks.
	 *
	 * @param array[] $blocks Parsed blocks.
	 * @param string  $name   Block name.
	 *
	 * @return array|null
	 */
	private function find_block( array $blocks, string $name ): ?array {
		foreach ( $blocks as $block ) {
			if ( $name === $block['blockName'] ) {
				return $block;
			}

			$found = $this->find_block( $block['innerBlocks'], $name );

			if ( $found ) {
				return $found;
			}
		}

		return null;
	}

	/**
	 * Creates an image attachment stored at a given uploads path.
	 *
	 * @param string $path Path relative to the uploads directory.
	 *
	 * @return int
	 */
	private function attachment_at( string $path ): int {
		$id = self::factory()->attachment->create( [ 'post_mime_type' => 'image/jpeg' ] );

		update_post_meta( $id, '_wp_attached_file', $path );

		return $id;
	}

	/**
	 * Creates an image attachment with a file, so wp_get_attachment_image_src() works for it.
	 *
	 * @param string $name Post name, the remote file's base name without sizes.
	 *
	 * @return int
	 */
	private function attachment( string $name ): int {
		return self::factory()->attachment->create(
			[
				'file'           => $name . '.jpg',
				'post_name'      => $name,
				'post_mime_type' => 'image/jpeg',
			]
		);
	}

	/**
	 * Builds a core/image block.
	 *
	 * @param int    $id   Old attachment ID.
	 * @param string $file Image file name.
	 * @param string $size Size slug, or empty for none.
	 * @param string $base Base URL of the image.
	 *
	 * @return string
	 */
	private function image( int $id, string $file, string $size = '', string $base = self::REMOTE ): string {
		$attrs  = $size ? sprintf( '{"id":%d,"sizeSlug":"%s"}', $id, $size ) : sprintf( '{"id":%d}', $id );
		$figure = $size ? 'wp-block-image size-' . $size : 'wp-block-image';

		return sprintf(
			"<!-- wp:image %s -->\n<figure class=\"%s\"><img src=\"%s%s\" alt=\"\" class=\"wp-image-%d\"/></figure>\n<!-- /wp:image -->",
			$attrs,
			$figure,
			$base,
			$file,
			$id
		);
	}

	/**
	 * Builds a core/cover block with a background image and a paragraph.
	 *
	 * @param int    $id   Old attachment ID.
	 * @param string $file Image file name.
	 * @param string $base Base URL of the image.
	 *
	 * @return string
	 */
	private function cover( int $id, string $file, string $base = self::REMOTE ): string {
		return sprintf(
			"<!-- wp:cover {\"url\":\"%1\$s%2\$s\",\"id\":%3\$d,\"dimRatio\":50} -->\n<div class=\"wp-block-cover\"><span aria-hidden=\"true\" class=\"wp-block-cover__background has-background-dim\"></span><img class=\"wp-block-cover__image-background wp-image-%3\$d\" alt=\"\" src=\"%1\$s%2\$s\" data-object-fit=\"cover\"/><div class=\"wp-block-cover__inner-container\"><!-- wp:paragraph -->\n<p>Text</p>\n<!-- /wp:paragraph --></div></div>\n<!-- /wp:cover -->",
			$base,
			$file,
			$id
		);
	}

	/**
	 * Builds a core/media-text block with an image and a paragraph.
	 *
	 * @param int    $id   Old attachment ID.
	 * @param string $file Image file name.
	 * @param string $base Base URL of the image.
	 *
	 * @return string
	 */
	private function media_text( int $id, string $file, string $base = self::REMOTE ): string {
		return sprintf(
			"<!-- wp:media-text {\"mediaId\":%d,\"mediaType\":\"image\"} -->\n<div class=\"wp-block-media-text is-stacked-on-mobile\"><figure class=\"wp-block-media-text__media\"><img src=\"%s%s\" alt=\"\" class=\"wp-image-%d size-full\"/></figure><div class=\"wp-block-media-text__content\"><!-- wp:paragraph -->\n<p>Text</p>\n<!-- /wp:paragraph --></div></div>\n<!-- /wp:media-text -->",
			$id,
			$base,
			$file,
			$id
		);
	}

	/**
	 * Returns the nth named block in the content, with its first img's src and class tokens.
	 *
	 * @param string $content Serialized blocks.
	 * @param int    $nth     Which block, starting at 1.
	 *
	 * @return array{attrs:array,src:?string,class:string[]}
	 */
	private function block( string $content, int $nth ): array {
		$blocks = array_values( array_filter( parse_blocks( $content ), static fn( $block ) => null !== $block['blockName'] ) );
		$block  = $blocks[ $nth - 1 ];
		$tags   = new WP_HTML_Tag_Processor( $block['innerHTML'] );
		$src    = null;

		if ( $tags->next_tag( 'img' ) ) {
			$src = $tags->get_attribute( 'src' );
		}

		return [
			'attrs' => $block['attrs'],
			'src'   => is_string( $src ) ? $src : null,
			'class' => $this->tagClasses( $block['innerHTML'], 'img' ),
		];
	}

	/**
	 * Returns the wp-image-{id} tokens in a class list.
	 *
	 * @param string[] $classes Class tokens.
	 *
	 * @return string[]
	 */
	private function image_id_classes( array $classes ): array {
		return array_values( array_filter( $classes, static fn( $class ) => (bool) preg_match( '/^wp-image-\d+$/', $class ) ) );
	}
}
