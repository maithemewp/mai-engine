# Mai Engine Cache Adoption Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Consolidate mai-engine's seven hand-rolled transients onto the shared `mai_cache()` wrapper (mai-cache 0.2.0), grouped by invalidation boundary, drop the over-broad per-`save_post` CSS flush, and make `wp mai flush` flush every mai-engine cache.

**Architecture:** Add `maithemewp/mai-cache:dev-develop` as a Composer dependency and a one-line `mai_cache( $group )` wrapper (prefix `mai`, transient mode). Then swap each `get_transient`/`set_transient`/`delete_transient` call for the equivalent `mai_cache($group)->get/set/delete/flush(...)`, preserving every surrounding condition. Behavior is identical except the intentional removal of the `save_post` CSS flush.

**Tech Stack:** PHP 8.1+, WordPress, Composer (committed `vendor/`), the `Mai\Cache\Cache` library.

## Global Constraints

- Prefix is `mai` for every cache (mai-engine is the base plugin and owns the `mai_*` namespace). Groups: `css`, `template-parts`, `demo`, `icons`, `mshots`. Two-level keys only (prefix + group).
- The migration is behavior-preserving: swap the storage primitive, keep all surrounding conditionals (frontend-only gates, error-vs-success TTL, only-on-HTTP-200 set, static memoization, `$use_transient` params). Do NOT convert conditional-cache sites to `remember()`.
- The one intentional behavior change: remove `mai_save_post_flush_customizer_transients` and its `save_post` hook. Verification gate before removal: confirm no post type legitimately changes the customizer CSS (the targeted `save_post_mai_template_part` hook is separate and stays).
- Leave `kirki_remote_url_contents` (Kirki-owned) and `pt_importer_data` (importer-owned) untouched.
- `mai_cache()` is guarded with `function_exists()` so a sibling plugin cannot fatal on redeclare.
- **Testing:** no new unit tests. The cache mechanics are unit-tested in mai-cache (21 tests); the wrapper is a trivial composition; `develop` has no unit-test harness and building one is out of scope here. Each task is verified by `php -l` on every changed PHP file plus review against the before/after in this plan. Final validation is the on-site integration checklist at the end, run by the user.
- Shipped text and commit messages: no em-dashes. `@since` tags use `2.40.0` (the working release version; adjust at release if it differs).
- Every commit message ends with these two lines verbatim:

  ```
  Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>
  Claude-Session: https://claude.ai/code/session_014Y3mM3j5pttsy276KJ3BUS
  ```

## File Structure

- Create: `lib/functions/cache.php` — the `mai_cache()` wrapper (one focused file).
- Modify: `composer.json` — add the VCS repository + the `maithemewp/mai-cache` require.
- Modify: `lib/init.php` — register the wrapper file in the `$files` load list; extend the `wp mai flush` CLI.
- Modify: `lib/customize/output.php` — CSS/font cache swaps; rewrite `mai_flush_customizer_transients()`; remove the `save_post` flush.
- Modify: `lib/functions/templates.php` — template-parts and demo cache swaps.
- Modify: `lib/fields/icons.php` — icon-choices cache swap.
- Modify: `lib/classes/class-mai-setup-wizard-demos.php` — mshots screenshot cache swap.
- Modify: `lib/admin/upgrade.php` — upgrade-time invalidation.

---

### Task 1: Dependency + the `mai_cache()` wrapper

**Files:**
- Modify: `composer.json`
- Create: `lib/functions/cache.php`
- Modify: `lib/init.php` (the `$files` array)

**Interfaces:**
- Consumes: `\Mai\Cache\Cache::for( string $prefix ): self` and `->group( string $group ): self` from the mai-cache library.
- Produces: `mai_cache( string $group = '' ): \Mai\Cache\Cache` — global helper. `mai_cache()` returns a transient-backed Cache bound to prefix `mai`; `mai_cache('x')` returns it scoped to group `x`.

- [ ] **Step 1 (controller, pre-authorized): push mai-cache `develop` to GitHub.** The VCS require below resolves `maithemewp/mai-cache:dev-develop` from `github.com/maithemewp/mai-cache`, so its `develop` (currently local-only at the 0.2.0 merge) must be pushed first. This push was authorized by the user. From `/Users/jivedig/LocalPackages/mai-cache`: `git push origin develop`.

- [ ] **Step 2: Add the repository + require to `composer.json`.** Change the `repositories` array from:

```json
  "repositories": [
    {
      "type": "composer",
      "url": "https://connect.advancedcustomfields.com"
    }
  ],
```

to:

```json
  "repositories": [
    {
      "type": "composer",
      "url": "https://connect.advancedcustomfields.com"
    },
    {
      "type": "vcs",
      "url": "https://github.com/maithemewp/mai-cache"
    }
  ],
```

and add `maithemewp/mai-cache` to the `require` block (alphabetical-ish, after `afragen/...`):

```json
    "afragen/wp-dependency-installer": "^4",
    "maithemewp/mai-cache": "dev-develop",
    "proteusthemes/wp-content-importer-v2": "^2.1",
```

(`minimum-stability` is already `dev` and `prefer-stable` is `true`, so `dev-develop` resolves cleanly.)

- [ ] **Step 3: Install and confirm the library resolves.**

Run: `composer update maithemewp/mai-cache --no-interaction`
Expected: `vendor/maithemewp/mai-cache/` is created (containing `init.php` and `src/`), and `composer.lock` + `vendor/composer/*` are regenerated. Then confirm the class loads:
Run: `composer dump-autoload && php -r "require 'vendor/autoload.php'; var_dump( class_exists( '\\Mai\\Cache\\Cache' ) );"`
Expected: `bool(true)`.

- [ ] **Step 4: Create `lib/functions/cache.php`.**

```php
<?php
/**
 * Mai Engine cache helper.
 *
 * @package   BizBudding\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright (c) BizBudding
 * @license   GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'mai_cache' ) ) {
	/**
	 * Returns mai-engine's cache instance, optionally scoped to an area group.
	 *
	 * Binds the `mai` prefix once. Pass an area group (e.g. 'css',
	 * 'template-parts') to scope reads, writes, and flushes to that area.
	 * Transient-backed: uses a persistent object cache (Redis) when present
	 * and the database otherwise.
	 *
	 * @since 2.40.0
	 *
	 * @param string $group Optional cache area group.
	 *
	 * @return \Mai\Cache\Cache
	 */
	function mai_cache( string $group = '' ): \Mai\Cache\Cache {
		$cache = \Mai\Cache\Cache::for( 'mai' );

		return $group ? $cache->group( $group ) : $cache;
	}
}
```

- [ ] **Step 5: Register the wrapper in `lib/init.php`.** In the `$files` array (the base list near the top of the includes function, alongside the other `'functions/...'` entries), add `'functions/cache',`. This makes the `foreach ( $files as $file ) { require_once __DIR__ . "/$file.php"; }` loop load it.

- [ ] **Step 6: Verify.**

Run: `php -l lib/functions/cache.php && php -l lib/init.php`
Expected: `No syntax errors detected` for both.

- [ ] **Step 7: Commit.**

```bash
git add composer.json composer.lock vendor lib/functions/cache.php lib/init.php
git commit -m "feat: add mai-cache dependency and mai_cache() wrapper"
```

(Append the Global Constraints commit footers. The `vendor` add includes the new `maithemewp/mai-cache` library and the regenerated autoloader.)

---

### Task 2: Migrate the CSS group + fix the save_post flush

**Files:**
- Modify: `lib/customize/output.php`

**Interfaces:**
- Consumes: `mai_cache( 'css' )` from Task 1, with `->get( $key )`, `->set( $key, $value, $ttl )`, `->flush()`.
- Produces: the `css` group holding keys `dynamic_css`, `dynamic_fonts`, `classic_editor_styles`; `mai_flush_customizer_transients()` now flushes that group.

- [ ] **Step 1: Swap `mai_dynamic_css` storage** in `mai_add_kirki_css()`. Remove the `$transient = 'mai_dynamic_css';` line. Change the read from:

```php
	if ( $use_transients && $cached_css = get_transient( $transient ) ) {
```

to:

```php
	if ( $use_transients && $cached_css = mai_cache( 'css' )->get( 'dynamic_css' ) ) {
```

and the write from:

```php
	if ( $use_transients ) {
		set_transient( $transient, $css, HOUR_IN_SECONDS );
	}
```

to:

```php
	if ( $use_transients ) {
		mai_cache( 'css' )->set( 'dynamic_css', $css, HOUR_IN_SECONDS );
	}
```

- [ ] **Step 2: Swap `mai_dynamic_fonts` storage** in `mai_add_kirki_fonts()`. Remove the `$transient = 'mai_dynamic_fonts';` line. Change the read from:

```php
	if ( ! ( $admin || $ajax || $preview ) && $cached_fonts = get_transient( $transient ) ) {
```

to:

```php
	if ( ! ( $admin || $ajax || $preview ) && $cached_fonts = mai_cache( 'css' )->get( 'dynamic_fonts' ) ) {
```

and the write from:

```php
	if ( ! ( $admin || $ajax || $preview ) ) {
		set_transient( $transient, $fonts, HOUR_IN_SECONDS );
	}
```

to:

```php
	if ( ! ( $admin || $ajax || $preview ) ) {
		mai_cache( 'css' )->set( 'dynamic_fonts', $fonts, HOUR_IN_SECONDS );
	}
```

- [ ] **Step 3: Swap `mai_classic_editor_styles` storage** in `mai_do_classic_editor_styles()`. Remove the `$transient = 'mai_classic_editor_styles';` line. Change the read from:

```php
	$css       = get_transient( $transient );
```

to:

```php
	$css       = mai_cache( 'css' )->get( 'classic_editor_styles' );
```

and the write (preserving the error-vs-success TTL) from:

```php
		set_transient( $transient, $css, $error ? MINUTE_IN_SECONDS : HOUR_IN_SECONDS );
```

to:

```php
		mai_cache( 'css' )->set( 'classic_editor_styles', $css, $error ? MINUTE_IN_SECONDS : HOUR_IN_SECONDS );
```

Leave the `get_transient( 'kirki_remote_url_contents' )` read in this function untouched (Kirki-owned).

- [ ] **Step 4: Rewrite `mai_flush_customizer_transients()` to a group flush.** Replace the function body from:

```php
function mai_flush_customizer_transients() {
	$transients = [
		'mai_dynamic_css',
		'mai_dynamic_fonts',
		'mai_classic_editor_styles',
	];
	foreach ( $transients as $transient ) {
		delete_transient( $transient );
	}
}
```

to:

```php
function mai_flush_customizer_transients() {
	mai_cache( 'css' )->flush();
}
```

Keep the three `add_action(...)` registrations above it (`after_switch_theme`, `customize_save_after`, `update_option_mai-engine`) unchanged.

- [ ] **Step 5: Remove the over-broad save_post flush.** First verify no post type legitimately changes the customizer CSS (the CSS derives from Kirki/customizer settings and the mai-engine option; the only post-driven input, template parts, has its own targeted `save_post_mai_template_part` invalidation). Then delete both the registration and the function:

```php
add_action( 'save_post', 'mai_save_post_flush_customizer_transients', 999, 3 );
/**
 * Flush transients when saving/updating posts.
 *
 * @since 2.21.0
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 * @param bool    $update  Whether this is an existing post being updated.
 *
 * @return void
 */
function mai_save_post_flush_customizer_transients( $post_id, $post, $update ) {
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	mai_flush_customizer_transients();
}
```

- [ ] **Step 6: Verify.**

Run: `php -l lib/customize/output.php`
Expected: `No syntax errors detected`.
Also confirm the old keys are gone: `grep -n "mai_dynamic_css\|mai_dynamic_fonts\|mai_classic_editor_styles\|mai_save_post_flush_customizer_transients" lib/customize/output.php` should return nothing.

- [ ] **Step 7: Commit.**

```bash
git add lib/customize/output.php
git commit -m "refactor: move CSS/font caches to mai_cache('css'); drop save_post CSS flush"
```

(Append the Global Constraints commit footers.)

---

### Task 3: Migrate template parts + demo

**Files:**
- Modify: `lib/functions/templates.php`
- Modify: `lib/init.php`

**Interfaces:**
- Consumes: `mai_cache( 'template-parts' )` and `mai_cache( 'demo' )` from Task 1.
- Produces: `template-parts` group key `objects`; `demo` group key `objects`; the template-part save hook flushes the `template-parts` group; `after_switch_theme` flushes the `demo` group.

- [ ] **Step 1: Swap `mai_template_parts` storage** in `mai_get_template_part_objects()`. Remove the `$transient = 'mai_template_parts';` line. Change the read from:

```php
		if ( ! ( $use_transient && $parts = get_transient( $transient ) ) ) {
```

to:

```php
		if ( ! ( $use_transient && $parts = mai_cache( 'template-parts' )->get( 'objects' ) ) ) {
```

and the write from:

```php
			// Set transient, and expire after 1 hour.
			set_transient( $transient, $parts, HOUR_IN_SECONDS );
```

to:

```php
			// Cache for 1 hour.
			mai_cache( 'template-parts' )->set( 'objects', $parts, HOUR_IN_SECONDS );
```

(Keep the `static $template_parts` memoization and the `$use_transient` param.)

- [ ] **Step 2: Swap `mai_demo_template_parts` storage** in `mai_get_template_parts_from_demo()`. Change the read from:

```php
	if ( false === ( $template_parts = get_transient( 'mai_demo_template_parts' ) ) ) {
```

to:

```php
	if ( false === ( $template_parts = mai_cache( 'demo' )->get( 'objects' ) ) ) {
```

and the write from:

```php
		set_transient( 'mai_demo_template_parts', $template_parts, HOUR_IN_SECONDS );
```

to:

```php
		mai_cache( 'demo' )->set( 'objects', $template_parts, HOUR_IN_SECONDS );
```

- [ ] **Step 3: Rewrite the template-part save invalidation** in `lib/init.php`. Change the body of `mai_save_template_part_delete_transient()` from:

```php
	delete_transient( 'mai_template_parts' );
```

to:

```php
	mai_cache( 'template-parts' )->flush();
```

Keep its `add_action( 'save_post_mai_template_part', 'mai_save_template_part_delete_transient', 20, 3 )` registration unchanged.

- [ ] **Step 4: Flush the demo cache on theme switch.** In `lib/functions/templates.php`, add this near `mai_get_template_parts_from_demo()`:

```php
add_action( 'after_switch_theme', 'mai_flush_demo_template_parts' );
/**
 * Flush the demo template-parts cache on theme switch.
 *
 * Demos are theme-dependent (registered via the mai_setup_wizard_demos filter),
 * so switching themes should refresh the cached demo content.
 *
 * @since 2.40.0
 *
 * @return void
 */
function mai_flush_demo_template_parts() {
	mai_cache( 'demo' )->flush();
}
```

- [ ] **Step 5: Verify.**

Run: `php -l lib/functions/templates.php && php -l lib/init.php`
Expected: `No syntax errors detected` for both.
Confirm: `grep -n "mai_template_parts\|mai_demo_template_parts" lib/functions/templates.php lib/init.php` returns nothing (no stale literal keys).

- [ ] **Step 6: Commit.**

```bash
git add lib/functions/templates.php lib/init.php
git commit -m "refactor: move template-part caches to mai_cache; flush demo on theme switch"
```

(Append the Global Constraints commit footers.)

---

### Task 4: Migrate icons (leave mshots as a raw transient)

**Files:**
- Modify: `lib/fields/icons.php`
- Modify: `lib/classes/class-mai-setup-wizard-demos.php` (comment only)

**Interfaces:**
- Consumes: `mai_cache( 'icons' )` from Task 1.
- Produces: `icons` group key `choices_{style}`. The mshots screenshot cache is intentionally NOT migrated; it stays a raw transient, exempt from the broad `mai_cache()->flush()`.

- [ ] **Step 1: Swap `mai_icon_choices_{style}` storage** in `mai_get_icon_choices()`. Replace the line:

```php
	$transient = 'mai_icon_choices_' . $style;

	if ( false === ( $choices = get_transient( $transient ) ) ) {
```

with:

```php
	if ( false === ( $choices = mai_cache( 'icons' )->get( "choices_{$style}" ) ) ) {
```

and the write from:

```php
		// Set transient, and expire after 1 hour.
		set_transient( $transient, $choices, 1 * HOUR_IN_SECONDS );
```

to:

```php
		// Cache for 1 hour.
		mai_cache( 'icons' )->set( "choices_{$style}", $choices, 1 * HOUR_IN_SECONDS );
```

(Keep the `static $cache` per-request memoization and the early `return $choices;` when the icons dir/url are missing.)

- [ ] **Step 2: Document why mshots stays a raw transient.** In `Mai_Setup_Wizard_Demos::get_screenshot()` (`lib/classes/class-mai-setup-wizard-demos.php`), add a comment directly above the `$cache_key = md5( $src );` line. Do NOT change the `get_transient`/`set_transient` calls; mshots intentionally stays a raw transient so the broad `mai_cache()->flush()` (wp mai flush, upgrades) never touches it:

```php
		// Intentionally a raw transient, not mai_cache: these mShots demo screenshots are an
		// expensive external resource that rarely changes, so they are kept out of the
		// flushable mai_cache layer and simply age out by the DAY TTL, which avoids needless
		// re-fetches from wordpress.com/mshots.
		$cache_key = md5( $src );
```

- [ ] **Step 3: Verify.**

Run: `php -l lib/fields/icons.php && php -l lib/classes/class-mai-setup-wizard-demos.php`
Expected: `No syntax errors detected` for both.

- [ ] **Step 4: Commit.**

```bash
git add lib/fields/icons.php lib/classes/class-mai-setup-wizard-demos.php
git commit -m "refactor: move icon-choices cache to mai_cache; document mshots staying raw"
```

(Append the Global Constraints commit footers.)

---

### Task 5: Upgrade-time invalidation + `wp mai flush`

**Files:**
- Modify: `lib/admin/upgrade.php`
- Modify: `lib/init.php`

**Interfaces:**
- Consumes: `mai_cache()->flush()` (prefix-level, busts every mai-engine group) from Task 1.
- Produces: upgrades and `wp mai flush` perform a full prefix-level cache flush.

- [ ] **Step 1: Replace the upgrade flush** in `mai_upgrade_complete()` (`lib/admin/upgrade.php`). Change the inner block from:

```php
	foreach( $options['plugins'] as $plugin ) {
		if ( $current_plugin !== $plugin ) {
			mai_flush_customizer_transients();
			delete_transient( 'mai_template_parts' );
			delete_transient( 'mai_classic_editor_styles' );
		}
	}
```

to:

```php
	foreach( $options['plugins'] as $plugin ) {
		if ( $current_plugin !== $plugin ) {
			mai_cache()->flush();
		}
	}
```

- [ ] **Step 2: Replace the 2.11.0 upgrade deletes** in `mai_upgrade_2_11_0()`. Change:

```php
	if ( $success ) {
		delete_transient( 'mai_template_parts' );
		delete_transient( 'mai_demo_template_parts' );
		flush_rewrite_rules( false );
```

to:

```php
	if ( $success ) {
		mai_cache()->flush();
		flush_rewrite_rules( false );
```

- [ ] **Step 3: Extend `wp mai flush`** in `lib/init.php`. Change the command from:

```php
		WP_CLI::add_command( 'mai flush', function() {
			$message = mai_typography_flush_local_fonts();
			WP_CLI::success( $message );
		});
```

to:

```php
		WP_CLI::add_command( 'mai flush', function() {
			$message = mai_typography_flush_local_fonts();
			mai_cache()->flush();
			WP_CLI::success( $message . ' Flushed all mai-engine caches.' );
		});
```

- [ ] **Step 4: Verify.**

Run: `php -l lib/admin/upgrade.php && php -l lib/init.php`
Expected: `No syntax errors detected` for both.
Confirm no stale literal transient keys remain anywhere: `grep -rn "get_transient\|set_transient\|delete_transient" lib/ --include='*.php' | grep -E "mai_dynamic_css|mai_dynamic_fonts|mai_classic_editor_styles|mai_template_parts|mai_demo_template_parts|mai_icon_choices" ` should return nothing.

- [ ] **Step 5: Commit.**

```bash
git add lib/admin/upgrade.php lib/init.php
git commit -m "refactor: full mai_cache flush on upgrade and in wp mai flush"
```

(Append the Global Constraints commit footers.)

---

## On-site integration verification (run by the user after implementation)

This is the meaningful test for a storage migration and is run on a real WordPress site (ideally one with a persistent object cache to exercise the Redis path, and one without to exercise the DB-transient path):

1. **Caching works:** load the front end twice; confirm the dynamic CSS is served and the second load is a cache hit (no regeneration). Repeat for template parts (a page that renders one) and icon fields (an editor screen).
2. **Correct invalidations fire:** save a Customizer change, then switch themes, then update the mai-engine option, and confirm the `css` cache is rebuilt after each. Save a `mai_template_part` post and confirm the `template-parts` cache rebuilds.
3. **The fix:** edit and save an ordinary post (not a template part) and confirm the dynamic CSS is NOT regenerated (the `css` cache survives the save). This is the core improvement.
4. **CLI:** run `wp mai flush` and confirm it reports success and that all mai-engine caches (CSS, template parts, icons) are cleared on the next load.
5. **No-Redis path:** on a site without a persistent object cache, confirm everything above still works via DB transients.

## Notes for the executor

- Work in `/Users/jivedig/Plugins/mai-engine` on branch `feature/mai-cache-adoption`.
- The pre-existing uncommitted `vendor/composer/*` working-tree changes are not yours; `composer update` in Task 1 regenerates those files cleanly, which resolves them. Commit the regenerated result as part of Task 1.
- Tasks 2 through 5 each depend only on Task 1 (the `mai_cache()` wrapper). They touch mostly separate files and can be reviewed independently.
