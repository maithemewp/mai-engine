# Dynamic CSS Cache Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make mai's generated global CSS (`dynamic_css`) actually cache on the front end, by caching mai's deterministic, settings-derived additions and merging them into each context's own Kirki array, removing the fragile `did_action('wp_head')` gate; harden `dynamic_fonts` to the same principle.

**Architecture:** mai injects its CSS custom properties into Kirki via the `kirki_global_styles` filter, which Kirki fires in many contexts. Today the cache is gated on a context proxy that never fires on the front end, so it is dead. Because every `mai_add_*` contributor is a pure function of saved settings, the computed additions are identical in every context except the customizer preview. So we cache the additions once (preview excluded) and merge them, base-first, into whatever array the current context passes. No context-specific value is ever cached, so the historical corruption bug cannot recur.

**Tech Stack:** PHP 8.1+, WordPress, the bundled Kirki framework (`packages/kirki`), the `mai_cache()` wrapper (`mai_cache('css')`, transient-backed), and the Brain Monkey + PHPUnit unit suite (`composer test-unit`).

## Global Constraints

- PHP 8.1+ (the plugin's floor).
- Branch `feature/dynamic-css-cache` is cut from `develop`, which already has the cache adoption (`mai_cache('css')` + the css-group flush hooks `customize_save_after`, `after_switch_theme`, `update_option_mai-engine`) and the Brain Monkey/PHPUnit harness.
- The unit suite runs via `composer test-unit`. ABSPATH is pre-defined through `auto_prepend_file=tests/phpunit/define-abspath.php` in that script, because mai-cache's vendored `init.php` exits at autoload time otherwise. Do not run raw `vendor/bin/phpunit` without that prepend (it silently exits).
- No em-dashes in any shipped text (comments, commit messages, changelog). Use commas, semicolons, colons, parentheses, or periods.
- Comments carry durable "why" only. No change-narration ("was X, now Y"); the commit and changelog carry that.
- Code style and docblocks must match the existing codebase. New files open with the standard file header (`@package BizBudding\MaiEngine` / `@link` / `@author` / `@copyright` / `@license`, as in `lib/functions/cache.php`), then `defined( 'ABSPATH' ) || exit;`. Function docblocks use the WordPress format with blank lines between the description, `@since`, the `@param` block (types/names aligned), and `@return`. New functions are `@since 2.40.0`; the rewritten `mai_add_kirki_css()` keeps its existing `@since 2.12.0`. Tabs for indentation; a blank line before `return`.
- Output must stay equivalent to the old in-place pipeline: the same CSS declarations are emitted. Order among custom properties is not forced, but mai's `:root` custom properties must all land together in the single `global > :root` block, and Kirki's own keys keep their positions.
- `dynamic_css` and `dynamic_fonts` TTL is `12 * HOUR_IN_SECONDS` (a backstop; the css-group flush does the real invalidation).
- Commit messages end with the two-line footer:
  `Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>`
  `Claude-Session: https://claude.ai/code/session_014Y3mM3j5pttsy276KJ3BUS`

---

## File Structure

- `lib/customize/css-cache.php` (create) — holds only the pure `mai_merge_kirki_css()` helper. Side-effect-free (no hook registrations), so the unit suite can `require_once` it in isolation without booting WordPress.
- `lib/init.php` (modify) — add `'customize/css-cache'` to the customize `$files` list, just before `'customize/output'`.
- `lib/customize/output.php` (modify) — replace the `mai_add_kirki_css()` body; add `mai_get_kirki_css_additions()` and `mai_build_kirki_css_additions()`; change the `mai_add_kirki_fonts()` cache gate. The `mai_add_*` contributor functions and `classic_editor_styles` are untouched.
- `tests/phpunit/unit/MergeKirkiCssTest.php` (create) — Brain Monkey/PHPUnit test for `mai_merge_kirki_css()`.

---

## Pre-flight (controller, before Task 1)

The live equivalence check in Task 4 compares the rendered CSS before and after the change, so capture the baseline now, while the symlinked site is still running the old in-place pipeline (the current `develop`).

- [ ] **Capture the baseline rendered custom-property declarations**

```bash
SITE=https://totalprosports.test
for path in "/" "/mlb/san-francisco-giants/harsh-warning-players-protested-against-pride-night/"; do
  curl -ks "$SITE$path" \
    | perl -0777 -ne 'print $1 if m{<style id="mai-inline-styles">(.*?)</style>}s'
done | grep -oE '\-\-[a-z0-9-]+:[^;}]+' | sort -u > /tmp/dynamic-css-baseline.txt
wc -l /tmp/dynamic-css-baseline.txt
```

Expected: a non-empty, sorted, de-duplicated list of `--token:value` declarations. Keep this file for Task 4.

---

## Task 1: The base-first merge helper (pure, unit-tested)

**Files:**
- Create: `lib/customize/css-cache.php`
- Modify: `lib/init.php` (add `'customize/css-cache'` to the customize `$files` list, before `'customize/output'` at ~line 383)
- Create: `tests/phpunit/unit/MergeKirkiCssTest.php`

**Interfaces:**
- Produces: `mai_merge_kirki_css( array $base, array $additions ): array` — deep-merges `$additions` onto `$base` base-first. `$base` keys keep their positions; keys present only in `$additions` are appended; on a leaf (scalar) conflict `$additions` wins; shared array keys recurse. Used by Task 2's `mai_add_kirki_css()`.

- [ ] **Step 1: Write the failing test**

Create `tests/phpunit/unit/MergeKirkiCssTest.php`:

```php
<?php

namespace BizBudding\MaiEngine\Tests\Unit;

use BizBudding\MaiEngine\Tests\TestCase;

require_once dirname( __DIR__, 3 ) . '/lib/customize/css-cache.php';

final class MergeKirkiCssTest extends TestCase {

	/** @return array{0: array, 1: array} */
	private function fixture(): array {
		$base = [
			'global' => [
				'.header-stuck,:root' => [ '--header-stuck-x' => '1' ],
				':root'               => [ '--title-area-padding-mobile' => '10px', '--color-primary' => 'OLD' ],
				'.header-right'       => [ '--header-right-y' => '2' ],
			],
			'@media (min-width: 1000px)' => [ ':root' => [ '--breakpoint-active' => 'lg' ] ],
		];
		$additions = [
			'global' => [
				':root' => [ '--breakpoint-md' => '1000px', '--color-primary' => 'NEW', '--heading-font-family' => 'Inter' ],
				'.is-style-altfont:where(p, span)' => [ 'font-family' => 'var(--alt-font-family)' ],
			],
		];
		return [ $base, $additions ];
	}

	public function test_kirki_root_key_is_preserved_and_first(): void {
		[ $base, $additions ] = $this->fixture();
		$root = mai_merge_kirki_css( $base, $additions )['global'][':root'];
		$this->assertSame( '--title-area-padding-mobile', array_key_first( $root ) );
	}

	public function test_mai_root_keys_all_land_in_one_root_block(): void {
		[ $base, $additions ] = $this->fixture();
		$global = mai_merge_kirki_css( $base, $additions )['global'];
		$root_selectors = array_filter( array_keys( $global ), static fn ( $k ) => ':root' === $k );
		$this->assertCount( 1, $root_selectors );
		$this->assertArrayHasKey( '--breakpoint-md', $global[':root'] );
		$this->assertArrayHasKey( '--heading-font-family', $global[':root'] );
	}

	public function test_mai_wins_on_leaf_conflict(): void {
		[ $base, $additions ] = $this->fixture();
		$root = mai_merge_kirki_css( $base, $additions )['global'][':root'];
		$this->assertSame( 'NEW', $root['--color-primary'] );
	}

	public function test_kirki_siblings_and_media_block_kept_in_place(): void {
		[ $base, $additions ] = $this->fixture();
		$merged = mai_merge_kirki_css( $base, $additions );
		$this->assertSame(
			[ '.header-stuck,:root', ':root', '.header-right' ],
			array_slice( array_keys( $merged['global'] ), 0, 3 )
		);
		$this->assertArrayHasKey( '.is-style-altfont:where(p, span)', $merged['global'] );
		$this->assertSame( [ ':root' => [ '--breakpoint-active' => 'lg' ] ], $merged['@media (min-width: 1000px)'] );
	}
}
```

- [ ] **Step 2: Run it to verify it fails**

Run: `composer test-unit`
Expected: a fatal/error because `lib/customize/css-cache.php` does not exist yet (the `require_once` fails). That counts as red; proceed.

- [ ] **Step 3: Create the helper**

Create `lib/customize/css-cache.php`:

```php
<?php
/**
 * Mai Engine Kirki CSS cache helpers.
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
 * Deep-merges mai's cached additions onto Kirki's per-context styles array, base-first.
 *
 * Kirki's own keys keep their positions; keys present only in $additions are appended;
 * mai wins on a leaf conflict (the old in-place pipeline overwrote). mai's :root custom
 * properties therefore all land together in the single global > :root block. Pure helper
 * (no WordPress), kept in its own file so it can be unit-tested in isolation.
 *
 * @since 2.40.0
 *
 * @param array $base      Kirki's styles array for the current context.
 * @param array $additions mai's settings-derived additions.
 *
 * @return array
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

- [ ] **Step 4: Run the test to verify it passes**

Run: `composer test-unit`
Expected: green, including the four new `MergeKirkiCssTest` cases.

- [ ] **Step 5: Wire the file into the loader**

In `lib/init.php`, in the customize `$files` list, add `'customize/css-cache',` immediately before `'customize/output', // Output last.` (around line 383):

```php
				'customize/css-cache',
				'customize/output', // Output last.
```

- [ ] **Step 6: Lint**

Run: `php -l lib/customize/css-cache.php && php -l lib/init.php`
Expected: `No syntax errors detected` for both.

- [ ] **Step 7: Commit**

```bash
git add lib/customize/css-cache.php lib/init.php tests/phpunit/unit/MergeKirkiCssTest.php
git commit -m "feat: add base-first mai_merge_kirki_css() helper with unit tests"
```

---

## Task 2: Cache deterministic additions and rewire dynamic_css

**Files:**
- Modify: `lib/customize/output.php` (the current `mai_add_kirki_css()` function, around lines 59-102)

**Interfaces:**
- Consumes: `mai_merge_kirki_css()` (Task 1, in `lib/customize/css-cache.php`); `mai_cache('css')`; the existing `mai_add_*` contributors (unchanged).
- Produces: `mai_get_kirki_css_additions(): array` and `mai_build_kirki_css_additions(): array`.

- [ ] **Step 1: Replace the `mai_add_kirki_css()` body and add the two new functions**

Replace the entire current `mai_add_kirki_css()` function (the version with the commented-out `$has_run` block, the `$admin = did_action( 'wp_head' )` gate, and the in-place `mai_add_*` pipeline) with:

```php
/**
 * Adds mai's global CSS custom properties to Kirki's styles array.
 *
 * mai's additions are a pure function of saved settings, so they are computed once and
 * cached (mai_get_kirki_css_additions), then merged into whatever array Kirki passes in
 * the current context. Nothing context-specific is ever cached.
 *
 * @since 2.12.0
 *
 * @param array $css Kirki global styles array.
 *
 * @return array
 */
function mai_add_kirki_css( $css ) {
	if ( ! is_array( $css ) ) {
		$css = [];
	}

	return mai_merge_kirki_css( $css, mai_get_kirki_css_additions() );
}

/**
 * Returns mai's global CSS additions, cached.
 *
 * The customizer preview reflects live, unsaved settings, so there the additions are
 * recomputed and the cache is left untouched. Everywhere else the value is identical, so
 * it is cached; the css-group flush busts it on any settings, theme, or option change.
 *
 * @since 2.40.0
 *
 * @return array
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
 * Builds mai's global CSS additions on an empty base, so the result holds only mai's
 * settings-derived custom properties with nothing context-specific captured.
 *
 * Every contributor here must stay a pure function of saved settings; if one starts
 * reading request context, the cache-once assumption in mai_get_kirki_css_additions breaks.
 *
 * @since 2.40.0
 *
 * @return array
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

Notes for the implementer: `mai_merge_kirki_css()` lives in `lib/customize/css-cache.php` (Task 1); do not redefine it here. The old "Make sure :root is set" guard now lives in `mai_build_kirki_css_additions()` (the empty base seeds `global > :root`). Do not modify any `mai_add_*` contributor.

- [ ] **Step 2: Lint and run the suite**

Run: `php -l lib/customize/output.php && composer test-unit`
Expected: no syntax errors; the unit suite stays green (no new unit tests here, but confirm nothing broke).

- [ ] **Step 3: Commit**

```bash
git add lib/customize/output.php
git commit -m "feat: cache deterministic dynamic_css additions, drop did_action gate"
```

(On-site verification that the cache is now alive and output is unchanged happens in Task 4.)

---

## Task 3: Harden the dynamic_fonts gate

**Files:**
- Modify: `lib/customize/output.php` (the `mai_add_kirki_fonts()` cache gate, around the current `$admin = is_admin()` lines)

**Interfaces:**
- Consumes: `mai_cache('css')`; `mai_add_font_variants()` / `mai_add_additional_fonts()` (unchanged).

- [ ] **Step 1 (controller, gate): probe where `kirki_enqueue_google_fonts` fires**

Before changing the gate, confirm the filter does not fire in a non-front-end context with a divergent font list (which dropping `is_admin` would then cache and serve). Drop a temporary mu-plugin on the symlinked site:

```php
<?php // wp-content/mu-plugins/zz-fonts-probe.php  (TEMPORARY)
add_filter( 'kirki_enqueue_google_fonts', function ( $fonts ) {
	error_log( sprintf(
		"[fonts-probe] uri=%s is_admin=%d ajax=%d preview=%d rest=%d stack=%s families=%s\n",
		$_SERVER['REQUEST_URI'] ?? '?',
		is_admin() ? 1 : 0,
		( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) ? 1 : 0,
		is_customize_preview() ? 1 : 0,
		( defined( 'REST_REQUEST' ) && REST_REQUEST ) ? 1 : 0,
		implode( ' > ', (array) ( $GLOBALS['wp_current_filter'] ?? [] ) ),
		is_array( $fonts ) ? implode( ',', array_keys( $fonts ) ) : gettype( $fonts )
	), 3, WP_CONTENT_DIR . '/fonts-probe.log' );
	return $fonts;
}, 1 );
```

Load a few front-end pages, then read `wp-content/fonts-probe.log`. Expected: the filter fires on front-end requests with `is_admin=0`, `preview=0`, and the site's configured families. Decision gate:
- If every non-preview firing carries the same front-end family set, proceed to Step 2.
- If any non-preview, non-front-end context (REST, admin-ajax) fires with a different/empty family set, do NOT drop `is_admin`; stop and report so we keep a guard for that context. Remove the probe mu-plugin and its log either way.

- [ ] **Step 2: Replace the gate**

In `mai_add_kirki_fonts()`, replace the three context variables and the two `! ( $admin || $ajax || $preview )` guards with a single preview check:

```php
	// The customizer preview reflects live, unsaved settings; everywhere else the font
	// set is a pure function of saved settings, so it is safe to cache and reuse.
	$skip_cache = is_customize_preview();

	if ( ! $skip_cache && $cached_fonts = mai_cache( 'css' )->get( 'dynamic_fonts' ) ) {
		return $cached_fonts;
	}

	$fonts = mai_add_font_variants( $fonts );
	$fonts = mai_add_additional_fonts( $fonts );

	// Remove any duplicates.
	foreach ( $fonts as $font_family => $font_variants ) {
		$fonts[ $font_family ] = array_unique( $fonts[ $font_family ] );
	}

	if ( ! $skip_cache ) {
		mai_cache( 'css' )->set( 'dynamic_fonts', $fonts, 12 * HOUR_IN_SECONDS );
	}

	return $fonts;
```

Keep the existing `static $has_run` guard and the `if ( ! $fonts ) { return $fonts; }` early bail above this block.

- [ ] **Step 3: Lint and run the suite**

Run: `php -l lib/customize/output.php && composer test-unit`
Expected: no syntax errors; suite green.

- [ ] **Step 4: Commit**

```bash
git add lib/customize/output.php
git commit -m "refactor: gate dynamic_fonts cache on customizer preview only"
```

---

## Task 4: On-site verification (controller)

Run against the symlinked site (`https://totalprosports.test`, which is `~/Plugins/mai-engine` on this branch). No commit; this is the integration gate.

- [ ] **Step 1: Output equivalence (same declarations as the baseline)**

```bash
SITE=https://totalprosports.test
wp --path="$HOME/Herd/totalprosports" mai flush >/dev/null 2>&1   # clear css group
for path in "/" "/mlb/san-francisco-giants/harsh-warning-players-protested-against-pride-night/"; do
  curl -ks "$SITE$path" \
    | perl -0777 -ne 'print $1 if m{<style id="mai-inline-styles">(.*?)</style>}s'
done | grep -oE '\-\-[a-z0-9-]+:[^;}]+' | sort -u > /tmp/dynamic-css-after.txt
diff /tmp/dynamic-css-baseline.txt /tmp/dynamic-css-after.txt && echo "EQUIVALENT: same custom-property declarations"
```

Expected: empty diff (same declarations; order is intentionally not compared). Investigate any diff before proceeding.

- [ ] **Step 2: The cache is now alive**

```bash
wp --path="$HOME/Herd/totalprosports" mai flush >/dev/null 2>&1
curl -ks -o /dev/null "$SITE/"   # one front-end load to populate
wp --path="$HOME/Herd/totalprosports" eval '
  $a = mai_cache("css")->get("dynamic_css");
  echo is_array($a) ? "dynamic_css CACHED, :root keys=" . count($a["global"][":root"] ?? []) . "\n" : "NOT CACHED\n";
' 2>&1 | grep -v "Deprecated:"
```

Expected: `dynamic_css CACHED, :root keys=N` with N > 0 (the dead cache is alive).

- [ ] **Step 3: Customizer preview does not write the cache, and a save flushes it**

Manual: with the cache populated, open the Customizer (preview recomputes, must not overwrite the saved cache) and confirm `mai_cache("css")->get("dynamic_css")` still returns the saved value; then Publish a change and confirm the entry is gone (flushed) and repopulates on the next front-end load. Confirm the page renders correctly in the preview.

- [ ] **Step 4: dynamic_fonts still caches and renders unchanged**

```bash
wp --path="$HOME/Herd/totalprosports" eval '
  echo is_array(mai_cache("css")->get("dynamic_fonts")) ? "dynamic_fonts CACHED\n" : "fonts not cached yet\n";
' 2>&1 | grep -v "Deprecated:"
```

Expected: `dynamic_fonts CACHED` after a front-end load. Spot-check that the rendered `@font-face`/font-family output is unchanged versus baseline.

- [ ] **Step 5: Visual no-regression (browser)**

Via the cmux browser: homepage, a single post, and a team archive. Confirm layout, colors, fonts, and custom-property-driven styling are unchanged versus the released build (compare to the earlier visual pass).

---

## Self-review notes

- Spec coverage: Task 1 = the merge (in its own file, unit-tested via the merged harness); Task 2 = `dynamic_css` (builder, cache accessor, preview gate, rewire); Task 3 = `dynamic_fonts` hardening with the spec's required empirical probe; Task 4 = the spec's testing section (equivalence, cache-alive, preview, flush, fonts, visual). `classic_editor_styles` is intentionally untouched per the spec.
- Type consistency: `mai_merge_kirki_css(array $base, array $additions): array`, `mai_get_kirki_css_additions(): array`, `mai_build_kirki_css_additions(): array` are used consistently across tasks.
- No placeholders: every code step shows the full code; the one judgement call (Task 3's probe outcome) is written as an explicit decision gate, not a TODO.
