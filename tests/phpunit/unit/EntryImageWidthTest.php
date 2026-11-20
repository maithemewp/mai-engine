<?php
declare(strict_types=1);

namespace BizBudding\MaiEngine\Tests\Unit;

use Brain\Monkey\Functions;
use BizBudding\MaiEngine\Tests\TestCase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * The image's max width that mai_do_entries_open() puts on the entries tag.
 *
 * A custom image size on the left or right takes its share of the card from the Image Width
 * setting, like every orientation, but never grows past the size's own width.
 *
 * The image size helpers cache statically, so each test runs in its own process.
 */
#[RunTestsInSeparateProcesses]
final class EntryImageWidthTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		$root = dirname( __DIR__, 3 );

		require_once $root . '/lib/functions/helpers.php';
		require_once $root . '/lib/functions/utilities.php';
		require_once $root . '/lib/functions/images.php';
		require_once $root . '/lib/functions/entries.php';
		require_once $root . '/lib/blocks/mai-grid/functions.php';
		require_once $root . '/lib/functions/columns.php';

		Functions\when( 'mai_get_config' )->justReturn( [ 'add' => [ 'landscape' => '4:3', 'portrait' => '3:4', 'square' => '1:1' ] ] );
		Functions\when( 'is_admin' )->justReturn( true );
		Functions\when( 'esc_html__' )->returnArg();
		Functions\when( 'esc_attr' )->returnArg();
		foreach ( [ 'is_home', 'is_post_type_archive', 'is_category', 'is_tag', 'is_tax', 'is_author', 'is_date', 'is_year', 'is_month', 'is_day', 'is_time', 'is_archive', 'is_search', 'is_front_page', 'is_single', 'is_page', 'is_404', 'is_attachment', 'is_singular' ] as $conditional ) {
			Functions\when( $conditional )->justReturn( false );
		}

		Functions\when( 'wp_parse_args' )->alias( static fn( $args, $defaults ) => array_merge( $defaults, $args ) );
		Functions\when( 'genesis_markup' )->alias(
			static function ( $args ) {
				printf( '<div class="%s" style="%s">', $args['atts']['class'], $args['atts']['style'] ?? '' );
			}
		);

		$GLOBALS['_wp_additional_image_sizes'] = [
			'landscape-md' => [ 'width' => 800, 'height' => 600, 'crop' => true ],
			'tiny'         => [ 'width' => 80, 'height' => 80, 'crop' => true ],
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

	/**
	 * Gets grid args with an image in the given orientation, size, position and width.
	 *
	 * @param string $orientation The image orientation.
	 * @param string $size        The image size.
	 * @param string $position    The image position.
	 * @param string $width       The image width setting.
	 *
	 * @return array
	 */
	private function args( string $orientation, string $size, string $position, string $width = 'third' ): array {
		return [
			'context'           => 'block',
			'boxed'             => false,
			'title_size'        => '',
			'show'              => [ 'image', 'title' ],
			'image_orientation' => $orientation,
			'image_size'        => $size,
			'image_position'    => $position,
			'image_width'       => $width,
			'image_alternate'   => false,
			'image_stack'       => false,
			'align_text'        => 'start',
		];
	}

	/**
	 * Gets the max width the entries tag gives the image link, or null when it sets none.
	 *
	 * @param array $args The grid args.
	 *
	 * @return string|null
	 */
	private function max_width( array $args ): ?string {
		ob_start();
		mai_do_entries_open( $args );
		$html = (string) ob_get_clean();

		return preg_match( '/--entry-image-link-max-width:([^;]+);/', $html, $match ) ? $match[1] : null;
	}

	public function test_a_custom_size_on_the_side_takes_its_share_but_never_grows_past_the_size(): void {
		$this->assertSame( 'min(1024px,33.33333333%)', $this->max_width( $this->args( 'custom', 'large', 'left-top' ) ) );
		$this->assertSame( 'min(150px,25%)', $this->max_width( $this->args( 'custom', 'thumbnail', 'left-full', 'fourth' ) ) );
		$this->assertSame( 'min(80px,50%)', $this->max_width( $this->args( 'custom', 'tiny', 'right-middle', 'half' ) ) );
	}

	public function test_a_custom_size_in_an_aligned_grid_on_the_side_also_takes_its_share(): void {
		$args          = $this->args( 'custom', 'large', 'right-full' );
		$args['class'] = 'alignwide';

		$this->assertSame( 'min(1024px,33.33333333%)', $this->max_width( $args ) );
	}

	public function test_a_size_with_no_width_on_the_side_takes_only_its_share(): void {
		$args          = $this->args( 'custom', 'nope', 'left-top' );
		$args['class'] = 'alignwide';

		$this->assertSame( '33.33333333%', $this->max_width( $args ) );
	}

	public function test_no_image_width_keeps_the_old_widths(): void {
		$this->assertSame( '1024px', $this->max_width( $this->args( 'custom', 'large', 'left-top', '' ) ) );
		$this->assertNull( $this->max_width( $this->args( 'landscape', 'landscape-md', 'left-top', '' ) ) );
	}

	public function test_a_custom_size_above_the_text_keeps_the_size_width(): void {
		$this->assertSame( '1024px', $this->max_width( $this->args( 'custom', 'large', 'center' ) ) );
		$this->assertSame( '150px', $this->max_width( $this->args( 'custom', 'thumbnail', 'full' ) ) );
	}

	public function test_an_orientation_on_the_side_is_unchanged(): void {
		$this->assertSame( '33.33333333%', $this->max_width( $this->args( 'landscape', 'landscape-md', 'left-top' ) ) );
		$this->assertSame( '50%', $this->max_width( $this->args( 'portrait', 'landscape-md', 'right-full', 'half' ) ) );
		$this->assertNull( $this->max_width( $this->args( 'landscape', 'landscape-md', 'center' ) ) );
	}
}
