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

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

// Remove default child theme stylesheet.
remove_action( 'genesis_meta', 'genesis_load_stylesheet' );

add_action( 'get_footer', 'mai_enqueue_child_theme_stylesheet' );
/**
 * Genesis style trump, and cache bust when stylesheet is updated.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_enqueue_child_theme_stylesheet() {
	$version = sprintf( '%s.%s',
		genesis_get_theme_version(),
		date( 'njYHi', filemtime( get_stylesheet_directory() . '/style.css' ) )
	);

	wp_enqueue_style(
		genesis_get_theme_handle(),
		get_stylesheet_uri(),
		false,
		$version
	);
}

add_action( 'genesis_before', 'mai_js_nojs_script', 1 );
/**
 * Echo out the script that changes 'no-js' class to 'js'.
 *
 * Adds a script on the genesis_before hook which immediately changes the
 * class to js if JavaScript is enabled. This is how WP does things on
 * the back end, to allow different styles for the same elements
 * depending if JavaScript is active or not.
 *
 * Outputting the script immediately also reduces a flash of incorrectly
 * styled content, as the page does not load with no-js styles, then
 * switch to js once everything has finished loading.
 *
 * @since  1.0.0
 *
 * @return void
 */
function mai_js_nojs_script() {
	echo <<<EOT
<script>
//<![CDATA[
(function(){var c = document.body.classList;c.remove('no-js');c.add('js')})();
//]]>
</script>
EOT;
}

add_action( 'wp_enqueue_scripts', 'mai_enqueue_assets' );
add_action( 'admin_enqueue_scripts', 'mai_enqueue_assets' );
add_action( 'enqueue_block_editor_assets', 'mai_enqueue_assets' );
add_action( 'customize_controls_enqueue_scripts', 'mai_enqueue_assets' );
/**
 * Register and enqueue all scripts and styles.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_enqueue_assets() {
	$assets         = mai_get_config( 'scripts-and-styles' )['add'];
	$current_screen = function_exists( 'get_current_screen' ) ? get_current_screen() : false;

	foreach ( $assets as $asset ) {
		if ( ! isset( $asset['handle'] ) || ! isset( $asset['src'] ) ) {
			continue;
		}

		$handle    = $asset['handle'];
		$src       = $asset['src'] . ( isset( $asset['async'] ) && $asset['async'] ? '#async' : '' );
		$type      = false !== strpos( $src, '.js' ) ? 'script' : 'style';
		$deps      = isset( $asset['deps'] ) ? $asset['deps'] : [];
		$ver       = isset( $asset['ver'] ) ? $asset['ver'] : mai_get_asset_version( $asset['src'] );
		$media     = isset( $asset['media'] ) ? $asset['media'] : 'all';
		$in_footer = isset( $asset['in_footer'] ) ? $asset['in_footer'] : true;
		$condition = isset( $asset['condition'] ) ? $asset['condition'] : '__return_true';
		$location  = isset( $asset['location'] ) ? is_array( $asset['location'] ) ? $asset['location'] : [ $asset['location'] ] : [ 'public' ];
		$localize  = isset( $asset['localize'] ) ? $asset['localize'] : [];
		$inline    = isset( $asset['inline'] ) ? $asset['inline'] : false;
		$onload    = isset( $asset['onload'] ) ? $asset['onload'] : false;
		$last_arg  = 'style' === $type ? $media : $in_footer;
		$register  = "wp_register_$type";
		$enqueue   = "wp_enqueue_$type";
		$load      = false;

		if ( in_array( 'public', $location, true ) && ! is_admin() ) {
			$load = true;
		}

		if ( in_array( 'admin', $location, true ) && is_admin() && ! is_customize_preview() ) {
			$load = true;
		}

		if ( in_array( 'editor', $location, true ) && $current_screen && method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor() ) {
			$load = true;
		}

		if ( in_array( 'customizer', $location, true ) && is_customize_preview() && ! did_action( 'genesis_meta' ) ) {
			$load = true;
		}

		if ( $load && is_callable( $condition ) && $condition() ) {
			$register( $handle, $src, $deps, $ver, $last_arg );
			$enqueue( $handle );

			if ( $inline ) {
				wp_add_inline_style( $handle, mai_minify_css( $inline ) );
			}

			if ( ! empty( $localize ) ) {
				if ( is_callable( $localize['data'] ) ) {
					$localize_data = call_user_func( $localize['data'] );
				} else {
					$localize_data = $localize['data'];
				}
				wp_localize_script( $handle, $localize['name'], $localize_data );
			}

			if ( $onload ) {
				add_filter( 'style_loader_tag', function ( $html, $handle ) use ( $asset ) {
					if ( $handle === $asset['handle'] ) {
						$html = str_replace( '>', ' onload="' . $asset['onload'] . '">', $html );
					}

					return $html;
				}, 11, 2 );
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
		wp_dequeue_script( $asset );
		wp_dequeue_style( $asset );
		$wp_styles->remove( $asset );
	}
}

add_filter( 'block_editor_settings', 'mai_remove_noto_serif_editor_styles' );
/**
 * Remove noto serif default editor style.
 *
 * @since 0.1.0
 *
 * @param array $settings Editor settings.
 *
 * @return array
 */
function mai_remove_noto_serif_editor_styles( $settings ) {
	unset( $settings['styles'][0] );
	unset( $settings['styles'][1] );

	return $settings;
}

add_action( 'wp_enqueue_scripts', 'mai_admin_bar_inline_styles' );
/**
 * Admin bar inline styles.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_admin_bar_inline_styles() {
	if ( ! is_admin_bar_showing() ) {
		return;
	}

	$css = <<<EOT
	@media (max-width: 782px) {
		body.admin-bar {
			min-height: calc(100vh - 46px);
		}
	@media (min-width: 783px) {
		body.admin-bar {
			min-height: calc(100vh - 32px);
		}
	}
EOT;

	wp_add_inline_style( mai_get_handle(), mai_minify_css( $css ) );
}

add_action( 'wp_head', 'mai_enqueue_desktop_styles' );
/**
 * Load desktop styles only at breakpoint set in Customizer.
 *
 * @since 0.3.5
 *
 * @return void
 */
function mai_enqueue_desktop_styles() {
	$file       = 'assets/css/desktop/desktop.min.css';
	$path       = mai_get_dir() . $file;
	$url        = mai_get_url() . $file;
	$breakpoint = mai_get_option( 'mobile-menu-breakpoint', mai_get_breakpoint() );

	if ( file_exists( $path ) && $breakpoint ) {
		printf( '<link id="mai-desktop-styles" href="%s" rel="stylesheet" media="screen and (min-width: %spx)" rel="preload">', $url, $breakpoint );
	}
}

add_filter( 'clean_url', 'mai_async_scripts', 11, 1 );
/**
 * Add async attribute to a url.
 *
 * @since 0.3.4
 *
 * @param $url
 *
 * @return mixed|string
 */
function mai_async_scripts( $url ) {
	if ( strpos( $url, '#async' ) !== false ) {
		$url = str_replace( '#async', '', $url ) . "' async='async";
	}

	return $url;
}

add_action( 'admin_init', 'mai_download_google_fonts' );
/**
 * Download google fonts locally.
 *
 * Note: Can't use `add_query_arg` because of
 * multiple "family" args required by the Google API.
 * Note: `esc_url` breaks the API call as well.
 *
 * @since 0.3.4
 *
 * @return void
 */
function mai_download_google_fonts() {
	$config = mai_get_config( 'google-fonts' );

	if ( $config && is_array( $config ) ) {
		$downloader = mai_get_instance( Mai_Fonts_Downloader::class );

		foreach ( $config as $url ) {
			$downloader->get_styles( $url );
		}
	}
}

add_action( 'wp_enqueue_scripts', 'mai_google_fonts_fallback' );
add_action( 'enqueue_block_editor_assets', 'mai_google_fonts_fallback' );
/**
 * Fallback to enqueue google fonts from google CDN.
 *
 * @since 0.3.4
 *
 * @return void
 */
function mai_google_fonts_fallback() {
	$config = mai_get_config( 'google-fonts' );

	if ( ! $config ) {
		return;
	}

	$local_css = WP_CONTENT_DIR . '/mai-fonts/style.min.css';

	if ( ! file_exists( $local_css ) ) {
		$handle = mai_get_handle() . '-google-fonts';

		foreach ( $config as $index => $url ) {
			if ( $index > 0 ) {
				$handle .= '-' . ( $index + 1 ); // Start with mai-engine-google-fonts-2.
			}

			wp_register_style(
				$handle,
				$url
			);

			wp_enqueue_style( $handle );
		}
	}
}

