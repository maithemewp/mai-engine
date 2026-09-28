# Mai Engine Cache Adoption Design

Date: 2026-06-25
Status: Design approved, pending implementation plan
Component: mai-engine. First consumer of the `maithemewp/mai-cache` 0.2.0 foundation.
Target runtime: PHP 8.1+

## Background

mai-engine caches several expensive, rarely-changing values with scattered, hand-rolled `get_transient` / `set_transient` / `delete_transient` calls. There is no shared cache wrapper. Two problems follow: the caching is inconsistent and hard to invalidate coherently, and one invalidation is actively harmful. The three customizer-derived CSS/font caches are flushed on every `save_post` (priority 999, `mai_save_post_flush_customizer_transients`), even though that CSS comes from theme/customizer settings, not post content. On a busy site the CSS regenerates on every post save for no reason.

The new `maithemewp/mai-cache` 0.2.0 library (already built and merged to its `develop`) provides a transient-backed `remember()` wrapper with Redis-when-present plus DB fallback, an object-cache-only mode, and a token-based group flush. This sub-project makes mai-engine the first consumer: it consolidates the existing transients onto a single `mai_cache()` wrapper, fixes the over-broad CSS invalidation, and wires a global flush into the existing `wp mai flush` CLI command.

This is the first of several mai-engine caching sub-projects (the planned order is: this transient migration, then menus, then grid result caching). Menus and grid are out of scope here.

## Goal

Replace mai-engine's scattered transient calls with the shared `mai_cache()` wrapper, group caches by invalidation boundary, drop the unnecessary per-`save_post` CSS flush, and make `wp mai flush` flush all mai-engine caches. Behavior is preserved everywhere except the intentional removal of the `save_post` CSS flush.

## Scope

In scope: six of the seven mai-engine-owned transients (the mshots demo-screenshot cache is deliberately excluded, see below).

| Current transient | TTL | Caches | Set / Get / Delete (file:line) |
|---|---|---|---|
| `mai_dynamic_css` | HOUR | assembled Kirki CSS array | set `lib/customize/output.php:124`, get `:105` |
| `mai_dynamic_fonts` | HOUR | deduped Google font families | set `lib/customize/output.php:176`, get `:163` |
| `mai_classic_editor_styles` | HOUR (MINUTE on Kirki error) | combined classic-editor CSS string | set `lib/customize/output.php:706`, get `:654` |
| `mai_template_parts` | HOUR | `mai_template_part` post objects by slug | set `lib/functions/templates.php:307`, get `:281` |
| `mai_demo_template_parts` | HOUR | demo-site REST template parts | set `lib/functions/templates.php:844`, get `:797` |
| `mai_icon_choices_{style}` | HOUR | SVG icon choices per style | set `lib/fields/icons.php:114`, get `:91`, key `:89` |

Out of scope (not mai-engine-owned): `kirki_remote_url_contents` (Kirki's `Downloader` owns it; mai-engine only reads it at `lib/customize/output.php:662` and `lib/functions/performance.php:222`) and `pt_importer_data` (the importer vendor library owns it). Leave both untouched.

Also deliberately excluded: the mshots demo-screenshot transient (`md5($src)` key, `lib/classes/class-mai-setup-wizard-demos.php`). It stays a raw transient so the broad `mai_cache()->flush()` (wp mai flush, upgrades) never touches it. Those screenshots are an expensive external resource (wordpress.com/mshots) that rarely changes, and flushing them only forces needless re-fetches; the cache ages out on its own DAY TTL, and it still uses a persistent object cache when present (transients route there automatically). Separately, the wizard fetches these synchronously on a cold load, which can lag the first wizard view; improving that (for example, letting the browser load the mShots URLs directly) is a follow-up to this sub-project and is a wizard-rendering change, not a caching change.

## Non-goals

- No menu caching, no content-area caching, no grid result caching. Those are later sub-projects.
- No object-cache-only mode here. Every cache in scope is expensive and rarely-changing, so all use the default transient mode (`Cache::for`).
- No change to what any cache computes; only where it is stored and when it is invalidated (with the single intentional exception of the `save_post` CSS flush).

## Design

### Dependency wiring

mai-engine uses Composer with a committed `vendor/` (`vendor/autoload.php` is required at `mai-engine.php:102`). It currently bundles no `maithemewp/*` library and has no shared-bootstrap wiring.

1. Push mai-cache `develop` to GitHub (`github.com/maithemewp/mai-cache`). This is an explicit, user-authorized push performed during implementation.
2. Add a `repositories` VCS entry for `maithemewp/mai-cache` to mai-engine's `composer.json` (mirroring how sibling plugins pull `maithemewp/*` packages).
3. `composer require maithemewp/mai-cache:dev-develop`, then commit the updated `composer.json`, `composer.lock`, and `vendor/`.

No bootstrap code is needed in mai-engine: mai-cache's `init.php` runs via Composer's `autoload.files` when `vendor/autoload.php` loads, registers its version into the shared registry, and autoloads `Mai\Cache\*`.

### The `mai_cache()` wrapper

A new focused file `lib/functions/cache.php` defines:

```php
function mai_cache( string $group = '' ): \Mai\Cache\Cache {
    $cache = \Mai\Cache\Cache::for( 'mai' );
    return $group ? $cache->group( $group ) : $cache;
}
```

Prefix `mai` is mai-engine's namespace (consistent with its `mai_*` functions and existing transients). Transient mode (the default `Cache::for`) gives Redis-when-present plus DB fallback. Call sites never repeat the prefix: `mai_cache('css')->remember( $key, $callback, $ttl )`.

### Migration mapping (group by invalidation boundary)

| Cache | Wrapper call | Group rationale |
|---|---|---|
| dynamic CSS, dynamic fonts, classic-editor styles | `mai_cache('css')->remember(...)` | all three invalidate together (theme / customize / option) |
| template parts | `mai_cache('template-parts')->remember(...)` | invalidated on template-part save |
| demo template parts | `mai_cache('demo')->remember(...)` | invalidated only on upgrade |
| icon choices | `mai_cache('icons')->remember( "choices_{$style}", ... )` | never event-invalidated; ages out |
| mshots screenshot | `mai_cache('mshots')->remember( md5($src), ... )` | never event-invalidated; ages out |

Each existing `get_transient` followed by compute-and-`set_transient` collapses into a single `remember( $key, $callback, $ttl )`, preserving the current TTL (including the classic-editor styles MINUTE-on-error case, which stays a conditional `set` rather than `remember`). The `mai_classic_editor_styles` path also reads `kirki_remote_url_contents`; that read is unchanged.

### Invalidation changes

- **Remove** `mai_save_post_flush_customizer_transients` and its `save_post` hook registration (`lib/customize/output.php:51-68`). Implementation gate: before removal, confirm no post type legitimately changes the customizer CSS. The targeted template-part invalidation is a separate hook (`save_post_mai_template_part`) and is unaffected.
- `mai_flush_customizer_transients()` (`lib/customize/output.php:41-48`) becomes `mai_cache('css')->flush()`. Keep its real hook registrations: `after_switch_theme`, `customize_save_after`, `update_option_mai-engine` (`:29-31`) and `upgrader_process_complete` (`lib/admin/upgrade.php:15`).
- `mai_save_template_part_delete_transient` (`lib/init.php:176`, hooked at `:170` on `save_post_mai_template_part`) becomes `mai_cache('template-parts')->flush()`.
- Upgrade-time deletes in `lib/admin/upgrade.php` become a single `mai_cache()->flush()` (a full prefix-level clear is appropriate on a plugin upgrade, and replaces the individual `delete_transient` calls there).
- `icons`, `mshots`, and `demo` keep their current behavior of ageing out by TTL (no event invalidation exists for them today, and none is added).

### `wp mai flush` CLI

The command at `lib/init.php:457-460` currently calls only `mai_typography_flush_local_fonts()`. Extend it to also call `mai_cache()->flush()` (prefix-level, which invalidates every mai-engine cache group in one token rotation), and report a combined success message. This makes `wp mai flush` a true "flush everything mai-engine caches" command (local font assets plus all transient/object caches).

## Verification

- Behavior: each migrated path returns the same cached value as before; the first request after a cold cache recomputes and stores; subsequent requests hit.
- Invalidation: theme switch / customize save / option update / upgrade flush the `css` group; a template-part save flushes the `template-parts` group; `wp mai flush` clears everything under the `mai` prefix.
- The fix: editing and saving an ordinary post no longer regenerates the dynamic CSS (the `save_post` flush is gone), verified on a site by confirming the `css` cache survives a post save.
- Tests: unit-test the `mai_cache()` wrapper and the invalidation wiring with mocked WordPress functions, matching mai-engine's existing Brain Monkey unit suite. The migration is otherwise behavior-preserving, so the remaining validation is on a real site.

## Rollout

1. Implement on a feature branch off mai-engine `develop`, PHP 8.1+.
2. Push mai-cache `develop`, wire the dependency, migrate the transients, fix invalidation, extend the CLI.
3. Verify locally and on a staging or live site (confirm caching, the invalidation events, and that post saves no longer churn the CSS).
4. Beta release once validated.

## Explicitly not doing

- Menu, content-area, or grid result caching: later sub-projects.
- Object-cache-only mode: not needed for these caches.
- Touching `kirki_remote_url_contents` or `pt_importer_data`: third-party-owned.
