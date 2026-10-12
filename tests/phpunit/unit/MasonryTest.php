<?php
declare(strict_types=1);

namespace BizBudding\MaiEngine\Tests\Unit;

use Brain\Monkey\Functions;
use BizBudding\MaiEngine\Tests\TestCase;
use Mai_Entry;
use ReflectionClass;

/**
 * The Masonry image orientation: its place in the choices, the is-masonry class on
 * entries-wrap, no --aspect-ratio, and the Large image size.
 *
 * Mai's config and Genesis can't load here, so the theme's orientations and genesis_markup()
 * are stubbed. The genesis_markup() stub prints each element's opening tag with its class and style.
 */
final class MasonryTest extends TestCase {

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
		// mai_get_columns_atts() asks what page this is, and here it's none.
		foreach ( [ 'is_home', 'is_post_type_archive', 'is_category', 'is_tag', 'is_tax', 'is_author', 'is_date', 'is_year', 'is_month', 'is_day', 'is_time', 'is_archive', 'is_search', 'is_front_page', 'is_single', 'is_page', 'is_404', 'is_attachment', 'is_singular' ] as $conditional ) {
			Functions\when( $conditional )->justReturn( false );
		}

		Functions\when( 'wp_parse_args' )->alias( static fn( $args, $defaults ) => array_merge( $defaults, $args ) );
		Functions\when( 'genesis_markup' )->alias(
			static function ( $args ) {
				printf( '<div class="%s" style="%s">', $args['atts']['class'], $args['atts']['style'] ?? '' );
			}
		);
	}

	/**
	 * Gets grid args with an image showing in the given orientation and position.
	 *
	 * @param string $orientation The image orientation.
	 * @param string $position    The image position.
	 *
	 * @return array
	 */
	private function args( string $orientation, string $position ): array {
		return [
			'context'           => 'block',
			'boxed'             => false,
			'title_size'        => '',
			'show'              => [ 'image', 'title' ],
			'image_orientation' => $orientation,
			'image_size'        => 'landscape-md',
			'image_position'    => $position,
			'image_width'       => 'third',
			'image_alternate'   => false,
			'image_stack'       => false,
			'align_text'        => 'start',
		];
	}

	/**
	 * Renders the entries opening markup.
	 *
	 * @param array $args The grid args.
	 *
	 * @return string
	 */
	private function open( array $args ): string {
		ob_start();
		mai_do_entries_open( $args );

		return (string) ob_get_clean();
	}

	public function test_masonry_is_a_grid_choice_but_not_a_single_one(): void {
		$this->assertArrayHasKey( 'masonry', mai_get_image_orientation_choices() );
		$this->assertArrayNotHasKey( 'masonry', mai_get_single_image_orientation_choices() );
		$this->assertArrayHasKey( 'custom', mai_get_single_image_orientation_choices() );
	}

	public function test_masonry_goes_on_entries_wrap_without_an_aspect_ratio(): void {
		$html = $this->open( $this->args( 'masonry', 'background' ) );

		$this->assertStringContainsString( 'class="entries-wrap has-columns is-masonry"', $html );
		$this->assertStringNotContainsString( 'entries entries-grid has-image-background is-masonry', $html );
		$this->assertStringNotContainsString( '--aspect-ratio', $html );
	}

	public function test_other_orientations_are_unchanged(): void {
		$html = $this->open( $this->args( 'landscape', 'background' ) );

		$this->assertStringNotContainsString( 'is-masonry', $html );
		$this->assertStringContainsString( '--aspect-ratio:4/3;', $html );
	}

	public function test_masonry_without_an_image_adds_no_class(): void {
		$args         = $this->args( 'masonry', 'full' );
		$args['show'] = [ 'title' ];

		$this->assertStringNotContainsString( 'is-masonry', $this->open( $args ) );
	}

	public function test_any_columns_caller_gets_is_masonry_from_its_args(): void {
		$gallery = [ 'image_orientation' => 'masonry' ];

		$this->assertStringContainsString( 'has-columns is-masonry', mai_get_columns_atts( [ 'class' => 'mai-gallery' ], $gallery )['class'] );
		$this->assertStringNotContainsString( 'is-masonry', mai_get_columns_atts( [], [ 'image_orientation' => 'square' ] )['class'] );
		$this->assertStringNotContainsString( 'is-masonry', mai_get_columns_atts( [], [ 'show' => [ 'name' ] ] )['class'] );
	}

	public function test_masonry_uses_the_large_image_size(): void {
		Functions\when( 'apply_filters' )->returnArg( 2 );

		$this->assertSame( 'large', $this->image_size( 'masonry' ) );
		$this->assertSame( 'landscape-sm', $this->image_size( 'landscape' ) );
		$this->assertSame( 'landscape-md', $this->image_size( 'custom' ) );
	}

	/**
	 * Gets the image size Mai_Entry picks for an orientation.
	 *
	 * @param string $orientation The image orientation.
	 *
	 * @return string
	 */
	private function image_size( string $orientation ): string {
		$class = new ReflectionClass( Mai_Entry::class );
		$entry = $class->newInstanceWithoutConstructor();
		$class->getProperty( 'args' )->setValue( $entry, $this->args( $orientation, 'full' ) );

		return $entry->get_image_size();
	}
}
