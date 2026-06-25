# Mai Cache Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extend `maithemewp/mai-cache` (0.1.0 → 0.2.0) with an object-cache-only storage mode, a token-based group-flush invalidation primitive, and a persistent-object-cache helper, fully backward compatible.

**Architecture:** Introduce a small `Store` strategy (`TransientStore`, `ObjectCacheStore`) so `Cache` delegates raw storage and stays backend-agnostic. Add `Cache::object()` alongside `Cache::for()`. Fold a rotated-unique-token version segment into every key so `flush()` (prefix) and `group()->flush()` (area) invalidate in one write with no per-key tracking. Add the package's first Brain Monkey unit-test harness.

**Tech Stack:** PHP 8.1+, WordPress transient + object-cache APIs, PHPUnit 10.5, Brain Monkey 2.6. Classes load via the existing frozen `Mai_Cache_Bootstrap` autoloader in `init.php`.

## Global Constraints

- Package `maithemewp/mai-cache`, namespace `Mai\Cache`, PHP `>=8.1`. Src classes load via the bootstrap autoloader in `init.php`, not composer PSR-4.
- Backward compatible: `Cache::for($prefix)` and `new Cache($prefix)` keep their 0.1.0 transient-backed behavior; all changes are additive. The only externally visible change is that stored keys gain a version-token segment (a one-time cold cache on upgrade), documented in CHANGES.md.
- The bootstrap signature is FROZEN: `Mai_Cache_Bootstrap::register( string $version, string $src_path )`. Only the registered version string changes (`0.1.0` → `0.2.0`).
- No new runtime dependencies: shipped `require` stays `{ "php": ">=8.1" }`, `autoload.files = ["init.php"]`. Test tooling is `require-dev` only.
- Two storage modes: `Cache::for()` transient-backed (Redis when present via `set_transient`, DB fallback); `Cache::object()` object-cache-only (`wp_cache_*`, no DB fallback), a no-op when `wp_using_ext_object_cache()` is false.
- Invalidation: rotated unique version tokens via `bin2hex( random_bytes( 6 ) )`, stored with no expiry (`0`), memoized per scope in a static array. Two levels only: prefix and one optional group. Per-key `delete()` for single items.
- `can_cache()`: false if `SCRIPT_DEBUG`, false if the store is unavailable, else `apply_filters( "{$prefix}_can_cache", true, $prefix )`.
- Every src file begins with `defined( 'ABSPATH' ) || exit;` (matches 0.1.0). The test bootstrap defines `ABSPATH`.
- Shipped text (CHANGES.md, README.md, code comments) and commit messages contain no em-dashes; use commas, periods, semicolons, or parentheses.
- Every commit message ends with these two lines verbatim:

  ```
  Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>
  Claude-Session: https://claude.ai/code/session_014Y3mM3j5pttsy276KJ3BUS
  ```

## File Structure

Source (`src/`, loaded by the bootstrap autoloader):
- `src/Store.php` — `Store` interface: the raw storage contract (`read`/`write`/`remove`/`available`).
- `src/TransientStore.php` — `Store` backed by WP transients; always available.
- `src/ObjectCacheStore.php` — `Store` backed by `wp_cache_*`; available only with a persistent object cache.
- `src/Cache.php` — the public API; holds a `Store`, builds versioned keys, owns `remember`/`group`/`flush`/factories.

Tests (`tests/`, PSR-4 `Mai\Cache\Tests\` via `autoload-dev`):
- `tests/bootstrap.php` — defines `ABSPATH`, requires composer autoload (which runs `init.php`).
- `tests/TestCase.php` — Brain Monkey setUp/tearDown base; resets `Cache` runtime state.
- `tests/Support/ArrayStore.php` — in-memory `Store` test double for pure `Cache` logic tests.
- `tests/Unit/TransientStoreTest.php`, `tests/Unit/ObjectCacheStoreTest.php`, `tests/Unit/CacheTest.php`, `tests/Unit/CacheVersioningTest.php`.

Config / docs:
- `composer.json` — add `require-dev`, `autoload-dev`, `scripts.test-unit`.
- `phpunit.xml.dist` — PHPUnit config, `suffix="Test.php"`, `tests/Unit` suite.
- `.gitignore` — ignore `/vendor/`.
- `init.php` — bump registered version to `0.2.0`.
- `CHANGES.md`, `README.md` — document 0.2.0.

---

### Task 1: Test harness + `Store` interface + `TransientStore`

**Files:**
- Modify: `composer.json`
- Create: `phpunit.xml.dist`
- Modify: `.gitignore`
- Create: `tests/bootstrap.php`
- Create: `tests/TestCase.php`
- Create: `src/Store.php`
- Create: `src/TransientStore.php`
- Test: `tests/Unit/TransientStoreTest.php`

**Interfaces:**
- Consumes: nothing (first task).
- Produces:
  - `interface Mai\Cache\Store { public function read( string $key ): mixed; public function write( string $key, mixed $value, int $expire ): bool; public function remove( string $key ): bool; public function available(): bool; }`
  - `class Mai\Cache\TransientStore implements Store` — delegates to `get/set/delete_transient`; `available()` returns `true`.
  - `abstract class Mai\Cache\Tests\TestCase` — Brain Monkey base; `tearDown()` calls `Mai\Cache\Cache::reset_runtime()` (added in Task 3; tolerate its absence here, see Step 4 note).

- [ ] **Step 1: Add dev tooling to `composer.json`**

Replace the file with:

```json
{
    "name": "maithemewp/mai-cache",
    "description": "Ergonomic remember()-pattern caching for WordPress transients and the object cache. SCRIPT_DEBUG-aware. Versioned, drop-in safe for use in multiple plugins on the same WordPress install.",
    "type": "library",
    "license": "GPL-2.0-or-later",
    "homepage": "https://github.com/maithemewp/mai-cache",
    "require": {
        "php": ">=8.1"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.5",
        "brain/monkey": "^2.6"
    },
    "autoload": {
        "files": ["init.php"]
    },
    "autoload-dev": {
        "psr-4": { "Mai\\Cache\\Tests\\": "tests/" }
    },
    "scripts": {
        "test-unit": "phpunit"
    }
}
```

- [ ] **Step 2: Create `phpunit.xml.dist`**

```xml
<?xml version="1.0"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true"
         failOnWarning="true">
    <testsuites>
        <testsuite name="Unit">
            <directory suffix="Test.php">tests/Unit</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

- [ ] **Step 3: Ignore `/vendor/` in `.gitignore`**

Append this line to `.gitignore` (only if not already present):

```
/vendor/
```

- [ ] **Step 4: Create `tests/bootstrap.php` and `tests/TestCase.php`**

`tests/bootstrap.php`:

```php
<?php
// Define ABSPATH before composer autoload runs init.php (its files guard on it).
define( 'ABSPATH', __DIR__ . '/' );

require dirname( __DIR__ ) . '/vendor/autoload.php';
```

`tests/TestCase.php`:

```php
<?php
namespace Mai\Cache\Tests;

use Brain\Monkey;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase {
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		if ( method_exists( \Mai\Cache\Cache::class, 'reset_runtime' ) ) {
			\Mai\Cache\Cache::reset_runtime();
		}
		Monkey\tearDown();
		parent::tearDown();
	}
}
```

Note: `reset_runtime()` does not exist until Task 3; the `method_exists` guard keeps Task 1/2 green before it lands.

- [ ] **Step 5: Write the failing test `tests/Unit/TransientStoreTest.php`**

```php
<?php
namespace Mai\Cache\Tests\Unit;

use Brain\Monkey\Functions;
use Mai\Cache\Tests\TestCase;
use Mai\Cache\TransientStore;

final class TransientStoreTest extends TestCase {
	public function test_reads_via_get_transient(): void {
		Functions\expect( 'get_transient' )->once()->with( 'k' )->andReturn( 'v' );
		$this->assertSame( 'v', ( new TransientStore() )->read( 'k' ) );
	}

	public function test_writes_via_set_transient(): void {
		Functions\expect( 'set_transient' )->once()->with( 'k', 'v', 60 )->andReturn( true );
		$this->assertTrue( ( new TransientStore() )->write( 'k', 'v', 60 ) );
	}

	public function test_removes_via_delete_transient(): void {
		Functions\expect( 'delete_transient' )->once()->with( 'k' )->andReturn( true );
		$this->assertTrue( ( new TransientStore() )->remove( 'k' ) );
	}

	public function test_is_always_available(): void {
		$this->assertTrue( ( new TransientStore() )->available() );
	}
}
```

- [ ] **Step 6: Install deps and run the test to verify it fails**

Run: `composer install && composer test-unit`
Expected: FAIL — `Mai\Cache\TransientStore` and `Mai\Cache\Store` do not exist yet (Error: class not found).

- [ ] **Step 7: Create `src/Store.php` and `src/TransientStore.php`**

`src/Store.php`:

```php
<?php
/**
 * Mai\Cache\Store — raw storage contract behind the Cache facade.
 *
 * @package maithemewp/mai-cache
 * @license GPL-2.0-or-later
 */

namespace Mai\Cache;

defined( 'ABSPATH' ) || exit;

/**
 * A storage backend for Cache. Implementations decide where values live and
 * whether they can persist right now.
 *
 * @since 0.2.0
 */
interface Store {
	/**
	 * Read a raw value. Returns false on miss.
	 *
	 * @since 0.2.0
	 */
	public function read( string $key ): mixed;

	/**
	 * Write a raw value with a TTL in seconds (0 = no expiry).
	 *
	 * @since 0.2.0
	 */
	public function write( string $key, mixed $value, int $expire ): bool;

	/**
	 * Remove a raw value.
	 *
	 * @since 0.2.0
	 */
	public function remove( string $key ): bool;

	/**
	 * Whether this backend can persist across requests right now.
	 *
	 * @since 0.2.0
	 */
	public function available(): bool;
}
```

`src/TransientStore.php`:

```php
<?php
/**
 * Mai\Cache\TransientStore — transient-backed Store (Redis when present, DB otherwise).
 *
 * @package maithemewp/mai-cache
 * @license GPL-2.0-or-later
 */

namespace Mai\Cache;

defined( 'ABSPATH' ) || exit;

/**
 * Stores values with the WordPress transient API, which routes to the object
 * cache when one is present and to wp_options otherwise. Always available.
 *
 * @since 0.2.0
 */
class TransientStore implements Store {
	public function read( string $key ): mixed {
		return get_transient( $key );
	}

	public function write( string $key, mixed $value, int $expire ): bool {
		return (bool) set_transient( $key, $value, $expire );
	}

	public function remove( string $key ): bool {
		return (bool) delete_transient( $key );
	}

	public function available(): bool {
		return true;
	}
}
```

- [ ] **Step 8: Run the test to verify it passes**

Run: `composer test-unit`
Expected: PASS (4 tests).

- [ ] **Step 9: Commit**

```bash
git add composer.json phpunit.xml.dist .gitignore tests/ src/Store.php src/TransientStore.php
git commit -m "test: add unit harness; extract Store interface + TransientStore"
```

(Append the Global Constraints commit footers.)

---

### Task 2: `ObjectCacheStore`

**Files:**
- Create: `src/ObjectCacheStore.php`
- Test: `tests/Unit/ObjectCacheStoreTest.php`

**Interfaces:**
- Consumes: `Mai\Cache\Store` (Task 1).
- Produces: `class Mai\Cache\ObjectCacheStore implements Store` — delegates to `wp_cache_get/set/delete` in the constant group `'mai_cache'`; `available()` returns `wp_using_ext_object_cache()`.

- [ ] **Step 1: Write the failing test `tests/Unit/ObjectCacheStoreTest.php`**

```php
<?php
namespace Mai\Cache\Tests\Unit;

use Brain\Monkey\Functions;
use Mai\Cache\ObjectCacheStore;
use Mai\Cache\Tests\TestCase;

final class ObjectCacheStoreTest extends TestCase {
	public function test_reads_via_wp_cache_get_in_group(): void {
		Functions\expect( 'wp_cache_get' )->once()->with( 'k', 'mai_cache' )->andReturn( 'v' );
		$this->assertSame( 'v', ( new ObjectCacheStore() )->read( 'k' ) );
	}

	public function test_writes_via_wp_cache_set_in_group(): void {
		Functions\expect( 'wp_cache_set' )->once()->with( 'k', 'v', 'mai_cache', 60 )->andReturn( true );
		$this->assertTrue( ( new ObjectCacheStore() )->write( 'k', 'v', 60 ) );
	}

	public function test_removes_via_wp_cache_delete_in_group(): void {
		Functions\expect( 'wp_cache_delete' )->once()->with( 'k', 'mai_cache' )->andReturn( true );
		$this->assertTrue( ( new ObjectCacheStore() )->remove( 'k' ) );
	}

	public function test_available_reflects_ext_object_cache(): void {
		Functions\when( 'wp_using_ext_object_cache' )->justReturn( true );
		$this->assertTrue( ( new ObjectCacheStore() )->available() );

		Functions\when( 'wp_using_ext_object_cache' )->justReturn( false );
		$this->assertFalse( ( new ObjectCacheStore() )->available() );
	}
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `composer test-unit`
Expected: FAIL — `Mai\Cache\ObjectCacheStore` does not exist (class not found).

- [ ] **Step 3: Create `src/ObjectCacheStore.php`**

```php
<?php
/**
 * Mai\Cache\ObjectCacheStore — object-cache-only Store (no DB fallback).
 *
 * @package maithemewp/mai-cache
 * @license GPL-2.0-or-later
 */

namespace Mai\Cache;

defined( 'ABSPATH' ) || exit;

/**
 * Stores values with the WordPress object cache (wp_cache_*). It deliberately
 * has no DB fallback: when there is no persistent object cache, available()
 * is false and Cache treats this store as a no-op, so it never writes to the
 * database. Keys already carry the Cache prefix, so a single constant group
 * is enough.
 *
 * @since 0.2.0
 */
class ObjectCacheStore implements Store {
	private const GROUP = 'mai_cache';

	public function read( string $key ): mixed {
		return wp_cache_get( $key, self::GROUP );
	}

	public function write( string $key, mixed $value, int $expire ): bool {
		return (bool) wp_cache_set( $key, $value, self::GROUP, $expire );
	}

	public function remove( string $key ): bool {
		return (bool) wp_cache_delete( $key, self::GROUP );
	}

	public function available(): bool {
		return (bool) wp_using_ext_object_cache();
	}
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `composer test-unit`
Expected: PASS (8 tests total).

- [ ] **Step 5: Commit**

```bash
git add src/ObjectCacheStore.php tests/Unit/ObjectCacheStoreTest.php
git commit -m "feat: add ObjectCacheStore (object-cache-only, no DB fallback)"
```

(Append the Global Constraints commit footers.)

---

### Task 3: `Cache` delegates to a `Store`; `for()` / `object()` factories; availability-aware `can_cache()`; helper

**Files:**
- Modify: `src/Cache.php` (full rewrite of the class body; see Step 4)
- Create: `tests/Support/ArrayStore.php`
- Test: `tests/Unit/CacheTest.php`

**Interfaces:**
- Consumes: `Store` (Task 1), `TransientStore` (Task 1), `ObjectCacheStore` (Task 2).
- Produces (final public surface of `Mai\Cache\Cache`, key/token internals added in Task 4):
  - `__construct( string $prefix = 'mai', ?Store $store = null )`
  - `static for( string $prefix = 'mai' ): self` (transient-backed)
  - `static object( string $prefix = 'mai' ): self` (object-cache-only)
  - `remember( string $key, callable $callback, int $expire ): mixed`
  - `pull( string $key, mixed $default = null ): mixed` (read-once; renamed from 0.1.0's `forget()`)
  - `get( string $key ): mixed` / `set( string $key, mixed $value, int $expire ): bool` / `delete( string $key ): bool`
  - `key( string $key ): string`
  - `can_cache(): bool`
  - `prefix(): string`
  - `static has_persistent_object_cache(): bool`
  - `static reset_runtime(): void`
- `Mai\Cache\Tests\Support\ArrayStore implements Store` — in-memory test double; public `array $data = []` and `bool $available = true`.

- [ ] **Step 1: Create `tests/Support/ArrayStore.php`**

```php
<?php
namespace Mai\Cache\Tests\Support;

use Mai\Cache\Store;

/**
 * In-memory Store double. read() returns false on miss, mirroring transients.
 */
final class ArrayStore implements Store {
	public array $data      = [];
	public bool  $available = true;

	public function read( string $key ): mixed {
		return array_key_exists( $key, $this->data ) ? $this->data[ $key ] : false;
	}

	public function write( string $key, mixed $value, int $expire ): bool {
		$this->data[ $key ] = $value;
		return true;
	}

	public function remove( string $key ): bool {
		unset( $this->data[ $key ] );
		return true;
	}

	public function available(): bool {
		return $this->available;
	}
}
```

- [ ] **Step 2: Write the failing test `tests/Unit/CacheTest.php`**

```php
<?php
namespace Mai\Cache\Tests\Unit;

use Brain\Monkey\Functions;
use Mai\Cache\Cache;
use Mai\Cache\Tests\Support\ArrayStore;
use Mai\Cache\Tests\TestCase;

final class CacheTest extends TestCase {
	private function allowCaching(): void {
		Functions\when( 'apply_filters' )->alias( fn( $tag, $value = null ) => $value );
		Functions\when( 'is_wp_error' )->justReturn( false );
	}

	public function test_remember_returns_callback_value_and_caches_it(): void {
		$this->allowCaching();
		$cache = new Cache( 'mai', new ArrayStore() );
		$calls = 0;
		$cb    = function () use ( &$calls ) { $calls++; return 'value'; };

		$this->assertSame( 'value', $cache->remember( 'k', $cb, 60 ) ); // miss → runs callback
		$this->assertSame( 'value', $cache->remember( 'k', $cb, 60 ) ); // hit → no callback
		$this->assertSame( 1, $calls );
	}

	public function test_remember_does_not_cache_wp_error(): void {
		Functions\when( 'apply_filters' )->alias( fn( $tag, $value = null ) => $value );
		Functions\when( 'is_wp_error' )->justReturn( true );
		$store = new ArrayStore();
		$cache = new Cache( 'mai', $store );

		$cache->remember( 'k', fn() => 'err', 60 );
		$this->assertSame( [], $store->data ); // nothing stored
	}

	public function test_object_mode_is_noop_when_store_unavailable(): void {
		Functions\when( 'apply_filters' )->alias( fn( $tag, $value = null ) => $value );
		$store            = new ArrayStore();
		$store->available = false;
		$cache            = new Cache( 'mai', $store );

		$this->assertFalse( $cache->set( 'k', 'v', 60 ) ); // can_cache false → no write
		$this->assertFalse( $cache->get( 'k' ) );          // can_cache false → miss
		$this->assertSame( [], $store->data );
	}

	public function test_factories_pick_the_right_store(): void {
		Functions\when( 'apply_filters' )->alias( fn( $tag, $value = null ) => $value );
		Functions\when( 'wp_using_ext_object_cache' )->justReturn( false );

		$this->assertTrue( Cache::for( 'x' )->can_cache() );      // transient store: always available
		$this->assertFalse( Cache::object( 'x' )->can_cache() );  // object store: unavailable here
	}

	public function test_has_persistent_object_cache_wraps_wp_function(): void {
		Functions\when( 'wp_using_ext_object_cache' )->justReturn( true );
		$this->assertTrue( Cache::has_persistent_object_cache() );
	}
}
```

- [ ] **Step 3: Run the test to verify it fails**

Run: `composer test-unit`
Expected: FAIL — `Cache::object`, `Cache::has_persistent_object_cache`, the `?Store` constructor param, and store delegation do not exist yet.

- [ ] **Step 4: Rewrite `src/Cache.php`**

Replace the entire file with the following. (This keeps `key()` simple, `{prefix}_{key}`; the version token is added in Task 4. The two static arrays and `reset_runtime()` are declared now so the harness can reset state.)

```php
<?php
/**
 * Mai\Cache\Cache — remember()-pattern cache over a pluggable Store.
 *
 * @package maithemewp/mai-cache
 * @license GPL-2.0-or-later
 */

namespace Mai\Cache;

defined( 'ABSPATH' ) || exit;

/**
 * Transient- or object-cache-backed cache with a Laravel-style remember()
 * pattern. Auto-bypasses caching when SCRIPT_DEBUG is true, and (in object
 * mode) when there is no persistent object cache.
 *
 * @since 0.1.0
 */
class Cache {

	/**
	 * Memoized instances keyed by "mode:prefix".
	 *
	 * @var array<string,self>
	 */
	private static array $instances = [];

	/**
	 * Memoized version tokens keyed by scope (used from 0.2.0).
	 *
	 * @var array<string,string>
	 */
	private static array $tokens = [];

	private string $prefix;
	private Store $store;

	/**
	 * @param string     $prefix Prefix prepended to all keys. Default 'mai'.
	 * @param Store|null  $store  Storage backend. Defaults to TransientStore.
	 *
	 * @since 0.1.0
	 */
	public function __construct( string $prefix = 'mai', ?Store $store = null ) {
		$this->prefix = trim( $prefix, '_' );
		$this->store  = $store ?? new TransientStore();
	}

	/**
	 * Transient-backed instance (Redis when present, DB fallback).
	 *
	 * @since 0.1.0
	 */
	public static function for( string $prefix = 'mai' ): self {
		return self::instance( 'transient', $prefix );
	}

	/**
	 * Object-cache-only instance (wp_cache_*, no DB fallback). A no-op when
	 * there is no persistent object cache.
	 *
	 * @since 0.2.0
	 */
	public static function object( string $prefix = 'mai' ): self {
		return self::instance( 'object', $prefix );
	}

	private static function instance( string $mode, string $prefix ): self {
		$prefix = trim( $prefix, '_' );
		$id     = $mode . ':' . $prefix;

		if ( ! isset( self::$instances[ $id ] ) ) {
			$store                   = 'object' === $mode ? new ObjectCacheStore() : new TransientStore();
			self::$instances[ $id ]  = new self( $prefix, $store );
		}

		return self::$instances[ $id ];
	}

	/**
	 * Get a cached value, or compute + cache it. A WP_Error result is not cached.
	 *
	 * @since 0.1.0
	 */
	public function remember( string $key, callable $callback, int $expire ): mixed {
		$cached = $this->get( $key );

		if ( false !== $cached ) {
			return $cached;
		}

		$value = $callback();

		if ( ! is_wp_error( $value ) ) {
			$this->set( $key, $value, $expire );
		}

		return $value;
	}

	/**
	 * Get a cached value, deleting it on hit (read-once / consume).
	 * Renamed from 0.1.0's forget() to match Laravel's pull() semantics.
	 *
	 * @since 0.2.0
	 */
	public function pull( string $key, mixed $default = null ): mixed {
		$cached = $this->get( $key );

		if ( false !== $cached ) {
			$this->delete( $key );
			return $cached;
		}

		return $default;
	}

	/**
	 * Get a cached value. Returns false on miss or when caching is disabled.
	 *
	 * @since 0.1.0
	 */
	public function get( string $key ): mixed {
		if ( ! $this->can_cache() ) {
			return false;
		}

		return $this->store->read( $this->key( $key ) );
	}

	/**
	 * Set a cached value. Returns false when caching is disabled.
	 *
	 * @since 0.1.0
	 */
	public function set( string $key, mixed $value, int $expire ): bool {
		if ( ! $this->can_cache() ) {
			return false;
		}

		return $this->store->write( $this->key( $key ), $value, max( 0, $expire ) );
	}

	/**
	 * Delete a cached value.
	 *
	 * @since 0.1.0
	 */
	public function delete( string $key ): bool {
		return $this->store->remove( $this->key( $key ) );
	}

	/**
	 * Build the fully-prefixed key.
	 *
	 * @since 0.1.0
	 */
	public function key( string $key ): string {
		return $this->prefix . '_' . ltrim( $key, '_' );
	}

	/**
	 * Whether caching is currently allowed.
	 *
	 * Disabled when SCRIPT_DEBUG is true, when the store cannot persist
	 * (object mode without a persistent object cache), or when the
	 * "{prefix}_can_cache" filter returns false.
	 *
	 * @since 0.1.0
	 */
	public function can_cache(): bool {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			return false;
		}

		if ( ! $this->store->available() ) {
			return false;
		}

		return (bool) apply_filters( $this->prefix . '_can_cache', true, $this->prefix );
	}

	/**
	 * Get the prefix used by this instance.
	 *
	 * @since 0.1.0
	 */
	public function prefix(): string {
		return $this->prefix;
	}

	/**
	 * Whether a persistent object cache (e.g. Redis) is in use.
	 *
	 * @since 0.2.0
	 */
	public static function has_persistent_object_cache(): bool {
		return (bool) wp_using_ext_object_cache();
	}

	/**
	 * Reset memoized instances and version tokens. For tests and long-running
	 * processes (e.g. WP-CLI) that must not hold stale state across boundaries.
	 *
	 * @since 0.2.0
	 */
	public static function reset_runtime(): void {
		self::$instances = [];
		self::$tokens    = [];
	}
}
```

- [ ] **Step 5: Run the test to verify it passes**

Run: `composer test-unit`
Expected: PASS (13 tests total).

- [ ] **Step 6: Commit**

```bash
git add src/Cache.php tests/Support/ArrayStore.php tests/Unit/CacheTest.php
git commit -m "feat: Cache delegates to Store; add object() factory + availability gate + helper"
```

(Append the Global Constraints commit footers.)

---

### Task 4: Versioned keys, `group()`, and `flush()` (prefix + group scopes)

**Files:**
- Modify: `src/Cache.php` (add token machinery + `group()` + `flush()`; change `key()`)
- Test: `tests/Unit/CacheVersioningTest.php`

**Interfaces:**
- Consumes: the full `Cache` from Task 3.
- Produces:
  - `group( string $group ): self` — returns a clone scoped to a sub-group.
  - `flush(): bool` — rotates the current scope's version token (prefix scope when ungrouped, group scope when grouped).
  - `key()` now returns `"{prefix}_{prefixToken}_{key}"` ungrouped, `"{prefix}_{prefixToken}_{group}_{groupToken}_{key}"` grouped. Tokens are 12 lowercase hex chars from `bin2hex( random_bytes( 6 ) )`.

- [ ] **Step 1: Write the failing test `tests/Unit/CacheVersioningTest.php`**

```php
<?php
namespace Mai\Cache\Tests\Unit;

use Brain\Monkey\Functions;
use Mai\Cache\Cache;
use Mai\Cache\Tests\Support\ArrayStore;
use Mai\Cache\Tests\TestCase;

final class CacheVersioningTest extends TestCase {
	private function allowCaching(): void {
		Functions\when( 'apply_filters' )->alias( fn( $tag, $value = null ) => $value );
		Functions\when( 'is_wp_error' )->justReturn( false );
	}

	public function test_key_includes_a_version_token(): void {
		$this->allowCaching();
		$cache = new Cache( 'mai', new ArrayStore() );
		$this->assertMatchesRegularExpression( '/^mai_[0-9a-f]{12}_thing$/', $cache->key( 'thing' ) );
	}

	public function test_grouped_key_includes_group_and_its_token(): void {
		$this->allowCaching();
		$cache = ( new Cache( 'mai', new ArrayStore() ) )->group( 'menu' );
		$this->assertMatchesRegularExpression( '/^mai_[0-9a-f]{12}_menu_[0-9a-f]{12}_primary$/', $cache->key( 'primary' ) );
	}

	public function test_token_is_stable_across_reads(): void {
		$this->allowCaching();
		$cache = new Cache( 'mai', new ArrayStore() );
		$this->assertSame( $cache->key( 'thing' ), $cache->key( 'thing' ) );
	}

	public function test_prefix_flush_busts_all_keys(): void {
		$this->allowCaching();
		$cache = new Cache( 'mai', new ArrayStore() );
		$cache->set( 'k', 'v', 60 );
		$this->assertSame( 'v', $cache->get( 'k' ) );

		$this->assertTrue( $cache->flush() );
		$this->assertFalse( $cache->get( 'k' ) ); // key changed → miss
	}

	public function test_group_flush_busts_only_that_group(): void {
		$this->allowCaching();
		$store  = new ArrayStore();
		$base   = new Cache( 'mai', $store );
		$menu   = $base->group( 'menu' );
		$header = $base->group( 'header' );

		$menu->set( 'a', 'M', 60 );
		$header->set( 'b', 'H', 60 );

		$menu->flush();
		$this->assertFalse( $menu->get( 'a' ) );     // busted
		$this->assertSame( 'H', $header->get( 'b' ) ); // intact
	}

	public function test_prefix_flush_also_busts_grouped_keys(): void {
		$this->allowCaching();
		$store = new ArrayStore();
		$base  = new Cache( 'mai', $store );
		$base->group( 'menu' )->set( 'a', 'M', 60 );
		$this->assertSame( 'M', $base->group( 'menu' )->get( 'a' ) );

		$base->flush();
		$this->assertFalse( $base->group( 'menu' )->get( 'a' ) );
	}
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `composer test-unit`
Expected: FAIL — `group()`/`flush()` do not exist and `key()` has no token segment (regex and flush assertions fail).

- [ ] **Step 3: Add the token machinery to `src/Cache.php`**

Add a `private string $group = '';` property next to `$store`:

```php
	private string $prefix;
	private Store $store;
	private string $group = '';
```

Replace the `key()` method with the versioned, group-aware version:

```php
	/**
	 * Build the fully-namespaced key: prefix, prefix version token, optional
	 * group + its version token, then the user key. Rotating a token (flush)
	 * changes every key under that scope, so old entries become unreachable.
	 *
	 * @since 0.1.0
	 */
	public function key( string $key ): string {
		$parts = [ $this->prefix, $this->token( $this->prefix ) ];

		if ( '' !== $this->group ) {
			$parts[] = $this->group;
			$parts[] = $this->token( $this->prefix . '_' . $this->group );
		}

		$parts[] = ltrim( $key, '_' );

		return implode( '_', $parts );
	}
```

Add `group()` and `flush()` (place `group()` after `object()`, `flush()` after `delete()`):

```php
	/**
	 * Scope a finer namespace within this prefix. Returns a configured clone;
	 * the base instance and its grouped views share the same prefix and store.
	 *
	 * @since 0.2.0
	 */
	public function group( string $group ): self {
		$clone        = clone $this;
		$clone->group = trim( $group, '_' );
		return $clone;
	}

	/**
	 * Invalidate the current scope by rotating its version token: the whole
	 * prefix when ungrouped, or just this group when grouped. Orphaned entries
	 * become unreachable and age out by TTL.
	 *
	 * @since 0.2.0
	 */
	public function flush(): bool {
		$scope = $this->scope();
		$token = self::new_token();

		self::$tokens[ $scope ] = $token;

		return $this->store->write( $this->token_key( $scope ), $token, 0 );
	}
```

Add the private helpers (place after `reset_runtime()`):

```php
	/**
	 * Current invalidation scope: "{prefix}" or "{prefix}_{group}".
	 *
	 * @since 0.2.0
	 */
	private function scope(): string {
		return '' !== $this->group ? $this->prefix . '_' . $this->group : $this->prefix;
	}

	/**
	 * Read (or lazily create + persist) the version token for a scope.
	 * Memoized per scope for the request.
	 *
	 * @since 0.2.0
	 */
	private function token( string $scope ): string {
		if ( isset( self::$tokens[ $scope ] ) ) {
			return self::$tokens[ $scope ];
		}

		$stored = $this->store->read( $this->token_key( $scope ) );

		if ( ! is_string( $stored ) || '' === $stored ) {
			$stored = self::new_token();
			$this->store->write( $this->token_key( $scope ), $stored, 0 );
		}

		return self::$tokens[ $scope ] = $stored;
	}

	/**
	 * Storage key that holds a scope's version token (not itself versioned).
	 *
	 * @since 0.2.0
	 */
	private function token_key( string $scope ): string {
		return $scope . '__token';
	}

	/**
	 * Generate a fresh unique version token (12 lowercase hex chars). Unique
	 * per generation, so a regenerated token never collides with old keys.
	 *
	 * @since 0.2.0
	 */
	private static function new_token(): string {
		return bin2hex( random_bytes( 6 ) );
	}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `composer test-unit`
Expected: PASS (19 tests total).

- [ ] **Step 5: Commit**

```bash
git add src/Cache.php tests/Unit/CacheVersioningTest.php
git commit -m "feat: versioned keys with prefix and group flush via rotated tokens"
```

(Append the Global Constraints commit footers.)

---

### Task 5: Release 0.2.0 (bootstrap version, changelog, readme)

**Files:**
- Modify: `init.php:98` (the registered version string)
- Modify: `CHANGES.md`
- Modify: `README.md`

**Interfaces:**
- Consumes: the finished 0.2.0 API. No code behavior change; this task is the release metadata.

- [ ] **Step 1: Bump the registered bootstrap version in `init.php`**

Change the final line from:

```php
Mai_Cache_Bootstrap::register( '0.1.0', __DIR__ . '/src' );
```

to:

```php
Mai_Cache_Bootstrap::register( '0.2.0', __DIR__ . '/src' );
```

- [ ] **Step 2: Add the 0.2.0 entry to `CHANGES.md`**

Insert this block directly beneath the `Versioning: ...` line and above `## [0.1.0]`:

```markdown
## [0.2.0] — 2026-06-25

### Added

- `Cache::object( $prefix )` — object-cache-only mode (`wp_cache_*`) with no DB fallback; a no-op when there is no persistent object cache, so it never adds database write load.
- `Cache::for()` and `Cache::object()` share one method surface via an internal `Store` strategy (`TransientStore`, `ObjectCacheStore`).
- `group( $group )` plus `flush()` — token-based group invalidation. `flush()` busts the whole prefix; `group( 'area' )->flush()` busts just that area. Per-key `delete()` remains for single entries.
- `Cache::has_persistent_object_cache()` — static helper over `wp_using_ext_object_cache()`.
- `Cache::reset_runtime()` — clears memoized instances and version tokens (for tests and long-running processes).
- First unit-test suite (PHPUnit + Brain Monkey).

### Changed

- Stored keys now carry a version-token segment. On upgrade from 0.1.0, existing cached entries are treated as a one-time miss and recomputed; they then age out by TTL.
- Renamed `forget()` to `pull()` (read-once / consume), matching Laravel's `pull()`. No alias is kept, since 0.1.0 had no consumers.

### Compatibility

- Backward compatible. `Cache::for( $prefix )` and `new Cache( $prefix )` behave as before. The `Mai_Cache_Bootstrap` signature is unchanged.
```

- [ ] **Step 3: Document the new API in `README.md`**

Add a section covering the two modes, grouping, and flushing. Use this content (adapt headings to the file's existing style; do not introduce em-dashes):

````markdown
## Storage modes

```php
use Mai\Cache\Cache;

// Transient-backed: Redis when present, database fallback otherwise.
Cache::for( 'mai' )->remember( 'key', fn() => expensive(), HOUR_IN_SECONDS );

// Object-cache-only: wp_cache_* with no DB fallback. No-op without a
// persistent object cache, so it never writes to wp_options.
Cache::object( 'mai' )->remember( 'key', fn() => expensive(), HOUR_IN_SECONDS );

if ( Cache::has_persistent_object_cache() ) {
    // Only worth caching this when Redis is present.
}
```

## Grouping and flushing

Use one prefix per plugin and a group per cache area. Bind the prefix once:

```php
function mai_cache( string $group = '' ): \Mai\Cache\Cache {
    $cache = \Mai\Cache\Cache::for( 'mai' );
    return $group ? $cache->group( $group ) : $cache;
}

mai_cache( 'menus' )->remember( $location, fn() => render_menu( $location ), DAY_IN_SECONDS );

mai_cache( 'menus' )->flush();   // bust every menu cache
mai_cache( 'menus' )->delete( $location ); // bust one entry
mai_cache()->flush();            // bust everything under this prefix
```

## Read-once state (flash messages, one-time tokens)

Beyond performance caching, `pull()` reads a value and deletes it in one call, which fits consume-once state. Use the transient mode (not object-only) and a dedicated prefix so content flushes never touch it. It is best-effort: never store state that must survive cache eviction (use options or user meta for that).

```php
// Store a one-time admin notice, then redirect.
mai_cache( 'flash' )->set( 'saved_' . get_current_user_id(), 'Settings saved.', 5 * MINUTE_IN_SECONDS );

// On the next page load, show it exactly once: pull() returns it and deletes it.
$notice = mai_cache( 'flash' )->pull( 'saved_' . get_current_user_id() );
if ( $notice ) {
    printf( '<div class="notice notice-success"><p>%s</p></div>', esc_html( $notice ) );
}
```
````

- [ ] **Step 4: Verify the suite still passes**

Run: `composer test-unit`
Expected: PASS (19 tests; this task changes no code paths).

- [ ] **Step 5: Commit**

```bash
git add init.php CHANGES.md README.md
git commit -m "release: mai-cache 0.2.0 (object-cache mode + group flush)"
```

(Append the Global Constraints commit footers.)

---

## Notes for the executor

- Run every command from the mai-cache repo root (`/Users/jivedig/LocalPackages/mai-cache`) on branch `feature/cache-foundation`.
- `composer install` (Task 1, Step 6) creates `vendor/`; it is gitignored and must never be committed.
- Do not touch `init.php`'s `Mai_Cache_Bootstrap` class body or `register()` signature; only the version string on the final line changes (Task 5).
- The consumer wrapper `mai_cache()` shown in the README lives in mai-engine, not this package; it is documentation only here.
