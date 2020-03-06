<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2019 BizBudding
 * @license   GPL-2.0-or-later
 */

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

// Genesis style trump.
remove_action( 'genesis_meta', 'genesis_load_stylesheet' );
add_action( 'wp_enqueue_scripts', 'genesis_enqueue_main_stylesheet', 99 );

add_action( 'wp_enqueue_scripts', 'mai_enqueue_assets' );
add_action( 'admin_enqueue_scripts', 'mai_enqueue_assets' );
add_action( 'customize_controls_enqueue_scripts', 'mai_enqueue_assets' );
add_action( 'enqueue_block_editor_assets', 'mai_enqueue_assets' );
/**
 * Register and enqueue all scripts and styles.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_enqueue_assets() {
	$assets       = mai_get_config( 'scripts-and-styles' )['add'];
	$google_fonts = implode( '|', mai_get_config( 'google-fonts' ) );

	if ( $google_fonts ) {
		$assets[] = [
			'handle' => mai_get_handle() . '-google-fonts',
			'src'    => "//fonts.googleapis.com/css?family=$google_fonts&display=swap",
			'editor' => 'both',
		];
	}

	foreach ( $assets as $asset ) {
		$handle     = $asset['handle']; // Required.
		$src        = $asset['src']; // Required.
		$type       = false !== strpos( $src, '.js' ) ? 'script' : 'style';
		$deps       = isset( $asset['deps'] ) ? $asset['deps'] : [];
		$ver        = isset( $asset['ver'] ) ? $asset['ver'] : mai_get_asset_version( $asset['src'] );
		$media      = isset( $asset['media'] ) ? $asset['media'] : 'all';
		$in_footer  = isset( $asset['in_footer'] ) ? $asset['in_footer'] : true;
		$editor     = isset( $asset['editor'] ) ? $asset['editor'] : false;
		$customizer = isset( $asset['customizer'] ) ? $asset['customizer'] : false;
		$condition  = isset( $asset['condition'] ) ? $asset['condition'] : '__return_true';
		$localize   = isset( $asset['localize'] ) ? $asset['localize'] : [];
		$hook       = isset( $asset['hook'] ) ? $asset['hook'] : false;
		$priority   = isset( $asset['priority'] ) ? $asset['priority'] : 10;
		$last_arg   = 'style' === $type ? $media : $in_footer;
		$register   = "wp_register_$type";
		$enqueue    = "wp_enqueue_$type";

		if ( is_admin() && $editor || ! is_admin() && ! $editor || 'both' === $editor || $customizer ) {
			if ( is_callable( $condition ) && $condition() ) {
				$register( $handle, $src, $deps, $ver, $last_arg );

				if ( ! $hook ) {
					$enqueue( $handle );
				} else {
					add_action( $hook, function () use ( $handle, $src, $ver, $media ) {
						printf(
							'<link rel="stylesheet" id="%s" href="%s?ver=%s" type="text/css" media="%s">',
							$handle,
							$src,
							$ver,
							$media
						);
					}, $priority );
				}

				if ( ! empty( $localize ) ) {
					wp_localize_script( $handle, $localize['name'], $localize['data'] );
				}
			}
		}
	}
}

add_action( 'wp_enqueue_scripts', 'mai_deregister_scripts_and_styles', 15 );
/**
 * Deregister scripts.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_deregister_scripts_and_styles() {
	global $wp_styles;
	$assets = mai_get_config( 'scripts-and-styles' )['remove'];

	foreach ( $assets as $asset ) {
		wp_deregister_script( $asset );
		wp_deregister_style( $asset );
		$wp_styles->remove( $asset );
	}
}

add_filter( 'block_editor_settings', 'mai_remove_noto_serif_editor_styles' );
/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 *
 * @return string
 */
function mai_remove_noto_serif_editor_styles( $settings ) {
	unset( $settings['styles'][0] );
	unset( $settings['styles'][1] );

	return $settings;
}

