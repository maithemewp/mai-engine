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
	echo "<script>document.body.classList.replace('no-js','js');</script>";
}

add_action( 'wp_enqueue_scripts', 'mai_enqueue_assets' );
add_action( 'admin_enqueue_scripts', 'mai_enqueue_assets' );
add_action( 'enqueue_block_editor_assets', 'mai_enqueue_assets' );
add_action( 'enqueue_block_assets', 'mai_enqueue_admin_iframe_styles' );
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

/**
 * Register admin-scoped styles through enqueue_block_assets so the
 * WP 6.9+ block-editor iframe recognizes them via the supported channel
 * and doesn't trigger "added to the iframe incorrectly" console warnings.
 *
 * @since 2.40.0
 *
 * @return void
 */
function mai_enqueue_admin_iframe_styles() {
	if ( ! is_admin() ) {
		return;
	}

	$styles = mai_get_config( 'styles' );

	if ( isset( $styles['admin'] ) ) {
		mai_enqueue_asset( 'admin', $styles['admin'], 'style' );
	}

	// Kirki registers its dynamic customizer CSS as `kirki-styles` but only
	// enqueues it on outer admin/frontend hooks, so the editor iframe falls
	// back to WP's heuristic copy (with warning + a flash of unstyled content
	// before the copy lands). Enqueue it here so it's formally part of the
	// iframe's assets and loads synchronously with the canvas.
	if ( wp_style_is( 'kirki-styles', 'registered' ) ) {
		wp_enqueue_style( 'kirki-styles' );
	}

	// Route Mai's per-post layout width and dark-body/boxed-container state into
	// the iframe canvas. These come from outer-admin body classes that don't cross
	// the iframe boundary, so the canvas can't see them; emit them as inline CSS
	// scoped to .editor-styles-wrapper instead.
	$handle = mai_get_handle() . '-admin';

	if ( wp_style_is( $handle, 'registered' ) ) {
		wp_add_inline_style( $handle, mai_minify_css( mai_get_admin_iframe_canvas_css() ) );
	}
}

/**
 * Builds inline canvas CSS mirroring Mai's outer-admin layout/color state into the
 * block-editor iframe (layout content width, dark-body, boxed-container).
 *
 * The matching body classes (mai_admin_body_classes) are set on the OUTER admin
 * document and aren't mirrored into the iframe, so descendant selectors keyed on
 * those classes never fire inside the canvas. Scope everything to
 * .editor-styles-wrapper (the canvas wrapper present in both the iframe and the
 * legacy non-iframe editor), so nothing leaks to the surrounding admin chrome.
 *
 * Reflects state at editor load; changing the layout/colors live needs a reload.
 *
 * @since 2.40.0
 *
 * @return string
 */
function mai_get_admin_iframe_canvas_css() {
	// Mirror admin.scss's outer .edit-post-layout width map. Default and
	// wide-content keep the breakpoint-xl base; narrow/standard/sidebar tighten it.
	$widths = [
		'narrow-content'   => 'sm',
		'standard-content' => 'md',
		'content-sidebar'  => 'md',
		'sidebar-content'  => 'md',
	];

	$layout = genesis_site_layout();
	$width  = isset( $widths[ $layout ] ) ? $widths[ $layout ] : 'xl';
	$css    = sprintf( '--wp-block-max-width:var(--breakpoint-%s);', $width );

	// Dark-body heading/body text already reaches the canvas via the Kirki color
	// system, so only the block appender (and the boxed canvas background) need
	// routing here. has-dark-body and has-boxed-container are mutually exclusive;
	// see mai_admin_body_classes.
	if ( mai_has_boxed_container() ) {
		// The iframe <body> owns --body-background-color, not this child wrapper, so
		// paint the wrapper directly to whiten the canvas inside the iframe too.
		$css .= '--body-background-color:var(--color-white);background-color:var(--color-white);--mai-block-appender-color:#1e1e1e;';
	} elseif ( mai_has_dark_body() ) {
		$css .= '--mai-block-appender-color:var(--color-white);';
	}

	return sprintf( '.editor-styles-wrapper{%s}', $css );
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
	$strategy  = ! empty( $args['async'] ) ? 'async' : ( ! empty( $args['defer'] ) ? 'defer' : '' );
	$last_arg  = 'style' === $type ? $media : $in_footer;

	// Scripts take a $args array so WordPress can apply the loading strategy itself. It is
	// dependency aware, and will decline async/defer where a dependent script would break,
	// which filtering script_loader_tag to bolt the attribute on could not do.
	if ( 'script' === $type && $strategy ) {
		$last_arg = [
			'in_footer' => (bool) $in_footer,
			'strategy'  => $strategy,
		];
	}
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

add_action( 'wp_head', 'mai_reorder_core_block_styles', 7 );
/**
 * Moves core block stylesheets back in front of Mai's, when they land behind them.
 *
 * Since WP 6.9 core block styles load on demand for classic themes, and where a
 * stylesheet lands depends on when its block first renders. A block rendered while
 * the page body renders has its stylesheet hoisted into the head ahead of Mai's,
 * which is the order Mai's block CSS is written against. A block rendered during
 * `wp_head` instead, by an SEO plugin building an excerpt for example, is not late
 * enough to be hoisted, so it prints at the end of the head after Mai and after the
 * child theme's style.css, and core's rules win instead of Mai's.
 *
 * This restores the intended order without loading anything extra: core, then Mai,
 * then style.css. Everything else keeps its position, so third party stylesheets
 * still override Mai the way they do today.
 *
 * The handles come from the block registry, so nothing here needs updating when core
 * adds or renames a block. Sites loading the single bundled block stylesheet have no
 * per-block handles registered, so this finds nothing and does nothing.
 *
 * Priority 7 runs after anything that renders content in the head, and before
 * `wp_print_styles()` at 8.
 *
 * @since 2.41.0
 *
 * @return void
 */
function mai_reorder_core_block_styles() {
	$styles = wp_styles();
	$queue  = $styles->queue;
	$prefix = mai_get_handle() . '-';
	$first  = null;

	foreach ( $queue as $index => $handle ) {
		if ( str_starts_with( $handle, $prefix ) ) {
			$first = $index;
			break;
		}
	}

	if ( is_null( $first ) ) {
		return;
	}

	$block_handles = [];

	foreach ( WP_Block_Type_Registry::get_instance()->get_all_registered() as $block_type ) {
		if ( ! str_starts_with( $block_type->name, 'core/' ) ) {
			continue;
		}

		foreach ( $block_type->style_handles as $style_handle ) {
			$block_handles[ $style_handle ] = true;
		}
	}

	$behind = [];

	foreach ( array_slice( $queue, $first ) as $handle ) {
		if ( isset( $block_handles[ $handle ] ) ) {
			$behind[] = $handle;
		}
	}

	if ( ! $behind ) {
		return;
	}

	$anchor    = $queue[ $first ];
	$remaining = array_values( array_diff( $queue, $behind ) );
	$position  = array_search( $anchor, $remaining, true );

	if ( false === $position ) {
		return;
	}

	array_splice( $remaining, $position, 0, $behind );

	$styles->queue = $remaining;
}

add_action( 'wp_head', 'mai_remove_legacy_block_css', 2 );
/**
 * Strips core's legacy preset rules from its inlined block library CSS.
 *
 * Core copies `block-library/common.css` into the `after` data of `wp-block-library`
 * in `wp_maybe_inline_styles()` on `wp_head` at priority 1, and prints it at 8.
 * Running at 2 changes the inline copy. When a page inlines too much CSS, core links
 * the file instead, there is nothing to change, and the classes stay.
 *
 * Only core's copy is changed. Core sets `src` to false and puts the file first in
 * `after`, so CSS a plugin or child theme adds to the same handle keeps its own
 * rules for these classes.
 *
 * A site that wants the classes back can remove this hook.
 *
 * @since 2.42.0
 *
 * @return void
 */
function mai_remove_legacy_block_css() {
	$styles = wp_styles();
	$style  = $styles->registered['wp-block-library'] ?? null;

	if ( ! $style || false !== $style->src || ! $styles->get_data( 'wp-block-library', 'inlined_src' ) ) {
		return;
	}

	$path = $styles->get_data( 'wp-block-library', 'path' );

	if ( ! is_string( $path ) || ! preg_match( '~/block-library/common(\.min)?\.css$~', $path ) ) {
		return;
	}

	if ( ! isset( $style->extra['after'][0] ) || ! is_string( $style->extra['after'][0] ) ) {
		return;
	}

	$style->extra['after'][0] = mai_strip_legacy_block_css( $style->extra['after'][0] );
}

/**
 * Removes core's 15 hard-coded legacy preset rules from block library CSS.
 *
 * Core's common block CSS ships classes for seven old gradients, four old grey colors
 * and four old font sizes. No filter reaches them, so they print on every page even
 * though Mai registers its own presets. Rules are matched by exact class name, never
 * by a pattern such as `*-gradient-background`, so a plugin's own gradient added to
 * the same stylesheet survives.
 *
 * It fails safe. If core renames one of these rules or adds a selector to it, the
 * pattern stops matching and the rule stays. A rule is only removed when it is flat,
 * with no nested rule inside it. A nested rule that is the first thing inside its
 * parent is left alone, but one that follows a sibling rule inside the same parent is
 * removed too, since it looks like a top level rule. Core does not nest these rules
 * today. Removing a flat rule cannot unbalance the braces, so the brace count check is
 * belt and braces: a stray brace would break every rule after it, so the original text
 * comes back if the counts ever differ.
 *
 * @since 2.42.0
 *
 * @param string $css Core's `common.css` or `common.min.css` contents.
 *
 * @return string The CSS without the legacy rules, or the original if unsure.
 */
function mai_strip_legacy_block_css( $css ) {
	$selectors = [];

	foreach ( [ 'vivid-green-cyan-to-vivid-cyan-blue', 'purple-crush', 'hazy-dawn', 'subdued-olive', 'atomic-cream', 'nightshade', 'midnight' ] as $name ) {
		$selectors[] = ':root .has-' . $name . '-gradient-background';
	}

	foreach ( [ 'very-light-gray-color', 'very-dark-gray-color', 'very-light-gray-background-color', 'very-dark-gray-background-color' ] as $name ) {
		$selectors[] = ':root .has-' . $name;
	}

	foreach ( [ 'regular', 'larger', 'normal', 'huge' ] as $name ) {
		$selectors[] = '.has-' . $name . '-font-size';
	}

	// The lookbehind makes a rule start at the top of the file or right after another
	// rule, so `.a, .has-huge-font-size{}` is never touched. `[^{}]*` stops at a nested
	// rule, which leaves it alone.
	$pattern = '~(?<=^|\})\s*(?:' . implode( '|', array_map( 'preg_quote', $selectors ) ) . ')\s*\{[^{}]*\}~';
	$result  = preg_replace( $pattern, '', $css );

	if ( null === $result || substr_count( $result, '{' ) !== substr_count( $result, '}' ) ) {
		return $css;
	}

	return $result;
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
