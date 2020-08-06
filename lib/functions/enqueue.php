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

add_action( 'init', 'mai_genesis_style_trump' );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_genesis_style_trump() {
	if ( mai_get_option( 'genesis-style-trump', true ) ) {
		remove_action( 'genesis_meta', 'genesis_load_stylesheet' );
		add_action( 'get_footer', 'mai_enqueue_child_theme_stylesheet' );
	}
}

/**
 * Genesis style trump, and cache bust when stylesheet is updated.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_enqueue_child_theme_stylesheet() {
	$version = sprintf(
		'%s.%s',
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
 * @since 2.4.0 Separate mai_enqueue_asset function.
 * @since 1.0.0
 *
 * @return void
 */
function mai_enqueue_assets() {
	$scripts = mai_get_config( 'scripts' );
	$styles  = mai_get_config( 'styles' );

	foreach ( $scripts as $handle => $args ) {
		mai_enqueue_asset( $handle, $args, 'script' );
	}

	foreach ( $styles as $handle => $args ) {
		mai_enqueue_asset( $handle, $args, 'style' );
	}
}

/**
 * Register and enqueue script or style.
 *
 * @since 2.4.0
 *
 * @param string $handle Asset handle.
 * @param array  $args   Asset args.
 * @param string $type   Asset type.
 *
 * @return void
 */
function mai_enqueue_asset( $handle, $args, $type ) {
	$suffix    = 'script' === $type ? '.js' : '.css';
	$src       = isset( $args['src'] ) ? $args['src'] : mai_get_asset_url( $handle . $suffix );
	$handle    = isset( $args['handle'] ) ? $args['handle'] : mai_get_handle() . '-' . $handle;
	$src       = isset( $args['async'] ) ? $src . '#async' : $src;
	$deps      = isset( $args['deps'] ) ? $args['deps'] : [];
	$ver       = isset( $args['ver'] ) ? $args['ver'] : mai_get_asset_version( $src );
	$media     = isset( $args['media'] ) ? $args['media'] : 'all';
	$in_footer = isset( $args['in_footer'] ) ? $args['in_footer'] : true;
	$condition = isset( $args['condition'] ) ? $args['condition'] : '__return_true';
	$location  = isset( $args['location'] ) ? is_array( $args['location'] ) ? $args['location'] : [ $args['location'] ] : [ 'public' ];
	$localize  = isset( $args['localize'] ) ? $args['localize'] : [];
	$inline    = isset( $args['inline'] ) ? $args['inline'] : false;
	$onload    = isset( $args['onload'] ) ? $args['onload'] : false;
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

	if ( in_array( 'editor', $location, true ) ) {
		$current_screen = function_exists( 'get_current_screen' ) ? get_current_screen() : false;

		if ( $current_screen && method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor() ) {
			$load = true;
		}
	}

	if ( in_array( 'customizer', $location, true ) && is_customize_preview() && ! did_action( 'genesis_meta' ) ) {
		$load = true;
	}

	if ( ! $load || ! is_callable( $condition ) || ! $condition() ) {
		return;
	}

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
		add_filter(
			'style_loader_tag',
			function ( $html, $original_handle ) use ( $args, $handle ) {
				if ( $original_handle === $handle ) {
					$html = str_replace( '>', ' onload="' . $args['onload'] . '">', $html );
				}

				return $html;
			},
			11,
			2
		);
	}
}

/**
 * Deregister script or style.
 *
 * @since 2.4.0
 *
 * @param string $handle Asset handle.
 *
 * @return void
 */
function mai_deregister_asset( $handle ) {
	global $wp_styles;

	wp_deregister_script( $handle );
	wp_deregister_style( $handle );
	wp_dequeue_script( $handle );
	wp_dequeue_style( $handle );
	$wp_styles->remove( $handle );
}

add_action( 'wp_enqueue_scripts', 'mai_remove_block_library_theme_css' );
add_action( 'admin_enqueue_scripts', 'mai_remove_block_library_theme_css', 9 );
/**
 * Remove block library theme CSS from admin.
 *
 * @since 2.4.0
 *
 * @return void
 */
function mai_remove_block_library_theme_css() {
	mai_deregister_asset( 'wp-block-library-theme' );
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
 * Can't be in config because it uses default breakpoint which is also set in config file.
 *
 * @since 0.3.5
 *
 * @return void
 */
function mai_enqueue_desktop_styles() {
	$file       = 'assets/css/desktop.min.css';
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
 * @param string $url URL to check.
 *
 * @return string
 */
function mai_async_scripts( $url ) {
	if ( strpos( $url, '#async' ) !== false ) {
		$url = str_replace( '#async', '', $url ) . "' async='async";
	}

	return $url;
}
