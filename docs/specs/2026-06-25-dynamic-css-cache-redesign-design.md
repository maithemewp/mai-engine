# Mai Engine: Dynamic CSS Cache Redesign — Design

Date: 2026-06-25
Status: Design pending user review
Component: `mai-engine` `lib/customize/output.php`
Depends on: the mai-engine cache adoption sub-project (merged into `develop`), which provides the `mai_cache( 'css' )` wrapper and the css-group flush wiring.

## Background

`mai_add_kirki_css()` (hooked on Kirki's `kirki_global_styles` filter) builds mai's global CSS custom properties (breakpoints, colors, fonts, buttons, icons, etc.) and is meant to cache the result so the work is not repeated on every request. It has not actually cached anything on the front end since June 2022.

The history: the cache was originally gated on `is_admin()` (plus `ajax`/`preview`). Kirki fires `kirki_global_styles` in more contexts than the front-end render (its `loop_controls()` builds the array for control rendering too), and in some of those contexts `is_admin()` is false, so a non-front-end value was written to the transient and then served to visitors. Commit `720d4d36c` ("Hopefully fixes css/transient bug", 2022-06-28) patched the symptom by swapping the proxy to `did_action( 'wp_head' )`, with the comment "this filter/css is only run on wp_head on front end."

That swap silently killed the cache. The filter fires *inside* `wp_head` on the front end, and during `wp_head` execution `did_action( 'wp_head' )` already returns `1`, so the gate `! ( did_action('wp_head') || $ajax || $preview )` is always false there. The cache is never read or written on the front end. This was confirmed live on a large site: after a flush and repeated front-end loads, the `dynamic_css` entry stays empty (0 bytes), and a probe on the same filter logged `stack = wp_head > kirki_global_styles` with `did_action('wp_head')=1` on every front-end request.

## Problem

Every gate the code has tried (`is_admin`, `did_action('wp_head')`, and the `doing_action('wp_head')` we briefly considered) is a *proxy* for one question: "is this the real front-end render, so the value is safe to cache and serve?" Proxies are fragile because they approximate the condition instead of naming it, and the 2022 author could not pin down the exact set of contexts to exclude. The result was either corruption (is_admin) or a dead cache (did_action).

## Key insight

mai's CSS additions are a **pure function of saved settings**. Every `mai_add_*` contributor reads settings (`mai_get_breakpoints()`, `mai_get_colors()`, `mai_get_global_styles()`, `mai_get_config()`, `mai_get_option()`, ...) and writes to `$css['global'][...]`. None read request context. The only read of the passed `$css` anywhere is a cosmetic "breakpoints first" ordering merge.

Therefore the computed value is byte-identical in every context, with exactly one exception: the **customizer preview**, where the customizer filters theme mods to the live, unsaved values. So the real, nameable condition behind all the proxies is simply: **"are these settings live/unsaved?"** which is `is_customize_preview()`.

This removes the need to detect context at all. We cache the deterministic computation and merge it into whatever array the current context passes, and the only thing we refuse to cache is a live preview.

## Goal

Make `dynamic_css` actually cache on the front end, in a way that structurally cannot reintroduce the 2022 corruption, removing the fragile context proxies. Harden `dynamic_fonts` to the same principle. Preserve existing output (including custom-property ordering) and existing invalidation.

## Non-goals

- No change to Kirki, to the `kirki_global_styles` injection point, or to how mai registers its global styles.
- No change to invalidation: the `css` group already flushes on `customize_save_after`, `after_switch_theme`, and `update_option_mai-engine`. That coverage is correct and unchanged.
- No change to `classic_editor_styles` (already correct; see below).

## The design

### 1. `dynamic_css`: cache deterministic additions, merge per context

Split the two things the old code tangled together: **injection** (must run in every context, so Kirki gets mai's styles) and **the expensive computation** (deterministic, so cache it once).

```php
add_filter( 'kirki_global_styles', 'mai_add_kirki_css' );
function mai_add_kirki_css( $css ) {
	if ( ! is_array( $css ) ) {
		$css = [];
	}

	// Merge mai's (cached) deterministic additions into THIS context's own Kirki array,
	// base-first so Kirki's own keys keep their positions and mai's custom properties all
	// land together in the existing global > :root block, appended after Kirki's keys.
	return mai_merge_kirki_css( $css, mai_get_kirki_css_additions() );
}

/**
 * mai's additions are a pure function of saved settings, so compute once and reuse.
 * The customizer preview reflects live/unsaved settings, so there we always recompute
 * and never touch the cache.
 */
function mai_get_kirki_css_additions() {
	if ( is_customize_preview() ) {
		return mai_build_kirki_css_additions();
	}

	$additions = mai_cache( 'css' )->get( 'dynamic_css' );

	if ( false === $additions ) {
		$additions = mai_build_kirki_css_additions();
		mai_cache( 'css' )->set( 'dynamic_css', $additions, 12 * HOUR_IN_SECONDS );
	}

	return $additions;
}

/**
 * Run the mai_add_* pipeline on an empty base so the result is ONLY mai's additions,
 * with nothing context-specific captured.
 */
function mai_build_kirki_css_additions() {
	$css = [ 'global' => [ ':root' => [] ] ];
	$css = mai_add_breakpoint_custom_properties( $css );
	$css = mai_add_title_area_custom_properties( $css );
	$css = mai_add_fonts_custom_properties( $css );
	$css = mai_add_colors_css( $css );
	$css = mai_add_light_surface_css( $css );
	$css = mai_add_buttons_css( $css );
	$css = mai_add_icons_css( $css );
	$css = mai_add_extra_custom_properties( $css );

	return $css;
}
```

The `mai_add_*` functions are unchanged: they already take `$css`, add settings-derived keys, and return it. Running them on an empty base yields pure additions because they only ever write.

### 2. Merge: keep Kirki's keys in place, mai's custom properties grouped

What Kirki itself contributes to the `global` config (captured live on the target site, identical on every front-end page) is small and settings-derived:

- top-level keys: `global`, `@media (min-width: 1000px)`
- `global` selectors: `.header-stuck,:root`, `:root`, `.header-right`
- `global > :root`: one key, `--title-area-padding-mobile`

These come from mai's registered title-area/header customizer fields' Kirki `output` configs. mai's additions all target the single main `global > :root` block (plus the `.is-style-altfont` selectors, as before), so the merge just needs to drop mai's keys into that block alongside Kirki's, without scattering them. A **base-first** merge does this: Kirki's array is the skeleton (its keys keep their positions), mai's keys append after, and mai wins on leaf conflicts (matching the old in-place overwrite):

```php
/**
 * Deep-merge mai's cached additions onto Kirki's per-context $css, base-first.
 * Kirki's own keys keep their positions; mai's new keys append after; mai wins on leaf
 * conflicts (same as the old in-place pipeline's overwrite). mai's :root custom
 * properties all land together in the single global > :root block.
 */
function mai_merge_kirki_css( array $base, array $additions ) {
	foreach ( $additions as $key => $value ) {
		if ( is_array( $value ) && isset( $base[ $key ] ) && is_array( $base[ $key ] ) ) {
			$base[ $key ] = mai_merge_kirki_css( $base[ $key ], $value );
		} else {
			$base[ $key ] = $value;
		}
	}

	return $base;
}
```

Walking the captured base through this: `global > :root` becomes `[--title-area-padding-mobile, <mai's breakpoints>, --header-shrink-offset, <fonts/colors/...>]` — Kirki's key keeps its position and all of mai's custom properties land together in that one `:root` block, breakpoints grouped consecutively. The `global` selectors stay `[.header-stuck,:root, :root, .header-right, .is-style-altfont...]` and the top-level `@media` block is untouched. Order among the custom properties is not forced (it is irrelevant to rendering); the only guarantee is that they all share the main `:root` block.

### 3. Why this is poison-proof by construction

Each context merges mai's additions into *its own* `$css`. We never cache or serve another context's base array. The only thing in the cache is mai's deterministic, settings-derived contribution, which is identical everywhere except preview, and preview never reads or writes the cache. The class of bug the 2022 commit was fighting (one context's value served in another) cannot occur, because no context-specific value is ever cached.

### 4. `dynamic_fonts`: drop the fragile proxies, gate on preview

`mai_add_kirki_fonts()` (on `kirki_enqueue_google_fonts`) currently works (it does cache on the front end), but it is gated on the same fragile `! ( is_admin() || $ajax || $preview )` proxy. Unlike `dynamic_css`, this function *augments* Kirki's passed-in font list (it adds variants to families already present and appends additional fonts), so the clean "additions on an empty base" split does not apply. But Kirki's input font list is itself derived from the typography settings, and mai's augmentation is settings-derived, so the final font set is settings-deterministic, with the same single exception of customizer preview.

So the hardening is to replace the proxy with the real condition and keep the existing merged-value cache:

```php
$skip_cache = is_customize_preview(); // live/unsaved settings

if ( ! $skip_cache && $cached_fonts = mai_cache( 'css' )->get( 'dynamic_fonts' ) ) {
	return $cached_fonts;
}

// ... existing mai_add_font_variants() / mai_add_additional_fonts() / dedupe ...

if ( ! $skip_cache ) {
	mai_cache( 'css' )->set( 'dynamic_fonts', $fonts, 12 * HOUR_IN_SECONDS );
}
```

The implementation plan must empirically verify (via the same probe technique used for `kirki_global_styles`) the contexts in which `kirki_enqueue_google_fonts` fires, and confirm that dropping `is_admin`/`ajax` does not let a non-front-end font list be cached, before this change is accepted. The existing `static $has_run` guard and the `if ( ! $fonts ) return` early bail stay.

### 5. `classic_editor_styles`: unchanged

`mai_do_classic_editor_styles()` is already the model this redesign moves the others toward: it is its own `wp_ajax_*` endpoint, so its context is unambiguous; it is plain get-or-set on `mai_cache( 'css' )`; it isolates Kirki's fatal-prone `print_styles_inline()` in a try/catch; and it uses an error-aware TTL (one minute after a failure so it self-heals, one hour on success). No fragile context proxy, no change. The spec documents it here so the audit is complete.

## Invalidation

Unchanged and already correct. The `css` group flushes on `customize_save_after`, `after_switch_theme`, and `update_option_mai-engine` (wired in the cache-adoption sub-project). Once `dynamic_css` actually caches, those flushes bust it on every settings change, theme switch, and options update. The 12-hour TTL is only a backstop.

## Backward compatibility and behavioral notes

- The old `dynamic_css` transient has been dead since 2022, so there is no live cache to migrate; the first front-end request after deploy computes and caches as a normal miss.
- The cache now stores mai's *additions* (a smaller array) rather than the merged Kirki+mai result. This is internal; the value returned from the filter is the same merged array as before.
- The output is functionally equivalent to the old in-place pipeline. The base-first merge keeps Kirki's own keys (the `--title-area-padding-mobile` `:root` property, the `.header-stuck,:root` and `.header-right` selectors, the desktop `@media` block) in their positions, and mai's custom properties all land together in the single main `global > :root` block. Order among the custom properties is not forced; CSS custom properties are order-independent for distinct keys, so rendering is unchanged.

## Testing

- Probe `kirki_global_styles` and `kirki_enqueue_google_fonts` (temporary mu-plugin) to confirm: after the change, `mai_cache('css')->get('dynamic_css')` is non-empty after a front-end load (the cache is alive), and the customizer preview path does not write the cache.
- Confirm a customizer save flushes the `css` group (cached additions disappear, recompute on next load).
- Confirm `dynamic_fonts` still caches on the front end and the rendered font set is unchanged.
- Visual no-regression pass (browser) on homepage, a single post, and a term archive: layout, colors, fonts, and custom-property-driven styles unchanged versus the released build.
- Unit coverage where practical: `mai_merge_kirki_css()` (Kirki's keys keep their positions, mai's keys append into the same sub-arrays, mai wins on conflict, and mai's `:root` custom properties all land in the single `global > :root` block); `mai_build_kirki_css_additions()` returns a settings-derived array with no context dependence. A fixture built from the captured Kirki base (the `--title-area-padding-mobile` `:root` key, the header selectors, the `@media` block) asserts the merged output contains the same declarations as the old in-place result (order-insensitive).

## Risks and edge cases

- A front-end request that never calls `wp_head` (feeds, some AMP paths) would not output this CSS anyway; the filter simply recomputes if it fires, with no cache benefit and no harm.
- If a future `mai_add_*` contributor is added that reads request context (rather than settings), the determinism assumption breaks. The plan should note this invariant so it is preserved: contributors must be pure functions of saved settings.
- `dynamic_fonts` augments Kirki's input, so its safety rests on the input font list being settings-derived. The plan verifies this empirically before the change lands.

## Where this sits

This is the "generated CSS" caching target within the broader Redis caching initiative, built on the mai-cache foundation and the mai-engine cache adoption. It is independent of the menus, content-areas, and grid sub-projects.
