# Grid Result Cache Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Cache resolved post-grid query results (ordered IDs + found count) under a version token we control instead of core's churn-prone `last_changed`, so grids stay warm through editorial write churn on Redis sites and gain cross-request caching on non-Redis sites.

**Architecture:** The reusable versioning / stale-while-revalidate machinery lives in the **mai-cache** package (a new primitive: per-scope version tokens stored as values, a fresh/stale/cold read, and a single-flight lock). mai-engine's `Mai_Grid_Cache` is a thin consumer: it builds a stable key, decides cacheability, and wires the primitive onto `posts_pre_query`/`the_posts` via a `mai_cache` query flag (so any `WP_Query` can opt in). The single-IN optimizer is decoupled and disabled by default.

**Tech Stack:** PHP 8.1+, WordPress 6.1+ (`posts_pre_query`, `_prime_post_caches`, `wp_cache_add`), the mai-cache package (`~/LocalPackages/mai-cache`, vendored into mai-engine as `maithemewp/mai-cache` dev-develop), PHPUnit + Brain Monkey unit suites in both repos, WP-CLI verification on eurweb.test / totalprosports.test / a seeded sportsdataio.

## Global Constraints

- House header on new files: `// Prevent direct file access.` + `defined( 'ABSPATH' ) || die;` + `@copyright Copyright © 2020 BizBudding`. No `exit`, no `(c)`. (mai-cache files follow that repo's existing header style.)
- No em-dashes in shipped text (comments, commit messages, changelog).
- `@since 2.40.0` on new mai-engine functions/classes; mai-cache uses its next version in its docblocks/CHANGES.
- Cache envelope shape (mai-cache): `[ '_v' => string, 'value' => mixed ]`. Grid value shape: `[ 'ids' => int[], 'found' => int ]`.
- TTL backstop default: `4 * HOUR_IN_SECONDS`, filterable per grid.
- Token rotates on: `transition_post_status` to/from `publish`, `deleted_post` of a published post, `save_post` of an already-published post. NOT on draft saves, autosaves, or `revision` post type.
- Optimizer OFF by default (`mai_post_grid_optimize_query` default flips to `false`).
- mai-cache prefix branding rule: mai-cache's OWN artifacts stay `mai`-branded (object-cache group `mai_cache`, lock group `mai_cache_lock`); consumer prefixes are left untouched (mai-engine keeps `mai`). Do NOT normalize/force consumer prefixes.
- mai-cache unit tests run via `composer test-unit` in `~/LocalPackages/mai-cache`; mai-engine unit tests via `composer test-unit` in the plugin. Integration/SWR behavior is verified on-site.

---

## File Structure

**mai-cache (`~/LocalPackages/mai-cache`):**
- Modify `src/Cache.php` — add the versioned SWR primitive: `version()`, `bump()`, `read_swr()`, `write_swr()`, `lock()`.
- Create `tests/Unit/SwrTest.php` — unit tests for the primitive against an in-memory fake store.
- Update `CHANGES.md`.

**mai-engine (`~/Plugins/mai-engine`):**
- Vendor sync: copy the changed `src/Cache.php` into `vendor/maithemewp/mai-cache/src/Cache.php` so the symlinked plugin sees it on the test sites (the released flow is push mai-cache + `composer update`).
- Create `lib/classes/class-mai-grid-cache.php` — key builder, cacheability predicate, prime-and-hydrate, and the `posts_pre_query`/`the_posts`/invalidation callbacks (delegating versioning/SWR to mai-cache).
- Create `lib/functions/grid-cache.php` — registration (filters, invalidation actions, WP-CLI flush hook).
- Modify `lib/init.php` — add `'functions/grid-cache'` to `$files`.
- Modify `lib/classes/class-mai-grid.php` — set the `mai_cache` flag in `get_post_query_args()`.
- Modify `lib/classes/class-mai-post-grid-query-optimizer.php` — flip the optimizer default to `false`.
- Create `tests/phpunit/unit/MaiGridCacheKeyTest.php`, `MaiGridCacheabilityTest.php`.
- Create `bin/grid-swr-concurrency.php` — on-site SWR/stampede probe.

---

### Task 1: mai-cache versioned stale-while-revalidate primitive

**Repo:** `~/LocalPackages/mai-cache` (run all commands there).

**Files:**
- Modify: `src/Cache.php`
- Test: `tests/Unit/SwrTest.php`
- Modify: `CHANGES.md`

**Interfaces:**
- Consumes (existing in `Cache.php`): `get(string):mixed` (returns `false` on miss), `set(string,mixed,int):bool`, `key(string):string`, and the private static `new_token():string`.
- Produces:
  - `version( array $scopes ): string` — composite current token for consumer-defined scope strings (each minted lazily as a stored value).
  - `bump( string $scope ): bool` — rotate one scope's token (a value write, NOT a group flush, so keys stay stable).
  - `read_swr( string $key, string $version ): ?array` — `[ 'value'=>mixed, 'fresh'=>bool ]` or `null` (cold).
  - `write_swr( string $key, mixed $value, string $version, int $ttl ): bool`.
  - `lock( string $key, int $ttl = 30 ): bool` — single-flight via `wp_cache_add`.

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/SwrTest.php` (match the namespace/base used by the repo's existing unit tests — check an existing file under `tests/Unit/`):

```php
<?php
namespace Mai\Cache\Tests\Unit;

use Brain\Monkey\Functions;
use Mai\Cache\Tests\TestCase;
use Mai\Cache\Cache;
use Mai\Cache\Store;

final class SwrTest extends TestCase {
	private function cache(): Cache {
		// In-memory Store so version/read/write hit a real key/value map.
		$store = new class implements Store {
			public array $data = [];
			public function read( string $key ): mixed { return $this->data[ $key ] ?? false; }
			public function write( string $key, mixed $value, int $expire ): bool { $this->data[ $key ] = $value; return true; }
			public function remove( string $key ): bool { unset( $this->data[ $key ] ); return true; }
			public function available(): bool { return true; }
		};
		return new Cache( 'mai', $store );
	}

	public function test_read_cold_is_null(): void {
		$c = $this->cache();
		$this->assertNull( $c->read_swr( 'k', $c->version( [ 'post' ] ) ) );
	}

	public function test_write_then_read_is_fresh(): void {
		$c = $this->cache();
		$v = $c->version( [ 'post' ] );
		$c->write_swr( 'k', [ 'ids' => [ 1, 2 ] ], $v, 3600 );
		$r = $c->read_swr( 'k', $v );
		$this->assertSame( [ 'ids' => [ 1, 2 ] ], $r['value'] );
		$this->assertTrue( $r['fresh'] );
	}

	public function test_bump_makes_value_stale_not_gone(): void {
		$c = $this->cache();
		$v = $c->version( [ 'post' ] );
		$c->write_swr( 'k', 'X', $v, 3600 );
		$c->bump( 'post' );
		$r = $c->read_swr( 'k', $c->version( [ 'post' ] ) );
		$this->assertSame( 'X', $r['value'] ); // still reachable
		$this->assertFalse( $r['fresh'] );      // but stale
	}

	public function test_version_is_per_scope(): void {
		$c = $this->cache();
		$v1 = $c->version( [ 'post' ] );
		$c->bump( 'page' );                       // bumping page must not change post's version
		$this->assertSame( $v1, $c->version( [ 'post' ] ) );
	}
}
```

- [ ] **Step 2: Run it to confirm it fails**

Run: `composer test-unit -- --filter SwrTest`
Expected: FAIL ("Call to undefined method Mai\\Cache\\Cache::read_swr()"). If the namespace/TestCase differ, fix the test's `namespace`/`use` to match an existing `tests/Unit/*` file first.

- [ ] **Step 3: Add the primitive to `src/Cache.php`**

Add these methods inside the `Cache` class:

```php
	/**
	 * Object-cache group for single-flight locks (mai-cache owned, mai-branded).
	 */
	private const LOCK_GROUP = 'mai_cache_lock';

	/**
	 * Composite current version token for one or more consumer-defined scopes.
	 *
	 * Each scope's token is stored as a value (minted lazily), so rotating it does NOT
	 * change the keys of cached results: the prior value stays readable for
	 * stale-while-revalidate. Scope strings are the consumer's domain (e.g. post types).
	 *
	 * @param string[] $scopes Scope keys.
	 *
	 * @return string
	 */
	public function version( array $scopes ): string {
		$scopes = $scopes ? $scopes : [ '' ];
		sort( $scopes );

		$parts = [];
		foreach ( $scopes as $scope ) {
			$parts[] = $this->scope_version( (string) $scope );
		}

		return implode( '.', $parts );
	}

	/**
	 * Read (and lazily mint) one scope's stored version token.
	 *
	 * @param string $scope Scope key.
	 *
	 * @return string
	 */
	private function scope_version( string $scope ): string {
		$key   = '__v_' . $scope;
		$token = $this->get( $key );

		if ( ! is_string( $token ) || '' === $token ) {
			$token = self::new_token();
			$this->set( $key, $token, 0 );
		}

		return $token;
	}

	/**
	 * Rotate one scope's version token. Cached results keep their stable keys and become
	 * stale (still readable) rather than orphaned.
	 *
	 * @param string $scope Scope key.
	 *
	 * @return bool
	 */
	public function bump( string $scope ): bool {
		return $this->set( '__v_' . $scope, self::new_token(), 0 );
	}

	/**
	 * Read a versioned value. Null when cold; otherwise the value plus whether the stored
	 * version stamp matches the supplied current version (fresh) or not (stale).
	 *
	 * @param string $key     Cache key.
	 * @param string $version Current composite version (from version()).
	 *
	 * @return array{value:mixed,fresh:bool}|null
	 */
	public function read_swr( string $key, string $version ): ?array {
		$envelope = $this->get( $key );

		if ( ! is_array( $envelope ) || ! array_key_exists( '_v', $envelope ) ) {
			return null;
		}

		return [
			'value' => $envelope['value'] ?? null,
			'fresh' => hash_equals( (string) $envelope['_v'], $version ),
		];
	}

	/**
	 * Store a value with the current version stamped into the envelope.
	 *
	 * @param string $key     Cache key.
	 * @param mixed  $value   Value.
	 * @param string $version Current composite version.
	 * @param int    $ttl     TTL in seconds.
	 *
	 * @return bool
	 */
	public function write_swr( string $key, mixed $value, string $version, int $ttl ): bool {
		return $this->set( $key, [ '_v' => $version, 'value' => $value ], $ttl );
	}

	/**
	 * Single-flight lock: true for the one caller that should recompute a stale/cold key.
	 * Atomic only with a persistent object cache; degrades to per-request otherwise
	 * (acceptable, since stampedes only matter on high-traffic Redis sites).
	 *
	 * @param string $key Lock key (typically the cache key).
	 * @param int    $ttl Lock TTL in seconds.
	 *
	 * @return bool
	 */
	public function lock( string $key, int $ttl = 30 ): bool {
		return wp_cache_add( $this->key( 'lock_' . $key ), 1, self::LOCK_GROUP, $ttl );
	}
```

If `new_token()` is not already a method, confirm its definition (it is used by `flush()`); reuse it verbatim.

- [ ] **Step 4: Run the test to confirm it passes**

Run: `composer test-unit -- --filter SwrTest`
Expected: PASS (4 tests).

- [ ] **Step 5: Run the whole mai-cache suite (no regressions)**

Run: `composer test-unit`
Expected: PASS (existing tests + the 4 new).

- [ ] **Step 6: Note it in CHANGES.md and commit**

Add a CHANGES.md line ("Added versioned stale-while-revalidate primitive (version/bump/read_swr/write_swr/lock).") then:

```bash
git add src/Cache.php tests/Unit/SwrTest.php CHANGES.md
git commit -m "feat: versioned stale-while-revalidate primitive (version/bump/read_swr/write_swr/lock)"
```

---

### Task 2: Vendor the mai-cache change into mai-engine for testing

**Repo:** both. The released path is push mai-cache develop + `composer update maithemewp/mai-cache` in mai-engine; for on-site testing now we sync the file directly (the plugin is symlinked into the test sites).

**Files:**
- Modify: `~/Plugins/mai-engine/vendor/maithemewp/mai-cache/src/Cache.php`

- [ ] **Step 1: Copy the updated source into the vendored copy**

```bash
cp ~/LocalPackages/mai-cache/src/Cache.php ~/Plugins/mai-engine/vendor/maithemewp/mai-cache/src/Cache.php
```

- [ ] **Step 2: Confirm the new methods are present**

Run: `grep -nE "function (version|bump|read_swr|write_swr|lock)\b" ~/Plugins/mai-engine/vendor/maithemewp/mai-cache/src/Cache.php`
Expected: 5 method definitions.

- [ ] **Step 3: Smoke test the primitive through mai_cache() on a site**

Run (from `~/Herd/eurweb`): `wp eval '$c=mai_cache("grid"); $v=$c->version(["post"]); $c->write_swr("t",[1,2],$v,60); var_dump($c->read_swr("t",$v));'`
Expected: an array with `fresh => true` and `value => [1,2]`.

- [ ] **Step 4: Commit the vendored change (mai-engine)**

```bash
cd ~/Plugins/mai-engine
git add vendor/maithemewp/mai-cache/src/Cache.php
git commit -m "chore(deps): vendor mai-cache SWR primitive for testing (pending mai-cache release)"
```

---

### Task 3: Cache key builder and cacheability predicate

**Repo:** `~/Plugins/mai-engine`.

**Files:**
- Create: `lib/classes/class-mai-grid-cache.php`
- Test: `tests/phpunit/unit/MaiGridCacheKeyTest.php`, `tests/phpunit/unit/MaiGridCacheabilityTest.php`

**Interfaces:**
- Produces: `Mai_Grid_Cache::cache_key( array $query_vars, string $sql ): string`; `Mai_Grid_Cache::is_cacheable( array $query_vars ): bool`.

- [ ] **Step 1: Write the failing key test**

Create `tests/phpunit/unit/MaiGridCacheKeyTest.php`:

```php
<?php
namespace BizBudding\MaiEngine\Tests\Unit;

use BizBudding\MaiEngine\Tests\TestCase;
use Mai_Grid_Cache;

final class MaiGridCacheKeyTest extends TestCase {
	public function test_arg_order_and_volatile_vars_do_not_change_the_key(): void {
		$c = new Mai_Grid_Cache();
		$a = $c->cache_key( [ 'post_type' => 'post', 'post__in' => [ 3, 1, 2 ], 'fields' => 'ids', 'cache_results' => true, 'update_post_meta_cache' => false ], 'SELECT 1' );
		$b = $c->cache_key( [ 'post__in' => [ 1, 2, 3 ], 'post_type' => 'post' ], 'SELECT 1' );
		$this->assertSame( $a, $b );
	}

	public function test_different_sql_changes_the_key(): void {
		$c = new Mai_Grid_Cache();
		$this->assertNotSame(
			$c->cache_key( [ 'post_type' => 'post' ], 'SELECT 1' ),
			$c->cache_key( [ 'post_type' => 'post' ], 'SELECT 2' )
		);
	}

	public function test_custom_args_are_kept_in_the_key(): void {
		$c = new Mai_Grid_Cache();
		$this->assertNotSame(
			$c->cache_key( [ 'post_type' => 'post', 'my_custom' => 'x' ], 'SELECT 1' ),
			$c->cache_key( [ 'post_type' => 'post', 'my_custom' => 'y' ], 'SELECT 1' )
		);
	}
}
```

- [ ] **Step 2: Run it to confirm it fails**

Run: `composer test-unit -- --filter MaiGridCacheKeyTest`
Expected: FAIL ("Class 'Mai_Grid_Cache' not found").

- [ ] **Step 3: Create the class with the key builder**

Create `lib/classes/class-mai-grid-cache.php`:

```php
<?php
/**
 * Mai Grid Result Cache.
 *
 * Caches resolved grid query results (ordered post IDs + found count) under a version
 * token we control (via the mai-cache SWR primitive), with stable keys and
 * stale-while-revalidate, so grids survive the last_changed churn that defeats core's
 * query caching on write-heavy sites. Activated per query by the `mai_cache` query var.
 *
 * @package BizBudding\MaiEngine
 */

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

class Mai_Grid_Cache {

	/**
	 * mai-cache group. Transient mode: Redis when present, wp_options otherwise.
	 */
	private const GROUP = 'grid';

	/**
	 * Default TTL backstop (filterable per grid via `mai_post_grid_cache_ttl`).
	 */
	private const TTL = 4 * HOUR_IN_SECONDS;

	/**
	 * Query vars that do not change which posts are returned, removed before hashing.
	 * Mirrors WP_Query::generate_cache_key().
	 */
	private const VOLATILE = [
		'cache_results',
		'fields',
		'update_post_meta_cache',
		'update_post_term_cache',
		'update_menu_item_cache',
		'lazy_load_term_meta',
		'suppress_filters',
		'mai_cache',
	];

	/**
	 * Order-insensitive id arrays, sorted before hashing.
	 */
	private const SORTABLE = [ 'post__in', 'post_parent__in', 'post_name__in' ];

	/**
	 * Build a stable cache key from the query vars and final SQL.
	 *
	 * The volatile-var denylist and the post_type / post__in normalization below are
	 * copied from WordPress core's WP_Query::generate_cache_key() (wp-includes/class-wp-query.php,
	 * verified against WP 6.x/7.0). Kept in sync by hand: if core changes how it derives its
	 * query cache key, re-check this method against it to avoid drift.
	 *
	 * @param array  $query_vars The WP_Query vars.
	 * @param string $sql        The final SQL request.
	 *
	 * @return string
	 */
	public function cache_key( array $query_vars, string $sql ): string {
		foreach ( self::VOLATILE as $key ) {
			unset( $query_vars[ $key ] );
		}

		$query_vars['post_type'] = (array) ( $query_vars['post_type'] ?? 'post' );
		sort( $query_vars['post_type'] );

		foreach ( self::SORTABLE as $key ) {
			if ( isset( $query_vars[ $key ] ) && is_array( $query_vars[ $key ] ) ) {
				$query_vars[ $key ] = array_values( array_unique( array_map( 'intval', $query_vars[ $key ] ) ) );
				sort( $query_vars[ $key ] );
			}
		}

		ksort( $query_vars );

		return md5( serialize( $query_vars ) . $sql );
	}

	/**
	 * Whether this query should use the grid cache.
	 *
	 * @param array $query_vars The WP_Query vars.
	 *
	 * @return bool
	 */
	public function is_cacheable( array $query_vars ): bool {
		$cacheable = true;

		if ( isset( $query_vars['query_by'] ) && 'id' === $query_vars['query_by'] ) {
			$cacheable = false;
		}

		// ElasticPress offloads to ES and hooks posts_pre_query itself; that interaction is
		// unverified (eurweb is not on EP), so skip ep_integrate grids until it is tested.
		if ( ! empty( $query_vars['ep_integrate'] ) ) {
			$cacheable = false;
		}

		// Optimizer already made this a post__in fast path (marker present, no meta JOIN).
		if ( isset( $query_vars['mai_post_grid_tt_ids'] ) && empty( $query_vars['meta_query'] ) ) {
			$cacheable = false;
		}

		$orderby = $query_vars['orderby'] ?? '';
		if ( is_string( $orderby ) && false !== stripos( $orderby, 'RAND(' ) ) {
			$cacheable = false;
		}

		return (bool) apply_filters( 'mai_post_grid_cache', $cacheable, $query_vars );
	}
}
```

- [ ] **Step 4: Run the key test to confirm it passes**

Run: `composer test-unit -- --filter MaiGridCacheKeyTest`
Expected: PASS (3 tests).

- [ ] **Step 5: Write the failing cacheability test**

Create `tests/phpunit/unit/MaiGridCacheabilityTest.php`:

```php
<?php
namespace BizBudding\MaiEngine\Tests\Unit;

use Brain\Monkey\Functions;
use BizBudding\MaiEngine\Tests\TestCase;
use Mai_Grid_Cache;

final class MaiGridCacheabilityTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'apply_filters' )->alias( fn( $tag, $value ) => $value );
	}

	public function test_skips_query_by_id(): void {
		$this->assertFalse( ( new Mai_Grid_Cache() )->is_cacheable( [ 'query_by' => 'id', 'post_type' => 'post' ] ) );
	}

	public function test_skips_ep_integrate(): void {
		$this->assertFalse( ( new Mai_Grid_Cache() )->is_cacheable( [ 'ep_integrate' => true, 'post_type' => 'post' ] ) );
	}

	public function test_skips_active_optimizer_marker_without_meta(): void {
		$this->assertFalse( ( new Mai_Grid_Cache() )->is_cacheable( [ 'mai_post_grid_tt_ids' => [ 5 ], 'post_type' => 'post' ] ) );
	}

	public function test_caches_optimizer_marker_with_meta(): void {
		$this->assertTrue( ( new Mai_Grid_Cache() )->is_cacheable( [ 'mai_post_grid_tt_ids' => [ 5 ], 'meta_query' => [ [ 'key' => 'x' ] ], 'post_type' => 'post' ] ) );
	}

	public function test_skips_rand_orderby(): void {
		$this->assertFalse( ( new Mai_Grid_Cache() )->is_cacheable( [ 'orderby' => 'RAND()', 'post_type' => 'post' ] ) );
	}

	public function test_caches_a_normal_tax_grid(): void {
		$this->assertTrue( ( new Mai_Grid_Cache() )->is_cacheable( [ 'post_type' => 'post', 'tax_query' => [ [ 'taxonomy' => 'category' ] ] ] ) );
	}
}
```

- [ ] **Step 6: Run it to confirm it fails, then it is already implemented above; run to pass**

Run: `composer test-unit -- --filter MaiGridCacheabilityTest`
Expected: PASS (6 tests) — `is_cacheable()` is included in Step 3's class.

- [ ] **Step 7: Commit**

```bash
git add lib/classes/class-mai-grid-cache.php tests/phpunit/unit/MaiGridCacheKeyTest.php tests/phpunit/unit/MaiGridCacheabilityTest.php
git commit -m "feat(grid-cache): cache key builder and cacheability predicate"
```

---

### Task 4: Hit/miss integration (consumes the mai-cache primitive)

**Files:**
- Modify: `lib/classes/class-mai-grid-cache.php`
- Create: `lib/functions/grid-cache.php`
- Modify: `lib/init.php`

**Interfaces:**
- Consumes: `mai_cache( 'grid' )` → `\Mai\Cache\Cache` with `version(array):string`, `read_swr(string,string):?array`, `write_swr(string,mixed,string,int):bool`, `lock(string,int):bool`; WP `posts_pre_query`, `the_posts`, `_prime_post_caches`, `get_post`.
- Produces: `Mai_Grid_Cache::pre_query()`, `the_posts()`, `hydrate(int[]):array`.

- [ ] **Step 1: Add the callbacks and hydration to the class**

Add to `lib/classes/class-mai-grid-cache.php`:

```php
	/**
	 * posts_pre_query: serve from cache (fresh or stale) or flag a miss for storage.
	 *
	 * @param array|null $posts Posts (null to run the query normally).
	 * @param WP_Query   $query The query.
	 *
	 * @return array|null
	 */
	public function pre_query( $posts, $query ) {
		if ( empty( $query->query_vars['mai_cache'] ) || ! $this->is_cacheable( $query->query_vars ) ) {
			return $posts;
		}

		$cache   = mai_cache( self::GROUP );
		$key     = $this->cache_key( $query->query_vars, (string) $query->request );
		$version = $cache->version( (array) ( $query->query_vars['post_type'] ?? 'post' ) );
		$hit     = $cache->read_swr( $key, $version );

		// Cold, or a stale entry where we won the single-flight lock: recompute.
		if ( null === $hit || ( ! $hit['fresh'] && $cache->lock( $key ) ) ) {
			$query->mai_cache_store_key     = $key;
			$query->mai_cache_store_version = $version;
			return $posts; // null -> WP runs the real query; the_posts stores it.
		}

		// Fresh hit, or a stale hit served while another request refreshes.
		$value                = $hit['value'];
		$query->found_posts   = (int) ( $value['found'] ?? count( $value['ids'] ) );
		$query->max_num_pages = ( ( $query->query_vars['posts_per_page'] ?? 0 ) > 0 )
			? (int) ceil( $query->found_posts / $query->query_vars['posts_per_page'] )
			: 1;

		return $this->hydrate( array_map( 'intval', $value['ids'] ) );
	}

	/**
	 * the_posts: store the freshly computed result for a flagged miss.
	 *
	 * @param array    $posts The posts.
	 * @param WP_Query $query The query.
	 *
	 * @return array
	 */
	public function the_posts( $posts, $query ) {
		if ( empty( $query->mai_cache_store_key ) ) {
			return $posts;
		}

		$ttl = (int) apply_filters( 'mai_post_grid_cache_ttl', self::TTL, $query->query_vars );

		mai_cache( self::GROUP )->write_swr(
			$query->mai_cache_store_key,
			[ 'ids' => wp_list_pluck( $posts, 'ID' ), 'found' => (int) $query->found_posts ],
			$query->mai_cache_store_version,
			$ttl
		);

		unset( $query->mai_cache_store_key, $query->mai_cache_store_version );

		return $posts;
	}

	/**
	 * Hydrate post objects from cached IDs in stored order, with no query.
	 *
	 * @param int[] $ids Ordered post IDs.
	 *
	 * @return WP_Post[]
	 */
	public function hydrate( array $ids ): array {
		if ( ! $ids ) {
			return [];
		}

		_prime_post_caches( $ids, true, true );

		return array_values( array_filter( array_map( 'get_post', $ids ) ) );
	}
```

- [ ] **Step 2: Create the registration file**

Create `lib/functions/grid-cache.php`:

```php
<?php
/**
 * Mai Grid Result Cache registration.
 *
 * @package BizBudding\MaiEngine
 */

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

add_action( 'init', 'mai_register_grid_cache' );
/**
 * Registers the grid result cache.
 *
 * @since 2.40.0
 *
 * @return void
 */
function mai_register_grid_cache() {
	$cache = new Mai_Grid_Cache();

	add_filter( 'posts_pre_query', [ $cache, 'pre_query' ], 10, 2 );
	add_filter( 'the_posts', [ $cache, 'the_posts' ], 10, 2 );
}
```

- [ ] **Step 3: Load the file**

In `lib/init.php`, in the `$files` array, add `'functions/grid-cache'` immediately after `'functions/cache'`.

- [ ] **Step 4: Run the unit suite**

Run: `composer test-unit`
Expected: PASS (the class loads; pre_query/the_posts are verified on-site).

- [ ] **Step 5: Commit**

```bash
git add lib/classes/class-mai-grid-cache.php lib/functions/grid-cache.php lib/init.php
git commit -m "feat(grid-cache): posts_pre_query/the_posts hit-miss path via the mai-cache SWR primitive"
```

---

### Task 5: Invalidation hooks and `wp cache flush` coverage

**Files:**
- Modify: `lib/classes/class-mai-grid-cache.php`
- Modify: `lib/functions/grid-cache.php`

**Interfaces:**
- Consumes: `mai_cache('grid')->bump(string)`, `mai_cache('grid')->flush()`; WP `transition_post_status`, `deleted_post`, `save_post`; `wp_is_post_revision`, `WP_CLI::add_hook`.
- Produces: `Mai_Grid_Cache::on_transition()`, `on_delete()`, `on_save()`, `flush_all()`.

- [ ] **Step 1: Add invalidation methods**

Add to `lib/classes/class-mai-grid-cache.php`:

```php
	/**
	 * Rotate a post type's token on a publish-affecting transition (publish/unpublish/trash).
	 *
	 * @param string  $new_status New status.
	 * @param string  $old_status Old status.
	 * @param WP_Post $post       The post.
	 *
	 * @return void
	 */
	public function on_transition( $new_status, $old_status, $post ) {
		if ( 'publish' !== $new_status && 'publish' !== $old_status ) {
			return;
		}
		if ( wp_is_post_revision( $post->ID ) || 'revision' === $post->post_type ) {
			return;
		}
		mai_cache( self::GROUP )->bump( $post->post_type );
	}

	/**
	 * Rotate on hard delete of a published post.
	 *
	 * @param int     $post_id The post ID.
	 * @param WP_Post $post    The post.
	 *
	 * @return void
	 */
	public function on_delete( $post_id, $post ) {
		if ( $post && 'publish' === $post->post_status && 'revision' !== $post->post_type ) {
			mai_cache( self::GROUP )->bump( $post->post_type );
		}
	}

	/**
	 * Rotate on a save to an already-published post (live edit). Drafts, autosaves, and
	 * revisions are excluded.
	 *
	 * @param int     $post_id The post ID.
	 * @param WP_Post $post    The post.
	 *
	 * @return void
	 */
	public function on_save( $post_id, $post ) {
		if ( wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return;
		}
		if ( 'publish' === $post->post_status && 'revision' !== $post->post_type ) {
			mai_cache( self::GROUP )->bump( $post->post_type );
		}
	}

	/**
	 * Full reset of the grid group (wp cache flush / wp mai flush). Orphans everything;
	 * the cold-start case, not per-type invalidation.
	 *
	 * @return void
	 */
	public function flush_all() {
		mai_cache( self::GROUP )->flush();
	}
```

- [ ] **Step 2: Register the hooks**

Extend `mai_register_grid_cache()` in `lib/functions/grid-cache.php`:

```php
	add_action( 'transition_post_status', [ $cache, 'on_transition' ], 10, 3 );
	add_action( 'deleted_post', [ $cache, 'on_delete' ], 10, 2 );
	add_action( 'save_post', [ $cache, 'on_save' ], 10, 2 );

	if ( defined( 'WP_CLI' ) && WP_CLI && class_exists( 'WP_CLI' ) ) {
		WP_CLI::add_hook( 'after_invoke:cache flush', [ $cache, 'flush_all' ] );
	}
```

- [ ] **Step 3: Unit suite stays green**

Run: `composer test-unit`
Expected: PASS.

- [ ] **Step 4: Commit**

```bash
git add lib/classes/class-mai-grid-cache.php lib/functions/grid-cache.php
git commit -m "feat(grid-cache): proactive token invalidation and wp cache flush coverage"
```

---

### Task 6: Opt grids into the cache + disable the optimizer by default

**Files:**
- Modify: `lib/classes/class-mai-grid.php` (line ~574)
- Modify: `lib/classes/class-mai-post-grid-query-optimizer.php` (line 28)

- [ ] **Step 1: Set the flag in `Mai_Grid::get_post_query_args()`**

Change the final return (line 574) from:

```php
	return apply_filters( 'mai_post_grid_query_args', $query_args, $this->args );
```

to:

```php
	// Opt this grid into the result cache. Mai_Grid_Cache::pre_query re-checks
	// cacheability and honors the `mai_post_grid_cache` filter, so this is opt-in only.
	$query_args['mai_cache'] = true;

	return apply_filters( 'mai_post_grid_query_args', $query_args, $this->args );
```

- [ ] **Step 2: Flip the optimizer default to false**

In `lib/classes/class-mai-post-grid-query-optimizer.php` `register()`, change:

```php
		if ( ! apply_filters( 'mai_post_grid_optimize_query', true ) ) {
			return;
		}
```

to:

```php
		// Off by default: the result cache makes warm hits free for every shape, so the
		// optimizer (a cold-path-only accelerator that is unvalidated and fails dangerously
		// on query offloaders) is opt-in. Enable with
		// add_filter( 'mai_post_grid_optimize_query', '__return_true' ).
		if ( ! apply_filters( 'mai_post_grid_optimize_query', false ) ) {
			return;
		}
```

- [ ] **Step 3: Confirm the optimizer arg tests still pass**

Run: `composer test-unit -- --filter PostGridQueryOptimizerArgsTest`
Expected: PASS (they call `maybe_optimize()` directly, not `register()`).

- [ ] **Step 4: Commit**

```bash
git add lib/classes/class-mai-grid.php lib/classes/class-mai-post-grid-query-optimizer.php
git commit -m "feat(grid-cache): opt grids into the cache; disable single-IN optimizer by default"
```

---

### Task 7: On-site verification (eurweb.test, totalprosports.test, seeded sportsdataio)

No unit tests; each check is a hard gate run with WP-CLI. Pipe wp-cli through `grep -viE 'Deprecated|react/promise|_load_textdomain|version 6.7.0|wp-migrate-db'`. Environments:
- **eurweb.test** (Redis engaged, real prod data): with-Redis gates — hit/miss, invalidation, equivalence, SWR/stampede, cold-miss.
- **totalprosports.test** (large, no Redis): the no-Redis `wp_options` persistence path + a second equivalence dataset.
- **sportsdataio** (seed posts/categories/grids first): controlled invalidation precision + concurrency with no prod noise.

- [ ] **Step 1: Functional hit/miss (eurweb.test)**

```bash
WP="$HOME/Herd/eurweb"; wp --path="$WP" cache flush
for i in 1 2 3; do
  wp --path="$WP" eval 'global $wpdb; $c=(int)(get_terms(["taxonomy"=>"category","orderby"=>"count","order"=>"DESC","number"=>1,"hide_empty"=>true,"fields"=>"ids"])[0]??0);
  $a=apply_filters("mai_post_grid_query_args",["post_type"=>"post","post_status"=>"publish","posts_per_page"=>12,"ignore_sticky_posts"=>true,"orderby"=>["date"=>"DESC","ID"=>"DESC"],"tax_query"=>[["taxonomy"=>"category","field"=>"term_id","terms"=>[$c],"operator"=>"IN"]],"mai_cache"=>true],[]);
  $b=$wpdb->num_queries; $q=new WP_Query($a); echo "run$i db=".($wpdb->num_queries-$b)." posts=".count($q->posts)."\n";'
done
```

Expected: run1 > 0 (cold), run2/run3 `db=0`.

- [ ] **Step 2: Equivalence (eurweb.test and totalprosports.test)**

```bash
for WP in "$HOME/Herd/eurweb" "$HOME/Herd/totalprosports"; do
  wp --path="$WP" eval 'global $wpdb; $c=(int)(get_terms(["taxonomy"=>"category","orderby"=>"count","order"=>"DESC","number"=>1,"fields"=>"ids","hide_empty"=>true])[0]??0);
  $base=["post_type"=>"post","post_status"=>"publish","posts_per_page"=>12,"ignore_sticky_posts"=>true,"orderby"=>["date"=>"DESC","ID"=>"DESC"],"tax_query"=>[["taxonomy"=>"category","field"=>"term_id","terms"=>[$c],"operator"=>"IN"]]];
  $n=new WP_Query($base); $ca=new WP_Query(array_merge($base,["mai_cache"=>true]));
  echo "'.basename("$WP").': ".(wp_list_pluck($n->posts,"ID")===wp_list_pluck($ca->posts,"ID")?"MATCH":"MISMATCH")."\n";'
done
```

Expected: `MATCH` on both.

- [ ] **Step 3: Invalidation correctness (sportsdataio, seeded)**

Seed if needed (`wp post generate`, `wp term generate`, plus a mai grid block on a page), then: warm a grid, `wp post update <id> --post_status=publish` a post in that category, confirm the next request recomputes; verify an autosave/`wp_is_post_revision` path does NOT bump `mai_cache('grid')->version(['post'])`.

- [ ] **Step 4: SWR/stampede probe (eurweb.test)**

Create `bin/grid-swr-concurrency.php`:

```php
<?php
/**
 * SWR/stampede probe: after a token rotation, N parallel requests for one grid should
 * trigger one recompute while the rest serve stale.
 *
 * @package BizBudding\MaiEngine
 */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) { return; }
global $wpdb;
$c = (int) ( get_terms( [ 'taxonomy' => 'category', 'orderby' => 'count', 'order' => 'DESC', 'number' => 1, 'hide_empty' => true, 'fields' => 'ids' ] )[0] ?? 0 );
$a = apply_filters( 'mai_post_grid_query_args', [ 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 12, 'ignore_sticky_posts' => true, 'orderby' => [ 'date' => 'DESC', 'ID' => 'DESC' ], 'tax_query' => [ [ 'taxonomy' => 'category', 'field' => 'term_id', 'terms' => [ $c ], 'operator' => 'IN' ] ], 'mai_cache' => true ], [] );
$b = $wpdb->num_queries;
$q = new WP_Query( $a );
WP_CLI::log( 'db=' . ( $wpdb->num_queries - $b ) . ' posts=' . count( $q->posts ) );
```

```bash
WP="$HOME/Herd/eurweb"
wp --path="$WP" eval-file "$WP/wp-content/plugins/mai-engine/bin/grid-swr-concurrency.php"   # warm
wp --path="$WP" eval 'do_action("transition_post_status","publish","draft",(object)["ID"=>0,"post_type"=>"post"]);' # rotate
seq 1 20 | xargs -P 20 -I{} wp --path="$WP" eval-file "$WP/wp-content/plugins/mai-engine/bin/grid-swr-concurrency.php" 2>/dev/null | grep -c 'db=[1-9]'
```

Expected: the `db>0` count is small (ideally 1).

- [ ] **Step 5: No-Redis persistence (totalprosports.test, no Redis)**

Run Step 1's loop on `$HOME/Herd/totalprosports`. Expected: run2/run3 cheaper than run1 (filtering query replaced by a `wp_options` read); `wp cache flush` resets it via the WP-CLI hook.

- [ ] **Step 6: Cold-miss load (eurweb.test, informs the optimizer decision)**

```bash
WP="$HOME/Herd/eurweb"
wp --path="$WP" eval 'global $wpdb; $c=(int)(get_terms(["taxonomy"=>"category","orderby"=>"count","order"=>"DESC","number"=>1,"fields"=>"ids","hide_empty"=>true])[0]??0);
$a=["post_type"=>"post","post_status"=>"publish","posts_per_page"=>12,"no_found_rows"=>true,"cache_results"=>false,"fields"=>"ids","ignore_sticky_posts"=>true,"orderby"=>["date"=>"DESC","ID"=>"DESC"],"tax_query"=>[["taxonomy"=>"category","field"=>"term_id","terms"=>[$c],"operator"=>"IN"]]];
$t=microtime(true); new WP_Query($a); echo "cold miss: ".round((microtime(true)-$t)*1000,1)."ms\n";'
```

Expected: records the stock cold-miss time (~250ms on the 80k-post category). Not a gate.

- [ ] **Step 7: Commit the probe**

```bash
git add bin/grid-swr-concurrency.php
git commit -m "test(grid-cache): SWR/stampede concurrency probe"
```

---

## Notes for the implementer

- **mai-cache `flush()` vs `bump()`.** `flush()` rotates mai-cache's group token (folded into the key) and orphans everything; it is only for full reset (Task 5 `flush_all`). Per-type invalidation uses `bump()` (Task 1/5), which rotates a stored value token so result keys stay stable for stale-while-revalidate.
- **Single-flight is "one blocks, rest serve stale."** The lock winner recomputes synchronously; concurrent stale requests are served instantly from the prior value. A fully background refresh (recompute on `shutdown`) is a later enhancement, not in scope.
- **ElasticPress is skipped for v1** (`is_cacheable` bails on `ep_integrate`); the `posts_pre_query` ordering with EP is unverified and eurweb is not on EP.
- **found_posts.** Default grids set `no_found_rows => true`, so it is not meaningful; load-more grids set it false and `pre_query` restores `found_posts`/`max_num_pages` from the cached count.
- **Vendoring caveat.** Task 2 copies the file for testing; the released path is push mai-cache develop + `composer update maithemewp/mai-cache` in mai-engine. Re-running `composer update` will overwrite the manual copy, so re-sync after a composer run during this work.
