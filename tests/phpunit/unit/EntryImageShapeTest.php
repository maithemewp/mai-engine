<?php
declare(strict_types=1);

namespace BizBudding\MaiEngine\Tests\Unit;

use Brain\Monkey\Functions;
use BizBudding\MaiEngine\Tests\TestCase;
use Mai_Entry;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use ReflectionClass;

/**
 * The helper that decides which ratio an entry image is forced to, if any.
 *
 * The helpers it calls cache statically, so each test runs in its own process.
 */
#[RunTestsInSeparateProcesses]
final class EntryImageShapeTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		$root = dirname( __DIR__, 3 );

		require_once $root . '/lib/functions/helpers.php';
		require_once $root . '/lib/functions/utilities.php';
		require_once $root . '/lib/functions/images.php';

		Functions\when( 'mai_get_config' )->justReturn( [ 'add' => [ 'landscape' => '4:3', 'portrait' => '3:4', 'square' => '1:1' ] ] );

		$GLOBALS['_wp_additional_image_sizes'] = [
			'hard' => [ 'width' => 600, 'height' => 400, 'crop' => true ],
			'soft' => [ 'width' => 600, 'height' => 400, 'crop' => false ],
			'flat' => [ 'width' => 600, 'height' => 0, 'crop' => true ],
			'pos'  => [ 'width' => 300, 'height' => 300, 'crop' => [ 'center', 'top' ] ],
		];

		$options = [
			'thumbnail_size_w' => 150,
			'thumbnail_size_h' => 150,
			'thumbnail_crop'   => '1',
			'large_size_w'     => 1024,
			'large_size_h'     => 1024,
			'large_crop'       => false,
			'medium_size_w'    => 300,
			'medium_size_h'    => 300,
			'medium_crop'      => '',
		];

		Functions\when( 'get_option' )->alias( static fn( $name ) => $options[ $name ] ?? false );
	}

	protected function tearDown(): void {
		unset( $GLOBALS['_wp_additional_image_sizes'] );

		parent::tearDown();
	}

	public function test_an_orientation_gives_its_ratio(): void {
		$this->assertSame( '4/3', mai_get_entry_aspect_ratio( [ 'image_orientation' => 'landscape', 'image_size' => 'md' ] ) );
		$this->assertSame( '3/4', mai_get_entry_aspect_ratio( [ 'image_orientation' => 'portrait', 'image_size' => 'md' ] ) );
	}

	public function test_masonry_gives_nothing(): void {
		$this->assertSame( '', mai_get_entry_aspect_ratio( [ 'image_orientation' => 'masonry', 'image_size' => 'hard' ] ) );
	}

	public function test_custom_with_a_cropped_size_gives_its_ratio(): void {
		$this->assertSame( '600/400', mai_get_entry_aspect_ratio( [ 'image_orientation' => 'custom', 'image_size' => 'hard' ] ) );
		$this->assertSame( '300/300', mai_get_entry_aspect_ratio( [ 'image_orientation' => 'custom', 'image_size' => 'pos' ] ) );
		$this->assertSame( '150/150', mai_get_entry_aspect_ratio( [ 'image_orientation' => 'custom', 'image_size' => 'thumbnail' ] ) );
	}

	public function test_custom_with_an_uncropped_size_gives_nothing(): void {
		foreach ( [ 'soft', 'large', 'medium' ] as $size ) {
			$this->assertSame( '', mai_get_entry_aspect_ratio( [ 'image_orientation' => 'custom', 'image_size' => $size ] ), $size );
		}
	}

	public function test_a_size_with_no_height_gives_nothing(): void {
		$this->assertSame( '', mai_get_entry_aspect_ratio( [ 'image_orientation' => 'custom', 'image_size' => 'flat' ] ) );
	}

	public function test_an_unknown_size_gives_nothing(): void {
		$this->assertSame( '', mai_get_entry_aspect_ratio( [ 'image_orientation' => 'custom', 'image_size' => 'nope' ] ) );
	}

	public function test_an_unknown_orientation_gives_nothing(): void {
		$this->assertSame( '', mai_get_entry_aspect_ratio( [ 'image_orientation' => 'wide', 'image_size' => 'hard' ] ) );
		$this->assertSame( '', mai_get_entry_aspect_ratio( [] ) );
	}

	public function test_a_shaped_image_carries_its_mark_and_ratio(): void {
		$attr = $this->image_attributes( 'archive', '4/3' );

		$this->assertSame( 'entry-image size-landscape-sm has-aspect-ratio', $attr['class'] );
		$this->assertSame( '--aspect-ratio:4/3;', $attr['style'] );
	}

	public function test_a_shaped_single_image_carries_its_mark_and_ratio(): void {
		$attr = $this->image_attributes( 'single', '3/4' );

		$this->assertSame( 'entry-image size-landscape-sm has-aspect-ratio', $attr['class'] );
		$this->assertSame( '--aspect-ratio:3/4;', $attr['style'] );
	}

	public function test_an_unshaped_image_is_unchanged(): void {
		foreach ( [ 'archive', 'single' ] as $context ) {
			$attr = $this->image_attributes( $context, '' );

			$this->assertSame( 'entry-image size-landscape-sm', $attr['class'], $context );
			$this->assertArrayNotHasKey( 'style', $attr, $context );
		}
	}

	/**
	 * Gets the attributes Mai_Entry::get_image() passes to wp_get_attachment_image().
	 *
	 * @param string $context The entry context.
	 * @param string $ratio   The forced ratio, or an empty string.
	 *
	 * @return array
	 */
	private function image_attributes( string $context, string $ratio ): array {
		$saved = null;

		Functions\when( 'wp_get_attachment_image' )->alias(
			static function ( $id, $size, $icon, $attr ) use ( &$saved ) {
				$saved = $attr;

				return '<img>';
			}
		);
		Functions\when( 'wp_get_attachment_caption' )->justReturn( '' );

		$class = new ReflectionClass( Mai_Entry::class );
		$entry = $class->newInstanceWithoutConstructor();

		foreach ( [ 'image_id' => 5, 'image_size' => 'landscape-sm', 'context' => $context, 'aspect_ratio' => $ratio ] as $name => $value ) {
			$class->getProperty( $name )->setValue( $entry, $value );
		}

		$entry->get_image();

		return $saved;
	}
}
