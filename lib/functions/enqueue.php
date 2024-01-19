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

/**
 * Removes default output of child theme stylesheet.
 *
 * @since 2.13.0
 *
 * @return void
 */
remove_action( 'genesis_meta', 'genesis_load_stylesheet' );

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
 * @since  0.1.0
 *
 * @return void
 */
function mai_js_nojs_script() {
	echo "<script>document.body.className = document.body.className.replace('no-js','js');</script>";
}

add_action( 'wp_enqueue_scripts', 'mai_enqueue_assets' );
add_action( 'admin_enqueue_scripts', 'mai_enqueue_assets' );
add_action( 'enqueue_block_editor_assets', 'mai_enqueue_assets' );
add_action( 'customize_controls_enqueue_scripts', 'mai_enqueue_assets' );
add_action( 'login_enqueue_scripts', 'mai_enqueue_assets' );
/**
 * Register and enqueue all scripts and styles.
 *
 * @since 2.4.0 Separate mai_enqueue_asset function.
 * @since 0.1.0
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

add_filter( 'mai_styles_config', 'mai_styles_desktop_breakpoint' );
/**
 * Adds media query from mobile menu breakpoint option.
 *
 * @param array The styles config.
 *
 * @return array
 */
function mai_styles_desktop_breakpoint( $config ) {
	$config['desktop']['media'] = sprintf( 'only screen and (min-width:%s)', mai_get_mobile_menu_breakpoint() );

	return $config;
}

add_filter( 'script_loader_tag', 'mai_script_loader_tag', 10, 3 );
/**
 * Adds attributes to scripts.
 *
 * @since 2.13.0
 *
 * @param string $tag    The <script> tag for the enqueued script.
 * @param string $handle The script's registered handle.
 * @param string $src    The script's source URL.
 *
 * @return string
 */
function mai_script_loader_tag( $tag, $handle, $src ) {
	$attributes = mai_get_script_attributes();
	if ( ! ( isset( $attributes[ $handle ] ) && $attributes[ $handle ] ) ) {
		return $tag;
	}
	$tag = mai_add_tag_attributes( $tag, $attributes[ $handle ] );
	return $tag;
}

/**
 * Adds attributes to styles.
 *
 * @since 2.13.0
 *
 * @param string $html   The link tag for the enqueued style.
 * @param string $handle The style's registered handle.
 * @param string $href   The stylesheet's source URL.
 * @param string $media  The stylesheet's media attribute.
 *
 * @return string
 */
add_filter( 'style_loader_tag', 'mai_style_loader_tag', 10, 4 );
function mai_style_loader_tag( $html, $handle, $href, $media ) {
	$attributes = mai_get_style_attributes();
	if ( ! ( isset( $attributes[ $handle ] ) && $attributes[ $handle ] ) ) {
		return $html;
	}
	$html = mai_add_tag_attributes( $html, $attributes[ $handle ] );
	return $html;
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
	$deps      = isset( $args['deps'] ) ? $args['deps'] : [];
	$ver       = isset( $args['ver'] ) ? $args['ver'] : mai_get_asset_version( $src );
	$media     = isset( $args['media'] ) ? $args['media'] : 'all';
	$in_footer = isset( $args['in_footer'] ) ? $args['in_footer'] : ( 'script' === $type ); // Default to true if script, false if style.
	$condition = isset( $args['condition'] ) ? $args['condition'] : '__return_true';
	$location  = isset( $args['location'] ) & ! empty( $args['location'] ) ? (array) $args['location'] : [ 'public' ];
	$localize  = isset( $args['localize'] ) ? $args['localize'] : [];
	$inline    = isset( $args['inline'] ) ? $args['inline'] : false;
	$last_arg  = 'style' === $type ? $media : $in_footer;
	$register  = "wp_register_$type";
	$enqueue   = "wp_enqueue_$type";
	$load      = false;

	if ( in_array( 'public', $location, true ) && ! is_admin() && ! did_action( 'login_enqueue_scripts' ) ) {
		$load = true;
	}

	if ( in_array( 'admin', $location, true ) && is_admin() && ! is_customize_preview() && ! did_action( 'login_enqueue_scripts' ) ) {
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

	if ( in_array( 'login', $location, true ) && did_action( 'login_enqueue_scripts' ) ) {
		$load = true;
	}

	if ( ! $load || ! is_callable( $condition ) || ! $condition() ) {
		return;
	}

	if ( '' === $src ) {
		$src = false;
	}

	$register( $handle, $src, $deps, $ver, $last_arg );

	if ( ! $in_footer || is_admin() ) {
		$enqueue( $handle );
	} else {
		// In footer, just before default for theme style.css
		add_action( 'get_footer', function() use ( $enqueue, $handle ) {
			$enqueue( $handle );
		}, 9 );
	}

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
}

/**
 * Adds attributes to an HTML tag.
 *
 * @access private
 *
 * @since 2.13.0
 *
 * @param string $tag The <script> or <style> tag.
 * @param array  $attributes The attributes by name and value.
 *
 * @return string
 */
function mai_add_tag_attributes( $tag, $attributes ) {
	if ( ! $tag ) {
		return $tag;
	}
	$dom   = mai_get_dom_document( $tag );
	$first = mai_get_dom_first_child( $dom );
	foreach ( $attributes as $name => $value ) {
		$first->setAttribute( $name, $value );
	}
	return mai_get_dom_html( $dom );
}

/**
 * Gets script attributes to be added later.
 * These are not available to be added in wp_enqueue_script().
 *
 * @access private
 *
 * @since 2.13.0
 *
 * @return array
 */
function mai_get_script_attributes() {
	static $attributes = null;
	if ( ! is_null( $attributes ) ) {
		return $attributes;
	}
	$attributes = mai_get_tag_attributes( 'scripts' );
	return $attributes;
}

/**
 * Gets style attributes to be added later.
 * These are not available to be added in wp_enqueue_style().
 *
 * @access private
 *
 * @since 2.13.0
 *
 * @return array
 */
function mai_get_style_attributes() {
	static $attributes = null;
	if ( ! is_null( $attributes ) ) {
		return $attributes;
	}
	$attributes = [
		'wp-block-library' => [],
	];
	$attributes = array_merge( mai_get_tag_attributes( 'styles' ), $attributes );
	return $attributes;
}

/**
 * Gets attributes of scripts or styles from the config.
 *
 * @access private
 *
 * @since 2.13.0
 *
 * @return array
 */
function mai_get_tag_attributes( $script_or_style ) {
	$attributes = [];
	$tags       = mai_get_config( $script_or_style );

	foreach ( $tags as $name => $args ) {
		$handle = isset( $args['handle'] ) ? $args['handle'] : mai_get_handle() . '-' . $name;

		if ( isset( $args[''] ) && $args['onload'] ) {
			$attributes[ $handle ]['onload'] = $args['onload'];
		}
		if ( isset( $args['async'] ) ) {
			$attributes[ $handle ]['async'] = 'async';
		}
		if ( isset( $args['defer'] ) ) {
			$attributes[ $handle ]['defer'] = 'defer';
		}
	}

	return $attributes;
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

// add_action( 'wp_enqueue_scripts', 'mai_remove_global_styles_css' );
// add_action( 'admin_enqueue_scripts', 'mai_remove_global_styles_css', 9 );
/**
 * Remove global styles CSS.
 *
 * @since 2.19.1
 *
 * @return void
 */
function mai_remove_global_styles_css() {
	mai_deregister_asset( 'global-styles' );
}


add_action( 'wp_enqueue_scripts', 'mai_remove_block_library_theme_css' );
add_action( 'admin_enqueue_scripts', 'mai_remove_block_library_theme_css', 9 );
/**
 * Remove block library theme CSS.
 *
 * @since 2.4.0
 *
 * @return void
 */
function mai_remove_block_library_theme_css() {
	// mai_deregister_asset( 'wp-block-library' );
	mai_deregister_asset( 'wp-block-library-theme' );
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
