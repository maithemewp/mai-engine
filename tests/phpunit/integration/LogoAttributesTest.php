<?php

namespace BizBudding\MaiEngine\Tests\Integration;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Covers the logo `sizes` from mai_add_logo_attributes() in lib/functions/images.php.
 *
 * A site that never saved the Customizer logo width has no stored value. That once gave
 * `sizes` of 1px, so browsers picked the smallest srcset file and upscaled it. The config
 * default is the width the Customizer shows and the CSS uses, so `sizes` follows it.
 *
 * Each test runs in its own process: the breakpoint is read once per request, and
 * images.php adds image filters the rest of the suite does not expect.
 */
#[RunTestsInSeparateProcesses]
final class LogoAttributesTest extends MaiIntegrationTestCase {

	public function set_up() {
		parent::set_up();

		require_once dirname( __DIR__, 3 ) . '/lib/functions/images.php';

		$GLOBALS['mai_test_config'] = [
			'settings' => [
				'mobile-menu-breakpoint' => '1000px',
				'logo'                   => [
					'width' => [
						'desktop' => '180px',
						'mobile'  => '120px',
					],
				],
			],
		];

		delete_option( 'mai-engine' );
		mai_reset_options_cache();
	}

	public function tear_down() {
		unset( $GLOBALS['mai_test_config'] );
		delete_option( 'mai-engine' );
		mai_reset_options_cache();

		parent::tear_down();
	}

	public function test_an_unsaved_width_uses_the_config_default(): void {
		$attr = mai_add_logo_attributes( [ 'class' => 'custom-logo' ] );

		$this->assertSame( '(min-width: 1000px) 180px, 120px', $attr['sizes'] );
		$this->assertSame( 'custom-logo', $attr['class'] );
	}

	public function test_a_saved_width_wins_over_the_config_default(): void {
		update_option(
			'mai-engine',
			[
				'logo-width' => [
					'desktop' => '240px',
					'mobile'  => '100px',
				],
			]
		);
		mai_reset_options_cache();

		$attr = mai_add_logo_attributes( [] );

		$this->assertSame( '(min-width: 1000px) 240px, 100px', $attr['sizes'] );
	}
}
