<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2020 BizBudding
 * @license   GPL-2.0-or-later
 */

use Kirki\Util\Helper;

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

add_action( 'init', 'mai_typography_customizer_settings' );
/**
 * Add Customizer font settings.
 * Font weights don't work in output so using undocumented `css_vars`.
 *
 * @link https://github.com/kirki-framework/kirki/issues/2019
 * @link https://github.com/kirki-framework/kirki/issues/1561
 *
 * @since 2.0.0
 * @since 2.6.0 Changed to css_vars for custom property output.
 *
 * @return void
 */
function mai_typography_customizer_settings() {
	$handle  = mai_get_handle();
	$section = $handle . '-typography';

	new \Kirki\Section(
		$section,
		[
			'title' => __( 'Typography', 'mai-engine' ),
			'panel' => $handle,
		]
	);

	$body_font_family = mai_get_default_font_family( 'body' );
	$body_font_weight = mai_get_default_font_weight( 'body' );

	new \Kirki\Field\Typography(
		mai_parse_kirki_args(
			[
				'settings'    => mai_get_kirki_setting( 'body-typography' ),
				'section'     => $section,
				'label'       => __( 'Body', 'mai-engine' ),
				'description' => __( 'Default: ', 'mai-engine' ) . $body_font_family . ' ' . $body_font_weight,
				'choices'     => [
					'fonts'   => [
						'standard' => [ 'serif', 'sans-serif', 'monospace' ],
					],
				],
				'default'     => [
					'font-family' => (string) $body_font_family,
					'variant'     => (string) $body_font_weight,
				],
			]
		)
	);

	$heading_font_family = mai_get_default_font_family( 'heading' );
	$heading_font_weight = mai_get_default_font_weight( 'heading' );

	new \Kirki\Field\Typography(
		mai_parse_kirki_args(
			[
				'settings'    => mai_get_kirki_setting( 'heading-typography' ),
				'section'     => $section,
				'label'       => __( 'Heading', 'mai-engine' ),
				'description' => __( 'Default: ', 'mai-engine' ) . $heading_font_family . ' ' . $heading_font_weight,
				'choices'     => [
					'fonts'   => [
						'standard' => [ 'serif', 'sans-serif', 'monospace' ],
					],
				],
				'default'     => [
					'font-family' => (string) $heading_font_family,
					'variant'     => (string) $heading_font_weight,
				],
			]
		)
	);

	$alt_font_family = mai_get_default_font_family( 'alt' );
	$alt_font_weight = mai_get_default_font_weight( 'alt' );

	new \Kirki\Field\Checkbox_Switch(
		mai_parse_kirki_args(
			[
				'settings'    => mai_get_kirki_setting( 'altfont-enabled' ),
				'section'     => $section,
				'label'       => __( 'Add alternate font', 'mai-engine' ),
				'description' => __( 'This option will register new paragraph and heading block styles for the chosen alternate font.', 'mai-engine' ),
				'default'     => mai_sanitize_bool( $alt_font_family ),
				'choices'     => [
					'on'  => __( 'Yes', 'mai-engine' ),
					'off' => __( 'No', 'mai-engine' )
				]
			]
		)
	);

	new \Kirki\Field\Typography(
		mai_parse_kirki_args(
			[
				'settings'    => mai_get_kirki_setting( 'alt-typography' ),
				'section'     => $section,
				'label'       => __( 'Alternate', 'mai-engine' ),
				'description' => __( 'Default: ', 'mai-engine' ) . ( $alt_font_family && $alt_font_weight ? $alt_font_family . ' ' . $alt_font_weight : __( 'None', 'mai-engine' ) ),
				'choices'     => [
					'fonts'   => [
						'standard' => [ 'serif', 'sans-serif', 'monospace' ],
					],
				],
				'default'     => [
					'font-family' => '',
					'variant'     => '',
				],
				'active_callback' => [
					[
						'setting'  => mai_get_kirki_setting( 'altfont-enabled' ),
						'operator' => '==',
						'value'    => true,
					],
				],
			]
		)
	);

	new \Kirki\Field\Checkbox_Switch(
		mai_parse_kirki_args(
			[
				'settings'    => mai_get_kirki_setting( 'flush-typography' ),
				'label'       => __( 'Flush local fonts', 'mai-engine' ),
				'description' => __( 'Warning: This will delete the entire /wp-content/fonts/ directory and all of it\'s contents. Enable this setting if your Google fonts are not loading correctly.', 'mai-engine' ),
				'section'     => $section,
				'transport'   => 'postMessage',
				'choices' => [
					'on'  => __( 'Flush once', 'mai-engine' ),
					'off' => __( 'No', 'mai-engine' )
				]
			]
		)
	);
}

add_action( 'init', 'mai_typography_maybe_flush_local_fonts' );
/**
 * Deletes `/wp-content/fonts/` directory to allow Kirki to rebuild.
 *
 * @since 2.6.0
 *
 * @return void
 */
function mai_typography_maybe_flush_local_fonts() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$flush = mai_get_option( 'flush-typography' );

	if ( ! $flush ) {
		return;
	}

	mai_typography_flush_local_fonts();
}

/**
 * Flushes local font files.
 *
 * @since 2.11.0
 *
 * @return void
 */
function mai_typography_flush_local_fonts() {
	$dir = WP_CONTENT_DIR . '/fonts';

	// From get_local_files_from_css() in class-kirki-fonts-downloader.php.
	if ( ! file_exists( $dir ) ) {
		return sprintf( '%s %s', $dir, __( 'does not exist.', 'mai-engine' ) );
	}

	$files = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator(
			$dir,
			RecursiveDirectoryIterator::SKIP_DOTS
		),
		RecursiveIteratorIterator::CHILD_FIRST
	);

	if ( $files ) {
		foreach ( $files as $file ) {
			if ( $file->isDir() ) {
				rmdir( $file->getRealPath() );
			} else {
				unlink( $file->getRealPath() );
			}
		}
	}

	rmdir( $dir );

	// Set option back to false.
	mai_update_option( 'flush-typography', 0 );

	// Delete stored Kirki font data.
	delete_option( 'kirki_downloaded_font_files' );

	return __( 'Fonts flushed successfully', 'mai-engine' );
}
