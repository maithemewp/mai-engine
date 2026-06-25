# Mai Cache Foundation — Design

Date: 2026-06-25
Status: Design approved, pending implementation plan
Component: `maithemewp/mai-cache` package. Enhances the existing `Mai\Cache\Cache` class. Version 0.1.0 → 0.2.0.
Target runtime: PHP 8.1+

## Background

mai-cache 0.1.0 is a small, transient-backed `remember()` wrapper (`Mai\Cache\Cache`): a per-prefix instance with `remember/forget/get/set/delete/key/can_cache/prefix`, auto-bypassed when `SCRIPT_DEBUG` is on, gated by a `{prefix}_can_cache` filter, and loaded across plugins by a frozen highest-version-wins bootstrap (`init.php` / `Mai_Cache_Bootstrap`). Keys are `{prefix}_{key}` and every value is stored with `set_transient`.

A broader initiative (the Redis caching effort across the mai plugins) wants to cut server load by caching expensive, rarely-changing work whenever a persistent object cache (Redis) is in use, with a DB-transient fallback where that still helps. The current package covers the common case well but is missing two things that initiative needs:

1. A storage mode that caches **only** when a persistent object cache is present, so churny or only-marginally-expensive targets do not add `wp_options` write load on non-Redis sites.
2. A cheap **bulk invalidation** primitive, so a content change can flush a whole cache area in one write without tracking individual keys (the per-key / `last_changed` approach was prototyped for the grid and failed to persist on the target site).

This spec covers only the mai-cache enhancements. The consumers (menus, content areas, grid result caching, and migrating mai-engine's existing ad-hoc transients) are separate sub-projects that build on this foundation.

## Goal

Add both capabilities while keeping the 0.1.0 public API and behavior fully backward compatible: every existing `Cache::for($prefix)` call site is unaffected, and copies of the library already bundled in shipped plugins keep loading through the unchanged bootstrap.

## Non-goals

- No consumer changes here. Caching menus, content areas, grid results, and migrating mai-engine's 7 existing transients are each their own sub-project.
- No change to the bootstrap (`Mai_Cache_Bootstrap::register()` signature is frozen). Only the bundled version string bumps.
- No new runtime dependencies in the shipped library: still `php >=8.1`, `autoload.files = [init.php]`. Dev-only test dependencies are fine.
- No automatic cross-request key tracking, and no global `last_changed`-style invalidation.

## The design

Three additions, each isolated.

### 1. Two storage modes

Selected by which factory the consumer calls; the method surface is identical, only the backend differs.

- `Cache::for($prefix)` — **unchanged**, transient-backed. `set_transient` already routes to the object cache when one is present (`wp_using_ext_object_cache()`) and to `wp_options` otherwise, so this mode uses Redis when available and the DB when not. Use for expensive + rarely-changing work worth caching even on non-Redis sites (menus, content areas, generated CSS).
- `Cache::object($prefix)` — **new**, object-cache-only (`wp_cache_*`), with **no DB fallback**. When there is no persistent object cache it is a deliberate no-op (`can_cache()` returns false), so it never adds DB write load. Use for churny or only-marginally-expensive work you only want cached when Redis is present (grid result caching is the canonical case: frequent post saves would otherwise mean a `wp_options` write per miss on non-Redis sites).

Internally a small `Store` strategy isolates the backend difference so the `remember()` / grouping / versioning logic stays backend-agnostic:

- `Store` interface: `read($key): mixed`, `write($key, $value, $expire): bool`, `remove($key): bool`, `available(): bool`.
- `TransientStore` (default): `get/set/delete_transient`; `available()` returns `true`.
- `ObjectCacheStore`: `wp_cache_get/set/delete` within the prefix as the object-cache group; `available()` returns `wp_using_ext_object_cache()`.
- `Cache` holds a `Store` and delegates all raw storage to it. `can_cache()` gains a `store->available()` term, which is what makes object-only mode self-disable without Redis (transient mode's `available()` is always true, so its behavior is unchanged).

### 2. Group flush (cheap bulk invalidation)

Two nested namespaces, both folded into the stored key through small version tokens:

- **Prefix** (top level): `Cache::for('mai')->flush()` rotates the prefix's stored token, which orphans every key under that prefix in a single write. Orphaned entries are simply unreachable and age out by TTL.
- **Optional group** (within a prefix): `Cache::for('mai')->group('menus')` scopes a finer namespace; `Cache::for('mai')->group('menus')->flush()` rotates only that group's token, leaving the rest of the prefix cached.

Key shape: `{prefix}_{prefixToken}[_{group}_{groupToken}]_{key}`, where each token is a short unique value, not a sequential counter. `flush()` rotates the relevant scope's token to a fresh unique value and stores it; because the token is folded into the key, the previous keys instantly become unreachable. Tokens are stored through the same `Store` as the data (object-only mode keeps them in the object cache, transient mode in transients/DB), read once per `(prefix[, group])` per request and memoized, and stored with no expiry so they persist. A token that is evicted is regenerated as a new unique value on next use, orphaning that scope's keys for a one-time recompute; because every generated token is unique, a regenerated token can never collide with surviving older keys, so there is no stale-read risk.

`group($group)` returns a configured `Cache` instance (a lightweight clone with the group set), so the ungrouped instance and any grouped views share the same prefix and store. Per-key `delete()` stays for precise single-key invalidation.

The hierarchy is intentionally two levels (prefix and one optional group): bulk area flush via `group()->flush()`, single-item invalidation via `delete()`. Deeper nesting (a sub-group within a group, e.g. `mai` > `menu` > `primary`) is deferred as YAGNI; its only niche is bulk-flushing a subset of one area's items, which `delete()` and group flush already bracket. It is a purely additive future extension (two-level call sites are unaffected, and the token scheme already generalizes to more levels), so it can be added the day a hot area actually needs it, with no redesign.

**Recommended convention (consumer guidance, not enforced):** one prefix per plugin, and a group per cache area (`'menus'`, `'headers'`, `'grid'`, `'ccas'`). One prefix per plugin is what lets a consumer set the prefix once (see Consumer ergonomics); groups give per-area flush, which is what makes a single prefix workable, since otherwise any `flush()` would clear the whole plugin's cache. The prefix is each plugin's established namespace root: mai-engine is the base plugin and owns `'mai'` (its functions and existing transients are all `mai_*`), while sibling plugins use their own roots (mai-publisher → `'maipub'`, and so on). Because every plugin uses a distinct root, prefixes never collide across the crowded `mai-*` ecosystem; the per-area `group` further namespaces within a prefix. Sharing the mai-cache *code* across plugins is the bootstrap's job (highest version wins) and is independent of prefixes.

### 3. Object-cache presence helper

`Cache::has_persistent_object_cache(): bool` — a static wrapper over `wp_using_ext_object_cache()`, so a consumer can branch ("only worth caching when Redis is here") without calling the WP function directly. This is the same predicate `ObjectCacheStore::available()` uses.

### 4. Consumer ergonomics (no repeated prefix)

The package is intentionally prefix-explicit and holds no global default prefix: a shared default would be ambiguous when several plugins share one loaded copy (whose prefix wins?). Consumers avoid repeating the prefix by binding it once in a one-line wrapper, so call sites never name it:

```php
// mai-engine, defined once:
function mai_cache( string $group = '' ): \Mai\Cache\Cache {
    $cache = \Mai\Cache\Cache::for( 'mai' );
    return $group ? $cache->group( $group ) : $cache;
}

// call sites stay terse:
mai_cache( 'menus' )->remember( $key, $callback, $ttl );
mai_cache( 'menus' )->flush();   // bust one area
mai_cache()->flush();            // bust everything for this plugin
```

`Cache::for()` is memoized per prefix, so the wrapper adds no object churn. Object-cache-only mode binds the same way (a parallel helper over `Cache::object('mai')`, or a flag the wrapper forwards). The abstraction (and multi-plugin safety) lives in the shared package; the terseness lives in each plugin's wrapper. This wrapper belongs to each consumer sub-project, not to this package.

## API summary

New or changed public surface (everything else in 0.1.0 is unchanged):

- `Cache::for(string $prefix = 'mai'): self` — transient-backed (unchanged).
- `Cache::object(string $prefix = 'mai'): self` — object-cache-only (new).
- `->group(string $group): self` — scope to a sub-group; returns a configured instance (new).
- `->flush(): bool` — rotate the current scope's version token (prefix-level, or group-level when grouped) (new).
- `Cache::has_persistent_object_cache(): bool` — static helper (new).
- `->remember/pull/get/set/delete/key/can_cache/prefix` — `get`/`set`/`delete` and `key()` now build the versioned key. `forget()` is renamed to `pull()` (read-once / consume), matching Laravel's `pull()`; see Backward compatibility.

## Backward compatibility

- Existing `Cache::for($p)->remember(...)` call sites are unaffected in behavior. The only internal change is that keys now carry a version-token segment.
- Consequence: on upgrade from 0.1.0, the old un-versioned keys no longer match, so each is treated as a one-time miss and recomputed (it then ages out by TTL). This is a single cold-cache event on deploy, called out in the changelog.
- The bootstrap is untouched. `init.php` bumps its registered version string `0.1.0` → `0.2.0`; older bundled copies in other plugins keep working, and the highest-version copy (this one) is what loads.
- `forget()` is renamed to `pull()`. This is the one non-additive change, and it is safe because 0.1.0 has no consumers (verified across all mai plugins and local packages: none require or call mai-cache), so no deprecated alias is kept.

## Testing

mai-cache 0.1.0 ships no test harness. This sub-project adds a Brain Monkey unit suite (mirroring mai-engine's setup: PHPUnit + `brain/monkey`, a `composer test-unit` script, `phpunit.xml.dist`, dev-only deps). Coverage:

- `remember()` hit returns the cached value without invoking the callback; miss invokes it and stores; a `WP_Error` result is not stored (unchanged behavior, re-asserted).
- Object-only mode is a no-op when `wp_using_ext_object_cache()` is false (nothing written, `get` returns false), and writes through `wp_cache_*` when it is true.
- Versioned key shape is correct with and without a group.
- `flush()` at the prefix level rotates the token so a subsequent read misses; `group()->flush()` busts only that group and leaves other groups/keys reachable.
- `has_persistent_object_cache()` reflects `wp_using_ext_object_cache()`.
- `SCRIPT_DEBUG` still bypasses; `{prefix}_can_cache` still gates.

## Edge cases and risks

- **Object-only without Redis:** no-op by design. Documented so consumers don't expect persistence there.
- **Version-token read cost:** one extra store read per `(prefix[, group])` per request, memoized. Negligible on Redis; on the DB path it is one `get_transient` per active prefix/group per request, which the selectivity discipline (few prefixes, few groups, only expensive targets) keeps small.
- **Upgrade cold cache:** one-time, documented above.
- **Two plugins sharing one prefix:** discouraged by convention; if it happens, `flush()` and `{prefix}_can_cache` are shared across them (acceptable as an escape hatch, not the recommended setup).

## Versioning and release

- 0.1.0 → **0.2.0** (MINOR: purely additive, backward compatible).
- Bump `Mai_Cache_Bootstrap::register( '0.2.0', … )` in `init.php`; add a `CHANGES.md` entry (note the one-time cold cache on upgrade); update `README.md` usage with the new factory, `group()`, `flush()`, and the helper.

## Where this sits

Foundation (this spec) → menus → content areas (mai-engine template parts + mai-custom-content-areas CCAs) → grid result caching (object-only mode, on top of the existing query rewrite) → misc. Each consumer is its own spec → plan, building on this package.
