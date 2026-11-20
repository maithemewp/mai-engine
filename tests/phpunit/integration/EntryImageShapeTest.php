<?php

namespace BizBudding\MaiEngine\Tests\Integration;

use Mai_Entry;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use ReflectionClass;

/**
 * Covers the ratio Mai_Entry keeps for a shaped image.
 *
 * The ratio follows the orientation the editor chose, not the size a filter swaps in.
 *
 * Each test runs in its own process: the helpers cache statically, and images.php adds
 * image filters the rest of the suite does not expect.
 */
#[RunTestsInSeparateProcesses]
final class EntryImageShapeTest extends MaiIntegrationTestCase {

	public function set_up() {
		parent::set_up();

		require_once dirname( __DIR__, 3 ) . '/lib/functions/images.php';

		$GLOBALS['mai_test_config'] = [
			'image-sizes'   => [ 'add' => [ 'landscape' => '4:3' ] ],
			'global-styles' => [ 'breakpoint' => 1200 ],
			'settings'      => [],
		];
	}

	public function tear_down() {
		unset( $GLOBALS['mai_test_config'] );

		parent::tear_down();
	}

	public function test_the_ratio_follows_the_setting_not_the_size_filter(): void {
		add_filter( 'mai_entry_image_size', static fn() => 'large' );

		$entry = $this->entry( [ 'image_orientation' => 'landscape', 'image_size' => 'md' ] );

		$this->assertSame( '4/3', $this->property( $entry, 'aspect_ratio' ) );
		$this->assertSame( 'large', $this->property( $entry, 'image_size' ) );
	}

	public function test_custom_with_an_uncropped_size_has_no_ratio(): void {
		$entry = $this->entry( [ 'image_orientation' => 'custom', 'image_size' => 'large' ] );

		$this->assertSame( '', $this->property( $entry, 'aspect_ratio' ) );
	}

	/**
	 * Builds an entry for a post that has a featured image.
	 *
	 * @param array $args The orientation and size args.
	 *
	 * @return Mai_Entry
	 */
	private function entry( array $args ): Mai_Entry {
		$post_id       = self::factory()->post->create();
		$attachment_id = self::factory()->attachment->create( [ 'post_parent' => $post_id ] );

		// set_post_thumbnail() refuses an attachment with no image file.
		update_post_meta( $post_id, '_thumbnail_id', $attachment_id );

		return new Mai_Entry(
			get_post( $post_id ),
			array_merge(
				[
					'context'            => 'archive',
					'disable_entry_link' => false,
					'show'               => [ 'image', 'title' ],
					'image_position'     => 'full',
				],
				$args
			)
		);
	}

	/**
	 * Reads a protected property from an entry.
	 *
	 * @param Mai_Entry $entry The entry.
	 * @param string    $name  The property name.
	 *
	 * @return mixed
	 */
	private function property( Mai_Entry $entry, string $name ) {
		return ( new ReflectionClass( Mai_Entry::class ) )->getProperty( $name )->getValue( $entry );
	}
}
