<?php
declare(strict_types=1);

namespace BizBudding\MaiEngine\Tests\Unit;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use BizBudding\MaiEngine\Tests\TestCase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Mai's Gravity Forms default styles: palette colors, radius and font sizes read from the
 * config's `extra` properties or the active Mai theme's stylesheet, a 0 radius kept, and
 * earlier values on the filter winning.
 *
 * Each test runs in its own process, because the theme stylesheet is read once per request.
 */
#[RunTestsInSeparateProcesses]
final class GravityFormsStylesTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		require_once dirname( __DIR__, 3 ) . '/lib/support/gravity-forms.php';

		Functions\when( 'mai_get_colors' )->justReturn(
			[
				'background' => '#ffffff',
				'alt'        => '#f3ece1',
				'body'       => '#4a3f33',
				'heading'    => '#2e2418',
				'primary'    => '#5c5b33',
			]
		);
		Functions\when( 'mai_get_input_border_color' )->justReturn( '#9a938d' );
		Functions\when( 'mai_get_dir' )->justReturn( dirname( __DIR__, 3 ) . '/' );
		Functions\when( 'sanitize_file_name' )->returnArg();
	}

	/**
	 * Gets the styles for a Mai theme and config `extra` properties.
	 *
	 * @param string $theme The active Mai theme.
	 * @param array  $extra The config's `extra` custom properties.
	 * @param mixed  $styles What earlier callbacks on the filter returned.
	 *
	 * @return array
	 */
	private function styles( string $theme, array $extra = [], $styles = false ): array {
		Functions\when( 'mai_get_active_theme' )->justReturn( $theme );
		Functions\when( 'mai_get_global_styles' )->justReturn( $extra );

		return mai_gform_default_styles( $styles );
	}

	public function test_colors_come_from_the_palette(): void {
		$styles = $this->styles( 'default' );

		$this->assertSame( 'orbital', $styles['theme'] );
		$this->assertSame( '#9a938d', $styles['inputBorderColor'] );
		$this->assertSame( '#2e2418', $styles['labelColor'] );
		$this->assertSame( '#5c5b33', $styles['buttonPrimaryBackgroundColor'] );
		$this->assertSame( '#ffffff', $styles['buttonPrimaryColor'] );
	}

	public function test_mai_defaults_without_a_theme_value(): void {
		$styles = $this->styles( 'default' );

		$this->assertSame( '4', $styles['inputBorderRadius'] );
		$this->assertSame( '16', $styles['labelFontSize'] );
		$this->assertSame( '13', $styles['descriptionFontSize'] );
	}

	public function test_the_mai_theme_stylesheet_sets_radius_and_size(): void {
		// chic.min.css: --font-size-base:15px; --font-scale:1.125; --border-radius:0.
		$styles = $this->styles( 'chic' );

		$this->assertSame( '0', $styles['inputBorderRadius'] );
		$this->assertSame( '15', $styles['labelFontSize'] );
		$this->assertSame( '13', $styles['descriptionFontSize'] );
	}

	public function test_config_extra_wins_over_the_theme_stylesheet(): void {
		$styles = $this->styles( 'chic', [ 'font-size-base' => '18px', 'border-radius' => '0.5rem' ] );

		$this->assertSame( '8', $styles['inputBorderRadius'] );
		$this->assertSame( '18', $styles['labelFontSize'] );
		$this->assertSame( '16', $styles['descriptionFontSize'] );
	}

	public function test_a_value_it_cant_read_falls_back(): void {
		$styles = $this->styles( 'default', [ 'border-radius' => 'var(--something)' ] );

		$this->assertSame( '4', $styles['inputBorderRadius'] );
	}

	public function test_earlier_values_on_the_filter_win(): void {
		$styles = $this->styles( 'default', [], [ 'inputBorderRadius' => '2' ] );

		$this->assertSame( '2', $styles['inputBorderRadius'] );
		$this->assertSame( '#5c5b33', $styles['inputPrimaryColor'] );
	}

	public function test_the_mai_filter_changes_one_value(): void {
		Filters\expectApplied( 'mai_gform_default_styles' )->once()->andReturnUsing(
			function ( array $styles ) {
				$styles['inputSize'] = 'lg';
				$styles['labelColor'] = '';

				return $styles;
			}
		);

		$styles = $this->styles( 'default' );

		$this->assertSame( 'lg', $styles['inputSize'] );
		$this->assertArrayNotHasKey( 'labelColor', $styles );
		$this->assertSame( '#2e2418', $styles['inputColor'] );
	}

	public function test_the_editor_defaults_get_the_same_styles(): void {
		if ( ! class_exists( 'GFFormDisplay', false ) ) {
			eval( 'class GFFormDisplay { public static function validate_form_styles( $styles ) { return is_array( $styles ) ? $styles : []; } }' );
		}

		Functions\when( 'mai_get_active_theme' )->justReturn( 'default' );
		Functions\when( 'mai_get_global_styles' )->justReturn( [] );
		Filters\expectApplied( 'gform_default_styles' )->once()->andReturnUsing( 'mai_gform_default_styles' );

		$attributes = mai_gform_block_attribute_defaults(
			[
				'theme'             => [ 'type' => 'string', 'default' => '' ],
				'inputBorderRadius' => [ 'type' => 'string', 'default' => 3 ],
				'labelColor'        => [ 'type' => 'string', 'default' => '#112337' ],
				'formId'            => [ 'type' => 'string' ],
			]
		);

		$this->assertSame( '', $attributes['theme']['default'] );
		$this->assertSame( '4', $attributes['inputBorderRadius']['default'] );
		$this->assertSame( '#2e2418', $attributes['labelColor']['default'] );
		$this->assertArrayNotHasKey( 'default', $attributes['formId'] );
	}
}
