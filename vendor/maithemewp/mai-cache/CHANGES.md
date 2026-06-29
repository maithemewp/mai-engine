# Changelog

All notable changes to `mai-cache` are documented here.

Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) · Versioning: [Semantic Versioning](https://semver.org/).

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
