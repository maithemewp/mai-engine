# Changelog

All notable changes to `mai-cache` are documented here.

Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) · Versioning: [Semantic Versioning](https://semver.org/).

## [0.4.0] - 2026-09-06

### Fixed

- **A stored `false` is now a cache hit.** The transient and object-cache APIs both use `false` as their miss sentinel, so a value that happened to be `false` was indistinguishable from nothing at all, and `remember()` re-ran its callback on every request for it. For a cached yes/no answer that is every request on one side of the answer, forever. Every value is now stored in an envelope (`[ '_v' => ..., 'value' => ... ]`), so a hit is always an array and can never collide with the sentinel. `pull()` had the same bug and gets the same fix.
- **The bootstrap registered every release as `0.2.0`.** `init.php` never had its version string bumped after 0.2.0, so the "highest bundled version wins" registry was comparing equal strings and the winner was whichever plugin loaded first. Now `0.4.0`, and worth checking on each release.

### Added

- `has( string $key ): bool` -- whether a value is stored, whatever it is. The only way to tell a stored `false` from a miss, since `get()` keeps returning `false` for both.
- A storage-format segment in every key (`e1`), after the prefix. A newer version never reads an older version's entries and vice versa, so a downgrade -- or a lower bundled copy winning the bootstrap -- is a one-time miss rather than a misread of an envelope as a value.

### Changed

- `write_swr()` and `set()` now share one envelope; a plain entry carries `_v => null`. `read_swr()` returns `null` for a plain entry rather than reporting it stale, because nobody stamped a version on it.
- On upgrade, existing entries live under the old key layout and are simply unreachable. They are recomputed on first request and age out by TTL -- the same story as the 0.2.0 upgrade. No flush needed.

## [0.3.1] - 2026-07-08

### Changed

- Added a `.gitattributes` with `export-ignore` so dev-only paths (`tests/`, `docs/`, `phpunit.xml.dist`) are stripped from the Composer dist archive, keeping a bundled copy of this package out of consumers' production trees.

## [0.3.0] - 2026-06-26

### Added

- Versioned stale-while-revalidate primitive: `version()`, `bump()`, `read_swr()`, `write_swr()`, `lock()`.
- `bump()` is best-effort like `delete()` and `flush()`: it rotates the scope token even when caching is disabled (`SCRIPT_DEBUG`, or a `{prefix}_can_cache` filter), so invalidation never silently no-ops and leaves stale content readable as fresh until TTL.

## [0.2.0] - 2026-06-25

### Added

- `Cache::object( $prefix )`: object-cache-only mode (`wp_cache_*`) with no DB fallback; a no-op when there is no persistent object cache, so it never adds database write load.
- `Cache::for()` and `Cache::object()` share one method surface via an internal `Store` strategy (`TransientStore`, `ObjectCacheStore`).
- `group( $group )` plus `flush()`: token-based group invalidation. `flush()` busts the whole prefix; `group( 'area' )->flush()` busts just that area. Per-key `delete()` remains for single entries.
- `Cache::has_persistent_object_cache()`: static helper over `wp_using_ext_object_cache()`.
- `Cache::reset_runtime()`: clears memoized instances and version tokens (for tests and long-running processes).
- First unit-test suite (PHPUnit + Brain Monkey).

### Changed

- Stored keys now carry a version-token segment. On upgrade from 0.1.0, existing cached entries are treated as a one-time miss and recomputed; they then age out by TTL.
- Renamed `forget()` to `pull()` (read-once / consume), matching Laravel's `pull()`. No alias is kept, since 0.1.0 had no consumers.

### Compatibility

- Backward compatible. `Cache::for( $prefix )` and `new Cache( $prefix )` behave as before. The `Mai_Cache_Bootstrap` signature is unchanged.

## [0.1.0] - 2026-05-15

### Added

- Initial release.
- `Mai\Cache\Cache`: transient-backed cache with a Laravel-style `remember()` pattern.
- Configurable per-instance prefix (`new Cache('acme')`) plus memoized static factory (`Cache::for('acme')`).
- Methods: `remember()`, `forget()`, `get()`, `set()`, `delete()`, `key()`, `can_cache()`, `prefix()`.
- Auto-bypass when `SCRIPT_DEBUG` is true so development never reads stale cache.
- Per-prefix filter hook `{prefix}_can_cache` to disable caching at runtime.
- `Mai_Cache_Bootstrap`: shared autoloader registry that picks the highest registered version across plugins on the same WordPress install (same pattern as [`maithemewp/mai-logger`](https://github.com/maithemewp/mai-logger)).
